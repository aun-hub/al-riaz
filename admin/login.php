<?php
/**
 * Al-Riaz Associates — Admin Login
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, go to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_PATH . '/admin/index.php');
    exit;
}

$error   = '';
$email   = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $postToken = $_POST['csrf_token'] ?? '';
    $sessToken = $_SESSION['csrf_token'] ?? '';
    if (!$sessToken || !hash_equals($sessToken, $postToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if ($email === '' || $password === '') {
            $error = 'Email and password are required.';
        } else {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare(
                    'SELECT id, name, email, password_hash, role, is_active, avatar_url
                     FROM users
                     WHERE email = ?
                     LIMIT 1'
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);

                    $_SESSION['admin_id']     = (int)$user['id'];
                    $_SESSION['admin_name']   = $user['name'];
                    $_SESSION['admin_role']   = $user['role'];
                    $_SESSION['admin_email']  = $user['email'];
                    $_SESSION['admin_avatar'] = $user['avatar_url'] ?? '';

                    // Remember me: extend session cookie lifetime
                    if ($remember) {
                        ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 days
                    }

                    // Update last_login
                    try {
                        $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')
                           ->execute([$user['id']]);
                    } catch(Exception $e) {}

                    // Log audit
                    try {
                        $db->prepare(
                            'INSERT INTO audit_log (admin_id, admin_name, action, entity, entity_id, detail, ip_address, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                        )->execute([$user['id'], $user['name'], 'login', 'users', $user['id'], 'Logged in', $_SERVER['REMOTE_ADDR'] ?? '']);
                    } catch(Exception $e) {}

                    header('Location: ' . BASE_PATH . '/admin/index.php');
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                    // Small delay to slow brute force
                    usleep(500_000);
                }
            } catch (Exception $e) {
                error_log('[Login] ' . $e->getMessage());
                $error = 'A system error occurred. Please try again later.';
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Al-Riaz Associates</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">

    <!-- Header -->
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
          <i class="fa-solid fa-building-columns"></i>
        <?php endif; ?>
      </div>
      <h1 style="color:#fff;font-size:1.25rem;font-weight:800;margin:0 0 0.25rem;">
        <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <p style="color:rgba(255,255,255,0.65);font-size:0.8rem;margin:0;letter-spacing:1px;text-transform:uppercase;">Admin Panel</p>
    </div>

    <!-- Body -->
    <div class="login-card-body">
      <h5 class="mb-1 fw-700" style="color:#0A1628;">Welcome back</h5>
      <p class="text-muted mb-4" style="font-size:0.85rem;">Sign in to your admin account to continue.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" role="alert">
          <i class="fa-solid fa-circle-xmark"></i>
          <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php
        $flash = $_SESSION['flash'] ?? null;
        if ($flash) unset($_SESSION['flash']);
      ?>
      <?php if ($flash && ($flash['type'] ?? '') === 'success'): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" role="alert">
          <i class="fa-solid fa-circle-check"></i>
          <span><?= htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_PATH ?>/admin/login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-3">
          <label for="email" class="form-label fw-600" style="font-size:0.85rem;">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="admin@alriazassociates.pk"
                   value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                   required autocomplete="email" autofocus>
          </div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label fw-600" style="font-size:0.85rem;">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" id="togglePass" tabindex="-1"
                    title="Show/Hide password">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
            <label class="form-check-label" for="remember" style="font-size:0.84rem;">Remember me</label>
          </div>
          <a href="<?= BASE_PATH ?>/admin/forgot-password.php" class="text-decoration-none" style="font-size:0.84rem;">
            Forgot password?
          </a>
        </div>

        <button type="submit" class="btn btn-login w-100 rounded-2">
          <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
        </button>
      </form>
    </div>

    <div class="text-center py-3 border-top" style="font-size:0.75rem;color:#adb5bd;">
      &copy; <?= date('Y') ?> Al-Riaz Associates. All rights reserved.
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle password visibility
  document.getElementById('togglePass').addEventListener('click', function() {
    var passInput = document.getElementById('password');
    var eyeIcon   = document.getElementById('eyeIcon');
    if (passInput.type === 'password') {
      passInput.type = 'text';
      eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      passInput.type = 'password';
      eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  });
</script>
</body>
</html>
