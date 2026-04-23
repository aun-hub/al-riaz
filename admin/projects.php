<?php
/**
 * Al-Riaz Associates — Projects Management
 */
$pageTitle = 'Projects';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();

// ── Single Delete ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $delId = (int)$_POST['delete_id'];
    try {
        $db->prepare("DELETE FROM projects WHERE id=?")->execute([$delId]);
        auditLog('delete', 'projects', $delId, 'Deleted project');
        setFlash('success', 'Project deleted successfully.');
    } catch(Exception $e) {
        setFlash('danger', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/projects.php');
    exit;
}

// ── Bulk Action ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    verifyCsrf();
    $action = $_POST['bulk_action'] ?? '';
    $ids    = array_filter(array_map('intval', (array)($_POST['selected_ids'] ?? [])));
    if (!empty($ids) && !empty($action)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        try {
            switch($action) {
                case 'publish':   $db->prepare("UPDATE projects SET is_published=1 WHERE id IN ($ph)")->execute($ids); setFlash('success', count($ids).' project(s) published.'); break;
                case 'unpublish': $db->prepare("UPDATE projects SET is_published=0 WHERE id IN ($ph)")->execute($ids); setFlash('success', count($ids).' project(s) unpublished.'); break;
                case 'feature':   $db->prepare("UPDATE projects SET is_featured=1 WHERE id IN ($ph)")->execute($ids); setFlash('success', count($ids).' project(s) featured.'); break;
                case 'delete':    $db->prepare("DELETE FROM projects WHERE id IN ($ph)")->execute($ids); setFlash('success', count($ids).' project(s) deleted.'); break;
            }
        } catch(Exception $e) { setFlash('danger', 'Bulk action failed: ' . $e->getMessage()); }
    }
    header('Location: ' . BASE_PATH . '/admin/projects.php');
    exit;
}

// ── Filters ──────────────────────────────────────────────────
$filterCity   = $_GET['city']   ?? '';
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;

