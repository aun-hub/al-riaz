<?php
/**
 * Al-Riaz Associates — Features & Amenities Management
 */
$pageTitle = 'Features & Amenities';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = Database::getInstance();

// ── Inline toggle: public-site visibility ────────────────────
// Hit from both the per-row checkbox (AJAX, returns JSON) and any non-JS
// fallback (form submit, regular redirect). Updating one row at a time
// keeps the JS dead simple.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_show_on_site'])) {
    verifyCsrf();
    $togId = (int)$_POST['toggle_show_on_site'];
    $val   = !empty($_POST['value']) ? 1 : 0;
    $ok = false; $err = '';
    try {
        $db->prepare("UPDATE property_features SET show_on_site = ? WHERE id = ?")
           ->execute([$val, $togId]);
        auditLog('update', 'property_features', $togId, 'show_on_site → ' . ($val ? 'on' : 'off'));
        $ok = true;
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'value' => $val, 'error' => $err]);
        exit;
    }
    setFlash($ok ? 'success' : 'danger', $ok ? 'Visibility updated.' : 'Update failed: ' . $err);
    header('Location: ' . BASE_PATH . '/admin/features.php');
    exit;
}

// ── Single Delete ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $delId = (int)$_POST['delete_id'];
    try {
        $db->prepare("DELETE FROM property_features WHERE id=?")->execute([$delId]);
        auditLog('delete', 'property_features', $delId, 'Deleted feature');
        setFlash('success', 'Feature deleted successfully.');
    } catch (Exception $e) {
        setFlash('danger', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/features.php');
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
                case 'activate':
                    $db->prepare("UPDATE property_features SET is_active=1 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' feature(s) activated.');
                    break;
                case 'deactivate':
                    $db->prepare("UPDATE property_features SET is_active=0 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' feature(s) deactivated.');
                    break;
                case 'show_on_site':
                    $db->prepare("UPDATE property_features SET show_on_site=1 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' feature(s) now visible on the public site.');
                    break;
                case 'hide_on_site':
                    $db->prepare("UPDATE property_features SET show_on_site=0 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' feature(s) hidden from the public site.');
                    break;
                case 'delete':
                    $db->prepare("DELETE FROM property_features WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' feature(s) deleted.');
                    break;
            }
        } catch (Exception $e) {
            setFlash('danger', 'Bulk action failed: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_PATH . '/admin/features.php');
    exit;
}

// ── List ─────────────────────────────────────────────────────
try {
    $features = $db->query("SELECT * FROM property_features ORDER BY sort_order ASC, label ASC")->fetchAll();
} catch (Exception $e) {
    $features = [];
    setFlash('danger', 'Features table not found. Run migration 007 first.');
}

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-list-check me-2" style="color:var(--gold)"></i>Features &amp; Amenities</h1>
    <p class="text-muted mb-0 fs-13"><?= count($features) ?> total features</p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/feature-form.php" class="btn btn-gold">
    <i class="fa-solid fa-plus me-1"></i> New Feature
  </a>
</div>

<div class="admin-table-wrapper">
  <form method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="admin-table-header">
      <h5><i class="fa-solid fa-list-check me-2" style="color:var(--gold)"></i>All Features</h5>
      <div class="d-flex gap-2">
        <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
          <option value="">Bulk Actions</option>
          <option value="activate">Activate (admin picker)</option>
          <option value="deactivate">Deactivate (admin picker)</option>
          <option value="show_on_site">Show on public site</option>
          <option value="hide_on_site">Hide from public site</option>
          <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"
                data-confirm="Apply this bulk action to the selected features?"
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
            <th style="width:60px;">Icon</th>
            <th>Label</th>
            <th>Slug</th>
            <th>Sort</th>
            <th>Status</th>
            <th title="If unchecked, this feature is hidden from the public listing detail page.">Show on Site</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($features)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="fa-solid fa-list-ul fa-2x mb-2 d-block"></i>
              No features yet. <a href="<?= BASE_PATH ?>/admin/feature-form.php">Add one</a>.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($features as $f): ?>
          <tr>
            <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$f['id'] ?>" class="form-check-input row-check"></td>
            <td><i class="fa-solid <?= htmlspecialchars($f['icon'], ENT_QUOTES, 'UTF-8') ?>" style="font-size:1.1rem;color:var(--navy-700);"></i></td>
            <td><div class="fw-600"><?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></div></td>
            <td><code class="fs-12 text-muted"><?= htmlspecialchars($f['slug'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= (int)$f['sort_order'] ?></td>
            <td>
              <?= (int)$f['is_active']
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>' ?>
            </td>
            <td>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input show-on-site-toggle" type="checkbox"
                       role="switch"
                       data-id="<?= (int)$f['id'] ?>"
                       <?= !empty($f['show_on_site']) ? 'checked' : '' ?>
                       aria-label="Show <?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?> on the public site">
              </div>
            </td>
            <td class="text-end" style="white-space:nowrap;">
              <a href="<?= BASE_PATH ?>/admin/feature-form.php?id=<?= (int)$f['id'] ?>" class="btn btn-outline-primary btn-action btn-edit me-1" title="Edit">
                <i class="fa-solid fa-pen"></i>
              </a>
              <button type="button" class="btn btn-outline-danger btn-action btn-delete"
                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                      data-id="<?= (int)$f['id'] ?>" data-name="<?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?>">
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
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete Feature</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Delete feature: <strong id="delFeatName"></strong>?</p>
        <p class="text-muted fs-13">Existing listings that reference this feature will keep the slug in their data, but it will no longer appear as a checkbox option for new listings.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="delete_id" id="delFeatId">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('delFeatId').value = btn.getAttribute('data-id');
  document.getElementById('delFeatName').textContent = btn.getAttribute('data-name');
});

// Inline "Show on Site" toggle — POSTs to this same page, reverts the
// checkbox if the server reports an error.
(function () {
  var csrf = document.querySelector('#bulkForm input[name="csrf_token"]').value;
  document.querySelectorAll('.show-on-site-toggle').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var id   = this.getAttribute('data-id');
      var prev = !this.checked; // pre-change state, in case we need to revert
      var self = this;
      self.disabled = true;
      var body = new URLSearchParams();
      body.set('csrf_token', csrf);
      body.set('toggle_show_on_site', id);
      body.set('value', self.checked ? '1' : '0');
      body.set('ajax', '1');
      fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin',
      })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || !j.ok) {
          self.checked = prev;
          alert('Could not update visibility: ' + (j && j.error ? j.error : 'unknown error'));
        }
      })
      .catch(function () {
        self.checked = prev;
        alert('Network error. Visibility not saved.');
      })
      .finally(function () { self.disabled = false; });
    });
  });
})();
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
