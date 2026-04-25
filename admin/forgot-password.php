<?php
/**
 * Al-Riaz Associates — Forgot Password
 * Generates a single-use reset token, emails it to the user as a link.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_PATH . '/admin/index.php');
    exit;
}

$error    = '';
$notice   = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    $sessToken = $_SESSION['csrf_token'] ?? '';
    if (!$sessToken || !hash_equals($sessToken, $postToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $emailVal = trim($_POST['email'] ?? '');
        if ($emailVal === '' || !filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare(
                    'SELECT id, name, email, is_active FROM users WHERE email = ? LIMIT 1'
                );
                $stmt->execute([$emailVal]);
                $user = $stmt->fetch();

                // Generic message regardless of whether the email exists, to
                // avoid leaking valid admin emails. If the account exists and
                // is active, we send the reset link.
                if ($user && (int)$user['is_active'] === 1) {
                    $token   = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                    $db->prepare(
                        'UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?'
                    )->execute([$token, $expires, $user['id']]);

                    $resetUrl = rtrim(APP_URL, '/') . '/admin/reset-password.php?token=' . urlencode($token);

                    $subject = 'Reset your Al-Riaz Associates admin password';
                    $body = '<p>Hi ' . htmlspecialchars($user['name']) . ',</p>'
                          . '<p>We received a request to reset the password for your admin account at Al-Riaz Associates.</p>'
                          . '<p><a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;padding:0.7rem 1.4rem;background:#F5B301;color:#0A1628;border-radius:6px;text-decoration:none;font-weight:700;">Reset password</a></p>'
                          . '<p>Or copy and paste this URL into your browser:<br>'
                          . '<code>' . htmlspecialchars($resetUrl) . '</code></p>'
                          . '<p>This link will expire in 1 hour. If you did not request this, you can safely ignore this email — your password will stay the same.</p>'
                          . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:1.5rem 0;">'
                          . '<p style="color:#6b7280;font-size:0.85rem;">Sent automatically by Al-Riaz Associates Admin Panel.</p>';
                    $altBody = "Reset your password: $resetUrl\n\nThis link expires in 1 hour. If you didn't request this, ignore this email.";

                    $send = sendMail($user['email'], $subject, $body, [
                        'html'     => true,
                        'alt_body' => $altBody,
                        'to_name'  => $user['name'],
                    ]);

                    if (!$send['ok']) {
                        error_log('[forgot-password] sendMail failed: ' . ($send['error'] ?? 'unknown'));
                    }

                    try {
                        $db->prepare(
                            'INSERT INTO audit_log (admin_id, admin_name, action, entity, entity_id, detail, ip_address, created_at)
                             VALUES (?,?,?,?,?,?,?, NOW())'
                        )->execute([$user['id'], $user['name'], 'password_reset_request', 'users', $user['id'], 'Password reset email sent', $_SERVER['REMOTE_ADDR'] ?? '']);
                    } catch (Exception $e) { /* best-effort */ }
                }

                $notice = 'If an account exists for that email, a password reset link has been sent. Check your inbox (and spam folder).';
                $emailVal = '';
            } catch (Exception $e) {
                error_log('[forgot-password] ' . $e->getMessage());
                $error = 'A system error occurred. Please try again later.';
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
  <title>Forgot Password — Al-Riaz Associates</title>
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
          <i class="fa-solid fa-key"></i>
        <?php endif; ?>
      </div>
      <h1 style="color:#fff;font-size:1.25rem;font-weight:800;margin:0 0 0.25rem;">Forgot Password</h1>
      <p style="color:rgba(255,255,255,0.65);font-size:0.8rem;margin:0;letter-spacing:1px;text-transform:uppercase;">
        <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>

    <div class="login-card-body">
      <h5 class="mb-1 fw-700" style="color:#0A1628;">Reset your password</h5>
      <p class="text-muted mb-4" style="font-size:0.85rem;">Enter your admin email and we'll send you a link to choose a new password.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
          <i class="fa-solid fa-circle-xmark"></i>
          <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      <?php endif; ?>

      <?php if ($notice): ?>
        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
          <i class="fa-solid fa-circle-check"></i>
          <span><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_PATH ?>/admin/forgot-password.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-4">
          <label for="email" class="form-label fw-600" style="font-size:0.85rem;">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="admin@alriazassociates.pk"
                   value="<?= htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8') ?>"
                   required autocomplete="email" autofocus>
          </div>
        </div>

        <button type="submit" class="btn btn-login w-100 rounded-2">
          <i class="fa-solid fa-paper-plane me-2"></i> Send Reset Link
        </button>
      </form>

      <div class="text-center mt-3">
        <a href="<?= BASE_PATH ?>/admin/login.php" class="text-decoration-none" style="font-size:0.85rem;">
          <i class="fa-solid fa-arrow-left me-1"></i>Back to sign-in
        </a>
      </div>
    </div>

    <div class="text-center py-3 border-top" style="font-size:0.75rem;color:#adb5bd;">
      &copy; <?= date('Y') ?> Al-Riaz Associates. All rights reserved.
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
