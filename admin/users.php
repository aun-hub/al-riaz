<?php
/**
 * Al-Riaz Associates — Users & Roles Management
 */
$pageTitle = 'Users & Roles';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance();

// ── Handle POST Actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'invite_user') {
        $email    = trim($_POST['email'] ?? '');
        $name     = trim($_POST['name']  ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = $_POST['role'] ?? 'agent';
        $validRoles = ['agent','admin','super_admin'];
        if (!in_array($role, $validRoles)) $role = 'agent';

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $name !== '') {
            try {
                // Check duplicate
                $chk = $db->prepare('SELECT id FROM users WHERE email=?');
                $chk->execute([$email]);
                if ($chk->fetch()) {
                    setFlash('danger', 'A user with this email already exists.');
                } else {
                    $token = bin2hex(random_bytes(24));
                    $db->prepare(
                        'INSERT INTO users (name, email, phone, role, password_hash, is_active, invite_token, created_by, created_at)
                         VALUES (?,?,?,?, ?, 0, ?, ?, NOW())'
                    )->execute([
                        $name, $email, $phone, $role,
                        password_hash($token, PASSWORD_BCRYPT),
                        $token,
                        (int)$_SESSION['admin_id'] ?: null,
                    ]);
                    auditLog('invite','users',0,'Invited: '.$email.' as '.$role);

                    // Build invite link (absolute URL for the email).
                    // SITE_URL already includes BASE_PATH (e.g. "http://localhost/al-riaz"), so don't add it again.
                    $inviteUrl = rtrim(SITE_URL, '/') . '/admin/invite-accept.php?token=' . $token;
                    $inviterName = $_SESSION['admin_name'] ?? 'an administrator';
                    $agencyName  = (function_exists('getSettings') ? (getSettings()['agency_name'] ?? SITE_NAME) : SITE_NAME);
                    $subject     = "You've been invited to $agencyName Admin Panel";
                    $safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $safeInviter = htmlspecialchars($inviterName, ENT_QUOTES, 'UTF-8');
                    $safeAgency  = htmlspecialchars($agencyName, ENT_QUOTES, 'UTF-8');
                    $safeRole    = htmlspecialchars(ucwords(str_replace('_',' ',$role)), ENT_QUOTES, 'UTF-8');
                    $safeUrl     = htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8');
                    $html = <<<HTML
<div style="font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:24px;color:#0A1628;">
  <h2 style="color:#0A1628;margin:0 0 1rem;">Welcome, $safeName</h2>
  <p>$safeInviter has invited you to join the <strong>$safeAgency</strong> admin panel as a <strong>$safeRole</strong>.</p>
  <p>Click the button below to set your password and activate your account:</p>
  <p style="margin:1.5rem 0;">
    <a href="$safeUrl" style="background:#F5B301;color:#0A1628;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">
      Accept Invitation
    </a>
  </p>
  <p style="color:#64748B;font-size:0.85rem;">Or copy this link into your browser:<br><span style="word-break:break-all;">$safeUrl</span></p>
</div>
HTML;
                    $alt = "Hi $name,\n\n$inviterName has invited you to join the $agencyName admin panel as $safeRole.\n\nAccept the invitation here:\n$inviteUrl\n";

                    $mail = sendMail($email, $subject, $html, ['html' => true, 'alt_body' => $alt, 'to_name' => $name]);

                    if ($mail['ok']) {
                        setFlash('success', "Invitation email sent to $email.");
                    } else {
                        // User row is created either way — surface both the failure and the manual link in plain text.
                        setFlash('danger',
                            "User created, but the invitation email failed (" . $mail['error'] . "). "
                            . "Share this link manually: " . $inviteUrl
                        );
                    }
                }
            } catch(Exception $e) {
                setFlash('danger', 'Error: ' . $e->getMessage());
            }
        } else {
            setFlash('danger', 'Valid name and email are required.');
        }
        header('Location: ' . BASE_PATH . '/admin/users.php');
        exit;
    }

    if ($action === 'change_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $role   = $_POST['role'] ?? '';
        // Prevent changing own role
        if ($userId === (int)$_SESSION['admin_id']) {
            setFlash('danger', 'You cannot change your own role.');
        } elseif (in_array($role, ['agent','admin','super_admin'])) {
            try {
                $db->prepare('UPDATE users SET role=?, updated_at=NOW() WHERE id=?')->execute([$role, $userId]);
                auditLog('change_role','users',$userId,'Role changed to: '.$role);
                setFlash('success', 'Role updated successfully.');
            } catch(Exception $e) {
                setFlash('danger', 'Error: ' . $e->getMessage());
            }
        }
        header('Location: ' . BASE_PATH . '/admin/users.php');
        exit;
    }

    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!hasRole('super_admin')) {
            setFlash('danger', 'Only a Super Admin can delete users.');
        } elseif ($userId === (int)$_SESSION['admin_id']) {
            setFlash('danger', 'You cannot delete your own account.');
        } else {
            try {
                // Look up for audit log before deleting
                $u = $db->prepare('SELECT name, email, role FROM users WHERE id=?');
                $u->execute([$userId]);
                $target = $u->fetch();

                if (!$target) {
                    setFlash('danger', 'User not found.');
                } else {
                    // Prevent deleting the last super_admin
                    if ($target['role'] === 'super_admin') {
                        $c = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='super_admin' AND is_active=1")->fetchColumn();
                        if ($c <= 1) {
                            setFlash('danger', 'Cannot delete the last active Super Admin.');
                            header('Location: ' . BASE_PATH . '/admin/users.php');
                            exit;
                        }
                    }
                    $db->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
                    auditLog('delete_user', 'users', $userId, 'Deleted: ' . $target['email'] . ' (' . $target['role'] . ')');
                    setFlash('success', 'User "' . $target['name'] . '" deleted.');
                }
            } catch(Exception $e) {
                setFlash('danger', 'Delete failed: ' . $e->getMessage());
            }
        }
        header('Location: ' . BASE_PATH . '/admin/users.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$_SESSION['admin_id']) {
            setFlash('danger', 'You cannot deactivate yourself.');
        } else {
            try {
                $cur = $db->prepare('SELECT is_active FROM users WHERE id=?');
                $cur->execute([$userId]);
                $row = $cur->fetch();
                if ($row) {
                    $newStatus = (int)$row['is_active'] === 1 ? 0 : 1;
                    $db->prepare('UPDATE users SET is_active=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, $userId]);
                    auditLog('toggle_status','users',$userId,'Status changed to: '.($newStatus?'active':'inactive'));
                    setFlash('success', 'User status updated.');
                }
            } catch(Exception $e) {
                setFlash('danger', 'Error: ' . $e->getMessage());
            }
        }
        header('Location: ' . BASE_PATH . '/admin/users.php');
        exit;
    }
}

