<?php
/**
 * Al-Riaz Associates — Reset Password
 * Validates the token from the email link and lets the user set a new password.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db    = Database::getInstance();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$user  = null;

// ── Validate token ──────────────────────────────────────────────
if ($token === '' || !ctype_xdigit($token) || strlen($token) < 32 || strlen($token) > 100) {
    $error = 'Invalid or missing reset token.';
} else {
    try {
        $stmt = $db->prepare(
            'SELECT id, email, name, role, reset_token_expires_at, is_active
             FROM users
             WHERE reset_token = ?
             LIMIT 1'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'This reset link is invalid or has already been used.';
            $user  = null;
        } elseif (!(int)$user['is_active']) {
            $error = 'This account is inactive. Please contact a super admin.';
            $user  = null;
        } elseif (!empty($user['reset_token_expires_at']) && strtotime($user['reset_token_expires_at']) < time()) {
            $error = 'This reset link has expired. Please request a new one.';
            $user  = null;
        }
    } catch (Exception $e) {
        error_log('[reset-password] ' . $e->getMessage());
        $error = 'A system error occurred. Please try again later.';
    }
}

// ── Handle password submission ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $postToken = $_POST['csrf_token'] ?? '';
    $sessToken = $_SESSION['csrf_token'] ?? '';
    if (!$sessToken || !hash_equals($sessToken, $postToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $pass1 = $_POST['password']         ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if (strlen($pass1) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pass1 !== $pass2) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $hash = password_hash($pass1, PASSWORD_BCRYPT, ['cost' => 10]);
                $db->prepare(
                    'UPDATE users
                       SET password_hash = ?,
                           reset_token = NULL,
                           reset_token_expires_at = NULL
                     WHERE id = ?'
                )->execute([$hash, $user['id']]);

                try {
                    $db->prepare(
                        'INSERT INTO audit_log (admin_id, admin_name, action, entity, entity_id, detail, ip_address, created_at)
                         VALUES (?,?,?,?,?,?,?, NOW())'
                    )->execute([$user['id'], $user['name'], 'password_reset', 'users', $user['id'], 'Password reset via email link', $_SERVER['REMOTE_ADDR'] ?? '']);
                } catch (Exception $e) { /* best-effort */ }

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password updated. Please sign in with your new password.'];
                header('Location: ' . BASE_PATH . '/admin/login.php');
                exit;
            } catch (Exception $e) {
                error_log('[reset-password] ' . $e->getMessage());
                $error = 'Could not save your password. Please try again.';
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$cssPath = __DIR__ . '/../assets/css/admin.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — Al-Riaz Associates</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/admin.css?v=<?= $cssVer ?>">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">

    <?php
      $brand     = function_exists('getSettings') ? getSettings() : [];
      $brandLogo = $brand['logo_path'] ?? '';
      $brandName = !empty($brand['agency_name']) ? $brand['agency_name'] : SITE_NAME;
      $brandLogoUrl = $brandLogo
          ? BASE_PATH . $brandLogo . '?v=' . (@filemtime(__DIR__ . '/..' . $brandLogo) ?: '')
          : '';
    ?>
    <div class="login-card-header">
      <div class="login-logo-circle">
        <?php if ($brandLogoUrl): ?>
          <img src="<?= htmlspecialchars($brandLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?>"
               style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else: ?>
          <i class="fa-solid fa-lock-open"></i>
        <?php endif; ?>
      </div>
      <h1 style="color:#fff;font-size:1.25rem;font-weight:800;margin:0 0 0.25rem;">Reset Password</h1>
      <p style="color:rgba(255,255,255,0.65);font-size:0.8rem;margin:0;letter-spacing:1px;text-transform:uppercase;">
        <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>

    <div class="login-card-body">

      <?php if ($error && !$user): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
          <i class="fa-solid fa-circle-xmark"></i>
          <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="text-center mt-3">
          <a href="<?= BASE_PATH ?>/admin/forgot-password.php" class="text-decoration-none" style="font-size:0.85rem;">
            Request a new reset link
          </a>
          <span class="text-muted mx-2">·</span>
          <a href="<?= BASE_PATH ?>/admin/login.php" class="text-decoration-none" style="font-size:0.85rem;">
            Back to sign-in
          </a>
        </div>

      <?php elseif ($user): ?>
        <h5 class="mb-1 fw-700" style="color:#0A1628;">Set a new password</h5>
        <p class="text-muted mb-4" style="font-size:0.85rem;">
          For account: <strong><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong>
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-circle-xmark"></i>
            <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="token"      value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label fw-600" style="font-size:0.85rem;">New Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
              <input type="password" class="form-control" name="password"
                     minlength="8" required autofocus autocomplete="new-password"
                     placeholder="At least 8 characters">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-600" style="font-size:0.85rem;">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
              <input type="password" class="form-control" name="password_confirm"
                     minlength="8" required autocomplete="new-password"
                     placeholder="Re-enter the password">
            </div>
          </div>

          <button type="submit" class="btn btn-login w-100 rounded-2">
            <i class="fa-solid fa-check me-2"></i> Update Password
          </button>
        </form>
      <?php endif; ?>
    </div>

    <div class="text-center py-3 border-top" style="font-size:0.75rem;color:#adb5bd;">
      &copy; <?= date('Y') ?> Al-Riaz Associates. All rights reserved.
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
