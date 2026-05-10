<?php
/**
 * Al-Riaz Associates — Edit User
 *
 * - Super admins and admins can edit any user.
 * - Agents cannot reach this page (blocked by requireRole('admin')).
 * - Editing your own profile happens at /admin/profile.php instead.
 */
$pageTitle = 'Edit User';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = Database::getInstance();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Invalid user id.');
    redirect('/admin/users.php');
}
if ($id === (int)$_SESSION['admin_id']) {
    redirect('/admin/profile.php');
}

try {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    error_log('[user-form] ' . $e->getMessage());
    setFlash('danger', 'Could not load user.');
    redirect('/admin/users.php');
}

if (!$user) {
    setFlash('danger', 'User not found.');
    redirect('/admin/users.php');
}

$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name      = trim($_POST['name']  ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $role      = $_POST['role'] ?? $user['role'];
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') $formErrors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $formErrors[] = 'A valid email is required.';

    // Role permissions:
    //  - super_admin can assign any role
    //  - admin can only assign 'agent' (cannot promote/demote to admin/super_admin)
    $allowedRoles = hasRole('super_admin') ? ['agent','admin','super_admin'] : ['agent'];
    if (!in_array($role, $allowedRoles, true)) {
        if (hasRole('super_admin')) {
            $formErrors[] = 'Invalid role.';
        } else {
            // Lock to existing role for non-super-admins editing if they tried to set something else
            $role = $user['role'];
        }
    }

    // Prevent removing the last active super_admin
    if (empty($formErrors) && $user['role'] === 'super_admin' && ($role !== 'super_admin' || $isActive === 0)) {
        $remaining = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='super_admin' AND is_active=1 AND id <> " . (int)$user['id'])->fetchColumn();
        if ($remaining < 1) {
            $formErrors[] = 'Cannot demote or deactivate the last active Super Admin.';
        }
    }

    if (empty($formErrors) && $email !== $user['email']) {
        $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $dup->execute([$email, $id]);
        if ($dup->fetch()) $formErrors[] = 'Another user already uses that email.';
    }

    // Avatar upload (optional)
    $avatarUrl = null;
    if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        if ($file['size'] > MAX_FILE_SIZE) {
            $formErrors[] = 'Avatar exceeds the file size limit.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
                $formErrors[] = 'Avatar must be JPEG, PNG, WebP, or GIF.';
            } else {
                $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fname = 'avatar_' . $id . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                    $avatarUrl = '/assets/uploads/avatars/' . $fname;
                    $old = (string)($user['avatar_url'] ?? '');
                    if ($old && str_starts_with($old, '/assets/uploads/avatars/')) {
                        $oldFs = __DIR__ . '/..' . $old;
                        if (is_file($oldFs)) @unlink($oldFs);
                    }
                } else {
                    $formErrors[] = 'Avatar upload failed.';
                }
            }
        }
    } elseif (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $formErrors[] = 'Avatar upload error (code ' . (int)$_FILES['avatar']['error'] . ').';
    }

    if (empty($formErrors)) {
        try {
            if ($avatarUrl !== null) {
                $db->prepare(
                    'UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ?, avatar_url = ?, updated_at = NOW()
                     WHERE id = ?'
                )->execute([$name, $email, $phone, $role, $isActive, $avatarUrl, $id]);
            } else {
                $db->prepare(
                    'UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ?, updated_at = NOW()
                     WHERE id = ?'
                )->execute([$name, $email, $phone, $role, $isActive, $id]);
            }
            auditLog('update', 'users', $id, 'Edited: ' . $email . ' (role=' . $role . ', active=' . $isActive . ($avatarUrl !== null ? ', avatar updated' : '') . ')');
            setFlash('success', 'User updated successfully.');
            redirect('/admin/users.php');
        } catch (Exception $e) {
            $formErrors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // Re-load form fields with submitted values on error
    $user['name']      = $name;
    $user['email']     = $email;
    $user['phone']     = $phone;
    $user['role']      = $role;
    $user['is_active'] = $isActive;
    if ($avatarUrl !== null) $user['avatar_url'] = $avatarUrl;
}

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-user-pen me-2" style="color:var(--gold)"></i>Edit User</h1>
    <p class="text-muted mb-0 fs-13">
      Editing: <strong><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/users.php" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Users
  </a>
</div>

<?php if (!empty($formErrors)): ?>
<div class="alert alert-danger">
  <strong>Please fix the following:</strong>
  <ul class="mb-0 mt-1">
    <?php foreach ($formErrors as $err): ?><li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" action="<?= BASE_PATH ?>/admin/user-form.php?id=<?= (int)$id ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-id-card" style="color:var(--gold)"></i> Account Details</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Full Name *</label>
          <input type="text" name="name" class="form-control" required maxlength="120"
                 value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Email *</label>
          <input type="email" name="email" class="form-control" required maxlength="160"
                 value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Phone</label>
          <input type="tel" name="phone" class="form-control" maxlength="20"
                 value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Role</label>
          <?php if (hasRole('super_admin')): ?>
            <select name="role" class="form-select">
              <?php foreach (['agent'=>'Agent','admin'=>'Admin','super_admin'=>'Super Admin'] as $rv => $rl): ?>
                <option value="<?= $rv ?>" <?= $user['role'] === $rv ? 'selected' : '' ?>><?= $rl ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="text" class="form-control" disabled
                   value="<?= htmlspecialchars(ucwords(str_replace('_',' ', $user['role'])), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="role" value="<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Only a Super Admin can change roles.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-image-portrait" style="color:var(--gold)"></i> Avatar</div>
    <div class="card-body">
      <div class="mb-3">
        <div class="fs-13 text-muted mb-1">Current avatar:</div>
        <?= renderUserAvatar($user, 96, 'border') ?>
      </div>
      <label class="form-label fw-600">Replace avatar</label>
      <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
      <div class="form-text">Max 5MB. JPEG / PNG / WebP / GIF. Square images look best.</div>
    </div>
  </div>

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-toggle-on" style="color:var(--gold)"></i> Status</div>
    <div class="card-body">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="userActive" name="is_active" value="1"
               <?= (int)$user['is_active'] === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="userActive">
          <i class="fa-solid fa-circle-check text-success me-1"></i> Account is active
        </label>
      </div>
      <div class="form-text mt-2">
        <i class="fa-solid fa-info-circle me-1"></i>
        To change a user's password, send them a password-reset link from the
        <a href="<?= BASE_PATH ?>/admin/forgot-password.php" target="_blank" rel="noopener">forgot-password</a> page.
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 pb-4">
    <a href="<?= BASE_PATH ?>/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-gold px-4">
      <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
    </button>
  </div>
</form>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
