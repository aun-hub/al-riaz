<?php
/**
 * Al-Riaz Associates — Listings Management
 */
$pageTitle = 'Listings';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();

// ── Bulk Action Handler ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    verifyCsrf();
    $action  = $_POST['bulk_action'] ?? '';
    $ids     = array_filter(array_map('intval', (array)($_POST['selected_ids'] ?? [])));

    if (!empty($ids) && !empty($action)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            switch ($action) {
                case 'publish':
                    $db->prepare("UPDATE properties SET is_published=1 WHERE id IN ($placeholders)")->execute($ids);
                    setFlash('success', count($ids) . ' listing(s) published.');
                    break;
                case 'unpublish':
                    $db->prepare("UPDATE properties SET is_published=0 WHERE id IN ($placeholders)")->execute($ids);
                    setFlash('success', count($ids) . ' listing(s) unpublished.');
                    break;
                case 'feature':
                    $db->prepare("UPDATE properties SET is_featured=1 WHERE id IN ($placeholders)")->execute($ids);
                    setFlash('success', count($ids) . ' listing(s) set as featured.');
                    break;
                case 'unfeature':
                    $db->prepare("UPDATE properties SET is_featured=0 WHERE id IN ($placeholders)")->execute($ids);
                    setFlash('success', count($ids) . ' listing(s) removed from featured.');
                    break;
                case 'mark_sold':
                    $db->prepare("UPDATE properties SET is_sold=1 WHERE id IN ($placeholders)")->execute($ids);
                    auditLog('mark_sold', 'properties', 0, 'IDs: ' . implode(',', $ids));
                    setFlash('success', count($ids) . ' listing(s) marked as sold and hidden from the site.');
                    break;
                case 'unmark_sold':
                    $db->prepare("UPDATE properties SET is_sold=0 WHERE id IN ($placeholders)")->execute($ids);
                    auditLog('unmark_sold', 'properties', 0, 'IDs: ' . implode(',', $ids));
                    setFlash('success', count($ids) . ' listing(s) marked as available.');
                    break;
                case 'delete':
                    $db->prepare("DELETE FROM properties WHERE id IN ($placeholders)")->execute($ids);
                    auditLog('bulk_delete', 'properties', 0, 'Deleted IDs: ' . implode(',', $ids));
                    setFlash('success', count($ids) . ' listing(s) deleted.');
                    break;
            }
        } catch (Exception $e) {
            setFlash('danger', 'Bulk action failed: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_PATH . '/admin/listings.php');
    exit;
}

// ── Single Delete ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $delId = (int)$_POST['delete_id'];
    try {
        $db->prepare("DELETE FROM properties WHERE id=?")->execute([$delId]);
        auditLog('delete', 'properties', $delId, 'Deleted listing');
        setFlash('success', 'Listing deleted successfully.');
    } catch(Exception $e) {
        setFlash('danger', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/listings.php');
    exit;
}

// ── Filters ──────────────────────────────────────────────────
$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterPurpose  = $_GET['purpose']  ?? '';
$filterCity     = $_GET['city']     ?? '';
$filterAgent    = $_GET['agent']    ?? '';
$search         = trim($_GET['q']   ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 15;

// Build WHERE clause
$where  = [];
$params = [];

if ($filterStatus !== '') {
    if ($filterStatus === 'sold') {
        $where[] = 'p.is_sold = 1';
    } elseif ($filterStatus === 'published') {
        $where[] = 'p.is_published = 1 AND p.is_sold = 0';
    } elseif ($filterStatus === 'draft') {
        $where[] = 'p.is_published = 0';
    }
}
if ($filterCategory !== '') {
    $where[]  = 'p.category = ?';
    $params[] = $filterCategory;
}
if ($filterPurpose !== '') {
    $where[]  = 'p.purpose = ?';
    $params[] = $filterPurpose;
}
if ($filterCity !== '') {
    // Case-insensitive so a typed city ("Lahore", "lahore", "LAHORE")
    // matches regardless of how it was stored.
    $where[]  = 'LOWER(p.city) = LOWER(?)';
    $params[] = $filterCity;
}
if ($filterAgent !== '') {
    $where[]  = 'p.agent_id = ?';
    $params[] = (int)$filterAgent;
}
if ($search !== '') {
    $where[]  = '(p.title LIKE ? OR p.city LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM properties p $whereSQL");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $offset     = ($page - 1) * $perPage;

    $listStmt = $db->prepare(
        "SELECT p.id, p.title, p.category, p.purpose, p.listing_type, p.city,
                p.price, p.price_on_demand, p.area_value, p.area_unit,
                p.is_published, p.is_featured, p.is_sold, p.created_at,
                u.name AS agent_name, u.avatar_url AS agent_avatar,
                u.phone AS agent_phone, u.email AS agent_email,
                (SELECT pm.url FROM property_media pm WHERE pm.property_id=p.id AND pm.kind='image' ORDER BY pm.sort_order ASC LIMIT 1) AS thumb
         FROM properties p
         LEFT JOIN users u ON p.agent_id = u.id
         $whereSQL
         ORDER BY p.created_at DESC
         LIMIT $perPage OFFSET $offset"
    );
    $listStmt->execute($params);
    $listings = $listStmt->fetchAll();

    // Cities dropdown
    $citiesStmt = $db->query("SELECT DISTINCT city FROM properties ORDER BY city ASC");
    $cities     = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

    // Agents
    $agentsStmt = $db->query("SELECT id, name FROM users WHERE role='agent' OR role='admin' ORDER BY name ASC");
    $agents     = $agentsStmt->fetchAll();

    $dbOk = true;
} catch (Exception $e) {
    error_log('[Listings] ' . $e->getMessage());
    $listings = [];
    $cities   = [];
    $agents   = [];
    $totalRows = $totalPages = 0;
    $dbOk = false;
}

// Status badge
function listingStatus(int $isPublished, int $isSold = 0): string {
    if ($isSold)      return '<span class="badge bg-danger">Sold</span>';
    if ($isPublished) return '<span class="badge bg-success">Published</span>';
    return '<span class="badge bg-secondary">Draft</span>';
}

function paginateUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-house me-2" style="color:var(--gold)"></i>Listings</h1>
    <p class="text-muted mb-0 fs-13"><?= number_format($totalRows) ?> total listings</p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/listing-form.php" class="btn btn-gold">
    <i class="fa-solid fa-plus me-1"></i> New Listing
  </a>
</div>

<!-- Filter Card -->
<div class="filter-card">
  <form method="GET" action="<?= BASE_PATH ?>/admin/listings.php" class="row g-2 align-items-end">
    <div class="col-12 col-md-3">
      <label class="form-label fw-600 fs-12">Search</label>
      <input type="text" name="q" class="form-control form-control-sm"
             placeholder="Title, city..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All Status</option>
        <option value="published" <?= $filterStatus==='published'?'selected':'' ?>>Published</option>
        <option value="draft"     <?= $filterStatus==='draft'?'selected':'' ?>>Draft</option>
        <option value="sold"      <?= $filterStatus==='sold'?'selected':'' ?>>Sold</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Category</label>
      <select name="category" class="form-select form-select-sm">
        <option value="">All Categories</option>
        <option value="residential" <?= $filterCategory==='residential'?'selected':'' ?>>Residential</option>
        <option value="commercial"  <?= $filterCategory==='commercial'?'selected':'' ?>>Commercial</option>
        <option value="plot"        <?= $filterCategory==='plot'?'selected':'' ?>>Plot</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Purpose</label>
      <select name="purpose" class="form-select form-select-sm">
        <option value="">All Purposes</option>
        <option value="sale" <?= $filterPurpose==='sale'?'selected':'' ?>>For Sale</option>
        <option value="rent" <?= $filterPurpose==='rent'?'selected':'' ?>>For Rent</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">City</label>
      <input type="text" name="city" class="form-control form-control-sm"
             list="adminCityOptions" autocomplete="off"
             placeholder="All Cities — type to search"
             value="<?= htmlspecialchars($filterCity, ENT_QUOTES, 'UTF-8') ?>">
      <datalist id="adminCityOptions">
        <?php foreach (getPakistanCities() as $cityName): ?>
          <option value="<?= htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8') ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>
    <div class="col-12 col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-dark w-100">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      <a href="<?= BASE_PATH ?>/admin/listings.php" class="btn btn-sm btn-outline-secondary w-100" title="Reset">
        <i class="fa-solid fa-rotate"></i>
      </a>
    </div>
  </form>
</div>

<!-- Bulk Action + Table -->
<div class="admin-table-wrapper">
  <form method="POST" action="<?= BASE_PATH ?>/admin/listings.php" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

    <div class="admin-table-header">
      <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">All Listings</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
      </div>
      <div class="d-flex gap-2">
        <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
          <option value="">Bulk Actions</option>
          <option value="publish">Publish</option>
          <option value="unpublish">Unpublish</option>
          <option value="feature">Set Featured</option>
          <option value="unfeature">Remove Featured</option>
          <option value="mark_sold">Mark as Sold (hide from site)</option>
          <option value="unmark_sold">Mark as Available</option>
          <option value="delete">Delete Selected</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"
                data-confirm="Apply this bulk action to the selected listings?"
                data-confirm-title="Apply bulk action"
                data-confirm-ok="Apply"
                data-confirm-variant="warning">
          Apply
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-admin table-striped table-hover mb-0">
        <thead>
          <tr>
            <th style="width:40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
            <th style="width:60px;">Photo</th>
            <th>Title</th>
            <th>Type</th>
            <th>City</th>
            <th>Price</th>
            <th>Area</th>
            <th>Status</th>
            <th>Agent</th>
            <th>Date</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($listings)): ?>
          <tr>
            <td colspan="11" class="text-center py-5 text-muted">
              <i class="fa-solid fa-house-circle-xmark fa-2x mb-2 d-block"></i>
              No listings found. <a href="<?= BASE_PATH ?>/admin/listing-form.php" class="text-decoration-none">Create the first one</a>.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($listings as $lst): ?>
          <tr>
            <td>
              <input type="checkbox" name="selected_ids[]" value="<?= (int)$lst['id'] ?>"
                     class="form-check-input row-check">
            </td>
            <td>
              <?php if ($lst['thumb']): ?>
                <img src="<?= htmlspecialchars(mediaUrl($lst['thumb']), ENT_QUOTES, 'UTF-8') ?>"
                     alt="" class="thumb" loading="lazy">
              <?php else: ?>
                <div class="thumb-placeholder"><i class="fa-solid fa-image"></i></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-600" style="max-width:200px;">
                <?= htmlspecialchars($lst['title'], ENT_QUOTES, 'UTF-8') ?>
              </div>
              <?php if ($lst['is_featured']): ?>
                <span class="badge bg-warning text-dark" style="font-size:0.65rem;">
                  <i class="fa-solid fa-star"></i> Featured
                </span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-light text-dark border" style="font-size:0.72rem;">
                <?= htmlspecialchars(ucfirst($lst['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </span>
              <div class="fs-12 text-muted mt-1">
                <?= htmlspecialchars(ucfirst($lst['purpose'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </div>
            </td>
            <td><?= htmlspecialchars(ucfirst($lst['city'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="white-space:nowrap;">
              <?= $lst['price_on_demand'] ? '<em class="text-muted fs-12">On Demand</em>' : htmlspecialchars(getPKRFormatted((float)($lst['price'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td style="white-space:nowrap;">
              <?= htmlspecialchars(getAreaFormatted((float)($lst['area_value'] ?? 0), $lst['area_unit'] ?? 'marla'), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td><?= listingStatus((int)$lst['is_published'], (int)$lst['is_sold']) ?></td>
            <td style="font-size:0.82rem; min-width:170px;">
              <?php if (!empty($lst['agent_name'])): ?>
                <div class="d-flex align-items-center gap-2">
                  <?= renderUserAvatar([
                      'name'       => $lst['agent_name'],
                      'avatar_url' => $lst['agent_avatar'] ?? null,
                  ], 30) ?>
                  <div class="text-truncate" style="min-width:0;">
                    <div class="fw-600 text-truncate"><?= htmlspecialchars($lst['agent_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($lst['agent_phone'])): ?>
                      <div class="text-muted text-truncate" style="font-size:0.72rem;">
                        <i class="fa-solid fa-phone fa-xs me-1"></i><?= htmlspecialchars($lst['agent_phone'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap;font-size:0.77rem;color:#6c757d;">
              <?= htmlspecialchars(date('d M Y', strtotime($lst['created_at'])), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="text-end" style="white-space:nowrap;">
              <a href="<?= BASE_PATH ?>/admin/listing-form.php?id=<?= (int)$lst['id'] ?>"
                 class="btn btn-outline-primary btn-action btn-edit me-1" title="Edit">
                <i class="fa-solid fa-pen"></i>
              </a>
              <a href="<?= BASE_PATH ?>/property/<?= (int)$lst['id'] ?>" target="_blank"
                 class="btn btn-outline-success btn-action btn-view me-1" title="View on site">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
              </a>
              <button type="button"
                      class="btn btn-outline-danger btn-action btn-delete"
                      title="Delete"
                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                      data-id="<?= (int)$lst['id'] ?>"
                      data-title="<?= htmlspecialchars($lst['title'], ENT_QUOTES, 'UTF-8') ?>">
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

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav aria-label="Listings pagination">
      <ul class="pagination pagination-sm mb-0">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?= htmlspecialchars(paginateUrl($page-1), ENT_QUOTES, 'UTF-8') ?>">&laquo;</a>
          </li>
        <?php endif; ?>
        <?php
        $start = max(1, $page-2);
        $end   = min($totalPages, $page+2);
        for ($p = $start; $p <= $end; $p++):
        ?>
          <li class="page-item <?= $p===$page?'active':'' ?>">
            <a class="page-link" href="<?= htmlspecialchars(paginateUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="<?= htmlspecialchars(paginateUrl($page+1), ENT_QUOTES, 'UTF-8') ?>">&raquo;</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>

</div>

<!-- ── Delete Confirm Modal ───────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger" id="deleteModalLabel">
          <i class="fa-solid fa-trash me-2"></i>Delete Listing
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Are you sure you want to permanently delete:</p>
        <p class="fw-700" id="deleteListingTitle" style="color:var(--sidebar-bg);"></p>
        <p class="text-muted fs-13 mb-0">This action cannot be undone. All photos and data will be removed.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" action="<?= BASE_PATH ?>/admin/listings.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="delete_id" id="deleteListingId">
          <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-trash me-1"></i> Yes, Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Populate delete modal
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('deleteListingId').value    = btn.getAttribute('data-id');
  document.getElementById('deleteListingTitle').textContent = btn.getAttribute('data-title');
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
