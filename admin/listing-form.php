<?php
/**
 * Al-Riaz Associates — New / Edit Listing Form (Multi-Step)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db  = Database::getInstance();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit Listing' : 'New Listing';

// Feature checkboxes list (loaded from DB; admins can manage at /admin/features.php)
$featureRows = [];
try {
    $featureRows = $db->query(
        "SELECT slug, label FROM property_features WHERE is_active = 1 ORDER BY sort_order ASC, label ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[listing-form] features query: ' . $e->getMessage());
}
$allFeatures   = array_column($featureRows, 'slug');
$featureLabels = array_column($featureRows, 'label', 'slug');

// Listing types per category
$listingTypes = [
    'residential' => ['house'=>'House','flat'=>'Flat / Apartment','upper_portion'=>'Upper Portion',
                      'lower_portion'=>'Lower Portion','room'=>'Room','farmhouse'=>'Farmhouse','penthouse'=>'Penthouse'],
    'commercial'  => ['shop'=>'Shop','office'=>'Office','warehouse'=>'Warehouse',
                      'showroom'=>'Showroom','building'=>'Building','factory'=>'Factory'],
    'plot'        => ['plot'=>'Plot','agricultural_land'=>'Agricultural Land'],
];

// Load existing data for edit
$data = [
    'title'           => '', 'category'        => 'residential', 'purpose' => 'sale',
    'listing_type'    => '', 'project_id'      => '', 'agent_id' => '',
    'price'           => '', 'price_on_demand' => 0, 'rent_period' => 'monthly',
    'city'            => 'islamabad', 'area_locality'   => '', 'address' => '',
    'latitude'        => '', 'longitude'       => '',
    'area_value'      => '', 'area_unit'       => 'marla',
    'bedrooms'        => 0,  'bathrooms'       => 0,
    'possession_status' => 'available',
    'features'        => [], 'description'     => '',
    'is_published'    => 0,  'is_featured'     => 0,
];
$existingPhotos = [];

if ($isEdit) {
    try {
        $stmt = $db->prepare('SELECT * FROM properties WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { setFlash('danger', 'Listing not found.'); redirect('/admin/listings.php'); }
        foreach ($row as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
        $data['features'] = $row['features'] ? json_decode($row['features'], true) : [];

        // Load photos
        $pStmt = $db->prepare('SELECT * FROM property_media WHERE property_id=? AND kind="image" ORDER BY sort_order ASC');
        $pStmt->execute([$id]);
        $existingPhotos = $pStmt->fetchAll();
    } catch(Exception $e) {
        setFlash('danger', 'Could not load listing: ' . $e->getMessage());
        redirect('/admin/listings.php');
    }
}

// Load agents and projects
try {
    $agentsStmt = $db->query("SELECT id, name FROM users WHERE role IN ('agent','admin') ORDER BY name ASC");
    $agents = $agentsStmt->fetchAll();
    $projStmt = $db->query("SELECT id, name FROM projects ORDER BY name ASC");
    $projects = $projStmt->fetchAll();
} catch(Exception $e) {
    $agents = []; $projects = [];
}

// ── Handle Form Submission ────────────────────────────────────
$formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Collect fields
    $fields = [
        'title'            => trim($_POST['title'] ?? ''),
        'category'         => $_POST['category']         ?? 'residential',
        'purpose'          => $_POST['purpose']          ?? 'sale',
        'listing_type'     => $_POST['listing_type']     ?? '',
        'project_id'       => (int)($_POST['project_id'] ?? 0) ?: null,
        'agent_id'         => (int)($_POST['agent_id']   ?? 0) ?: null,
        'price'            => preg_replace('/[^0-9.]/', '', $_POST['price'] ?? ''),
        'price_on_demand'  => isset($_POST['price_on_demand']) ? 1 : 0,
        'city'             => $_POST['city']              ?? 'islamabad',
        'area_locality'    => trim($_POST['area_locality'] ?? ''),
        'address_line'     => trim($_POST['address']      ?? ''),
        'lat'              => trim($_POST['latitude']     ?? '') ?: null,
        'lng'              => trim($_POST['longitude']    ?? '') ?: null,
        'area_value'       => trim($_POST['area_value']   ?? ''),
        'area_unit'        => $_POST['area_unit']         ?? 'marla',
        'bedrooms'         => (int)($_POST['bedrooms']    ?? 0),
        'bathrooms'        => (int)($_POST['bathrooms']   ?? 0),
        'possession_status'=> $_POST['possession_status'] ?? 'available',
        'description'      => trim($_POST['description']  ?? ''),
        'is_published'     => isset($_POST['is_published']) ? 1 : 0,
        'is_featured'      => isset($_POST['is_featured'])  ? 1 : 0,
    ];
    // Accept any slug that matches the storage pattern. Admins are the only
    // submitters and can only check options the form rendered for them, so
    // pattern validation is enough; using array_intersect here would silently
    // drop pre-existing slugs that have since been deactivated.
    $features = [];
    foreach ((array)($_POST['features'] ?? []) as $slug) {
        $slug = trim((string)$slug);
        if ($slug !== '' && preg_match('/^[a-z0-9_]+$/', $slug)) {
            $features[] = $slug;
        }
    }
    $features = array_values(array_unique($features));

    // Validation
    if ($fields['title'] === '') $formErrors[] = 'Title is required.';
    if ($fields['area_value'] === '') $formErrors[] = 'Area value is required.';
    if (!$fields['price_on_demand'] && ($fields['price'] === '' || (float)$fields['price'] <= 0)) {
        $formErrors[] = 'Price is required (or check Price on Demand).';
    }

    if (empty($formErrors)) {
        $fields['features']    = json_encode(array_values($features));
        $fields['price']       = $fields['price_on_demand'] ? 0 : (float)$fields['price'];
        $fields['updated_at']  = date('Y-m-d H:i:s');

        try {
            if ($isEdit) {
                $setClauses = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
                $stmt = $db->prepare("UPDATE properties SET $setClauses WHERE id = ?");
                $stmt->execute([...array_values($fields), $id]);
                $propId = $id;
                auditLog('update', 'properties', $id, 'Updated listing: ' . $fields['title']);
                $flashMsg = 'Listing updated successfully.';
            } else {
                $fields['created_at'] = date('Y-m-d H:i:s');
                $fields['views_count'] = 0;
                $columns = implode(', ', array_keys($fields));
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $stmt = $db->prepare("INSERT INTO properties ($columns) VALUES ($placeholders)");
                $stmt->execute(array_values($fields));
                $propId = (int)$db->lastInsertId();
                auditLog('create', 'properties', $propId, 'Created listing: ' . $fields['title']);
                $flashMsg = 'Listing created successfully.';
            }

            // Handle photo uploads
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = __DIR__ . '/../assets/uploads/properties/' . $propId . '/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $sortOrder = $isEdit ? (int)($db->query("SELECT MAX(sort_order) FROM property_media WHERE property_id=$propId")->fetchColumn()) : 0;

                foreach ($_FILES['photos']['error'] as $idx => $err) {
                    if ($err !== UPLOAD_ERR_OK) continue;
                    $tmpName = $_FILES['photos']['tmp_name'][$idx];
                    $origName= $_FILES['photos']['name'][$idx];
                    $mime    = mime_content_type($tmpName);
                    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) continue;
                    if ($_FILES['photos']['size'][$idx] > MAX_FILE_SIZE) continue;

                    $ext  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $fname= 'photo_' . uniqid() . '.' . $ext;
                    $localPath = $uploadDir . $fname;
                    if (move_uploaded_file($tmpName, $localPath)) {
                        $sortOrder++;
                        $publicUrl = '/assets/uploads/properties/' . $propId . '/' . $fname;
                        $db->prepare(
                            'INSERT INTO property_media (property_id, url, thumbnail_url, alt_text, kind, sort_order)
                             VALUES (?, ?, ?, ?, "image", ?)'
                        )->execute([$propId, $publicUrl, $publicUrl, $origName, $sortOrder]);
                    }
                }
            }

            // Delete individual photos
            if (!empty($_POST['delete_photo'])) {
                foreach ((array)$_POST['delete_photo'] as $photoId) {
                    $photoId = (int)$photoId;
                    $pRow = $db->prepare('SELECT url FROM property_media WHERE id=? AND property_id=?');
                    $pRow->execute([$photoId, $propId]);
                    $pData = $pRow->fetch();
                    if ($pData) {
                        // Only unlink if it's a local file (not external URL)
                        if (strpos($pData['url'], '/assets/uploads/') === 0) {
                            $fullPath = __DIR__ . '/..' . $pData['url'];
                            if (file_exists($fullPath)) @unlink($fullPath);
                        }
                        $db->prepare('DELETE FROM property_media WHERE id=?')->execute([$photoId]);
                    }
                }
            }

            setFlash('success', $flashMsg);
            redirect('/admin/listings.php');
        } catch(Exception $e) {
            $formErrors[] = 'Database error: ' . $e->getMessage();
        }
    }
    // Repopulate data on error
    foreach ($fields as $k => $v) { if (array_key_exists($k, $data)) $data[$k] = $v; }
    $data['features'] = $features;
}

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1>
      <i class="fa-solid <?= $isEdit?'fa-pen-to-square':'fa-plus-circle' ?> me-2" style="color:var(--gold)"></i>
      <?= $isEdit ? 'Edit Listing' : 'New Listing' ?>
    </h1>
    <?php if ($isEdit): ?>
      <p class="text-muted mb-0 fs-13">Editing: <strong><?= htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>
  </div>
  <a href="<?= BASE_PATH ?>/admin/listings.php" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Listings
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

<!-- Step Indicator -->
<div class="step-indicator mb-4">
  <div class="d-flex flex-column align-items-center" style="flex:1;max-width:160px;">
    <div class="step-num active" id="stepNum1">1</div>
    <div class="step-label" id="stepLbl1">Basics</div>
  </div>
  <div class="step-line" id="stepLine1"></div>
  <div class="d-flex flex-column align-items-center" style="flex:1;max-width:160px;">
    <div class="step-num" id="stepNum2">2</div>
    <div class="step-label" id="stepLbl2">Location</div>
  </div>
  <div class="step-line" id="stepLine2"></div>
  <div class="d-flex flex-column align-items-center" style="flex:1;max-width:160px;">
    <div class="step-num" id="stepNum3">3</div>
    <div class="step-label" id="stepLbl3">Specs</div>
  </div>
  <div class="step-line" id="stepLine3"></div>
  <div class="d-flex flex-column align-items-center" style="flex:1;max-width:160px;">
    <div class="step-num" id="stepNum4">4</div>
    <div class="step-label" id="stepLbl4">Photos</div>
  </div>
  <div class="step-line" id="stepLine4"></div>
  <div class="d-flex flex-column align-items-center" style="flex:1;max-width:160px;">
    <div class="step-num" id="stepNum5">5</div>
    <div class="step-label" id="stepLbl5">Review</div>
  </div>
</div>

<form method="POST" enctype="multipart/form-data" id="listingForm" action="<?= BASE_PATH ?>/admin/listing-form.php<?= $isEdit?"?id=$id":'' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

  <!-- ── Step 1: Basics ────────────────────────────────────── -->
  <div class="step-panel" id="panel1">
    <div class="form-section-card">
      <div class="card-header">
        <i class="fa-solid fa-circle-info" style="color:var(--gold)"></i> Basic Information
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-600">Listing Title *</label>
            <input type="text" name="title" class="form-control" required maxlength="200"
                   placeholder="e.g. Beautiful 5 Marla House in Bahria Town Phase 8"
                   value="<?= htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Category *</label>
            <div class="d-flex flex-wrap gap-2" id="categoryGroup">
              <?php foreach (['residential'=>'Residential','commercial'=>'Commercial','plot'=>'Plot'] as $val=>$lbl): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="category"
                       id="cat_<?= $val ?>" value="<?= $val ?>"
                       <?= $data['category']===$val?'checked':'' ?>>
                <label class="form-check-label" for="cat_<?= $val ?>"><?= $lbl ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Purpose *</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="purpose"
                       id="purposeSale" value="sale" <?= $data['purpose']==='sale'?'checked':'' ?>>
                <label class="form-check-label" for="purposeSale">For Sale</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="purpose"
                       id="purposeRent" value="rent" <?= $data['purpose']==='rent'?'checked':'' ?>>
                <label class="form-check-label" for="purposeRent">For Rent</label>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Listing Type *</label>
            <select name="listing_type" id="listingTypeSelect" class="form-select" required>
              <option value="">— Select type —</option>
              <?php foreach ($listingTypes as $cat => $types): ?>
                <optgroup label="<?= ucfirst($cat) ?>" data-cat="<?= $cat ?>">
                  <?php foreach ($types as $val => $lbl): ?>
                    <option value="<?= $val ?>" data-cat="<?= $cat ?>"
                            <?= $data['listing_type']===$val?'selected':'' ?>><?= $lbl ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Project (Optional)</label>
            <select name="project_id" class="form-select">
              <option value="">— No Project —</option>
              <?php foreach ($projects as $proj): ?>
                <option value="<?= (int)$proj['id'] ?>" <?= (int)$data['project_id']===(int)$proj['id']?'selected':'' ?>>
                  <?= htmlspecialchars($proj['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Assign Agent</label>
            <select name="agent_id" class="form-select">
              <option value="">— Unassigned —</option>
              <?php foreach ($agents as $agent): ?>
                <option value="<?= (int)$agent['id'] ?>" <?= (int)$data['agent_id']===(int)$agent['id']?'selected':'' ?>>
                  <?= htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <div class="row g-3 align-items-end">
              <div class="col-auto">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="priceOnDemand"
                         name="price_on_demand" value="1"
                         <?= $data['price_on_demand']?'checked':'' ?>>
                  <label class="form-check-label" for="priceOnDemand">Price on Demand</label>
                </div>
              </div>
              <div class="col-12 col-md-4" id="priceField">
                <label class="form-label fw-600">Price (PKR) *</label>
                <input type="number" name="price" class="form-control" id="priceInput"
                       min="0" step="1000"
                       value="<?= htmlspecialchars($data['price'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="e.g. 8500000">
              </div>
              <div class="col-12 col-md-3" id="rentPeriodField" style="display:<?= $data['purpose']==='rent'?'block':'none' ?>;">
                <label class="form-label fw-600">Rent Period</label>
                <select name="rent_period" class="form-select">
                  <option value="monthly"  <?= $data['rent_period']==='monthly'?'selected':'' ?>>Monthly</option>
                  <option value="yearly"   <?= $data['rent_period']==='yearly'?'selected':'' ?>>Yearly</option>
                </select>
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label fw-600">Description</label>
            <textarea name="description" class="form-control" rows="4"
                      placeholder="Describe the property in detail..."><?= htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-end">
      <button type="button" class="btn btn-gold btn-next" data-next="2">
        Next: Location <i class="fa-solid fa-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- ── Step 2: Location ──────────────────────────────────── -->
  <div class="step-panel d-none" id="panel2">
    <div class="form-section-card">
      <div class="card-header">
        <i class="fa-solid fa-map-location-dot" style="color:var(--gold)"></i> Location Details
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">City *</label>
            <select name="city" class="form-select" required>
              <?php foreach (['islamabad'=>'Islamabad','rawalpindi'=>'Rawalpindi','lahore'=>'Lahore','karachi'=>'Karachi','peshawar'=>'Peshawar','multan'=>'Multan','faisalabad'=>'Faisalabad','quetta'=>'Quetta'] as $val=>$lbl): ?>
                <option value="<?= $val ?>" <?= $data['city']===$val?'selected':'' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Area / Locality *</label>
            <input type="text" name="area_locality" class="form-control" required
                   placeholder="e.g. Bahria Town Phase 8"
                   value="<?= htmlspecialchars($data['area_locality'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-600">Full Address</label>
            <input type="text" name="address" class="form-control"
                   placeholder="Street, Block, Sector..."
                   value="<?= htmlspecialchars($data['address_line'] ?? $data['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Latitude</label>
            <input type="number" name="latitude" class="form-control" step="any"
                   placeholder="33.6844"
                   value="<?= htmlspecialchars($data['lat'] ?? $data['latitude'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Longitude</label>
            <input type="number" name="longitude" class="form-control" step="any"
                   placeholder="73.0479"
                   value="<?= htmlspecialchars($data['lng'] ?? $data['longitude'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <button type="button" class="btn btn-outline-secondary btn-prev" data-prev="1">
        <i class="fa-solid fa-arrow-left me-1"></i> Previous
      </button>
      <button type="button" class="btn btn-gold btn-next" data-next="3">
        Next: Specs <i class="fa-solid fa-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- ── Step 3: Specs ─────────────────────────────────────── -->
  <div class="step-panel d-none" id="panel3">
    <div class="form-section-card">
      <div class="card-header">
        <i class="fa-solid fa-ruler-combined" style="color:var(--gold)"></i> Property Specifications
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Area Value *</label>
            <input type="number" name="area_value" class="form-control" step="0.01" min="0" required
                   placeholder="e.g. 10"
                   value="<?= htmlspecialchars($data['area_value'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Area Unit *</label>
            <select name="area_unit" class="form-select" required>
              <?php foreach (['marla'=>'Marla','kanal'=>'Kanal','sq_ft'=>'Sq Ft','sq_yard'=>'Sq Yard','acre'=>'Acre'] as $val=>$lbl): ?>
                <option value="<?= $val ?>" <?= $data['area_unit']===$val?'selected':'' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Possession Status</label>
            <select name="possession_status" class="form-select">
              <?php foreach (['available'=>'Available','under_construction'=>'Under Construction','ready'=>'Ready to Move','on_possession'=>'On Possession'] as $val=>$lbl): ?>
                <option value="<?= $val ?>" <?= $data['possession_status']===$val?'selected':'' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Bedrooms</label>
            <select name="bedrooms" class="form-select">
              <?php for ($i=0;$i<=10;$i++): ?>
                <option value="<?= $i ?>" <?= (int)$data['bedrooms']===$i?'selected':'' ?>>
                  <?= $i === 0 ? 'Studio / N/A' : $i ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Bathrooms</label>
            <select name="bathrooms" class="form-select">
              <?php for ($i=0;$i<=10;$i++): ?>
                <option value="<?= $i ?>" <?= (int)$data['bathrooms']===$i?'selected':'' ?>>
                  <?= $i === 0 ? 'N/A' : $i ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label fw-600 d-flex align-items-center justify-content-between">
              <span>Features &amp; Amenities</span>
              <?php if (in_array($_SESSION['admin_role'] ?? '', ['admin','super_admin'], true)): ?>
              <a href="<?= BASE_PATH ?>/admin/features.php" class="fs-12 text-muted text-decoration-none" target="_blank" rel="noopener">
                <i class="fa-solid fa-gear me-1"></i>Manage list
              </a>
              <?php endif; ?>
            </label>
            <?php if (empty($allFeatures)): ?>
              <div class="alert alert-warning fs-13 mb-0">
                No active features defined.
                <?php if (in_array($_SESSION['admin_role'] ?? '', ['admin','super_admin'], true)): ?>
                  <a href="<?= BASE_PATH ?>/admin/features.php">Add some</a> to make them selectable here.
                <?php endif; ?>
              </div>
            <?php else:
              // Always include any slug already on this listing, even if its
              // feature row has since been deactivated, so the admin sees and
              // can choose to keep or remove it.
              $renderSlugs = $allFeatures;
              foreach ((array)$data['features'] as $existingSlug) {
                  if (!in_array($existingSlug, $renderSlugs, true)) $renderSlugs[] = $existingSlug;
              }
            ?>
            <div class="feature-grid">
              <?php foreach ($renderSlugs as $feat):
                $featLabel  = $featureLabels[$feat] ?? ucwords(str_replace('_',' ',$feat));
                $isInactive = !in_array($feat, $allFeatures, true);
              ?>
              <label class="feature-check-item" <?= $isInactive ? 'title="This feature is inactive — uncheck to remove from this listing."' : '' ?>>
                <input type="checkbox" name="features[]" value="<?= htmlspecialchars($feat, ENT_QUOTES, 'UTF-8') ?>"
                       <?= in_array($feat, (array)$data['features'])?'checked':'' ?>>
                <span>
                  <?= htmlspecialchars($featLabel, ENT_QUOTES, 'UTF-8') ?>
                  <?php if ($isInactive): ?><small class="text-muted">(inactive)</small><?php endif; ?>
                </span>
              </label>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <button type="button" class="btn btn-outline-secondary btn-prev" data-prev="2">
        <i class="fa-solid fa-arrow-left me-1"></i> Previous
      </button>
      <button type="button" class="btn btn-gold btn-next" data-next="4">
        Next: Photos <i class="fa-solid fa-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- ── Step 4: Photos ────────────────────────────────────── -->
  <div class="step-panel d-none" id="panel4">
    <div class="form-section-card">
      <div class="card-header">
        <i class="fa-solid fa-images" style="color:var(--gold)"></i> Property Photos
      </div>
      <div class="card-body">
        <!-- Existing photos -->
        <?php if (!empty($existingPhotos)): ?>
        <div class="mb-3">
          <label class="form-label fw-600">Current Photos</label>
          <div class="photo-preview-grid" id="existingPhotoGrid">
            <?php foreach ($existingPhotos as $photo): ?>
            <div class="photo-preview-item" id="existing_<?= (int)$photo['id'] ?>">
              <img src="<?= htmlspecialchars(mediaUrl($photo['url'] ?? $photo['file_path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   alt="<?= htmlspecialchars($photo['alt_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
              <button type="button" class="btn-remove-photo"
                      onclick="removeExistingPhoto(<?= (int)$photo['id'] ?>, this)">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <input type="hidden" class="delete-photo-input" name="" value="">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <hr>
        <?php endif; ?>

        <!-- New uploads -->
        <label class="form-label fw-600">Upload New Photos <span class="text-muted fw-normal fs-12">(Max 20, 5MB each, JPEG/PNG/WebP)</span></label>
        <div class="drop-zone" id="dropZone">
          <i class="fa-solid fa-cloud-arrow-up d-block mb-2"></i>
          <p class="mb-1">Drag & drop photos here or <strong>click to browse</strong></p>
          <small>Supported: JPEG, PNG, WebP — Max 5MB per file</small>
          <input type="file" id="photoInput" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="d-none">
        </div>
        <div class="photo-preview-grid mt-3" id="newPhotoGrid"></div>
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <button type="button" class="btn btn-outline-secondary btn-prev" data-prev="3">
        <i class="fa-solid fa-arrow-left me-1"></i> Previous
      </button>
      <button type="button" class="btn btn-gold btn-next" data-next="5">
        Review <i class="fa-solid fa-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- ── Step 5: Review & Submit ───────────────────────────── -->
  <div class="step-panel d-none" id="panel5">
    <div class="form-section-card">
      <div class="card-header">
        <i class="fa-solid fa-clipboard-check" style="color:var(--gold)"></i> Review & Publish
      </div>
      <div class="card-body">
        <div class="row g-3 mb-4">
          <div class="col-12">
            <div class="alert alert-info d-flex gap-2">
              <i class="fa-solid fa-circle-info mt-1"></i>
              <span>Review your listing details below before publishing. You can go back to any step to make changes.</span>
            </div>
          </div>
          <div class="col-12">
            <table class="table table-bordered fs-13">
              <tbody>
                <tr><th style="width:35%;background:#f8f9fa;">Title</th><td id="rev_title">—</td></tr>
                <tr><th style="background:#f8f9fa;">Category</th><td id="rev_category">—</td></tr>
                <tr><th style="background:#f8f9fa;">Purpose</th><td id="rev_purpose">—</td></tr>
                <tr><th style="background:#f8f9fa;">City</th><td id="rev_city">—</td></tr>
                <tr><th style="background:#f8f9fa;">Locality</th><td id="rev_locality">—</td></tr>
                <tr><th style="background:#f8f9fa;">Price</th><td id="rev_price">—</td></tr>
                <tr><th style="background:#f8f9fa;">Area</th><td id="rev_area">—</td></tr>
                <tr><th style="background:#f8f9fa;">Bedrooms / Baths</th><td id="rev_beds">—</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Publish settings -->
        <div class="row g-3 border-top pt-3">
          <div class="col-12">
            <label class="form-label fw-600">Publication Settings</label>
          </div>
          <div class="col-auto">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="isPublished" name="is_published"
                     value="1" <?= $data['is_published']?'checked':'' ?>>
              <label class="form-check-label" for="isPublished">Publish immediately</label>
            </div>
          </div>
          <div class="col-auto">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="isFeatured" name="is_featured"
                     value="1" <?= $data['is_featured']?'checked':'' ?>>
              <label class="form-check-label" for="isFeatured">Set as Featured</label>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-between gap-2 flex-wrap">
      <button type="button" class="btn btn-outline-secondary btn-prev" data-prev="4">
        <i class="fa-solid fa-arrow-left me-1"></i> Previous
      </button>
      <div class="d-flex gap-2">
        <button type="submit" name="submit_action" value="draft" class="btn btn-outline-dark">
          <i class="fa-regular fa-floppy-disk me-1"></i> Save as Draft
        </button>
        <button type="submit" name="submit_action" value="publish" class="btn btn-gold">
          <i class="fa-solid fa-paper-plane me-1"></i>
          <?= $isEdit ? 'Update Listing' : 'Create Listing' ?>
        </button>
      </div>
    </div>
  </div>

</form>

<script>
// ── Step Navigation ────────────────────────────────────────
var currentStep = 1;
var totalSteps  = 5;

function goToStep(step) {
  // Hide all panels
  for (var i = 1; i <= totalSteps; i++) {
    var panel = document.getElementById('panel' + i);
    var num   = document.getElementById('stepNum' + i);
    var lbl   = document.getElementById('stepLbl' + i);
    var line  = document.getElementById('stepLine' + i);
    if (panel) panel.classList.add('d-none');
    if (num) {
      num.classList.remove('active','completed');
      if (i < step) num.classList.add('completed');
      if (i === step) num.classList.add('active');
    }
    if (line && i < totalSteps) {
      if (line) line.style.background = i < step ? 'var(--sidebar-bg)' : '#dee2e6';
    }
  }
  var target = document.getElementById('panel' + step);
  if (target) target.classList.remove('d-none');
  currentStep = step;

  if (step === 5) populateReview();

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Next buttons
document.querySelectorAll('.btn-next').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var next = parseInt(this.getAttribute('data-next'));
    goToStep(next);
  });
});

// Prev buttons
document.querySelectorAll('.btn-prev').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var prev = parseInt(this.getAttribute('data-prev'));
    goToStep(prev);
  });
});

// Draft submit sets is_published to 0
document.querySelector('[name="submit_action"][value="draft"]')?.addEventListener('click', function() {
  document.getElementById('isPublished').checked = false;
});

// ── Populate Review Step ───────────────────────────────────
function populateReview() {
  document.getElementById('rev_title').textContent    = document.querySelector('[name="title"]').value || '—';
  document.getElementById('rev_category').textContent = document.querySelector('[name="category"]:checked')?.value || '—';
  document.getElementById('rev_purpose').textContent  = document.querySelector('[name="purpose"]:checked')?.value || '—';
  document.getElementById('rev_city').textContent     = document.querySelector('[name="city"]').options[document.querySelector('[name="city"]').selectedIndex]?.text || '—';
  document.getElementById('rev_locality').textContent = document.querySelector('[name="area_locality"]').value || '—';
  var pod = document.getElementById('priceOnDemand').checked;
  document.getElementById('rev_price').textContent    = pod ? 'Price on Demand' : (document.querySelector('[name="price"]').value || '—') + ' PKR';
  var av = document.querySelector('[name="area_value"]').value;
  var au = document.querySelector('[name="area_unit"]').options[document.querySelector('[name="area_unit"]').selectedIndex]?.text;
  document.getElementById('rev_area').textContent     = av ? (av + ' ' + (au||'')) : '—';
  var beds  = document.querySelector('[name="bedrooms"]').value;
  var baths = document.querySelector('[name="bathrooms"]').value;
  document.getElementById('rev_beds').textContent     = beds + ' bed / ' + baths + ' bath';
}

// ── Dynamic listing type based on category ─────────────────
var listingTypes = <?= json_encode($listingTypes) ?>;
function updateListingTypes(cat) {
  var sel = document.getElementById('listingTypeSelect');
  var curr = sel.value;
  // Remove all optgroups/options except placeholder
  while (sel.options.length > 1) sel.remove(1);
  var types = listingTypes[cat] || {};
  for (var val in types) {
    var opt = new Option(types[val], val);
    sel.add(opt);
  }
  if (types[curr]) sel.value = curr;
}

document.querySelectorAll('[name="category"]').forEach(function(radio) {
  radio.addEventListener('change', function() { updateListingTypes(this.value); });
});
updateListingTypes(document.querySelector('[name="category"]:checked')?.value || 'residential');

// ── Price on Demand toggle ─────────────────────────────────
document.getElementById('priceOnDemand').addEventListener('change', function() {
  var priceInp = document.getElementById('priceInput');
  priceInp.disabled = this.checked;
  priceInp.required = !this.checked;
  document.getElementById('priceField').style.opacity = this.checked ? '0.4' : '1';
});
if (document.getElementById('priceOnDemand').checked) {
  document.getElementById('priceInput').disabled = true;
  document.getElementById('priceInput').required = false;
  document.getElementById('priceField').style.opacity = '0.4';
}

// ── Rent period toggle ─────────────────────────────────────
document.querySelectorAll('[name="purpose"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.getElementById('rentPeriodField').style.display = this.value === 'rent' ? 'block' : 'none';
  });
});

// ── Photo upload drag & drop ───────────────────────────────
var dropZone  = document.getElementById('dropZone');
var photoInput= document.getElementById('photoInput');
var previewGrid = document.getElementById('newPhotoGrid');
var deletedPhotoIds = [];

dropZone.addEventListener('click', function() { photoInput.click(); });
dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', function()  { dropZone.classList.remove('dragover'); });
dropZone.addEventListener('drop', function(e) {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  addFiles(e.dataTransfer.files);
});
photoInput.addEventListener('change', function() { addFiles(this.files); });

function addFiles(files) {
  var existingCount = document.querySelectorAll('#existingPhotoGrid .photo-preview-item:not([style*="display:none"])').length;
  var newCount = previewGrid.children.length;
  Array.from(files).forEach(function(file) {
    if (existingCount + newCount >= 20) return;
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) return;
    if (file.size > 5*1024*1024) return;
    var reader = new FileReader();
    reader.onload = function(e) {
      var div = document.createElement('div');
      div.className = 'photo-preview-item';
      div.innerHTML = '<img src="' + e.target.result + '" alt=""><button type="button" class="btn-remove-photo" onclick="this.closest(\'.photo-preview-item\').remove()"><i class="fa-solid fa-xmark"></i></button>';
      previewGrid.appendChild(div);
    };
    reader.readAsDataURL(file);
    newCount++;
  });
}

function removeExistingPhoto(photoId, btn) {
  var container = btn.closest('.photo-preview-item');
  container.style.opacity = '0.3';
  // Add hidden input for deletion
  var inp = document.createElement('input');
  inp.type = 'hidden';
  inp.name = 'delete_photo[]';
  inp.value = photoId;
  document.getElementById('listingForm').appendChild(inp);
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-check"></i>';
}
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
