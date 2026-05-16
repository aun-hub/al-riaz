<?php
/**
 * Al-Riaz Associates — New / Edit Project Notice
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit Notice' : 'New Notice';

$data = [
    'project_id'     => '',
    'title'          => '',
    'body'           => '',
    'source_url'     => '',
    'attachment_url' => '',
    'severity'       => 'info',
    'starts_at'      => '',
    'ends_at'        => '',
    'is_published'   => 0,
];

if ($isEdit) {
    try {
        $stmt = $db->prepare("SELECT * FROM project_notices WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { setFlash('danger', 'Notice not found.'); redirect('/admin/notices.php'); }
        foreach ($row as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
        // Normalise datetime for <input type="datetime-local">
        foreach (['starts_at', 'ends_at'] as $field) {
            if (!empty($data[$field])) $data[$field] = date('Y-m-d\TH:i', strtotime($data[$field]));
        }
    } catch (Exception $e) {
        setFlash('danger', 'Could not load notice: ' . $e->getMessage());
        redirect('/admin/notices.php');
    }
}

// Load project list (for the dropdown)
try {
    $projects = $db->query("SELECT id, name, website_url FROM projects ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    $projects = [];
}

$formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fields = [
        'project_id'     => (int)($_POST['project_id'] ?? 0) ?: null,
        'title'          => trim($_POST['title']        ?? ''),
        'body'           => trim($_POST['body']         ?? ''),
        'source_url'     => trim($_POST['source_url']   ?? '') ?: null,
        // attachment_url is resolved further down once we know the upload result
        'attachment_url' => $isEdit ? ($data['attachment_url'] ?: null) : null,
        'severity'       => in_array($_POST['severity'] ?? '', ['info', 'warning', 'critical'], true)
                            ? $_POST['severity']
                            : 'info',
        'starts_at'      => trim($_POST['starts_at']    ?? '') ?: null,
        'ends_at'        => trim($_POST['ends_at']      ?? '') ?: null,
        'is_published'   => isset($_POST['is_published']) ? 1 : 0,
    ];

    // ── Attachment: upload (image or PDF) or remove ─────────────────────
    $uploadDir = __DIR__ . '/../assets/uploads/notices/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    if (!empty($_FILES['attachment']['tmp_name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $f       = $_FILES['attachment'];
        $allowed = array_merge(ALLOWED_IMAGE_TYPES, ['application/pdf']);
        $mime    = mime_content_type($f['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $formErrors[] = 'Attachment must be an image (JPEG/PNG/WebP/GIF) or PDF.';
        } elseif ($f['size'] > MAX_FILE_SIZE) {
            $formErrors[] = 'Attachment exceeds the ' . (int)(MAX_FILE_SIZE / 1024 / 1024) . 'MB limit.';
        } else {
            $ext   = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: ($mime === 'application/pdf' ? 'pdf' : 'jpg');
            $fname = 'notice_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $uploadDir . $fname)) {
                // Delete the previous file when replacing (local files only).
                if ($isEdit && !empty($data['attachment_url']) && strpos($data['attachment_url'], '/assets/uploads/') === 0) {
                    @unlink(__DIR__ . '/..' . $data['attachment_url']);
                }
                $fields['attachment_url'] = '/assets/uploads/notices/' . $fname;
            } else {
                $formErrors[] = 'Could not save the attachment — check folder permissions.';
            }
        }
    } elseif (!empty($_POST['remove_attachment']) && $isEdit && !empty($data['attachment_url'])) {
        if (strpos($data['attachment_url'], '/assets/uploads/') === 0) {
            @unlink(__DIR__ . '/..' . $data['attachment_url']);
        }
        $fields['attachment_url'] = null;
    }

    // Normalise datetime-local → MySQL DATETIME
    foreach (['starts_at', 'ends_at'] as $field) {
        if (!empty($fields[$field])) {
            $ts = strtotime($fields[$field]);
            $fields[$field] = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }
    }

    if ($fields['title'] === '')             $formErrors[] = 'Title is required.';
    if ($fields['body']  === '')             $formErrors[] = 'Body is required.';
    if (mb_strlen($fields['title']) > 200)   $formErrors[] = 'Title is too long (max 200 chars).';
    if ($fields['source_url'] !== null && !preg_match('~^https?://~i', $fields['source_url'])) {
        $formErrors[] = 'Source URL must start with http:// or https://';
    }
    if ($fields['starts_at'] && $fields['ends_at'] && $fields['ends_at'] < $fields['starts_at']) {
        $formErrors[] = 'End date must be after start date.';
    }

    if (empty($formErrors)) {
        $fields['updated_at'] = date('Y-m-d H:i:s');
        try {
            if ($isEdit) {
                $setClauses = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
                $stmt = $db->prepare("UPDATE project_notices SET $setClauses WHERE id = ?");
                $stmt->execute([...array_values($fields), $id]);
                auditLog('update', 'project_notices', $id, 'Updated notice: ' . $fields['title']);
                setFlash('success', 'Notice updated.');
            } else {
                $fields['created_at'] = date('Y-m-d H:i:s');
                $fields['created_by'] = (int)($_SESSION['admin_id'] ?? 0) ?: null;
                $cols  = implode(', ', array_keys($fields));
                $phs   = implode(', ', array_fill(0, count($fields), '?'));
                $db->prepare("INSERT INTO project_notices ($cols) VALUES ($phs)")->execute(array_values($fields));
                $newId = (int)$db->lastInsertId();
                auditLog('create', 'project_notices', $newId, 'Created notice: ' . $fields['title']);
                setFlash('success', 'Notice created.');
            }
            redirect('/admin/notices.php');
        } catch (Exception $e) {
            $formErrors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // Repopulate on error
    foreach ($fields as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
    foreach (['starts_at', 'ends_at'] as $field) {
        if (!empty($data[$field])) $data[$field] = date('Y-m-d\TH:i', strtotime($data[$field]));
    }
}

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1>
      <i class="fa-solid <?= $isEdit ? 'fa-pen-to-square' : 'fa-plus-circle' ?> me-2" style="color:var(--gold)"></i>
      <?= $isEdit ? 'Edit Notice' : 'New Notice' ?>
    </h1>
    <?php if ($isEdit): ?>
      <p class="text-muted mb-0 fs-13">Editing: <strong><?= htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>
  </div>
  <a href="<?= BASE_PATH ?>/admin/notices.php" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Notices
  </a>
</div>

<?php if (!empty($formErrors)): ?>
<div class="alert alert-danger d-flex gap-2">
  <i class="fa-solid fa-triangle-exclamation mt-1"></i>
  <div>
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($formErrors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" action="<?= BASE_PATH ?>/admin/notice-form.php<?= $isEdit ? "?id=$id" : '' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-section-card">
    <div class="card-header">
      <i class="fa-solid fa-circle-info" style="color:var(--gold)"></i> Notice Details
    </div>
    <div class="card-body">
      <div class="row g-3">

        <div class="col-12">
          <label class="form-label fw-600">Title *</label>
          <input type="text" name="title" class="form-control" required maxlength="200"
                 placeholder="e.g. Booking now open for Phase 5"
                 value="<?= htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Project</label>
          <select name="project_id" class="form-select">
            <option value="">— General (not tied to any project) —</option>
            <?php foreach ($projects as $proj): ?>
              <option value="<?= (int)$proj['id'] ?>" <?= (int)$data['project_id'] === (int)$proj['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($proj['name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Severity</label>
          <select name="severity" class="form-select">
            <?php foreach (['info' => 'Info', 'warning' => 'Warning', 'critical' => 'Critical'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= $data['severity'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label fw-600">Body *</label>
          <textarea name="body" class="form-control" rows="6" required
                    placeholder="Paste or write the notice content. Basic HTML is allowed."><?= htmlspecialchars($data['body'], ENT_QUOTES, 'UTF-8') ?></textarea>
          <div class="form-text">Plain text is fine — paragraphs render automatically. Inline HTML (links, bold) is also supported.</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-600">Source URL <span class="text-muted fw-normal fs-12">(optional)</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-link text-muted"></i></span>
            <input type="url" name="source_url" class="form-control" maxlength="500"
                   placeholder="https://developer-site.com/announcements/…"
                   value="<?= htmlspecialchars((string)$data['source_url'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="form-text">If you're mirroring a notice from the developer's website, paste the original URL so visitors can verify.</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-600">Attachment <span class="text-muted fw-normal fs-12">(optional · image or PDF, max <?= (int)(MAX_FILE_SIZE / 1024 / 1024) ?>MB)</span></label>

          <?php
            $att     = (string)$data['attachment_url'];
            $attExt  = $att !== '' ? strtolower(pathinfo($att, PATHINFO_EXTENSION)) : '';
            $isImg   = in_array($attExt, ['jpg','jpeg','png','webp','gif'], true);
            $isPdf   = $attExt === 'pdf';
          ?>

          <?php if ($att !== ''): ?>
          <div class="d-flex align-items-center gap-3 p-2 mb-2 rounded" style="background:#f4f7fb; border:1px solid #e6ebf2;">
            <?php if ($isImg): ?>
              <img src="<?= htmlspecialchars(mediaUrl($att), ENT_QUOTES, 'UTF-8') ?>" alt=""
                   style="width:64px; height:64px; object-fit:cover; border-radius:6px; background:#fff;">
            <?php elseif ($isPdf): ?>
              <div style="width:64px; height:64px; border-radius:6px; background:#fff; display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-file-pdf fa-2x text-danger"></i>
              </div>
            <?php else: ?>
              <i class="fa-solid fa-paperclip fa-2x text-muted"></i>
            <?php endif; ?>
            <div style="flex:1; min-width:0;">
              <a href="<?= htmlspecialchars(mediaUrl($att), ENT_QUOTES, 'UTF-8') ?>"
                 target="_blank" rel="noopener noreferrer"
                 class="text-decoration-none text-truncate d-block fw-600" style="color:var(--navy-700);">
                <?= htmlspecialchars(basename($att), ENT_QUOTES, 'UTF-8') ?>
              </a>
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" id="remove_attachment" name="remove_attachment" value="1">
                <label class="form-check-label fs-13 text-danger" for="remove_attachment">Remove this attachment when saving</label>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <input type="file" name="attachment" class="form-control"
                 accept="image/jpeg,image/png,image/webp,image/gif,application/pdf">
          <div class="form-text">
            <?= $att !== '' ? 'Pick a new file to replace the current attachment, or tick the checkbox above to clear it.' : 'Uploading is optional. JPEG/PNG/WebP/GIF or PDF.' ?>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Starts at <span class="text-muted fw-normal fs-12">(optional)</span></label>
          <input type="datetime-local" name="starts_at" class="form-control"
                 value="<?= htmlspecialchars((string)$data['starts_at'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Ends at <span class="text-muted fw-normal fs-12">(optional)</span></label>
          <input type="datetime-local" name="ends_at" class="form-control"
                 value="<?= htmlspecialchars((string)$data['ends_at'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-12 border-top pt-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="isPublished" name="is_published"
                   value="1" <?= $data['is_published'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="isPublished">Publish (visible on public site)</label>
          </div>
          <div class="form-text">Drafts are hidden from visitors. Scheduling lets you publish in advance and let the start/end window control when it's actually live.</div>
        </div>

      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between gap-2">
    <a href="<?= BASE_PATH ?>/admin/notices.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-gold">
      <i class="fa-solid fa-floppy-disk me-1"></i> <?= $isEdit ? 'Update Notice' : 'Create Notice' ?>
    </button>
  </div>
</form>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
