<?php
/**
 * Al-Riaz Associates — Project Notices list
 */
$pageTitle = 'Project Notices';

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
        $db->prepare("DELETE FROM project_notices WHERE id=?")->execute([$delId]);
        auditLog('delete', 'project_notices', $delId, 'Deleted notice');
        setFlash('success', 'Notice deleted.');
    } catch (Exception $e) {
        setFlash('danger', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/notices.php');
    exit;
}

// ── Bulk Action ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    verifyCsrf();
    $action = $_POST['bulk_action'] ?? '';
    $ids    = array_filter(array_map('intval', (array)($_POST['selected_ids'] ?? [])));
    if (!empty($ids) && $action !== '') {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        try {
            switch ($action) {
                case 'publish':
                    $db->prepare("UPDATE project_notices SET is_published=1 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' notice(s) published.');
                    break;
                case 'unpublish':
                    $db->prepare("UPDATE project_notices SET is_published=0 WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' notice(s) unpublished.');
                    break;
                case 'delete':
                    $db->prepare("DELETE FROM project_notices WHERE id IN ($ph)")->execute($ids);
                    setFlash('success', count($ids) . ' notice(s) deleted.');
                    break;
            }
        } catch (Exception $e) {
            setFlash('danger', 'Bulk action failed: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_PATH . '/admin/notices.php');
    exit;
}

// ── List ─────────────────────────────────────────────────────
try {
    $notices = $db->query("
        SELECT n.*, p.name AS project_name, p.slug AS project_slug
        FROM   project_notices n
        LEFT JOIN projects p ON p.id = n.project_id
        ORDER BY n.is_published DESC, n.starts_at IS NULL, n.starts_at DESC, n.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $notices = [];
    setFlash('danger', 'Notices table missing — run latest migration.');
}

$now = date('Y-m-d H:i:s');
$noticeStatus = function (array $n) use ($now): array {
    if (!$n['is_published']) return ['Draft', 'bg-secondary'];
    if (!empty($n['starts_at']) && $n['starts_at'] > $now) return ['Scheduled', 'bg-info text-dark'];
    if (!empty($n['ends_at'])   && $n['ends_at']   < $now) return ['Expired',   'bg-secondary'];
    return ['Live', 'bg-success'];
};
$severityBadge = ['info' => 'bg-info text-dark', 'warning' => 'bg-warning text-dark', 'critical' => 'bg-danger'];

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-bullhorn me-2" style="color:var(--gold)"></i>Project Notices</h1>
    <p class="text-muted mb-0 fs-13"><?= count($notices) ?> total · published notices appear on the public <a href="<?= BASE_PATH ?>/notices.php" target="_blank" rel="noopener">/notices</a> page</p>
  </div>
  <a href="<?= BASE_PATH ?>/admin/notice-form.php" class="btn btn-gold">
    <i class="fa-solid fa-plus me-1"></i> New Notice
  </a>
</div>

<div class="admin-table-wrapper">
  <form method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="admin-table-header">
      <h5><i class="fa-solid fa-bullhorn me-2" style="color:var(--gold)"></i>All Notices</h5>
      <div class="d-flex gap-2">
        <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
          <option value="">Bulk Actions</option>
          <option value="publish">Publish</option>
          <option value="unpublish">Unpublish</option>
          <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"
                data-confirm="Apply this bulk action to the selected notices?"
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
            <th>Title</th>
            <th>Project</th>
            <th>Severity</th>
            <th>Window</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($notices)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="fa-solid fa-bullhorn fa-2x mb-2 d-block"></i>
              No notices yet. <a href="<?= BASE_PATH ?>/admin/notice-form.php">Add one</a>.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($notices as $n):
              [$statusLabel, $statusClass] = $noticeStatus($n);
              $sevClass = $severityBadge[$n['severity']] ?? 'bg-secondary';
          ?>
          <tr>
            <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$n['id'] ?>" class="form-check-input row-check"></td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php if (!empty($n['source_url'])): ?>
              <a href="<?= htmlspecialchars($n['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="fs-12 text-muted text-decoration-none">
                <i class="fa-solid fa-arrow-up-right-from-square fa-2xs me-1"></i>source
              </a>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($n['project_name'])): ?>
                <?= htmlspecialchars($n['project_name'], ENT_QUOTES, 'UTF-8') ?>
              <?php else: ?>
                <span class="text-muted fs-13">— general —</span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= $sevClass ?>"><?= htmlspecialchars(ucfirst($n['severity']), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="fs-13">
              <?php
                $sw = $n['starts_at'] ? date('M j, Y', strtotime($n['starts_at'])) : '—';
                $ew = $n['ends_at']   ? date('M j, Y', strtotime($n['ends_at']))   : '—';
                echo htmlspecialchars($sw . ' → ' . $ew);
              ?>
            </td>
            <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
            <td class="text-end" style="white-space:nowrap;">
              <a href="<?= BASE_PATH ?>/admin/notice-form.php?id=<?= (int)$n['id'] ?>" class="btn btn-outline-primary btn-action btn-edit me-1" title="Edit">
                <i class="fa-solid fa-pen"></i>
              </a>
              <button type="button" class="btn btn-outline-danger btn-action btn-delete"
                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                      data-id="<?= (int)$n['id'] ?>" data-name="<?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?>">
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
        <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete Notice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Delete notice: <strong id="delName"></strong>?</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="delete_id" id="delId">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('delId').value = btn.getAttribute('data-id');
  document.getElementById('delName').textContent = btn.getAttribute('data-name');
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