$where = []; $params = [];
if ($filterCity !== '') { $where[] = 'city=?'; $params[] = $filterCity; }
if ($filterStatus !== '') {
    if ($filterStatus === 'published')   { $where[] = 'is_published=1'; }
    elseif ($filterStatus === 'draft')   { $where[] = 'is_published=0'; }
}
if ($search !== '') { $where[] = '(name LIKE ? OR developer LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $total = (int)$db->prepare("SELECT COUNT(*) FROM projects $whereSQL")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM projects $whereSQL")->execute($params) : 0;
    $countStmt = $db->prepare("SELECT COUNT(*) FROM projects $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $offset     = ($page-1) * $perPage;

    $listStmt = $db->prepare("SELECT * FROM projects $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $listStmt->execute($params);
    $projects = $listStmt->fetchAll();

    $citiesStmt = $db->query("SELECT DISTINCT city FROM projects ORDER BY city ASC");
    $cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $dbOk = true;
} catch(Exception $e) {
    error_log('[Projects] ' . $e->getMessage());
    $projects = []; $cities = []; $totalRows = $totalPages = 0; $dbOk = false;
}

function paginateUrl(int $p): string { $params = $_GET; $params['page'] = $p; return '?' . http_build_query($params); }

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-building me-2" style="color:var(--gold)"></i>Projects</h1>
    <p class="text-muted mb-0 fs-13"><?= number_format($totalRows) ?> total projects</p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/project-form.php" class="btn btn-gold">
    <i class="fa-solid fa-plus me-1"></i> New Project
  </a>
</div>

<!-- Filter -->
<div class="filter-card">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-4">
      <label class="form-label fw-600 fs-12">Search</label>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Name, developer..."
             value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label fw-600 fs-12">City</label>
      <select name="city" class="form-select form-select-sm">
        <option value="">All Cities</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $filterCity===$c?'selected':'' ?>><?= htmlspecialchars(ucfirst($c), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label fw-600 fs-12">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All Status</option>
        <option value="published" <?= $filterStatus==='published'?'selected':'' ?>>Published</option>
        <option value="draft" <?= $filterStatus==='draft'?'selected':'' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a href="<?= BASE_PATH ?>/admin/projects.php" class="btn btn-sm btn-outline-secondary w-100" title="Reset"><i class="fa-solid fa-rotate"></i></a>
    </div>
  </form>
</div>

<div class="admin-table-wrapper">
  <form method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="admin-table-header">
      <h5><i class="fa-solid fa-building me-2" style="color:var(--gold)"></i>All Projects</h5>
      <div class="d-flex gap-2">
        <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
          <option value="">Bulk Actions</option>
          <option value="publish">Publish</option>
          <option value="unpublish">Unpublish</option>
          <option value="feature">Set Featured</option>
          <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"
                data-confirm="Apply this bulk action to the selected projects?"
                data-confirm-title="Apply bulk action"
                data-confirm-ok="Apply"
                data-confirm-variant="warning">Apply</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-admin table-striped table-hover mb-0">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
            <th>Thumbnail</th>
            <th>Name</th>
            <th>Developer</th>
            <th>City</th>
            <th>Status</th>
            <th>NOC</th>
            <th>Featured</th>
            <th>Published</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($projects)): ?>
          <tr>
            <td colspan="10" class="text-center py-5 text-muted">
              <i class="fa-solid fa-building-circle-xmark fa-2x mb-2 d-block"></i>
              No projects found. <a href="<?= BASE_PATH ?>/admin/project-form.php">Create one</a>.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($projects as $proj): ?>
          <tr>
            <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$proj['id'] ?>" class="form-check-input row-check"></td>
            <td>
              <?php $heroImg = $proj['hero_image_url'] ?? $proj['hero_image'] ?? ''; ?>
              <?php if ($heroImg): ?>
                <img src="<?= htmlspecialchars(mediaUrl($heroImg), ENT_QUOTES, 'UTF-8') ?>" alt="" class="thumb">
              <?php else: ?>
                <div class="thumb-placeholder"><i class="fa-solid fa-building"></i></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($proj['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="fs-12 text-muted"><?= htmlspecialchars($proj['area_locality'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= htmlspecialchars($proj['developer'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(ucfirst($proj['city'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php $statusMap=['upcoming'=>['info','Upcoming'],'under_development'=>['warning','Under Dev'],'ready'=>['success','Ready'],'possession'=>['primary','Possession']];
              [$cls,$lbl] = $statusMap[$proj['status']??''] ?? ['secondary', ucfirst($proj['status']??'—')]; ?>
              <span class="badge bg-<?= $cls ?> text-<?= $cls==='warning'?'dark':'white' ?>"><?= $lbl ?></span>
            </td>
            <td>
              <?php $nocMap=['approved'=>['success','Approved'],'pending'=>['warning','Pending'],'not_required'=>['secondary','N/A']];
              [$nc,$nl] = $nocMap[$proj['noc_status']??''] ?? ['secondary','—']; ?>
              <span class="badge bg-<?= $nc ?>"><?= $nl ?></span>
            </td>
            <td>
              <?php if ($proj['is_featured']): ?>
                <span class="badge bg-warning text-dark"><i class="fa-solid fa-star"></i> Yes</span>
              <?php else: ?>
                <span class="text-muted fs-12">No</span>
              <?php endif; ?>
            </td>
            <td>
              <?= (int)$proj['is_published']
                ? '<span class="badge bg-success">Live</span>'
                : '<span class="badge bg-secondary">Draft</span>' ?>
            </td>
            <td class="text-end" style="white-space:nowrap;">
              <a href="<?= BASE_PATH ?>/admin/project-form.php?id=<?= (int)$proj['id'] ?>" class="btn btn-outline-primary btn-action btn-edit me-1" title="Edit">
                <i class="fa-solid fa-pen"></i>
              </a>
              <button type="button" class="btn btn-outline-danger btn-action btn-delete"
                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                      data-id="<?= (int)$proj['id'] ?>" data-name="<?= htmlspecialchars($proj['name'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>

  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page-1), ENT_QUOTES, 'UTF-8') ?>">&laquo;</a></li><?php endif; ?>
      <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page+1), ENT_QUOTES, 'UTF-8') ?>">&raquo;</a></li><?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Delete project: <strong id="delProjName"></strong>?</p>
        <p class="text-muted fs-13">All related data will be removed. This cannot be undone.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="delete_id" id="delProjId">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('delProjId').value = btn.getAttribute('data-id');
  document.getElementById('delProjName').textContent = btn.getAttribute('data-name');
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
