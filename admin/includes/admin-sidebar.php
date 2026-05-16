<?php
/**
 * Al-Riaz Associates — Admin Sidebar
 * Include after admin-header.php on every admin page.
 */

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch inquiry badge count, role-scoped:
//   - admin / super_admin: all inquiries still in `new` status
//   - agent: their own assigned inquiries that are not yet closed
$newInquiryCount = 0;
$pendingReviewCount = 0;
try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/auth.php';
    $dbSide = Database::getInstance();
    if (function_exists('hasRole') && hasRole('admin')) {
        $sideStmt = $dbSide->query("SELECT COUNT(*) FROM inquiries WHERE status='new'");
        $newInquiryCount = (int)$sideStmt->fetchColumn();
        // Reviews are admin-only; query is wrapped in its own try so a missing
        // table (pre-migration) doesn't break the sidebar.
        try {
            $rStmt = $dbSide->query("SELECT COUNT(*) FROM reviews WHERE status='pending'");
            $pendingReviewCount = (int)$rStmt->fetchColumn();
        } catch (Exception $e) {}
    } else {
        $sideStmt = $dbSide->prepare(
            "SELECT COUNT(*) FROM inquiries
              WHERE assigned_to = ?
                AND status NOT IN ('closed_won','closed_lost')"
        );
        $sideStmt->execute([(int)($_SESSION['admin_id'] ?? 0)]);
        $newInquiryCount = (int)$sideStmt->fetchColumn();
    }
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
  <?php
    // Role → panel label. Falls back to "Admin Panel" for unknown roles so
    // a misconfigured account doesn't render an empty header.
    $roleLabels = [
        'super_admin' => 'Super Admin Panel',
        'admin'       => 'Admin Panel',
        'agent'       => 'Agent Panel',
    ];
    $currentRole = $_SESSION['admin_role'] ?? '';
    $panelLabel  = $roleLabels[$currentRole] ?? 'Admin Panel';
    // SITE_NAME is sourced from settings.json (Agency Profile) in config.php,
    // so editing the agency name in admin updates this header automatically.
    $brandName   = defined('SITE_NAME') ? SITE_NAME : 'Admin';
  ?>
  <div class="sidebar-logo">
    <a href="<?= BASE_PATH ?>/admin/index.php" class="text-decoration-none">
      <div class="sidebar-logo-text">
        <span class="sidebar-logo-main"><?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="sidebar-logo-sub"><?= htmlspecialchars($panelLabel, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </a>
  </div>

  <!-- Navigation -->
  <ul class="sidebar-nav">

    <li class="sidebar-section-label">Main</li>

    <?= sideLink('/admin/index.php',    'fa-gauge-high',     'Dashboard',     $currentPage) ?>
    <?= sideLink('/admin/listings.php', 'fa-house',          'Listings',      $currentPage) ?>
    <?= sideLink('/admin/projects.php', 'fa-building',       'Projects',      $currentPage) ?>
    <?= sideLink('/admin/notices.php',  'fa-bullhorn',       'Notices',       $currentPage) ?>
    <?= sideLink('/admin/inquiries.php','fa-inbox',          'Inquiries',     $currentPage, $newInquiryCount > 0 ? (string)$newInquiryCount : '') ?>
    <?= sideLink('/admin/media.php',    'fa-images',         'Media Library', $currentPage) ?>

    <hr class="sidebar-divider">

    <li class="sidebar-section-label">Administration</li>

    <?php if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'super_admin'])): ?>
      <?= sideLink('/admin/reviews.php',  'fa-star',           'Reviews',              $currentPage, $pendingReviewCount > 0 ? (string)$pendingReviewCount : '') ?>
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
