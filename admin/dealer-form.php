<?php
/**
 * Al-Riaz Associates — New / Edit Authorized Dealer Form
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db        = Database::getInstance();
$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit    = $id > 0;
$pageTitle = $isEdit ? 'Edit Dealer' : 'New Dealer';

$data = [
    'name'         => '',
    'logo_url'     => '',
    'website_url'  => '',
    'sort_order'   => 0,
    'is_published' => 1,
];

if ($isEdit) {
    try {
        $stmt = $db->prepare('SELECT * FROM authorized_dealers WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { setFlash('danger', 'Dealer not found.'); redirect('/admin/dealers.php'); }
        foreach ($row as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
    } catch (Exception $e) {
        setFlash('danger', 'Could not load dealer.');
        redirect('/admin/dealers.php');
    }
}

$formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fields = [
        'name'         => trim($_POST['name'] ?? ''),
        'website_url'  => trim($_POST['website_url'] ?? ''),
        'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];

    if ($fields['name'] === '') $formErrors[] = 'Dealer name is required.';
    if (mb_strlen($fields['name']) > 200) $formErrors[] = 'Dealer name is too long (max 200 characters).';
    if ($fields['website_url'] !== '' && !filter_var($fields['website_url'], FILTER_VALIDATE_URL)) {
        $formErrors[] = 'Website URL is not valid.';
    }

    // Logo upload
    $uploadDir = __DIR__ . '/../assets/uploads/dealers/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $logoUrl = null;
    if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        if ($file['size'] > MAX_FILE_SIZE) {
            $formErrors[] = 'Logo exceeds file size limit.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
                $formErrors[] = 'Invalid logo file type. Allowed: JPEG, PNG, WebP, GIF.';
            } else {
                $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fname = 'dealer_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                    $logoUrl = '/assets/uploads/dealers/' . $fname;
                } else {
                    $formErrors[] = 'Logo upload failed.';
                }
            }
        }
    } elseif (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $formErrors[] = 'Logo upload error (code ' . (int)$_FILES['logo']['error'] . ').';
    }

    if ($logoUrl !== null) {
        $oldLogo = $isEdit ? (string)$data['logo_url'] : '';
        $fields['logo_url'] = $logoUrl;
        if ($oldLogo && str_starts_with($oldLogo, '/assets/uploads/dealers/')) {
            $oldFs = __DIR__ . '/..' . $oldLogo;
            if (is_file($oldFs)) @unlink($oldFs);
        }
    } else {
        $fields['logo_url'] = $isEdit ? (string)$data['logo_url'] : '';
    }

    if (!$isEdit && $fields['logo_url'] === '') {
        $formErrors[] = 'Logo is required for new dealers.';
    }

    if (empty($formErrors)) {
        try {
            if ($isEdit) {
                $setClauses = implode(', ', array_map(fn($k) => "$k=?", array_keys($fields)));
                $db->prepare("UPDATE authorized_dealers SET $setClauses WHERE id=?")
                   ->execute([...array_values($fields), $id]);
                auditLog('update', 'authorized_dealers', $id, 'Updated: ' . $fields['name']);
                setFlash('success', 'Dealer updated successfully.');
            } else {
                $cols = implode(', ', array_keys($fields));
                $phs  = implode(', ', array_fill(0, count($fields), '?'));
                $db->prepare("INSERT INTO authorized_dealers ($cols) VALUES ($phs)")
                   ->execute(array_values($fields));
                $newId = (int)$db->lastInsertId();
                auditLog('create', 'authorized_dealers', $newId, 'Created: ' . $fields['name']);
                setFlash('success', 'Dealer created successfully.');
            }
            redirect('/admin/dealers.php');
        } catch (Exception $e) {
            $formErrors[] = 'Database error: ' . $e->getMessage();
        }
    }
    foreach ($fields as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
}

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1>
      <i class="fa-solid <?= $isEdit ? 'fa-pen-to-square' : 'fa-plus-circle' ?> me-2" style="color:var(--gold)"></i>
      <?= $isEdit ? 'Edit Dealer' : 'New Dealer' ?>
    </h1>
  </div>
  <a href="<?= BASE_PATH ?>/admin/dealers.php" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dealers
  </a>
</div>

<?php if (!empty($formErrors)): ?>
<div class="alert alert-danger">
  <strong>Please fix the following errors:</strong>
  <ul class="mb-0 mt-1">
    <?php foreach ($formErrors as $err): ?><li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" action="<?= BASE_PATH ?>/admin/dealer-form.php<?= $isEdit ? "?id=$id" : '' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-circle-info" style="color:var(--gold)"></i> Dealer Information</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-8">
          <label class="form-label fw-600">Dealer / Developer Name *</label>
          <input type="text" name="name" class="form-control" required maxlength="200"
                 placeholder="e.g. Bahria Town (Pvt.) Ltd."
                 value="<?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-600">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" min="0" max="9999"
                 value="<?= (int)$data['sort_order'] ?>">
          <div class="form-text">Lower numbers appear first.</div>
        </div>
        <div class="col-12">
          <label class="form-label fw-600">Website URL</label>
          <input type="url" name="website_url" class="form-control" maxlength="500"
                 placeholder="https://example.com"
                 value="<?= htmlspecialchars($data['website_url'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="form-text">Optional. Used to link the dealer logo on the website.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-image" style="color:var(--gold)"></i> Logo</div>
    <div class="card-body">
      <?php if (!empty($data['logo_url'])): ?>
        <div class="mb-3">
          <div class="fs-13 text-muted mb-1">Current logo:</div>
          <img src="<?= htmlspecialchars(mediaUrl($data['logo_url']), ENT_QUOTES, 'UTF-8') ?>"
               alt="Current logo" style="max-height:120px;max-width:240px;border:1px solid #dee2e6;border-radius:6px;padding:8px;background:#fff;">
        </div>
      <?php endif; ?>
      <label class="form-label fw-600"><?= $isEdit ? 'Replace Logo' : 'Dealer Logo *' ?></label>
      <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" <?= $isEdit ? '' : 'required' ?>>
      <div class="form-text">Max 5MB. JPEG / PNG / WebP / GIF. Transparent PNGs look best.</div>
    </div>
  </div>

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-sliders" style="color:var(--gold)"></i> Publishing Settings</div>
    <div class="card-body">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="dealerPublished" name="is_published" value="1"
               <?= $data['is_published'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="dealerPublished">
          <i class="fa-solid fa-globe text-success me-1"></i> Publish on Website
        </label>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 pb-4">
    <a href="<?= BASE_PATH ?>/admin/dealers.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-gold px-4">
      <i class="fa-solid fa-floppy-disk me-1"></i>
      <?= $isEdit ? 'Update Dealer' : 'Create Dealer' ?>
    </button>
  </div>
</form>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
