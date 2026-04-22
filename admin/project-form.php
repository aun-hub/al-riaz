<?php
/**
 * Al-Riaz Associates — New / Edit Project Form
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db     = Database::getInstance();
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit Project' : 'New Project';

$data = [
    'name'=>'', 'slug'=>'', 'developer'=>'', 'city'=>'islamabad', 'area_locality'=>'',
    'status'=>'upcoming', 'noc_status'=>'pending', 'noc_ref'=>'',
    'authorised_since'=>'', 'authorisation_ref'=>'', 'description'=>'',
    'lat'=>'', 'lng'=>'',
    'is_featured'=>0, 'is_published'=>0,
    'hero_image_url'=>'', 'brochure_pdf'=>'', 'master_plan'=>'',
    'gallery'=>'',
];
$existingGallery = [];

if ($isEdit) {
    try {
        $stmt = $db->prepare('SELECT * FROM projects WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { setFlash('danger','Project not found.'); redirect('/admin/projects.php'); }
        foreach ($row as $k=>$v) { if (array_key_exists($k,$data)) $data[$k]=$v; }
        // Load gallery from JSON field
        if (!empty($row['gallery'])) {
            $galleryUrls = json_decode($row['gallery'], true) ?: [];
            foreach ($galleryUrls as $idx => $url) {
                $existingGallery[] = ['id' => $idx, 'file_path' => $url, 'url' => $url];
            }
        }
    } catch(Exception $e) {
        setFlash('danger','Could not load project.'); redirect('/admin/projects.php');
    }
}

$formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fields = [
        'name'            => trim($_POST['name'] ?? ''),
        'slug'            => trim($_POST['slug'] ?? ''),
        'developer'       => trim($_POST['developer'] ?? ''),
        'city'            => $_POST['city'] ?? 'islamabad',
        'area_locality'   => trim($_POST['area_locality'] ?? ''),
        'status'          => $_POST['status'] ?? 'upcoming',
        'noc_status'      => $_POST['noc_status'] ?? 'pending',
        'noc_ref'         => trim($_POST['noc_ref'] ?? ''),
        'authorised_since'=> $_POST['authorised_since'] ?? '',
        'authorisation_ref'=> trim($_POST['authorisation_ref'] ?? ''),
        'description'     => trim($_POST['description'] ?? ''),
        'lat'             => trim($_POST['latitude'] ?? '') ?: null,
        'lng'             => trim($_POST['longitude'] ?? '') ?: null,
        'is_featured'     => isset($_POST['is_featured']) ? 1 : 0,
        'is_published'    => isset($_POST['is_published']) ? 1 : 0,
    ];

    // Auto-slug if empty
    if ($fields['slug'] === '' && $fields['name'] !== '') {
        $fields['slug'] = makeSlug($fields['name']);
    }

    if ($fields['name'] === '') $formErrors[] = 'Project name is required.';
    if ($fields['slug'] === '') $formErrors[] = 'Slug is required.';

    // File uploads
    $uploadDir = __DIR__ . '/../assets/uploads/projects/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (empty($formErrors)) {
        $fields['updated_at'] = date('Y-m-d H:i:s');

        // Handle file uploads — return public URLs for DB storage
        $handleUploadUrl = function(string $fieldName) use ($uploadDir, &$formErrors): ?string {
            if (empty($_FILES[$fieldName]['tmp_name'])) return null;
            $file = $_FILES[$fieldName];
            if ($file['error'] !== UPLOAD_ERR_OK) return null;
            if ($file['size'] > MAX_FILE_SIZE) { $formErrors[] = "$fieldName exceeds 5MB limit."; return null; }
            $allowedTypes = $fieldName === 'brochure_pdf' ? ['application/pdf'] : ALLOWED_IMAGE_TYPES;
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowedTypes)) { $formErrors[] = "Invalid file type for $fieldName."; return null; }
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fname = 'proj_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                return '/assets/uploads/projects/' . $fname;
            }
            return null;
        };

        $heroUrl     = $handleUploadUrl('hero_image');
        $brochureUrl = $handleUploadUrl('brochure_pdf');
        $masterUrl   = $handleUploadUrl('master_plan');

        if ($heroUrl)     $fields['hero_image_url'] = $heroUrl;
        elseif ($isEdit)  $fields['hero_image_url'] = $data['hero_image_url'];

        if ($brochureUrl) $fields['brochure_pdf']   = $brochureUrl;
        elseif ($isEdit)  $fields['brochure_pdf']   = $data['brochure_pdf'];

        if ($masterUrl)   $fields['master_plan']    = $masterUrl;
        elseif ($isEdit)  $fields['master_plan']    = $data['master_plan'];

        // Build/update gallery JSON
        $existingGalleryUrls = [];
        if ($isEdit && !empty($data['gallery'])) {
            $existingGalleryUrls = json_decode($data['gallery'], true) ?: [];
        }
        // Remove deleted gallery items
        if (!empty($_POST['delete_gallery'])) {
            foreach ((array)$_POST['delete_gallery'] as $delIdx) {
                unset($existingGalleryUrls[(int)$delIdx]);
            }
            $existingGalleryUrls = array_values($existingGalleryUrls);
        }

        try {
            if ($isEdit) {
                $setClauses = implode(', ', array_map(fn($k) => "$k=?", array_keys($fields)));
                $db->prepare("UPDATE projects SET $setClauses WHERE id=?")->execute([...array_values($fields), $id]);
                $projId = $id;
                auditLog('update','projects',$id,'Updated: '.$fields['name']);
                $msg = 'Project updated successfully.';
            } else {
                $fields['created_at'] = date('Y-m-d H:i:s');
                $fields['gallery']    = json_encode([]);
                $cols = implode(', ', array_keys($fields));
                $phs  = implode(', ', array_fill(0, count($fields), '?'));
                $db->prepare("INSERT INTO projects ($cols) VALUES ($phs)")->execute(array_values($fields));
                $projId = (int)$db->lastInsertId();
                auditLog('create','projects',$projId,'Created: '.$fields['name']);
                $msg = 'Project created successfully.';
            }

            // Gallery uploads — append to JSON array
            $newGalleryUrls = $existingGalleryUrls;
            if (!empty($_FILES['gallery_images']['name'][0])) {
                $gDir = $uploadDir . $projId . '/gallery/';
                if (!is_dir($gDir)) mkdir($gDir, 0755, true);
                foreach ($_FILES['gallery_images']['error'] as $idx => $err) {
                    if ($err !== UPLOAD_ERR_OK) continue;
                    $tmp  = $_FILES['gallery_images']['tmp_name'][$idx];
                    $mime = mime_content_type($tmp);
                    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) continue;
                    $ext  = strtolower(pathinfo($_FILES['gallery_images']['name'][$idx], PATHINFO_EXTENSION));
                    $fname= 'gallery_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmp, $gDir . $fname)) {
                        $newGalleryUrls[] = '/assets/uploads/projects/' . $projId . '/gallery/' . $fname;
                    }
                }
                // Update gallery JSON in DB
                $db->prepare("UPDATE projects SET gallery=? WHERE id=?")->execute([json_encode($newGalleryUrls), $projId]);
            } elseif ($isEdit) {
                // Update gallery if items were deleted
                $db->prepare("UPDATE projects SET gallery=? WHERE id=?")->execute([json_encode($existingGalleryUrls), $projId]);
            }

            setFlash('success', $msg);
            redirect('/admin/projects.php');
        } catch(Exception $e) {
            $formErrors[] = 'Database error: ' . $e->getMessage();
        }
    }
    foreach ($fields as $k=>$v) { if (array_key_exists($k,$data)) $data[$k]=$v; }
}

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1>
      <i class="fa-solid <?= $isEdit?'fa-pen-to-square':'fa-plus-circle' ?> me-2" style="color:var(--gold)"></i>
      <?= $isEdit ? 'Edit Project' : 'New Project' ?>
    </h1>
  </div>
  <a href="/admin/projects.php" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Projects
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

<form method="POST" enctype="multipart/form-data" action="/admin/project-form.php<?= $isEdit?"?id=$id":'' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

  <!-- 1. Basics -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-circle-info" style="color:var(--gold)"></i> Basic Information</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Project Name *</label>
          <input type="text" name="name" id="projName" class="form-control" required maxlength="200"
                 placeholder="e.g. Bahria Enclave Phase II"
                 value="<?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Slug *</label>
          <input type="text" name="slug" id="projSlug" class="form-control" required maxlength="200"
                 placeholder="auto-generated from name"
                 value="<?= htmlspecialchars($data['slug'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="form-text">URL-friendly identifier. Auto-fills from name.</div>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Developer</label>
          <input type="text" name="developer" class="form-control" maxlength="200"
                 placeholder="e.g. Bahria Town (Pvt.) Ltd."
                 value="<?= htmlspecialchars($data['developer'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label fw-600">City *</label>
          <select name="city" class="form-select" required>
            <?php foreach (['islamabad'=>'Islamabad','rawalpindi'=>'Rawalpindi','lahore'=>'Lahore','karachi'=>'Karachi','peshawar'=>'Peshawar','multan'=>'Multan'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $data['city']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label fw-600">Area / Locality</label>
          <input type="text" name="area_locality" class="form-control"
                 value="<?= htmlspecialchars($data['area_locality'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Developer & Authorisation -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-file-shield" style="color:var(--gold)"></i> Status & Authorisation</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label fw-600">Project Status</label>
          <select name="status" class="form-select">
            <?php foreach (['upcoming'=>'Upcoming','under_development'=>'Under Development','ready'=>'Ready','possession'=>'Possession'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $data['status']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-600">NOC Status</label>
          <select name="noc_status" class="form-select">
            <option value="approved"     <?= $data['noc_status']==='approved'?'selected':'' ?>>Approved</option>
            <option value="pending"      <?= $data['noc_status']==='pending'?'selected':'' ?>>Pending</option>
            <option value="not_required" <?= $data['noc_status']==='not_required'?'selected':'' ?>>Not Required</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-600">NOC Reference No.</label>
          <input type="text" name="noc_ref" class="form-control"
                 placeholder="e.g. RDA/NOC/2024/1234"
                 value="<?= htmlspecialchars($data['noc_ref'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-600">Authorised Since</label>
          <input type="date" name="authorised_since" class="form-control"
                 value="<?= htmlspecialchars($data['authorised_since'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-8">
          <label class="form-label fw-600">Authorisation Reference</label>
          <input type="text" name="authorisation_ref" class="form-control"
                 value="<?= htmlspecialchars($data['authorisation_ref'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Description -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-align-left" style="color:var(--gold)"></i> Description</div>
    <div class="card-body">
      <label class="form-label fw-600">Project Description</label>
      <textarea name="description" class="form-control" rows="6"
                placeholder="Describe the project, its features, location advantages..."><?= htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
  </div>

  <!-- 4. Media -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-images" style="color:var(--gold)"></i> Media</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Hero / Cover Image</label>
          <?php $heroUrl = $data['hero_image_url'] ?? $data['hero_image'] ?? ''; ?>
          <?php if ($heroUrl): ?>
            <div class="mb-2">
              <?php $heroSrc = (strpos($heroUrl,'http')===0) ? $heroUrl : '/assets/uploads/'.ltrim($heroUrl,'/'); ?>
              <img src="<?= htmlspecialchars($heroSrc, ENT_QUOTES, 'UTF-8') ?>"
                   alt="Hero" style="max-height:120px;border-radius:6px;">
            </div>
          <?php endif; ?>
          <input type="file" name="hero_image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <div class="form-text">Max 5MB. JPEG/PNG/WebP.</div>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Gallery Images</label>
          <input type="file" name="gallery_images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
          <div class="form-text">Upload multiple. Existing gallery shown below.</div>
          <?php if (!empty($existingGallery)): ?>
          <div class="d-flex flex-wrap gap-2 mt-2" id="galleryGrid">
            <?php foreach ($existingGallery as $gi): ?>
            <?php $giSrc = $gi['url'] ?? $gi['file_path'] ?? ''; $giSrc = (strpos($giSrc,'http')===0) ? $giSrc : '/assets/uploads/'.ltrim($giSrc,'/'); ?>
            <div class="position-relative" id="gi_<?= (int)$gi['id'] ?>">
              <img src="<?= htmlspecialchars($giSrc, ENT_QUOTES, 'UTF-8') ?>"
                   alt="" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
              <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0"
                      style="width:18px;height:18px;font-size:0.6rem;border-radius:50%;"
                      onclick="removeGalleryItem(<?= (int)$gi['id'] ?>, this)">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. Documents -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-file-pdf" style="color:var(--gold)"></i> Documents</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Brochure (PDF)</label>
          <?php if ($data['brochure_pdf']): ?>
            <?php $brochureSrc = (strpos($data['brochure_pdf'],'http')===0) ? $data['brochure_pdf'] : '/assets/uploads/'.ltrim($data['brochure_pdf'],'/'); ?>
            <div class="mb-2">
              <a href="<?= htmlspecialchars($brochureSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-file-pdf me-1"></i> View Current Brochure
              </a>
            </div>
          <?php endif; ?>
          <input type="file" name="brochure_pdf" class="form-control" accept="application/pdf">
          <div class="form-text">Max 5MB. PDF only.</div>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Master Plan Image</label>
          <?php if ($data['master_plan']): ?>
            <?php $masterSrc = (strpos($data['master_plan'],'http')===0) ? $data['master_plan'] : '/assets/uploads/'.ltrim($data['master_plan'],'/'); ?>
            <div class="mb-2">
              <img src="<?= htmlspecialchars($masterSrc, ENT_QUOTES, 'UTF-8') ?>"
                   alt="Master Plan" style="max-height:100px;border-radius:6px;">
            </div>
          <?php endif; ?>
          <input type="file" name="master_plan" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>
    </div>
  </div>

  <!-- 6. Location -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-map-pin" style="color:var(--gold)"></i> Map Location</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Latitude</label>
          <input type="number" name="latitude" class="form-control" step="any" placeholder="33.6844"
                 value="<?= htmlspecialchars($data['lat'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-600">Longitude</label>
          <input type="number" name="longitude" class="form-control" step="any" placeholder="73.0479"
                 value="<?= htmlspecialchars($data['lng'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- 7. Settings -->
  <div class="form-section-card">
    <div class="card-header"><i class="fa-solid fa-sliders" style="color:var(--gold)"></i> Publishing Settings</div>
    <div class="card-body">
      <div class="d-flex gap-4 flex-wrap">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="projFeatured" name="is_featured" value="1"
                 <?= $data['is_featured']?'checked':'' ?>>
          <label class="form-check-label" for="projFeatured">
            <i class="fa-solid fa-star text-warning me-1"></i> Set as Featured
          </label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="projPublished" name="is_published" value="1"
                 <?= $data['is_published']?'checked':'' ?>>
          <label class="form-check-label" for="projPublished">
            <i class="fa-solid fa-globe text-success me-1"></i> Publish on Website
          </label>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 pb-4">
    <a href="/admin/projects.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-gold px-4">
      <i class="fa-solid fa-floppy-disk me-1"></i>
      <?= $isEdit ? 'Update Project' : 'Create Project' ?>
    </button>
  </div>

</form>

<script>
// Auto-generate slug from name
var nameInput = document.getElementById('projName');
var slugInput = document.getElementById('projSlug');
var slugManual = <?= ($isEdit && $data['slug'] !== '') ? 'true' : 'false' ?>;

nameInput.addEventListener('input', function() {
  if (!slugManual) {
    slugInput.value = this.value
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }
});

slugInput.addEventListener('input', function() { slugManual = true; });

function removeGalleryItem(id, btn) {
  var container = btn.closest('[id^="gi_"]');
  var inp = document.createElement('input');
  inp.type = 'hidden';
  inp.name = 'delete_gallery[]';
  inp.value = id;
  document.querySelector('form').appendChild(inp);
  container.style.opacity = '0.3';
  btn.disabled = true;
}
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
