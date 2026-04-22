<?php
/**
 * Al-Riaz Associates — Commercial Property Listings
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Commercial Properties for Sale & Rent';
$metaDesc  = 'Browse commercial properties for sale and rent in Islamabad, Rawalpindi, Lahore and Karachi. Shops, offices, warehouses, showrooms and more.';

// ─── Input Sanitisation ────────────────────────────────────────────────────
$purpose   = in_array($_GET['purpose'] ?? '', ['sale', 'rent']) ? $_GET['purpose'] : '';
$city      = trim($_GET['city']      ?? '');
$type      = trim($_GET['type']      ?? '');
$minPrice  = (int)($_GET['min_price'] ?? 0);
$maxPrice  = (int)($_GET['max_price'] ?? 0);
$minArea   = (float)($_GET['min_area'] ?? 0);
$maxArea   = (float)($_GET['max_area'] ?? 0);
$areaUnit  = in_array($_GET['area_unit'] ?? '', ['marla','kanal','sq_ft','sq_yard','acre'])
             ? $_GET['area_unit'] : 'sq_ft';
$floor     = (int)($_GET['floor'] ?? 0);
$sort      = in_array($_GET['sort'] ?? '', ['newest','price_asc','price_desc','area'])
             ? $_GET['sort'] : 'newest';
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 12;
$offset    = ($page - 1) * $limit;

// ─── DB ───────────────────────────────────────────────────────────────────
$db = Database::getInstance();

// Cities
$knownCities = ['Islamabad', 'Rawalpindi', 'Lahore', 'Karachi'];
try {
    $citiesStmt = $db->query("SELECT DISTINCT city FROM properties WHERE is_published = 1 AND category = 'commercial' ORDER BY city");
    $dbCities   = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $allCities  = array_unique(array_merge($knownCities, $dbCities));
    sort($allCities);
} catch (Exception $e) {
    $allCities = $knownCities;
}

// ─── Build WHERE ──────────────────────────────────────────────────────────
$where  = ["p.is_published = 1", "p.category = 'commercial'"];
$params = [];

if ($purpose !== '') {
    $where[]  = "p.purpose = ?";
    $params[] = $purpose;
}
if ($city !== '') {
    $where[]  = "p.city = ?";
    $params[] = $city;
}
if ($type !== '') {
    $where[]  = "p.listing_type = ?";
    $params[] = $type;
}
if ($minPrice > 0) {
    $where[]  = "p.price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $where[]  = "p.price <= ?";
    $params[] = $maxPrice;
}
if ($minArea > 0) {
    $where[]  = "p.area_value >= ?";
    $params[] = $minArea;
}
if ($maxArea > 0) {
    $where[]  = "p.area_value <= ?";
    $params[] = $maxArea;
}
if ($areaUnit !== '' && ($minArea > 0 || $maxArea > 0)) {
    $where[]  = "p.area_unit = ?";
    $params[] = $areaUnit;
}
if ($floor > 0) {
    $where[]  = "p.floor_number = ?";
    $params[] = $floor;
}

$whereSQL = implode(' AND ', $where);

$orderMap = [
    'newest'     => 'p.published_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'area'       => 'p.area_value DESC',
];
$orderSQL = $orderMap[$sort];

// Count
try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM properties p WHERE $whereSQL");
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    error_log('[commercial.php] count error: ' . $e->getMessage());
    $totalCount = 0;
}

$totalPages = $totalCount > 0 ? (int)ceil($totalCount / $limit) : 1;

// Fetch
$properties = [];
try {
    $sql = "
        SELECT
            p.id, p.slug, p.title, p.city, p.area_locality, p.listing_type,
            p.purpose, p.category, p.price, p.price_on_demand,
            p.area_value, p.area_unit, p.floor_number,
            p.is_featured, p.published_at,
            u.name AS agent_name, u.phone AS agent_phone,
            pm.url AS thumbnail
        FROM properties p
        LEFT JOIN users u ON u.id = p.agent_id
        LEFT JOIN property_media pm
               ON pm.property_id = p.id
              AND pm.kind = 'image'
              AND pm.sort_order = 0
        WHERE $whereSQL
        ORDER BY p.is_featured DESC, $orderSQL
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[commercial.php] fetch error: ' . $e->getMessage());
    $properties = [];
}

function pageURL(int $p): string
{
    $q = $_GET;
    $q['page'] = $p;
    return (defined('BASE_PATH') ? BASE_PATH : '') . '/commercial.php?' . http_build_query($q);
}

$showFrom = $totalCount > 0 ? $offset + 1 : 0;
$showTo   = min($offset + $limit, $totalCount);

$commTypes = [
    'shop'             => 'Shop',
    'office'           => 'Office',
    'warehouse'        => 'Warehouse',
    'showroom'         => 'Showroom',
    'building'         => 'Building',
    'factory'          => 'Factory',
    'commercial_plot'  => 'Commercial Plot',
];

$b = defined('BASE_PATH') ? BASE_PATH : '';
$waPhone = SITE_WHATSAPP;

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([
            ['label' => 'Home', 'url' => $b . '/'],
            ['label' => 'Commercial']
        ]) ?>
        <h1 class="page-header-title">Commercial Properties</h1>
        <p class="page-header-sub">
            <?php if ($totalCount > 0): ?>
                <strong><?= number_format($totalCount) ?></strong> commercial properties found
            <?php else: ?>
                No commercial properties found
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container py-4">

    <!-- Purpose Pills -->
    <div class="purpose-pill-group mb-4">
        <a href="<?= $b ?>/commercial.php?purpose=sale" class="purpose-pill <?= $purpose === 'sale' ? 'active' : '' ?>">For Sale</a>
        <a href="<?= $b ?>/commercial.php?purpose=rent" class="purpose-pill <?= $purpose === 'rent' ? 'active' : '' ?>">For Rent</a>
        <a href="<?= $b ?>/commercial.php" class="purpose-pill <?= $purpose === '' ? 'active' : '' ?>">All</a>
    </div>

    <!-- Mobile: filter button -->
    <div class="d-lg-none mb-3">
        <button class="btn-navy w-100" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas"
                style="display:flex;align-items:center;justify-content:center;gap:.5rem;">
            <i class="fa-solid fa-sliders me-2"></i>Show Filters
        </button>
    </div>

    <div class="row g-4">

        <!-- ── LEFT: Filter Sidebar ──────────────────────────────────────── -->
        <div class="col-lg-3 d-none d-lg-block">
            <form method="GET" action="<?= $b ?>/commercial.php" id="filterForm" class="filter-sidebar">

                <input type="hidden" name="category" value="commercial">

                <!-- Purpose -->
                <div class="filter-group">
                    <label class="filter-label">Purpose</label>
                    <div class="d-flex gap-2">
                        <label class="flex-fill">
                            <input type="radio" name="purpose" value="sale" class="btn-check"
                                   id="purposeSale" <?= ($purpose === '' || $purpose === 'sale') ? 'checked' : '' ?>>
                            <span class="btn btn-sm btn-outline-secondary w-100 d-block">For Sale</span>
                        </label>
                        <label class="flex-fill">
                            <input type="radio" name="purpose" value="rent" class="btn-check"
                                   id="purposeRent" <?= $purpose === 'rent' ? 'checked' : '' ?>>
                            <span class="btn btn-sm btn-outline-secondary w-100 d-block">For Rent</span>
                        </label>
                    </div>
                </div>

                <!-- City -->
                <div class="filter-group">
                    <label class="filter-label">City</label>
                    <select name="city" class="filter-select">
                        <option value="">All Cities</option>
                        <?php foreach ($allCities as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Commercial Type -->
                <div class="filter-group">
                    <label class="filter-label">Property Type</label>
                    <?php foreach ($commTypes as $val => $label): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="type[]"
                                   value="<?= htmlspecialchars($val) ?>" id="ctype_<?= $val ?>"
                                   <?= (is_array($_GET['type'] ?? null) && in_array($val, (array)$_GET['type'])) || $type === $val ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="ctype_<?= $val ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <label class="filter-label">Price Range (PKR)</label>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="priceMinLabel">Any</span>
                        <span id="priceMaxLabel">Any</span>
                    </div>
                    <input type="range" class="form-range mb-2" id="priceMinSlider"
                           min="0" max="500000000" step="1000000" value="<?= $minPrice ?>">
                    <input type="range" class="form-range mb-2" id="priceMaxSlider"
                           min="0" max="500000000" step="1000000" value="<?= $maxPrice ?: 500000000 ?>">
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" class="filter-input" id="priceMin"
                                   name="min_price" placeholder="Min" value="<?= $minPrice ?: '' ?>">
                        </div>
                        <div class="col-6">
                            <input type="number" class="filter-input" id="priceMax"
                                   name="max_price" placeholder="Max" value="<?= $maxPrice ?: '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Area Range -->
                <div class="filter-group">
                    <label class="filter-label">Area</label>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="areaMinLabel">Any</span>
                        <span id="areaMaxLabel">Any</span>
                    </div>
                    <input type="range" class="form-range mb-2" id="areaMinSlider"
                           min="0" max="50000" step="100" value="<?= $minArea ?>">
                    <input type="range" class="form-range mb-2" id="areaMaxSlider"
                           min="0" max="50000" step="100" value="<?= $maxArea ?: 50000 ?>">
                    <div class="row g-2">
                        <div class="col-4">
                            <select name="area_unit" class="filter-select" id="areaUnit">
                                <option value="sq_ft"   <?= $areaUnit === 'sq_ft'   ? 'selected' : '' ?>>Sq Ft</option>
                                <option value="sq_yard" <?= $areaUnit === 'sq_yard' ? 'selected' : '' ?>>Sq Yd</option>
                                <option value="marla"   <?= $areaUnit === 'marla'   ? 'selected' : '' ?>>Marla</option>
                                <option value="kanal"   <?= $areaUnit === 'kanal'   ? 'selected' : '' ?>>Kanal</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <input type="number" class="filter-input" id="areaMin"
                                   name="min_area" placeholder="Min" value="<?= $minArea ?: '' ?>">
                        </div>
                        <div class="col-4">
                            <input type="number" class="filter-input" id="areaMax"
                                   name="max_area" placeholder="Max" value="<?= $maxArea ?: '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Floor Number -->
                <div class="filter-group">
                    <label class="filter-label">Floor Number</label>
                    <input type="number" class="filter-input" name="floor"
                           placeholder="e.g. 2" min="0" max="50" value="<?= $floor ?: '' ?>">
                </div>

                <!-- Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" class="filter-apply-btn">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Apply Filters
                    </button>
                    <button type="button" id="resetFiltersBtn" class="btn-outline-navy btn-sm">
                        <i class="fa-solid fa-rotate-right me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- ── RIGHT: Results ─────────────────────────────────────────────── -->
        <div class="col-lg-9">

            <!-- Results bar -->
            <div class="results-bar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <span class="text-muted small">Showing <strong><?= $showFrom ?>–<?= $showTo ?></strong> of <strong><?= number_format($totalCount) ?></strong> properties</span>
                <div class="d-flex align-items-center gap-2">
                    <select id="sortSelect" class="sort-select">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price ↑</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
                        <option value="area" <?= $sort === 'area' ? 'selected' : '' ?>>Largest Area</option>
                    </select>
                    <button id="viewGrid" class="view-toggle-btn active" title="Grid"><i class="fa-solid fa-grip"></i></button>
                    <button id="viewList" class="view-toggle-btn" title="List"><i class="fa-solid fa-list"></i></button>
                </div>
            </div>

            <!-- Active filter chips -->
            <div id="activeFilterChips" class="mb-3 d-none"></div>

            <!-- Property grid -->
            <?php if (!empty($properties)): ?>
                <div class="row g-3" id="propertiesGrid">
                    <?php foreach ($properties as $i => $prop): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="pagination-nav mt-4" aria-label="Property pages">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= pageURL($page - 1) ?>"><i class="fa-solid fa-chevron-left"></i></a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li>
                        <?php endif; ?>

                        <?php
                        $range = 2;
                        for ($p = 1; $p <= $totalPages; $p++):
                            if ($p === 1 || $p === $totalPages || abs($p - $page) <= $range):
                        ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= pageURL($p) ?>"><?= $p ?></a>
                                </li>
                        <?php
                            elseif (abs($p - $page) === $range + 1):
                        ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                        <?php
                            endif;
                        endfor;
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= pageURL($page + 1) ?>"><i class="fa-solid fa-chevron-right"></i></a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-building-circle-xmark"></i>
                    <h4>No Commercial Properties Found</h4>
                    <p>Try adjusting your filters or <a href="<?= $b ?>/commercial.php">reset all filters</a>.</p>
                    <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=<?= rawurlencode('Hi, I am looking for a commercial property. Can you help?') ?>" target="_blank" class="btn-navy mt-3" style="display:inline-flex;gap:.5rem;align-items:center;"><i class="fa-brands fa-whatsapp"></i> Ask on WhatsApp</a>
                </div>
            <?php endif; ?>

        </div><!-- /.col-lg-9 -->
    </div><!-- /.row -->
</div><!-- /.container -->

<!-- Mobile Filter Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header" style="background:var(--navy-800); color:#fff;">
        <h5 class="offcanvas-title" id="filterOffcanvasLabel" style="color:var(--gold);">
            <i class="fa-solid fa-sliders me-2"></i>Filters
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form method="GET" action="<?= $b ?>/commercial.php" id="filterFormMobile">
            <input type="hidden" name="category" value="commercial">

            <div class="filter-group">
                <label class="filter-label">Purpose</label>
                <div class="d-flex gap-2">
                    <label class="flex-fill">
                        <input type="radio" name="purpose" value="sale" class="btn-check"
                               id="mPurposeSale" <?= ($purpose === '' || $purpose === 'sale') ? 'checked' : '' ?>>
                        <span class="btn btn-sm btn-outline-secondary w-100 d-block">For Sale</span>
                    </label>
                    <label class="flex-fill">
                        <input type="radio" name="purpose" value="rent" class="btn-check"
                               id="mPurposeRent" <?= $purpose === 'rent' ? 'checked' : '' ?>>
                        <span class="btn btn-sm btn-outline-secondary w-100 d-block">For Rent</span>
                    </label>
                </div>
            </div>
            <div class="filter-group">
                <label class="filter-label">City</label>
                <select name="city" class="filter-select">
                    <option value="">All Cities</option>
                    <?php foreach ($allCities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Property Type</label>
                <?php foreach ($commTypes as $val => $label): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="type[]"
                               value="<?= htmlspecialchars($val) ?>" id="mctype_<?= $val ?>"
                               <?= (is_array($_GET['type'] ?? null) && in_array($val, (array)$_GET['type'])) || $type === $val ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="mctype_<?= $val ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="filter-group">
                <label class="filter-label">Min Price (PKR)</label>
                <input type="number" class="filter-input" name="min_price" placeholder="e.g. 5000000" value="<?= $minPrice ?: '' ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Max Price (PKR)</label>
                <input type="number" class="filter-input" name="max_price" placeholder="e.g. 50000000" value="<?= $maxPrice ?: '' ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Floor Number</label>
                <input type="number" class="filter-input" name="floor"
                       placeholder="e.g. 2" min="0" max="50" value="<?= $floor ?: '' ?>">
            </div>
        </form>
    </div>
    <div class="p-3 border-top">
        <button id="offcanvasApplyFilters" class="filter-apply-btn w-100">
            <i class="fa-solid fa-check me-2"></i>Apply Filters
        </button>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="<?= $b ?>/assets/js/listings.js"></script>
