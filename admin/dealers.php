<?php
/**
 * Al-Riaz Associates — Authorized Dealers Management
 */
$pageTitle = 'Authorized Dealers';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = Database::getInstance();

// ── Single Delete ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $delId = (int)$_POST['delete_id'];
    try {
        $stmt = $db->prepare("SELECT logo_url FROM authorized_dealers WHERE id=?");
        $stmt->execute([$delId]);
        $logo = (string)$stmt->fetchColumn();

        $db->prepare("DELETE FROM authorized_dealers WHERE id=?")->execute([$delId]);

        if ($logo && str_starts_with($logo, '/assets/uploads/dealers/')) {
            $fsPath = __DIR__ . '/..' . $logo;
            if (is_file($fsPath)) @unlink($fsPath);
        }

        auditLog('delete', 'authorized_dealers', $delId, 'Deleted dealer');
        setFlash('success', 'Dealer deleted successfully.');
    } catch (Exception $e) {
        setFlash('danger', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/dealers.php');
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
            switch ($action) {
                case 'publish':
                    $db->prepare("UPDATE authorized_dealers SET is_published=1 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' dealer(s) published.');
                    break;
                case 'unpublish':
                    $db->prepare("UPDATE authorized_dealers SET is_published=0 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' dealer(s) unpublished.');
                    break;
                case 'delete':
                    $logoStmt = $db->prepare("SELECT logo_url FROM authorized_dealers WHERE id IN ($ph)");
                    $logoStmt->execute($ids);
                    $logos = $logoStmt->fetchAll(PDO::FETCH_COLUMN);
                    $db->prepare("DELETE FROM authorized_dealers WHERE id IN ($ph)")->execute($ids);
                    foreach ($logos as $logo) {
                        if ($logo && str_starts_with($logo, '/assets/uploads/dealers/')) {
                            $fsPath = __DIR__ . '/..' . $logo;
                            if (is_file($fsPath)) @unlink($fsPath);
                        }
                    }
                    setFlash('success', count($ids) . ' dealer(s) deleted.');
                    break;
            }
        } catch (Exception $e) {
            setFlash('danger', 'Bulk action failed: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_PATH . '/admin/dealers.php');
    exit;
}

// ── Filters ──────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$where  = [];
$params = [];
if ($filterStatus === 'published') { $where[] = 'is_published=1'; }
elseif ($filterStatus === 'draft') { $where[] = 'is_published=0'; }
if ($search !== '') { $where[] = 'name LIKE ?'; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM authorized_dealers $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $offset     = ($page - 1) * $perPage;

    $listStmt = $db->prepare("SELECT * FROM authorized_dealers $whereSQL ORDER BY sort_order ASC, name ASC LIMIT $perPage OFFSET $offset");
    $listStmt->execute($params);
    $dealers = $listStmt->fetchAll();
} catch (Exception $e) {
    error_log('[Dealers] ' . $e->getMessage());
    $dealers = [];
    $totalRows = $totalPages = 0;
    setFlash('danger', 'Dealers table not found. Run migration 006 first.');
}

function paginateUrl(int $p): string { $params = $_GET; $params['page'] = $p; return '?' . http_build_query($params); }

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-handshake me-2" style="color:var(--gold)"></i>Authorized Dealers</h1>
    <p class="text-muted mb-0 fs-13"><?= number_format($totalRows) ?> total dealers</p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/dealer-form.php" class="btn btn-gold">
    <i class="fa-solid fa-plus me-1"></i> New Dealer
  </a>
</div>

<!-- Filter -->
<div class="filter-card">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-6">
      <label class="form-label fw-600 fs-12">Search</label>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Dealer name..."
             value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label fw-600 fs-12">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All Status</option>
        <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12 col-md-3 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a href="<?= BASE_PATH ?>/admin/dealers.php" class="btn btn-sm btn-outline-secondary w-100" title="Reset"><i class="fa-solid fa-rotate"></i></a>
    </div>
  </form>
</div>

<div class="admin-table-wrapper">
  <form method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="admin-table-header">
      <h5><i class="fa-solid fa-handshake me-2" style="color:var(--gold)"></i>All Dealers</h5>
      <div class="d-flex gap-2">
        <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
          <option value="">Bulk Actions</option>
          <option value="publish">Publish</option>
          <option value="unpublish">Unpublish</option>
          <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"
                data-confirm="Apply this bulk action to the selected dealers?"
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
            <th>Logo</th>
            <th>Name</th>
            <th>Website</th>
            <th>Sort</th>
            <th>Published</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($dealers)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="fa-solid fa-handshake-slash fa-2x mb-2 d-block"></i>
              No dealers found. <a href="<?= BASE_PATH ?>/admin/dealer-form.php">Add one</a>.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($dealers as $d): ?>
          <tr>
            <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$d['id'] ?>" class="form-check-input row-check"></td>
            <td>
              <?php if (!empty($d['logo_url'])): ?>
                <img src="<?= htmlspecialchars(mediaUrl($d['logo_url']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="thumb" style="object-fit:contain;background:#f8f9fa;">
              <?php else: ?>
                <div class="thumb-placeholder"><i class="fa-solid fa-handshake"></i></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td>
              <?php if (!empty($d['website_url'])): ?>
                <a href="<?= htmlspecialchars($d['website_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="fs-13">
                  <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Visit
                </a>
              <?php else: ?>
                <span class="text-muted fs-12">—</span>
              <?php endif; ?>
            </td>
            <td><?= (int)$d['sort_order'] ?></td>
            <td>
              <?= (int)$d['is_published']
                ? '<span class="badge bg-success">Live</span>'
                : '<span class="badge bg-secondary">Draft</span>' ?>
            </td>
            <td class="text-end" style="white-space:nowrap;">
              <a href="<?= BASE_PATH ?>/admin/dealer-form.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline-primary btn-action btn-edit me-1" title="Edit">
                <i class="fa-solid fa-pen"></i>
              </a>
              <button type="button" class="btn btn-outline-danger btn-action btn-delete"
                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                      data-id="<?= (int)$d['id'] ?>" data-name="<?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>">
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
      <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>">&laquo;</a></li><?php endif; ?>
      <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>">&raquo;</a></li><?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete Dealer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Delete dealer: <strong id="delDealerName"></strong>?</p>
        <p class="text-muted fs-13">The logo file will also be removed. This cannot be undone.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="delete_id" id="delDealerId">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('delDealerId').value = btn.getAttribute('data-id');
  document.getElementById('delDealerName').textContent = btn.getAttribute('data-name');
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
