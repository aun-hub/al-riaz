<?php
/**
 * Al-Riaz Associates — Accept Invite
 * Lands users here from the invite email. Validates the token and lets them
 * set a password, then activates the account and logs them in.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$db = Database::getInstance();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$user  = null;

// ── Validate token ──────────────────────────────────────────────
if ($token === '' || !ctype_xdigit($token) || strlen($token) < 10 || strlen($token) > 100) {
    $error = 'Invalid or missing invite token.';
} else {
    try {
        $stmt = $db->prepare('SELECT id, email, name, role, invite_token, is_active, avatar_url FROM users WHERE invite_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) {
            $error = 'This invite link is invalid or has already been used.';
        }
    } catch (Exception $e) {
        error_log('[invite-accept] ' . $e->getMessage());
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
                    'UPDATE users SET password_hash = ?, is_active = 1, invite_token = NULL, last_login = NOW()
                     WHERE id = ?'
                )->execute([$hash, $user['id']]);

                // Log the new session in
                session_regenerate_id(true);
                $_SESSION['admin_id']     = (int)$user['id'];
                $_SESSION['admin_name']   = $user['name'];
                $_SESSION['admin_role']   = $user['role'];
                $_SESSION['admin_email']  = $user['email'];
                $_SESSION['admin_avatar'] = (string)($user['avatar_url'] ?? '');

                try {
                    $db->prepare(
                        'INSERT INTO audit_log (admin_id, admin_name, action, entity, entity_id, detail, ip_address, created_at)
                         VALUES (?,?,?,?,?,?,?, NOW())'
                    )->execute([$user['id'], $user['name'], 'invite_accept', 'users', $user['id'], 'Accepted invite', $_SERVER['REMOTE_ADDR'] ?? '']);
                } catch (Exception $e) { /* best-effort */ }

                header('Location: ' . BASE_PATH . '/admin/index.php');
                exit;
            } catch (Exception $e) {
                error_log('[invite-accept] ' . $e->getMessage());
                $error = 'Could not save your password. Please try again.';
            }
        }
    }
}

// CSRF token for form
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
  <title>Accept Invitation — Al-Riaz Associates</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/admin.css?v=<?= $cssVer ?>">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">

    <div class="login-card-header">
      <div class="login-logo-circle">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h1 style="color:#fff;font-size:1.25rem;font-weight:800;margin:0 0 0.25rem;">Accept Invitation</h1>
      <p style="color:rgba(255,255,255,0.65);font-size:0.8rem;margin:0;letter-spacing:1px;text-transform:uppercase;">Al-Riaz Associates</p>
    </div>

    <div class="login-card-body">

      <?php if ($error && !$user): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
          <i class="fa-solid fa-circle-xmark"></i>
          <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="text-center mt-3">
          <a href="<?= BASE_PATH ?>/admin/login.php" class="text-decoration-none">Back to sign-in</a>
        </div>

      <?php elseif ($user): ?>
        <h5 class="mb-1 fw-700" style="color:#0A1628;">Welcome, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h5>
        <p class="text-muted mb-4" style="font-size:0.85rem;">
          Set a password for <strong><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong> to activate your account.
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" role="alert">
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
            <i class="fa-solid fa-check me-2"></i> Activate Account
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
