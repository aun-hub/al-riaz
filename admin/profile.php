<?php
/**
 * Al-Riaz Associates — My Profile
 * Any logged-in user can edit their own name, email, phone, avatar, and password.
 */
$pageTitle = 'My Profile';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db    = Database::getInstance();
$myId  = (int)($_SESSION['admin_id'] ?? 0);

try {
    $stmt = $db->prepare('SELECT id, name, email, phone, role, avatar_url FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$myId]);
    $me = $stmt->fetch();
} catch (Exception $e) {
    error_log('[profile] ' . $e->getMessage());
    setFlash('danger', 'Could not load your profile.');
    redirect('/admin/index.php');
}
if (!$me) {
    setFlash('danger', 'Your account record could not be found.');
    redirect('/admin/logout.php');
}

$formErrors = [];
$pwdErrors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '')  $formErrors[] = 'Name is required.';
        if (mb_strlen($name) > 120) $formErrors[] = 'Name is too long.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $formErrors[] = 'A valid email is required.';
        if (mb_strlen($phone) > 20) $formErrors[] = 'Phone is too long.';

        // Email uniqueness
        if (empty($formErrors) && $email !== $me['email']) {
            $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $dup->execute([$email, $myId]);
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
                    $fname = 'avatar_' . $myId . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                        $avatarUrl = '/assets/uploads/avatars/' . $fname;
                        // Delete old avatar if it was a local upload
                        $old = (string)($me['avatar_url'] ?? '');
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
                    $db->prepare('UPDATE users SET name=?, email=?, phone=?, avatar_url=?, updated_at=NOW() WHERE id=?')
                       ->execute([$name, $email, $phone, $avatarUrl, $myId]);
                } else {
                    $db->prepare('UPDATE users SET name=?, email=?, phone=?, updated_at=NOW() WHERE id=?')
                       ->execute([$name, $email, $phone, $myId]);
                }

                // Refresh session display fields
                $_SESSION['admin_name']   = $name;
                $_SESSION['admin_email']  = $email;
                if ($avatarUrl !== null) {
                    $_SESSION['admin_avatar'] = $avatarUrl;
                }

                auditLog('profile_update', 'users', $myId, 'Updated own profile');
                setFlash('success', 'Profile updated successfully.');
                redirect('/admin/profile.php');
            } catch (Exception $e) {
                $formErrors[] = 'Database error: ' . $e->getMessage();
            }
        }

        $me['name']  = $name;
        $me['email'] = $email;
        $me['phone'] = $phone;
        if ($avatarUrl !== null) $me['avatar_url'] = $avatarUrl;
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';

        try {
            $hashRow = $db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $hashRow->execute([$myId]);
            $hash = $hashRow->fetchColumn();
        } catch (Exception $e) {
            $hash = '';
        }

        if (!$hash || !password_verify($current, $hash)) {
            $pwdErrors[] = 'Current password is incorrect.';
        }
        if (strlen($new) < 8) $pwdErrors[] = 'New password must be at least 8 characters.';
        if ($new !== $confirm) $pwdErrors[] = 'Passwords do not match.';

        if (empty($pwdErrors)) {
            try {
                $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 10]);
                $db->prepare('UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?')
                   ->execute([$newHash, $myId]);
                auditLog('password_change', 'users', $myId, 'Changed own password');
                setFlash('success', 'Password updated successfully.');
                redirect('/admin/profile.php');
            } catch (Exception $e) {
                $pwdErrors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-user-gear me-2" style="color:var(--gold)"></i>My Profile</h1>
    <p class="text-muted mb-0 fs-13">
      Signed in as <strong><?= htmlspecialchars($me['email'], ENT_QUOTES, 'UTF-8') ?></strong>
      &middot; Role: <span class="text-capitalize"><?= htmlspecialchars(str_replace('_',' ', $me['role']), ENT_QUOTES, 'UTF-8') ?></span>
    </p>
  </div>
</div>

<div class="row g-4">
  <!-- Profile details -->
  <div class="col-12 col-lg-7">
    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
      <strong>Please fix the following:</strong>
      <ul class="mb-0 mt-1">
        <?php foreach ($formErrors as $err): ?><li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="<?= BASE_PATH ?>/admin/profile.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="profile">

      <div class="form-section-card">
        <div class="card-header"><i class="fa-solid fa-id-card" style="color:var(--gold)"></i> Personal Information</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-600">Full Name *</label>
              <input type="text" name="name" class="form-control" required maxlength="120"
                     value="<?= htmlspecialchars($me['name'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-600">Email *</label>
              <input type="email" name="email" class="form-control" required maxlength="160"
                     value="<?= htmlspecialchars($me['email'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-600">Phone</label>
              <input type="tel" name="phone" class="form-control" maxlength="20"
                     value="<?= htmlspecialchars($me['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="form-section-card">
        <div class="card-header"><i class="fa-solid fa-image-portrait" style="color:var(--gold)"></i> Avatar</div>
        <div class="card-body">
          <?php if (!empty($me['avatar_url'])): ?>
          <div class="mb-3">
            <div class="fs-13 text-muted mb-1">Current avatar:</div>
            <img src="<?= htmlspecialchars(mediaUrl($me['avatar_url']), ENT_QUOTES, 'UTF-8') ?>"
                 alt="Current avatar"
                 style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:1px solid #dee2e6;">
          </div>
          <?php endif; ?>
          <label class="form-label fw-600">Replace avatar</label>
          <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
          <div class="form-text">Max 5MB. JPEG / PNG / WebP / GIF. Square images look best.</div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 pb-4">
        <button type="submit" class="btn btn-gold px-4">
          <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile
        </button>
      </div>
    </form>
  </div>

  <!-- Password change -->
  <div class="col-12 col-lg-5">
    <?php if (!empty($pwdErrors)): ?>
    <div class="alert alert-danger">
      <strong>Could not update password:</strong>
      <ul class="mb-0 mt-1">
        <?php foreach ($pwdErrors as $err): ?><li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_PATH ?>/admin/profile.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="password">

      <div class="form-section-card">
        <div class="card-header"><i class="fa-solid fa-key" style="color:var(--gold)"></i> Change Password</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-600">Current Password *</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">New Password *</label>
            <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
            <div class="form-text">At least 8 characters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Confirm New Password *</label>
            <input type="password" name="new_password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-dark w-100">
            <i class="fa-solid fa-key me-1"></i> Update Password
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
