<?php
/**
 * Al-Riaz Associates — Reviews Moderation
 *
 * Admins and super admins moderate client reviews submitted from the
 * public site. Agents do not have access.
 */
$pageTitle = 'Reviews';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = Database::getInstance();

// ── Handle POST actions ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action   = $_POST['action']    ?? '';
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $myId     = (int)($_SESSION['admin_id'] ?? 0);

    if ($reviewId <= 0) {
        setFlash('danger', 'Invalid review id.');
        header('Location: ' . BASE_PATH . '/admin/reviews.php');
        exit;
    }

    try {
        if ($action === 'approve') {
            $db->prepare("UPDATE reviews SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
               ->execute([$myId, $reviewId]);
            auditLog('approve', 'reviews', $reviewId, 'Approved review #' . $reviewId);
            setFlash('success', 'Review approved and published.');
        } elseif ($action === 'reject') {
            $db->prepare("UPDATE reviews SET status='rejected', is_featured=0, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
               ->execute([$myId, $reviewId]);
            auditLog('reject', 'reviews', $reviewId, 'Rejected review #' . $reviewId);
            setFlash('success', 'Review rejected.');
        } elseif ($action === 'unpublish') {
            $db->prepare("UPDATE reviews SET status='pending', is_featured=0, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
               ->execute([$myId, $reviewId]);
            auditLog('unpublish', 'reviews', $reviewId, 'Moved review #' . $reviewId . ' back to pending');
            setFlash('success', 'Review moved back to pending.');
        } elseif ($action === 'toggle_featured') {
            // Featuring requires the review to be approved.
            $row = $db->prepare('SELECT status, is_featured FROM reviews WHERE id=?');
            $row->execute([$reviewId]);
            $r = $row->fetch();
            if ($r && $r['status'] === 'approved') {
                $newVal = (int)$r['is_featured'] === 1 ? 0 : 1;
                $db->prepare('UPDATE reviews SET is_featured=? WHERE id=?')->execute([$newVal, $reviewId]);
                auditLog('feature', 'reviews', $reviewId, ($newVal ? 'Featured' : 'Unfeatured') . ' review #' . $reviewId);
                setFlash('success', $newVal ? 'Review featured on the site.' : 'Review unfeatured.');
            } else {
                setFlash('warning', 'Only approved reviews can be featured.');
            }
        } elseif ($action === 'delete') {
            if (!hasRole('super_admin')) {
                setFlash('danger', 'Only a Super Admin can delete reviews.');
            } else {
                $db->prepare('DELETE FROM reviews WHERE id=?')->execute([$reviewId]);
                auditLog('delete', 'reviews', $reviewId, 'Deleted review #' . $reviewId);
                setFlash('success', 'Review deleted.');
            }
        } else {
            setFlash('danger', 'Unknown action.');
        }
    } catch (Exception $e) {
        setFlash('danger', 'Error: ' . $e->getMessage());
    }

    header('Location: ' . BASE_PATH . '/admin/reviews.php' . (!empty($_POST['return_status']) ? '?status=' . urlencode($_POST['return_status']) : ''));
    exit;
}

// ── Filters & Listing ───────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$validStatuses = ['pending', 'approved', 'rejected'];

$where  = [];
$params = [];
if (in_array($filterStatus, $validStatuses, true)) {
    $where[] = 'status = ?';
    $params[] = $filterStatus;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$reviews = [];
$counts  = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
try {
    $stmt = $db->prepare("SELECT r.*, u.name AS reviewer_name
                          FROM reviews r
                          LEFT JOIN users u ON r.reviewed_by = u.id
                          $whereSql
                          ORDER BY r.created_at DESC");
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    $cStmt = $db->query("SELECT status, COUNT(*) AS c FROM reviews GROUP BY status");
    foreach ($cStmt->fetchAll() as $c) {
        $counts[$c['status']] = (int)$c['c'];
    }
    $counts['all'] = $counts['pending'] + $counts['approved'] + $counts['rejected'];
} catch (Exception $e) {
    setFlash('danger', 'Could not load reviews: ' . $e->getMessage());
}

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';

function renderStars(int $rating): string {
    $rating = max(0, min(5, $rating));
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= $rating ? 'fa-solid fa-star' : 'fa-regular fa-star';
        $out .= '<i class="' . $cls . '" style="color:var(--gold);"></i>';
    }
    return $out;
}
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-star me-2" style="color:var(--gold)"></i>Client Reviews</h1>
    <p class="text-muted mb-0 fs-13">
      <?= (int)$counts['all'] ?> total &middot;
      <strong><?= (int)$counts['pending'] ?></strong> pending &middot;
      <?= (int)$counts['approved'] ?> approved &middot;
      <?= (int)$counts['rejected'] ?> rejected
    </p>
  </div>
</div>

<?php if ($flash = getFlash()): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
  <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <?php
    $tabs = [
        ''         => 'All ('         . $counts['all']      . ')',
        'pending'  => 'Pending ('     . $counts['pending']  . ')',
        'approved' => 'Approved ('    . $counts['approved'] . ')',
        'rejected' => 'Rejected ('    . $counts['rejected'] . ')',
    ];
    foreach ($tabs as $val => $label):
        $active = ($filterStatus === $val);
  ?>
  <a href="<?= BASE_PATH ?>/admin/reviews.php<?= $val !== '' ? '?status=' . urlencode($val) : '' ?>"
     class="btn btn-sm <?= $active ? 'btn-dark' : 'btn-outline-secondary' ?>">
    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="admin-table-wrapper">
  <div class="table-responsive">
    <table class="table table-admin table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>Reviewer</th>
          <th>Rating</th>
          <th>Review</th>
          <th>Status</th>
          <th>Submitted</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reviews)): ?>
        <tr>
          <td colspan="6" class="text-center py-5 text-muted">
            <i class="fa-regular fa-comment-dots fa-2x mb-2 d-block"></i>
            No reviews <?= $filterStatus !== '' ? 'with this status' : 'yet' ?>.
          </td>
        </tr>
        <?php else: foreach ($reviews as $r): ?>
        <tr>
          <td style="min-width:150px;">
            <div class="fw-600" style="font-size:0.88rem;"><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php if (!empty($r['email'])): ?>
              <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;"><?= renderStars((int)$r['rating']) ?></td>
          <td>
            <?php if (!empty($r['title'])): ?>
              <div class="fw-600" style="font-size:0.88rem;"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div style="font-size:0.83rem; max-width:520px; white-space:pre-line;">
              <?= htmlspecialchars($r['body'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </td>
          <td>
            <?php if ($r['status'] === 'approved'): ?>
              <span class="badge bg-success">Approved</span>
              <?php if ((int)$r['is_featured'] === 1): ?>
                <span class="badge bg-warning text-dark mt-1">
                  <i class="fa-solid fa-star"></i> Featured
                </span>
              <?php endif; ?>
            <?php elseif ($r['status'] === 'rejected'): ?>
              <span class="badge bg-secondary">Rejected</span>
            <?php else: ?>
              <span class="badge bg-info text-dark">Pending</span>
            <?php endif; ?>
          </td>
          <td style="font-size:0.77rem; color:#6c757d; white-space:nowrap;">
            <?= htmlspecialchars(date('d M Y', strtotime($r['created_at'])), ENT_QUOTES, 'UTF-8') ?>
            <div><?= htmlspecialchars(date('H:i', strtotime($r['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
          </td>
          <td class="text-end">
            <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
              <?php if ($r['status'] !== 'approved'): ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve">
                  <i class="fa-solid fa-check"></i>
                </button>
              </form>
              <?php endif; ?>

              <?php if ($r['status'] === 'approved'): ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="toggle_featured">
                <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit"
                        class="btn btn-sm <?= (int)$r['is_featured'] === 1 ? 'btn-warning' : 'btn-outline-warning' ?>"
                        title="<?= (int)$r['is_featured'] === 1 ? 'Unfeature' : 'Feature' ?>">
                  <i class="fa-solid fa-star"></i>
                </button>
              </form>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="unpublish">
                <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary"
                        data-confirm="Move this review back to Pending?"
                        data-confirm-title="Unpublish review"
                        data-confirm-ok="Unpublish"
                        data-confirm-variant="warning"
                        title="Unpublish">
                  <i class="fa-solid fa-eye-slash"></i>
                </button>
              </form>
              <?php endif; ?>

              <?php if ($r['status'] !== 'rejected'): ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-outline-warning"
                        data-confirm="Reject this review? It will be hidden from the public site."
                        data-confirm-title="Reject review"
                        data-confirm-ok="Reject"
                        data-confirm-variant="warning"
                        title="Reject">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </form>
              <?php endif; ?>

              <?php if (hasRole('super_admin')): ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Permanently delete this review? This cannot be undone."
                        data-confirm-title="Delete review"
                        data-confirm-ok="Delete"
                        data-confirm-variant="danger"
                        title="Delete">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