// ── Load Users ───────────────────────────────────────────────
try {
    $stmt = $db->query(
        'SELECT id, name, email, phone, role, is_active, last_login, created_at, created_by, avatar_url
         FROM users ORDER BY role ASC, name ASC'
    );
    $users  = $stmt->fetchAll();
    $dbOk   = true;
} catch(Exception $e) {
    error_log('[Users] ' . $e->getMessage());
    $users = []; $dbOk = false;
}

$csrf = csrfToken();
$roleColors = ['super_admin'=>'gold','admin'=>'dark-green','agent'=>'primary'];

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-users me-2" style="color:var(--gold)"></i>Users & Roles</h1>
    <p class="text-muted mb-0 fs-13"><?= count($users) ?> users registered</p>
  </div>
  <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#inviteModal">
    <i class="fa-solid fa-user-plus me-1"></i> Invite User
  </button>
</div>

<div class="admin-table-wrapper">
  <div class="table-responsive">
    <table class="table table-admin table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Status</th>
          <th>Last Login</th>
          <th>Created</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr>
          <td colspan="8" class="text-center py-5 text-muted">
            <i class="fa-solid fa-user-slash fa-2x mb-2 d-block"></i>No users found.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($u['avatar_url'])): ?>
                <img src="<?= htmlspecialchars(mediaUrl($u['avatar_url']), ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;">
              <?php else: ?>
              <div style="width:34px;height:34px;border-radius:50%;background:var(--sidebar-bg);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;flex-shrink:0;">
                <?= strtoupper(substr($u['name'], 0, 1)) ?>
              </div>
              <?php endif; ?>
              <div>
                <div class="fw-600" style="font-size:0.88rem;"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ((int)$u['id'] === (int)$_SESSION['admin_id']): ?>
                  <span class="badge bg-info text-dark" style="font-size:0.65rem;">You</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:0.83rem;"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-size:0.83rem;"><?= htmlspecialchars($u['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
            <form method="POST" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="action" value="change_role">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <select name="role" class="form-select form-select-sm" style="width:auto;display:inline-block;"
                      data-confirm-change="Change this user's role to {value}?"
                      data-confirm-title="Change role"
                      data-confirm-ok="Change role"
                      data-confirm-variant="primary">
                <?php foreach (['agent'=>'Agent','admin'=>'Admin','super_admin'=>'Super Admin'] as $rv=>$rl): ?>
                  <option value="<?= $rv ?>" <?= $u['role']===$rv?'selected':'' ?>><?= $rl ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <?php else: ?>
              <span class="role-badge role-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>">
                <?= ucwords(str_replace('_',' ',$u['role'])) ?>
              </span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td style="font-size:0.77rem;color:#6c757d;">
            <?= $u['last_login'] ? htmlspecialchars(date('d M Y, H:i', strtotime($u['last_login'])), ENT_QUOTES,'UTF-8') : '—' ?>
          </td>
          <td style="font-size:0.77rem;color:#6c757d;white-space:nowrap;">
            <?= htmlspecialchars(date('d M Y', strtotime($u['created_at'])), ENT_QUOTES,'UTF-8') ?>
          </td>
          <td class="text-end">
            <?php
              // Edit eligibility: super_admin can edit anyone; admin can only
              // edit users they created.
              $canEdit = (int)$u['id'] !== (int)$_SESSION['admin_id'] && (
                  hasRole('super_admin')
                  || (hasRole('admin') && (int)($u['created_by'] ?? 0) === (int)$_SESSION['admin_id'])
              );
            ?>
            <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
            <div class="d-inline-flex gap-1">
              <?php if ($canEdit): ?>
              <a href="<?= BASE_PATH ?>/admin/user-form.php?id=<?= (int)$u['id'] ?>"
                 class="btn btn-sm btn-outline-primary" title="Edit user">
                <i class="fa-solid fa-pen"></i>
              </a>
              <?php endif; ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                        data-confirm="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>?"
                        data-confirm-title="<?= $u['is_active'] ? 'Deactivate user' : 'Activate user' ?>"
                        data-confirm-ok="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"
                        data-confirm-variant="<?= $u['is_active'] ? 'warning' : 'success' ?>"
                        title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                  <i class="fa-solid <?= $u['is_active'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                </button>
              </form>
              <?php if (hasRole('super_admin')): ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Permanently delete <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>? This cannot be undone."
                        data-confirm-title="Delete user"
                        data-confirm-ok="Delete"
                        data-confirm-variant="danger"
                        title="Delete user">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
            <?php else: ?>
              <span class="text-muted fs-12">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Invite User Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="invite_user">
        <div class="modal-header" style="background:var(--sidebar-bg);color:#fff;">
          <h5 class="modal-title" id="inviteModalLabel">
            <i class="fa-solid fa-user-plus me-2"></i>Invite New User
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-600">Full Name *</label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Ahmed Raza">
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Email Address *</label>
              <input type="email" name="email" class="form-control" required placeholder="ahmed@example.com">
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Phone</label>
              <input type="tel" name="phone" class="form-control" placeholder="+92 300 1234567">
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Role *</label>
              <select name="role" class="form-select" required>
                <option value="agent">Agent</option>
                <?php if (hasRole('super_admin')): ?>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
                <?php endif; ?>
              </select>
              <div class="form-text">Agents can manage listings and view inquiries. Admins can manage users and settings.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gold">
            <i class="fa-solid fa-paper-plane me-1"></i> Send Invitation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
