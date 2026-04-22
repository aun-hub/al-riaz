<?php
/**
 * Al-Riaz Associates — Audit Log
 * Super Admin only
 */
$pageTitle = 'Audit Log';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole('super_admin');

$db = Database::getInstance();

// ── Ensure audit_log table exists ────────────────────────────
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id    INT UNSIGNED NOT NULL DEFAULT 0,
            admin_name  VARCHAR(200) NOT NULL DEFAULT '',
            action      VARCHAR(100) NOT NULL,
            entity      VARCHAR(100) NOT NULL DEFAULT '',
            entity_id   INT UNSIGNED NOT NULL DEFAULT 0,
            detail      TEXT,
            ip_address  VARCHAR(45) DEFAULT '',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at DESC),
            INDEX idx_admin   (admin_id),
            INDEX idx_action  (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch(Exception $e) {
    error_log('[AuditLog create] ' . $e->getMessage());
}

// ── Filters ──────────────────────────────────────────────────
$filterAction = $_GET['action'] ?? '';
$filterAdmin  = (int)($_GET['admin_id'] ?? 0);
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to']   ?? '';
$search       = trim($_GET['q']    ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 30;

$where = []; $params = [];
if ($filterAction !== '') { $where[] = 'action=?'; $params[] = $filterAction; }
if ($filterAdmin > 0)     { $where[] = 'admin_id=?'; $params[] = $filterAdmin; }
if ($dateFrom !== '')     { $where[] = 'created_at >= ?'; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo   !== '')     { $where[] = 'created_at <= ?'; $params[] = $dateTo   . ' 23:59:59'; }
if ($search !== '')       { $where[] = '(detail LIKE ? OR admin_name LIKE ? OR entity LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_log $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1,(int)ceil($totalRows/$perPage));
    $offset     = ($page-1)*$perPage;

    $logStmt = $db->prepare(
        "SELECT * FROM audit_log $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $logStmt->execute($params);
    $logs = $logStmt->fetchAll();

    // Distinct actions for filter
    $actionsStmt = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action ASC");
    $actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

    // Admins for filter
    $adminsStmt = $db->query("SELECT DISTINCT admin_id, admin_name FROM audit_log ORDER BY admin_name ASC");
    $admins = $adminsStmt->fetchAll();

    $dbOk = true;
} catch(Exception $e) {
    error_log('[Audit] ' . $e->getMessage());
    $logs = []; $actions = []; $admins = []; $totalRows = $totalPages = 0; $dbOk = false;
}

$actionColors = [
    'login'        => 'success',
    'logout'       => 'secondary',
    'create'       => 'primary',
    'update'       => 'info',
    'update_status'=> 'info',
    'delete'       => 'danger',
    'bulk_delete'  => 'danger',
    'invite'       => 'warning',
    'change_role'  => 'warning',
    'toggle_status'=> 'secondary',
];

function auditBadge(string $action, array $colors): string {
    $cls = $colors[$action] ?? 'secondary';
    return '<span class="badge bg-'.$cls.' audit-action-badge">'.htmlspecialchars(str_replace('_',' ',$action),ENT_QUOTES,'UTF-8').'</span>';
}
function paginateUrl(int $p): string { $ps=$_GET;$ps['page']=$p;return '?'.http_build_query($ps); }

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-clock-rotate-left me-2" style="color:var(--gold)"></i>Audit Log</h1>
    <p class="text-muted mb-0 fs-13"><?= number_format($totalRows) ?> total events</p>
  </div>
</div>

<?php if (!$dbOk): ?>
<div class="alert alert-warning">Could not connect to database. Audit log unavailable.</div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-card">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-3">
      <label class="form-label fw-600 fs-12">Search</label>
      <input type="text" name="q" class="form-control form-control-sm"
             placeholder="Details, entity, admin..."
             value="<?= htmlspecialchars($search, ENT_QUOTES,'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Action</label>
      <select name="action" class="form-select form-select-sm">
        <option value="">All Actions</option>
        <?php foreach ($actions as $act): ?>
          <option value="<?= htmlspecialchars($act,ENT_QUOTES,'UTF-8') ?>" <?= $filterAction===$act?'selected':'' ?>>
            <?= htmlspecialchars(ucfirst(str_replace('_',' ',$act)),ENT_QUOTES,'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Admin</label>
      <select name="admin_id" class="form-select form-select-sm">
        <option value="0">All Admins</option>
        <?php foreach ($admins as $adm): ?>
          <option value="<?= (int)$adm['admin_id'] ?>" <?= $filterAdmin===(int)$adm['admin_id']?'selected':'' ?>>
            <?= htmlspecialchars($adm['admin_name'],ENT_QUOTES,'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">From Date</label>
      <input type="date" name="date_from" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateFrom,ENT_QUOTES,'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">To Date</label>
      <input type="date" name="date_to" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateTo,ENT_QUOTES,'UTF-8') ?>">
    </div>
    <div class="col-12 col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a href="/admin/audit.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fa-solid fa-rotate"></i></a>
    </div>
  </form>
</div>

<div class="admin-table-wrapper">
  <div class="table-responsive">
    <table class="table table-admin table-striped table-hover mb-0">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Admin</th>
          <th>Action</th>
          <th>Entity</th>
          <th>Details</th>
          <th>IP Address</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr>
          <td colspan="7" class="text-center py-5 text-muted">
            <i class="fa-solid fa-clock-rotate-left fa-2x mb-2 d-block"></i>
            No audit events found.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="text-muted fs-12"><?= (int)$log['id'] ?></td>
          <td>
            <div class="fw-600" style="font-size:0.85rem;"><?= htmlspecialchars($log['admin_name'], ENT_QUOTES,'UTF-8') ?></div>
            <div class="fs-12 text-muted">ID: <?= (int)$log['admin_id'] ?></div>
          </td>
          <td><?= auditBadge($log['action'], $actionColors) ?></td>
          <td>
            <div class="fw-600 fs-13"><?= htmlspecialchars(ucfirst($log['entity']),ENT_QUOTES,'UTF-8') ?></div>
            <?php if ($log['entity_id'] > 0): ?>
              <div class="fs-12 text-muted">ID: <?= (int)$log['entity_id'] ?></div>
            <?php endif; ?>
          </td>
          <td style="max-width:250px;">
            <span class="fs-12 text-muted d-block text-truncate"
                  title="<?= htmlspecialchars($log['detail']??'',ENT_QUOTES,'UTF-8') ?>">
              <?= htmlspecialchars($log['detail'] ?? '—', ENT_QUOTES,'UTF-8') ?>
            </span>
          </td>
          <td class="fs-12 text-muted"><?= htmlspecialchars($log['ip_address']??'—',ENT_QUOTES,'UTF-8') ?></td>
          <td style="white-space:nowrap;font-size:0.77rem;color:#6c757d;">
            <?= htmlspecialchars(date('d M Y, H:i', strtotime($log['created_at'])),ENT_QUOTES,'UTF-8') ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page-1),ENT_QUOTES,'UTF-8') ?>">&laquo;</a></li><?php endif; ?>
      <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($p),ENT_QUOTES,'UTF-8') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page+1),ENT_QUOTES,'UTF-8') ?>">&raquo;</a></li><?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
