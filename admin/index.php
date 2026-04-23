<?php
/**
 * Al-Riaz Associates — Admin Dashboard Home
 */
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();

// ── KPI Data ─────────────────────────────────────────────────
try {
    // New inquiries (last 7 days)
    $stmt = $db->query("SELECT COUNT(*) FROM inquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $newInquiries7d = (int)$stmt->fetchColumn();

    // Total pending inquiries
    $stmt = $db->query("SELECT COUNT(*) FROM inquiries WHERE status='new'");
    $pendingInquiries = (int)$stmt->fetchColumn();

    // Total published listings
    $stmt = $db->query("SELECT COUNT(*) FROM properties WHERE is_published=1");
    $totalListings = (int)$stmt->fetchColumn();

    // Total projects (published)
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE is_published=1");
    $totalProjects = (int)$stmt->fetchColumn();

    // Featured listings
    $stmt = $db->query("SELECT COUNT(*) FROM properties WHERE is_featured=1 AND is_published=1");
    $featuredListings = (int)$stmt->fetchColumn();

    // Total inquiries
    $stmt = $db->query("SELECT COUNT(*) FROM inquiries");
    $totalInquiries = (int)$stmt->fetchColumn();

    // Most viewed property
    $stmt = $db->query("SELECT title, views_count, id FROM properties WHERE is_published=1 ORDER BY views_count DESC LIMIT 1");
    $topProperty = $stmt->fetch();

    // Recent 10 inquiries
    $stmt = $db->query(
        "SELECT i.id, i.name AS visitor_name, i.phone, i.status, i.created_at,
                p.title AS property_title, p.id AS property_id
         FROM inquiries i
         LEFT JOIN properties p ON i.property_id = p.id
         ORDER BY i.created_at DESC
         LIMIT 10"
    );
    $recentInquiries = $stmt->fetchAll();

    // Listings by city
    $stmt = $db->query(
        "SELECT city, COUNT(*) as cnt FROM properties WHERE is_published=1 GROUP BY city ORDER BY cnt DESC"
    );
    $byCity = $stmt->fetchAll();

    // Inquiries last 30 days by status
    $stmt = $db->query(
        "SELECT status, COUNT(*) as cnt FROM inquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY status"
    );
    $inquiriesByStatus = $stmt->fetchAll();

    $dbError = false;
} catch (Exception $e) {
    error_log('[Dashboard] ' . $e->getMessage());
    $dbError = true;
    $newInquiries7d = $pendingInquiries = $totalListings = $totalProjects = $featuredListings = $totalInquiries = 0;
    $topProperty = null;
    $recentInquiries = $byCity = $inquiriesByStatus = [];
}

// Status badge helper (inline for dashboard)
$statusColors = [
    'new'         => 'primary',
    'assigned'    => 'warning',
    'contacted'   => 'info',
    'qualified'   => 'success',
    'closed_won'  => 'success',
    'closed_lost' => 'danger',
];

function dashBadge(string $status, array $colors): string {
    $cls = $colors[$status] ?? 'secondary';
    $lbl = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars($lbl) . '</span>';
}

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-gauge-high me-2 text-gold" style="color:var(--gold)"></i>Dashboard</h1>
    <p class="text-muted mb-0" style="font-size:0.85rem;">
      Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong>
      &nbsp;·&nbsp; <?= date('l, d F Y') ?>
    </p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/listing-form.php" class="btn btn-gold">
    <i class="fa-solid fa-plus me-1"></i> New Listing
  </a>
</div>

<?php if ($dbError): ?>
<div class="alert alert-warning d-flex align-items-center gap-2">
  <i class="fa-solid fa-triangle-exclamation"></i>
  <span>Could not connect to the database. KPI data unavailable.</span>
</div>
<?php endif; ?>

<!-- ── KPI Cards ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="kpi-card">
      <div class="kpi-icon gold"><i class="fa-solid fa-envelope-open-text"></i></div>
      <div class="kpi-info">
        <div class="kpi-value"><?= $newInquiries7d ?></div>
        <div class="kpi-label">New Inquiries (7d)</div>
        <div class="kpi-change up"><i class="fa-solid fa-arrow-up"></i> Last 7 days</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi-card">
      <div class="kpi-icon green"><i class="fa-solid fa-house"></i></div>
      <div class="kpi-info">
        <div class="kpi-value"><?= $totalListings ?></div>
        <div class="kpi-label">Published Listings</div>
        <div class="kpi-change"><span class="text-warning fw-600"><?= $featuredListings ?></span> featured</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi-card">
      <div class="kpi-icon blue"><i class="fa-solid fa-building"></i></div>
      <div class="kpi-info">
        <div class="kpi-value"><?= $totalProjects ?></div>
        <div class="kpi-label">Live Projects</div>
        <div class="kpi-change">Active developments</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi-card">
      <div class="kpi-icon red"><i class="fa-solid fa-bell-concierge"></i></div>
      <div class="kpi-info">
        <div class="kpi-value"><?= $pendingInquiries ?></div>
        <div class="kpi-label">Pending Inquiries</div>
        <?php if ($pendingInquiries > 0): ?>
        <div class="kpi-change down"><i class="fa-solid fa-circle-exclamation"></i> Needs attention</div>
        <?php else: ?>
        <div class="kpi-change up"><i class="fa-solid fa-circle-check"></i> All clear</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Row 2: Recent Inquiries + Quick Stats ──────────────── -->
<div class="row g-3">

  <!-- Recent Inquiries -->
  <div class="col-lg-8">
    <div class="admin-table-wrapper">
      <div class="admin-table-header">
        <h5><i class="fa-solid fa-inbox me-2" style="color:var(--gold)"></i>Recent Inquiries</h5>
        <a href="<?= BASE_PATH ?>/admin/inquiries.php" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
          <thead>
            <tr>
              <th>Visitor</th>
              <th>Phone</th>
              <th>Property</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentInquiries)): ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="fa-regular fa-inbox fa-2x mb-2 d-block"></i>
                No inquiries yet.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($recentInquiries as $inq): ?>
            <tr>
              <td>
                <div class="fw-600"><?= htmlspecialchars($inq['visitor_name'], ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td>
                <a href="tel:<?= htmlspecialchars($inq['phone'], ENT_QUOTES, 'UTF-8') ?>"
                   class="text-decoration-none text-dark">
                  <?= htmlspecialchars($inq['phone'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              </td>
              <td>
                <?php if ($inq['property_title']): ?>
                  <span class="text-truncate d-inline-block" style="max-width:160px;"
                        title="<?= htmlspecialchars($inq['property_title'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($inq['property_title'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">General</span>
                <?php endif; ?>
              </td>
              <td><?= dashBadge($inq['status'], $statusColors) ?></td>
              <td style="white-space:nowrap;font-size:0.78rem;">
                <?= htmlspecialchars(date('d M Y', strtotime($inq['created_at'])), ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td>
                <a href="<?= BASE_PATH ?>/admin/inquiries.php?view=<?= (int)$inq['id'] ?>"
                   class="btn btn-outline-success btn-action btn-view">
                  <i class="fa-solid fa-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Quick Stats -->
  <div class="col-lg-4">

    <!-- Listings by City -->
    <div class="card-plain mb-3">
      <h6 class="fw-700 mb-3" style="color:var(--sidebar-bg);">
        <i class="fa-solid fa-map-location-dot me-2" style="color:var(--gold)"></i>Listings by City
      </h6>
      <?php if (empty($byCity)): ?>
        <p class="text-muted mb-0" style="font-size:0.85rem;">No listings published yet.</p>
      <?php else: ?>
        <?php $maxCnt = max(array_column($byCity, 'cnt')); ?>
        <?php foreach ($byCity as $row): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
            <span class="fw-600"><?= htmlspecialchars(ucfirst($row['city']), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="text-muted"><?= (int)$row['cnt'] ?> listings</span>
          </div>
          <div class="progress" style="height:6px;border-radius:3px;">
            <div class="progress-bar" role="progressbar"
                 style="width:<?= $maxCnt > 0 ? round((int)$row['cnt']/$maxCnt*100) : 0 ?>%;background:var(--gold);"
                 aria-valuenow="<?= (int)$row['cnt'] ?>" aria-valuemin="0" aria-valuemax="<?= $maxCnt ?>">
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Most Viewed Property -->
    <?php if ($topProperty): ?>
    <div class="card-plain mb-3">
      <h6 class="fw-700 mb-3" style="color:var(--sidebar-bg);">
        <i class="fa-solid fa-fire me-2" style="color:#dc3545"></i>Top Viewed Property
      </h6>
      <p class="fw-600 mb-1" style="font-size:0.85rem;">
        <?= htmlspecialchars($topProperty['title'], ENT_QUOTES, 'UTF-8') ?>
      </p>
      <p class="text-muted mb-2" style="font-size:0.8rem;">
        <i class="fa-solid fa-eye me-1"></i><?= number_format((int)$topProperty['views_count']) ?> views
      </p>
      <a href="<?= BASE_PATH ?>/admin/listing-form.php?id=<?= (int)$topProperty['id'] ?>"
         class="btn btn-sm btn-outline-secondary">Edit Listing</a>
    </div>
    <?php endif; ?>

    <!-- Inquiry Summary (30d) -->
    <div class="card-plain">
      <h6 class="fw-700 mb-3" style="color:var(--sidebar-bg);">
        <i class="fa-solid fa-chart-pie me-2" style="color:var(--gold)"></i>Inquiries This Month
      </h6>
      <?php if (empty($inquiriesByStatus)): ?>
        <p class="text-muted mb-0" style="font-size:0.85rem;">No inquiries this month.</p>
      <?php else: ?>
        <?php foreach ($inquiriesByStatus as $row): ?>
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size:0.82rem;">
          <span><?= dashBadge($row['status'], $statusColors) ?></span>
          <span class="fw-600"><?= (int)$row['cnt'] ?></span>
        </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between align-items-center pt-2" style="font-size:0.82rem;">
          <span class="text-muted">Total (all time)</span>
          <span class="fw-600"><?= $totalInquiries ?></span>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /col -->
</div><!-- /row -->

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
