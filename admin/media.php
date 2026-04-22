<?php
/**
 * Al-Riaz Associates — Media Library
 */
$pageTitle = 'Media Library';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();

// ── Delete Media ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media_id'])) {
    verifyCsrf();
    $mediaId  = (int)$_POST['delete_media_id'];
    $mediaType= $_POST['media_source'] ?? 'property'; // 'property' or 'project'

    try {
        if ($mediaType === 'project') {
            $stmt = $db->prepare('SELECT file_path FROM project_media WHERE id=?');
            $stmt->execute([$mediaId]);
            $row = $stmt->fetch();
            if ($row) {
                $fp = __DIR__ . '/../assets/uploads/' . $row['file_path'];
                if (file_exists($fp)) @unlink($fp);
                $db->prepare('DELETE FROM project_media WHERE id=?')->execute([$mediaId]);
            }
        } else {
            $stmt = $db->prepare('SELECT url FROM property_media WHERE id=?');
            $stmt->execute([$mediaId]);
            $row = $stmt->fetch();
            if ($row) {
                // Only delete local files (not external URLs like picsum)
                $url = $row['url'] ?? '';
                if (strpos($url, '/assets/uploads/') === 0) {
                    $fp = __DIR__ . '/..' . $url;
                    if (file_exists($fp)) @unlink($fp);
                }
                $db->prepare('DELETE FROM property_media WHERE id=?')->execute([$mediaId]);
            }
        }
        auditLog('delete','media',$mediaId,'Deleted media file');
        setFlash('success', 'File deleted successfully.');
    } catch(Exception $e) {
        setFlash('danger', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: /admin/media.php');
    exit;
}

// ── Upload Media ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    verifyCsrf();
    $file   = $_FILES['media_file'];
    $propId = (int)($_POST['property_id'] ?? 0) ?: null;

    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= MAX_FILE_SIZE) {
        $mime = mime_content_type($file['tmp_name']);
        $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ['application/pdf']);
        if (in_array($mime, $allowedTypes)) {
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $subDir  = 'media';
            $uploadDir = __DIR__ . '/../assets/uploads/' . $subDir . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fname  = 'media_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                $mtype = in_array($mime, ALLOWED_IMAGE_TYPES) ? 'image' : 'document';
                try {
                    $pubUrl = '/assets/uploads/' . $subDir . '/' . $fname;
                    $db->prepare(
                        'INSERT INTO property_media (property_id, url, thumbnail_url, alt_text, kind, sort_order)
                         VALUES (?,?,?,?,?,0)'
                    )->execute([$propId, $pubUrl, $pubUrl, $file['name'], $mtype]);
                    setFlash('success', 'File uploaded successfully.');
                } catch(Exception $e) {
                    setFlash('danger', 'DB error: ' . $e->getMessage());
                }
            } else {
                setFlash('danger', 'Failed to move uploaded file.');
            }
        } else {
            setFlash('danger', 'Invalid file type. Allowed: JPEG, PNG, WebP, PDF.');
        }
    } else {
        setFlash('danger', 'Upload error or file too large (max 5MB).');
    }
    header('Location: /admin/media.php');
    exit;
}

