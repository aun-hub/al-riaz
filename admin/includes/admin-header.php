<?php
/**
 * Al-Riaz Associates — Admin Page Header
 * Include at the top of every admin page.
 * Expects $pageTitle to be set before inclusion.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = $pageTitle ?? 'Admin Panel';
$flash = !empty($_SESSION['flash']) ? $_SESSION['flash'] : null;
if ($flash) unset($_SESSION['flash']);

// Lazy-load avatar URL into the session for older sessions that pre-date the
// avatar feature, so the header circle picks it up without a re-login.
if (!isset($_SESSION['admin_avatar']) && !empty($_SESSION['admin_id'])) {
    try {
        require_once __DIR__ . '/../../includes/db.php';
        $hdrDb = Database::getInstance();
        $hdrStmt = $hdrDb->prepare('SELECT avatar_url FROM users WHERE id = ? LIMIT 1');
        $hdrStmt->execute([(int)$_SESSION['admin_id']]);
        $_SESSION['admin_avatar'] = (string)($hdrStmt->fetchColumn() ?: '');
    } catch (Exception $e) {
        $_SESSION['admin_avatar'] = '';
    }
}
$adminAvatar = (string)($_SESSION['admin_avatar'] ?? '');
$adminAvatarUrl = $adminAvatar !== ''
    ? (function_exists('mediaUrl') ? mediaUrl($adminAvatar) : $adminAvatar)
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Al-Riaz Associates Admin</title>

  <!-- Bootstrap 5.3.2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome 6.5 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Admin CSS -->
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/admin.css') ?>">

  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">

<!-- ── Sidebar Overlay (mobile) ─────────────────────────── -->
<div id="sidebarOverlay"></div>

<!-- ── Top Admin Header ─────────────────────────────────── -->
<header id="adminHeader">
  <button id="hamburgerBtn" aria-label="Toggle sidebar" title="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
  </button>

  <a class="header-brand d-flex d-lg-none align-items-center gap-2" href="<?= BASE_PATH ?>/admin/index.php">
    <i class="fa-solid fa-shield-halved text-gold" style="color:var(--gold)"></i>
    Al-Riaz Admin
  </a>

  <p class="page-title-text">
    <i class="fa-solid fa-angle-right d-none d-md-inline text-muted me-1"></i>
    <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
  </p>

  <!-- Right side -->
  <div class="d-flex align-items-center gap-3 ms-auto">
    <!-- Inquiries Bell -->
    <a href="<?= BASE_PATH ?>/admin/inquiries.php" class="btn btn-light btn-sm position-relative" title="Inquiries">
      <i class="fa-solid fa-bell"></i>
      <?php
      try {
        require_once __DIR__ . '/../../includes/db.php';
        $dbh = Database::getInstance();
        $bellStmt = $dbh->query("SELECT COUNT(*) FROM inquiries WHERE status='new'");
        $newCount = (int)$bellStmt->fetchColumn();
        if ($newCount > 0):
      ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem">
          <?= $newCount ?>
        </span>
      <?php endif; } catch(Exception $e) {} ?>
    </a>

    <!-- View Site -->
    <a href="<?= BASE_PATH ?>/" target="_blank" class="btn btn-outline-secondary btn-sm d-none d-md-inline-flex align-items-center gap-1">
      <i class="fa-solid fa-arrow-up-right-from-square"></i>
      View Site
    </a>

    <!-- Admin Avatar Dropdown -->
    <div class="dropdown">
      <button class="btn btn-sm d-flex align-items-center gap-2 px-2 py-1"
              data-bs-toggle="dropdown" aria-expanded="false"
              style="border:1px solid #dee2e6; border-radius:8px; background:#fff;">
        <?php if ($adminAvatarUrl): ?>
          <img src="<?= htmlspecialchars($adminAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($_SESSION['admin_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
        <?php else: ?>
          <div style="width:32px;height:32px;border-radius:50%;background:var(--sidebar-bg);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:0.85rem;font-weight:700;">
            <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="text-start d-none d-lg-block">
          <div style="font-size:0.82rem;font-weight:600;color:#1a1a2e;line-height:1.1;">
            <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div style="font-size:0.7rem;color:#6c757d;text-transform:capitalize;">
            <?= htmlspecialchars($_SESSION['admin_role'] ?? 'agent', ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        <i class="fa-solid fa-chevron-down text-muted" style="font-size:0.65rem;"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;">
        <li>
          <div class="px-3 py-2 border-bottom">
            <div class="fw-600" style="font-size:0.85rem;"><?= htmlspecialchars($_SESSION['admin_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_PATH ?>/admin/profile.php">
            <i class="fa-solid fa-user fa-fw text-muted"></i> My Profile
          </a>
        </li>
        <?php if (function_exists('hasRole') && hasRole('admin')): ?>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_PATH ?>/admin/settings.php">
            <i class="fa-solid fa-gear fa-fw text-muted"></i> System Settings
          </a>
        </li>
        <?php endif; ?>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?= BASE_PATH ?>/admin/logout.php">
            <i class="fa-solid fa-right-from-bracket fa-fw"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</header>

<!-- ── Flash Message ─────────────────────────────────────── -->
<?php if ($flash): ?>
<div class="flash-wrapper" id="flashWrapper">
  <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible shadow-sm d-flex align-items-center gap-2 mb-0" role="alert">
    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info') ?>"></i>
    <span><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
</div>
<script>
  setTimeout(function(){ var el=document.getElementById('flashWrapper'); if(el){ el.style.opacity='0'; el.style.transition='opacity 0.5s'; setTimeout(function(){ el.remove(); }, 500); } }, 4000);
</script>
<?php endif; ?>
