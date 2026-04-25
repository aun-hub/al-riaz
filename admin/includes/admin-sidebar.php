<?php
/**
 * Al-Riaz Associates — Admin Sidebar
 * Include after admin-header.php on every admin page.
 */

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch new inquiry count for badge
$newInquiryCount = 0;
try {
    require_once __DIR__ . '/../../includes/db.php';
    $dbSide = Database::getInstance();
    $sideStmt = $dbSide->query("SELECT COUNT(*) FROM inquiries WHERE status='new'");
    $newInquiryCount = (int)$sideStmt->fetchColumn();
} catch(Exception $e) {}

function sideLink(string $href, string $icon, string $label, string $current, string $badge = ''): string
{
    $page = basename($href);
    $isActive = ($page === $current) ? ' active' : '';
    $badgeHtml = $badge
        ? '<span class="sidebar-badge">' . htmlspecialchars($badge) . '</span>'
        : '';
    return sprintf(
        '<li><a href="%s" class="sidebar-link%s"><span class="nav-icon"><i class="fa-solid %s"></i></span><span>%s</span>%s</a></li>',
        htmlspecialchars(BASE_PATH . $href),
        $isActive,
        htmlspecialchars($icon),
        htmlspecialchars($label),
        $badgeHtml
    );
}
?>

<!-- ── Sidebar ───────────────────────────────────────────── -->
<nav id="adminSidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <a href="<?= BASE_PATH ?>/admin/index.php" class="text-decoration-none">
      <div class="sidebar-logo-text">
        <span class="sidebar-logo-main">Al-Riaz Associates</span>
        <span class="sidebar-logo-sub">Admin Panel</span>
      </div>
    </a>
  </div>

  <!-- Navigation -->
  <ul class="sidebar-nav">

    <li class="sidebar-section-label">Main</li>

    <?= sideLink('/admin/index.php',    'fa-gauge-high',     'Dashboard',     $currentPage) ?>
    <?= sideLink('/admin/listings.php', 'fa-house',          'Listings',      $currentPage) ?>
    <?= sideLink('/admin/projects.php', 'fa-building',       'Projects',      $currentPage) ?>
    <?= sideLink('/admin/inquiries.php','fa-inbox',          'Inquiries',     $currentPage, $newInquiryCount > 0 ? (string)$newInquiryCount : '') ?>
    <?= sideLink('/admin/media.php',    'fa-images',         'Media Library', $currentPage) ?>

    <hr class="sidebar-divider">

    <li class="sidebar-section-label">Administration</li>

    <?php if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'super_admin'])): ?>
      <?= sideLink('/admin/dealers.php',  'fa-handshake',      'Authorized Dealers',   $currentPage) ?>
      <?= sideLink('/admin/features.php', 'fa-list-check',     'Features & Amenities', $currentPage) ?>
      <?= sideLink('/admin/users.php',    'fa-users',          'Users & Roles',        $currentPage) ?>
      <?= sideLink('/admin/settings.php', 'fa-gear',           'Settings',             $currentPage) ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
      <?= sideLink('/admin/audit.php', 'fa-clock-rotate-left', 'Audit Log', $currentPage) ?>
    <?php endif; ?>

  </ul>

  <!-- Bottom Logout -->
  <div class="sidebar-bottom">
    <ul class="sidebar-nav" style="padding:0;">
      <li>
        <a href="<?= BASE_PATH ?>/admin/logout.php" class="sidebar-link">
          <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>

</nav>

<!-- ── Main Content Wrapper ──────────────────────────────── -->
<main id="mainContent">
