<?php
/**
 * Al-Riaz Associates — New / Edit Feature Form
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db        = Database::getInstance();
$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit    = $id > 0;
$pageTitle = $isEdit ? 'Edit Feature' : 'New Feature';

$data = [
    'slug'       => '',
    'label'      => '',
    'icon'       => 'fa-check-circle',
    'sort_order' => 0,
    'is_active'  => 1,
];

if ($isEdit) {
    try {
        $stmt = $db->prepare('SELECT * FROM property_features WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { setFlash('danger', 'Feature not found.'); redirect('/admin/features.php'); }
        foreach ($row as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
    } catch (Exception $e) {
        setFlash('danger', 'Could not load feature.');
        redirect('/admin/features.php');
    }
}

$formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fields = [
        'slug'       => preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['slug'] ?? ''))),
        'label'      => trim($_POST['label'] ?? ''),
        'icon'       => trim($_POST['icon'] ?? 'fa-check-circle') ?: 'fa-check-circle',
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active'  => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($fields['label'] === '') $formErrors[] = 'Label is required.';
    if ($fields['slug'] === '' && $fields['label'] !== '') {
        $fields['slug'] = preg_replace('/[^a-z0-9]+/', '_', strtolower($fields['label']));
        $fields['slug'] = trim($fields['slug'], '_');
    }
    if ($fields['slug'] === '') $formErrors[] = 'Slug is required.';
    if (mb_strlen($fields['slug']) > 80) $formErrors[] = 'Slug must be 80 characters or less.';
    if (mb_strlen($fields['label']) > 120) $formErrors[] = 'Label must be 120 characters or less.';

    if (empty($formErrors)) {
        try {
            // Check slug uniqueness (excluding self when editing)
            $check = $db->prepare("SELECT id FROM property_features WHERE slug=? AND id<>?");
            $check->execute([$fields['slug'], $id]);
            if ($check->fetch()) {
                $formErrors[] = 'Slug "' . $fields['slug'] . '" is already in use.';
            }
        } catch (Exception $e) {
            $formErrors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (empty($formErrors)) {
        try {
            if ($isEdit) {
                $setClauses = implode(', ', array_map(fn($k) => "$k=?", array_keys($fields)));
                $db->prepare("UPDATE property_features SET $setClauses WHERE id=?")
                   ->execute([...array_values($fields), $id]);
                auditLog('update', 'property_features', $id, 'Updated: ' . $fields['label']);
                setFlash('success', 'Feature updated successfully.');
            } else {
                $cols = implode(', ', array_keys($fields));
                $phs  = implode(', ', array_fill(0, count($fields), '?'));
                $db->prepare("INSERT INTO property_features ($cols) VALUES ($phs)")
                   ->execute(array_values($fields));
                $newId = (int)$db->lastInsertId();
                auditLog('create', 'property_features', $newId, 'Created: ' . $fields['label']);
                setFlash('success', 'Feature created successfully.');
            }
            redirect('/admin/features.php');
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
      <?= $isEdit ? 'Edit Feature' : 'New Feature' ?>
    </h1>
  </div>
  <a href="<?= BASE_PATH ?>/admin/features.php" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Features
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

<form method="POST" action="<?= BASE_PATH ?>/admin/feature-form.php<?= $isEdit ? "?id=$id" : '' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-circle-info" style="color:var(--gold)"></i> Feature Details</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Label *</label>
          <input type="text" name="label" id="featLabel" class="form-control" required maxlength="120"
                 placeholder="e.g. Swimming Pool"
                 value="<?= htmlspecialchars($data['label'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="form-text">The text shown to users on listings.</div>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Slug *</label>
          <input type="text" name="slug" id="featSlug" class="form-control" required maxlength="80"
                 placeholder="auto-generated from label"
                 value="<?= htmlspecialchars($data['slug'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="form-text">Lowercase letters, digits, underscores only. Used as the storage key — change with care on existing features.</div>
        </div>
        <div class="col-12 col-md-8">
          <label class="form-label fw-600">Icon</label>
          <?php include __DIR__ . '/includes/_icon_picker.php'; ?>
          <input type="hidden" name="icon" id="featIcon" value="<?= htmlspecialchars($data['icon'], ENT_QUOTES, 'UTF-8') ?>">
          <button type="button" class="btn btn-outline-secondary icon-picker-trigger w-100"
                  data-icon-target="featIcon" title="Click to choose an icon">
            <i class="fa-solid <?= htmlspecialchars($data['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
            <span class="icon-picker-label"><?= htmlspecialchars($data['icon'], ENT_QUOTES, 'UTF-8') ?></span>
            <i class="fa-solid fa-chevron-down ms-auto text-muted small"></i>
          </button>
          <div class="form-text">Pick an icon from the curated list — search by name to narrow it down.</div>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-600">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" min="0" max="9999"
                 value="<?= (int)$data['sort_order'] ?>">
          <div class="form-text">Lower numbers appear first.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-sliders" style="color:var(--gold)"></i> Status</div>
    <div class="card-body">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="featActive" name="is_active" value="1"
               <?= $data['is_active'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="featActive">
          <i class="fa-solid fa-eye text-success me-1"></i> Show as a checkbox option in the listing form
        </label>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 pb-4">
    <a href="<?= BASE_PATH ?>/admin/features.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-gold px-4">
      <i class="fa-solid fa-floppy-disk me-1"></i>
      <?= $isEdit ? 'Update Feature' : 'Create Feature' ?>
    </button>
  </div>
</form>

<script>
(function () {
  var label = document.getElementById('featLabel');
  var slug  = document.getElementById('featSlug');
  var slugManual = <?= ($isEdit && $data['slug'] !== '') ? 'true' : 'false' ?>;

  label.addEventListener('input', function () {
    if (!slugManual) {
      slug.value = this.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
    }
  });
  slug.addEventListener('input', function () { slugManual = true; });
  // Icon preview/sync is handled by the shared icon picker in
  // admin/includes/_icon_picker.php — nothing to do here.
})();
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