// ── Filters ──────────────────────────────────────────────────
$filterType  = $_GET['type']  ?? '';
$filterProp  = trim($_GET['prop'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 40;

// Build query: union property_media + project_media
$propWhere = [];
$propParams = [];
if ($filterType !== '') { $propWhere[] = 'pm.kind=?'; $propParams[] = $filterType; }
if ($filterProp !== '') { $propWhere[] = 'p.title LIKE ?';  $propParams[] = "%$filterProp%"; }
$propWhereSQL = $propWhere ? 'WHERE ' . implode(' AND ', $propWhere) : '';

try {
    // Property media
    $pStmt = $db->prepare(
        "SELECT pm.id, pm.url AS file_path, pm.alt_text AS original_name, pm.kind AS media_type, pm.sort_order,
                NOW() AS uploaded_at,
                p.id AS source_id, p.title AS source_title, 'property' AS source_type
         FROM property_media pm
         LEFT JOIN properties p ON pm.property_id=p.id
         $propWhereSQL
         ORDER BY pm.sort_order ASC"
    );
    $pStmt->execute($propParams);
    $propertyMedia = $pStmt->fetchAll();

    // Project media (no type filter for simplicity)
    $projStmt = $db->query(
        "SELECT gm.id, gm.file_path AS file_path, gm.file_path AS original_name, gm.media_type, gm.sort_order,
                gm.uploaded_at,
                pr.id AS source_id, pr.name AS source_title, 'project' AS source_type
         FROM project_media gm
         LEFT JOIN projects pr ON gm.project_id=pr.id
         ORDER BY gm.uploaded_at DESC"
    );
    $projectMedia = $projStmt->fetchAll();

    $allMedia   = array_merge($propertyMedia, $projectMedia);
    // Sort by uploaded_at desc
    usort($allMedia, fn($a,$b) => strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']));

    $totalItems = count($allMedia);
    $totalPages = max(1,(int)ceil($totalItems/$perPage));
    $offset     = ($page-1)*$perPage;
    $pageMedia  = array_slice($allMedia, $offset, $perPage);

    // Properties for upload form
    $propListStmt = $db->query("SELECT id, title FROM properties ORDER BY title ASC LIMIT 200");
    $propList = $propListStmt->fetchAll();

    $dbOk = true;
} catch(Exception $e) {
    error_log('[Media] ' . $e->getMessage());
    $allMedia = $pageMedia = $propList = []; $totalItems = $totalPages = 0; $dbOk = false;
}

function paginateUrl(int $p): string { $ps = $_GET; $ps['page']=$p; return '?'.http_build_query($ps); }

$imageTypes = ALLOWED_IMAGE_TYPES;
$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-images me-2" style="color:var(--gold)"></i>Media Library</h1>
    <p class="text-muted mb-0 fs-13"><?= number_format($totalItems) ?> files total</p>
  </div>
  <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#uploadModal">
    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Files
  </button>
</div>

<!-- Filters -->
<div class="filter-card">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-5">
      <label class="form-label fw-600 fs-12">Search by Property</label>
      <input type="text" name="prop" class="form-control form-control-sm"
             placeholder="Property name..."
             value="<?= htmlspecialchars($filterProp, ENT_QUOTES,'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label fw-600 fs-12">Media Type</label>
      <select name="type" class="form-select form-select-sm">
        <option value="">All Types</option>
        <option value="image"    <?= $filterType==='image'?'selected':'' ?>>Images</option>
        <option value="document" <?= $filterType==='document'?'selected':'' ?>>Documents</option>
        <option value="floor_plan" <?= $filterType==='floor_plan'?'selected':'' ?>>Floor Plans</option>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a href="/admin/media.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fa-solid fa-rotate"></i></a>
    </div>
  </form>
</div>

<?php if (empty($pageMedia)): ?>
<div class="text-center py-5 text-muted">
  <i class="fa-solid fa-images fa-3x mb-3 d-block" style="color:#dee2e6;"></i>
  <p class="mb-2">No media files found.</p>
  <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#uploadModal">
    <i class="fa-solid fa-upload me-1"></i> Upload First File
  </button>
</div>
<?php else: ?>

<!-- Media Grid -->
<div class="media-grid">
  <?php foreach ($pageMedia as $item): ?>
  <?php
    $fileUrl = $item['file_path'] ?? '';  // already aliased to url for property_media
    $ext     = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
    $isPdf   = $ext === 'pdf';
    $isImage = !$isPdf && !empty($fileUrl);
    $mediaType = $item['media_type'] ?? 'image';
    $displayUrl = $fileUrl; // Could be absolute URL or relative path
    // Ensure relative paths are served correctly
    if ($displayUrl && strpos($displayUrl, 'http') === false && strpos($displayUrl, '/') !== 0) {
        $displayUrl = '/assets/uploads/' . $displayUrl;
    }
  ?>
  <div class="media-item">
    <?php if ($isImage): ?>
      <img src="<?= htmlspecialchars($displayUrl, ENT_QUOTES,'UTF-8') ?>"
           alt="" class="media-thumb" loading="lazy"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <div class="media-thumb-placeholder" style="display:none;"><i class="fa-regular fa-image"></i></div>
    <?php elseif ($isPdf): ?>
      <div class="media-thumb-placeholder"><i class="fa-solid fa-file-pdf text-danger"></i></div>
    <?php else: ?>
      <div class="media-thumb-placeholder"><i class="fa-solid fa-file"></i></div>
    <?php endif; ?>

    <div class="media-info">
      <div class="media-name" title="<?= htmlspecialchars($item['original_name'] ?? basename($fileUrl), ENT_QUOTES,'UTF-8') ?>">
        <?= htmlspecialchars(mb_strimwidth($item['original_name'] ?? basename($fileUrl), 0, 28, '…'), ENT_QUOTES,'UTF-8') ?>
      </div>
      <div class="media-meta">
        <?php if ($item['source_title']): ?>
          <div class="text-truncate" title="<?= htmlspecialchars($item['source_title'], ENT_QUOTES,'UTF-8') ?>">
            <i class="fa-solid fa-<?= $item['source_type']==='project'?'building':'house' ?> fa-xs me-1"></i>
            <?= htmlspecialchars(mb_strimwidth($item['source_title'],0,22,'…'), ENT_QUOTES,'UTF-8') ?>
          </div>
        <?php else: ?>
          <span class="text-muted">Unattached</span>
        <?php endif; ?>
        <div><?= htmlspecialchars(date('d M Y', strtotime($item['uploaded_at'] ?? 'now')), ENT_QUOTES,'UTF-8') ?></div>
      </div>
      <div class="d-flex gap-1 mt-1">
        <a href="<?= htmlspecialchars($displayUrl, ENT_QUOTES,'UTF-8') ?>"
           target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size:0.7rem;"
           title="View">
          <i class="fa-solid fa-eye"></i>
        </a>
        <button type="button"
                class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:0.7rem;"
                data-bs-toggle="modal" data-bs-target="#deleteMediaModal"
                data-id="<?= (int)$item['id'] ?>"
                data-source="<?= htmlspecialchars($item['source_type'], ENT_QUOTES,'UTF-8') ?>"
                data-name="<?= htmlspecialchars($item['original_name'] ?? basename($item['file_path']), ENT_QUOTES,'UTF-8') ?>"
                title="Delete">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center mt-4">
  <nav><ul class="pagination pagination-sm mb-0">
    <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page-1),ENT_QUOTES,'UTF-8') ?>">&laquo;</a></li><?php endif; ?>
    <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($p),ENT_QUOTES,'UTF-8') ?>"><?= $p ?></a></li>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page+1),ENT_QUOTES,'UTF-8') ?>">&raquo;</a></li><?php endif; ?>
  </ul></nav>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <div class="modal-header" style="background:var(--sidebar-bg);color:#fff;">
          <h5 class="modal-title"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Media</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-600">File *</label>
            <input type="file" name="media_file" class="form-control" required
                   accept="image/jpeg,image/png,image/webp,application/pdf">
            <div class="form-text">Max 5MB. JPEG, PNG, WebP, or PDF.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Link to Property (optional)</label>
            <select name="property_id" class="form-select">
              <option value="">— Not linked —</option>
              <?php foreach ($propList as $pl): ?>
                <option value="<?= (int)$pl['id'] ?>"><?= htmlspecialchars($pl['title'], ENT_QUOTES,'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gold"><i class="fa-solid fa-upload me-1"></i> Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteMediaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to permanently delete:</p>
        <p class="fw-700" id="delMediaName"></p>
        <p class="text-muted fs-13">This will remove the file from the server and database.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
          <input type="hidden" name="delete_media_id" id="delMediaId">
          <input type="hidden" name="media_source" id="delMediaSource">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteMediaModal').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('delMediaId').value     = btn.getAttribute('data-id');
  document.getElementById('delMediaSource').value = btn.getAttribute('data-source');
  document.getElementById('delMediaName').textContent = btn.getAttribute('data-name');
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
