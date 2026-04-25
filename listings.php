<?php
/**
 * Al-Riaz Associates — Unified Property Listings
 *
 * One page handles every combination of category × purpose:
 *   /listings.php                       → all properties
 *   /listings.php?category=residential  → residential only
 *   /listings.php?category=commercial   → commercial only
 *   /listings.php?purpose=sale|rent     → any category, filtered by purpose
 *   /listings.php?category=X&purpose=Y  → both
 *
 * Legacy entry points residential.php / commercial.php / rent.php are thin
 * wrappers that preset $_GET['category'] / $_GET['purpose'] then include this file.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

/* ─── Input sanitisation ─────────────────────────────────────────────────── */
$category  = in_array($_GET['category'] ?? '', ['residential', 'commercial'], true)
             ? $_GET['category'] : '';
$purpose   = in_array($_GET['purpose']  ?? '', ['sale', 'rent'],              true)
             ? $_GET['purpose']  : '';
$city      = trim($_GET['city'] ?? '');

$rawType   = $_GET['type'] ?? '';
if (is_array($rawType)) {
    $type = array_values(array_filter(array_map('trim', $rawType), 'strlen'));
} else {
    $type = ($t = trim($rawType)) !== '' ? [$t] : [];
}

$minPrice  = max(0, (int)($_GET['min_price'] ?? 0));
$maxPrice  = max(0, (int)($_GET['max_price'] ?? 0));
$minArea   = max(0, (float)($_GET['min_area'] ?? 0));
$maxArea   = max(0, (float)($_GET['max_area'] ?? 0));
$areaUnit  = in_array($_GET['area_unit'] ?? '', ['marla','kanal','sq_ft','sq_yard','acre'], true)
             ? $_GET['area_unit']
             : ($category === 'commercial' ? 'sq_ft' : 'marla');
$bedrooms  = max(0, (int)($_GET['bedrooms'] ?? 0));
$features  = isset($_GET['features']) && is_array($_GET['features'])
             ? array_values(array_filter(array_map('trim', $_GET['features']), 'strlen'))
             : [];
$floor     = max(0, (int)($_GET['floor'] ?? 0));
$sort      = in_array($_GET['sort'] ?? '', ['newest','price_asc','price_desc','area'], true)
             ? $_GET['sort'] : 'newest';
$pageNum   = max(1, (int)($_GET['page'] ?? 1));
$limit     = 12;
$offset    = ($pageNum - 1) * $limit;

$db = Database::getInstance();

/* ─── Lists ─────────────────────────────────────────────────────────────── */
$resTypes = [
    'house'         => 'House',
    'flat'          => 'Flat / Apartment',
    'upper_portion' => 'Upper Portion',
    'lower_portion' => 'Lower Portion',
    'farmhouse'     => 'Farmhouse',
    'penthouse'     => 'Penthouse',
    'plot'          => 'Plot',
];
$commTypes = [
    'shop'            => 'Shop',
    'office'          => 'Office',
    'warehouse'       => 'Warehouse',
    'showroom'        => 'Showroom',
    'building'        => 'Building',
    'factory'         => 'Factory',
    'commercial_plot' => 'Commercial Plot',
];
$featureOptions = [
    'parking'          => 'Parking',
    'gas'              => 'Gas',
    'electricity'      => 'Electricity',
    'furnished'        => 'Furnished',
    'corner'           => 'Corner',
    'boundary_wall'    => 'Boundary Wall',
    'servant_quarters' => 'Servant Quarters',
    'garden'           => 'Garden',
];
if ($category === 'commercial') {
    $typeOptions = $commTypes;
} elseif ($category === 'residential') {
    $typeOptions = $resTypes;
} else {
    $typeOptions = $resTypes + $commTypes;  // both
}

/* ─── Cities ────────────────────────────────────────────────────────────── */
$knownCities = ['Islamabad', 'Rawalpindi', 'Lahore', 'Karachi'];
try {
    $citySql    = "SELECT DISTINCT city FROM properties WHERE is_published = 1 AND is_sold = 0";
    $cityParams = [];
    if ($category !== '') { $citySql .= " AND category = ?"; $cityParams[] = $category; }
    if ($purpose  !== '') { $citySql .= " AND purpose  = ?"; $cityParams[] = $purpose;  }
    $citySql .= " ORDER BY city";
    $citiesStmt = $db->prepare($citySql);
    $citiesStmt->execute($cityParams);
    $dbCities   = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $allCities  = array_values(array_unique(array_merge($knownCities, $dbCities)));
    sort($allCities);
} catch (Exception $e) {
    $allCities = $knownCities;
}

/* ─── Filter WHERE ──────────────────────────────────────────────────────── */
$where  = ['p.is_published = 1', 'p.is_sold = 0'];
$params = [];

if ($category !== '') { $where[] = 'p.category = ?'; $params[] = $category; }
if ($purpose  !== '') { $where[] = 'p.purpose  = ?'; $params[] = $purpose;  }
if ($city     !== '') { $where[] = 'LOWER(p.city) = LOWER(?)'; $params[] = $city; }

if (!empty($type)) {
    $ph = implode(',', array_fill(0, count($type), '?'));
    $where[] = "p.listing_type IN ($ph)";
    foreach ($type as $t) { $params[] = $t; }
}
if ($minPrice > 0) { $where[] = 'p.price      >= ?'; $params[] = $minPrice; }
if ($maxPrice > 0) { $where[] = 'p.price      <= ?'; $params[] = $maxPrice; }
if ($minArea  > 0) { $where[] = 'p.area_value >= ?'; $params[] = $minArea;  }
if ($maxArea  > 0) { $where[] = 'p.area_value <= ?'; $params[] = $maxArea;  }
if (($minArea > 0 || $maxArea > 0) && $areaUnit !== '') {
    $where[] = 'p.area_unit = ?'; $params[] = $areaUnit;
}
if ($bedrooms > 0 && $category !== 'commercial') {
    $where[] = 'p.bedrooms >= ?'; $params[] = $bedrooms;
}
// NOTE: properties table has no floor_number column yet; skip the filter silently.

$allowedFeatures = array_keys($featureOptions);
foreach ($features as $feat) {
    if (in_array($feat, $allowedFeatures, true)) {
        $where[]  = "JSON_CONTAINS(COALESCE(p.features,'[]'), ?, '$')";
        $params[] = json_encode($feat);
    }
}

$whereSQL = implode(' AND ', $where);

$orderMap = [
    'newest'     => 'p.published_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'area'       => 'p.area_value DESC',
];
$orderSQL = $orderMap[$sort];

/* ─── Count + Fetch ─────────────────────────────────────────────────────── */
try {
    $cStmt = $db->prepare("SELECT COUNT(*) FROM properties p WHERE $whereSQL");
    $cStmt->execute($params);
    $totalCount = (int)$cStmt->fetchColumn();
} catch (Exception $e) {
    error_log('[listings.php] count: ' . $e->getMessage());
    $totalCount = 0;
}
$totalPages = $totalCount > 0 ? (int)ceil($totalCount / $limit) : 1;

$properties = [];
try {
    $sql = "
        SELECT p.id, p.slug, p.title, p.city, p.area_locality, p.listing_type,
               p.purpose, p.category, p.price, p.price_on_demand,
               p.area_value, p.area_unit, p.bedrooms, p.bathrooms,
               p.is_featured, p.published_at, p.possession_status,
               u.name  AS agent_name,
               u.phone AS agent_phone,
               (SELECT pm.url FROM property_media pm
                 WHERE pm.property_id = p.id AND pm.kind = 'image'
                 ORDER BY pm.sort_order ASC, pm.id ASC LIMIT 1) AS thumbnail
          FROM properties p
     LEFT JOIN users u           ON u.id = p.agent_id
         WHERE $whereSQL
         ORDER BY p.is_featured DESC, $orderSQL
         LIMIT $limit OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[listings.php] fetch: ' . $e->getMessage());
}

/* ─── Copy & meta ───────────────────────────────────────────────────────── */
$categoryLabel = $category === 'commercial' ? 'Commercial' : ($category === 'residential' ? 'Residential' : '');
$purposeLabel  = $purpose  === 'rent'       ? 'for Rent'   : ($purpose  === 'sale'         ? 'for Sale'    : '');

$h1 = trim(($categoryLabel ?: 'All') . ' Properties ' . $purposeLabel);
if ($h1 === 'All Properties') { $h1 = 'All Properties'; }

$breadcrumbLabel = $categoryLabel ?: 'Listings';
if ($purpose === 'rent' && !$categoryLabel) { $breadcrumbLabel = 'For Rent'; }
if ($purpose === 'sale' && !$categoryLabel) { $breadcrumbLabel = 'For Sale'; }

$pageTitle = $h1;
$metaDesc  = 'Browse ' . strtolower($h1) . ' in Islamabad, Rawalpindi, Lahore and Karachi. Verified listings, transparent pricing, WhatsApp-first support.';

$b       = defined('BASE_PATH') ? BASE_PATH : '';
$waPhone = SITE_WHATSAPP;

/* ─── URL helpers ───────────────────────────────────────────────────────── */
function listingsURL(array $overrides = [], array $drop = ['page']): string
{
    $q = $_GET;
    foreach ($drop as $k) { unset($q[$k]); }
    foreach ($overrides as $k => $v) {
        if ($v === null) { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return (defined('BASE_PATH') ? BASE_PATH : '') . '/listings.php'
         . (empty($q) ? '' : '?' . http_build_query($q));
}

function pageURL(int $p): string
{
    return listingsURL(['page' => $p], []);
}

$showFrom = $totalCount > 0 ? $offset + 1 : 0;
$showTo   = min($offset + $limit, $totalCount);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page Header ───────────────────────────────────────────────────────── -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([
            ['label' => 'Home', 'url' => $b . '/'],
            ['label' => $breadcrumbLabel],
        ]) ?>
        <h1 class="page-header-title"><?= htmlspecialchars($h1) ?></h1>
        <p class="page-header-sub">
            <?php if ($totalCount > 0): ?>
                <strong><?= number_format($totalCount) ?></strong> properties found
            <?php else: ?>
                No properties match your current filters
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container py-4">

    <!-- Top tab row: Category + Purpose -->
    <div class="listings-tabrow mb-4">
        <div class="listings-tabs-group" role="tablist" aria-label="Category">
            <a href="<?= listingsURL(['category' => null]) ?>"
               class="purpose-pill <?= $category === '' ? 'active' : '' ?>">
                <i class="fa-solid fa-layer-group me-1"></i>All
            </a>
            <a href="<?= listingsURL(['category' => 'residential']) ?>"
               class="purpose-pill <?= $category === 'residential' ? 'active' : '' ?>">
                <i class="fa-solid fa-house me-1"></i>Residential
            </a>
            <a href="<?= listingsURL(['category' => 'commercial']) ?>"
               class="purpose-pill <?= $category === 'commercial' ? 'active' : '' ?>">
                <i class="fa-solid fa-building me-1"></i>Commercial
            </a>
        </div>

        <div class="listings-tabs-group" role="tablist" aria-label="Purpose">
            <a href="<?= listingsURL(['purpose' => null]) ?>"
               class="purpose-pill <?= $purpose === '' ? 'active' : '' ?>">Any</a>
            <a href="<?= listingsURL(['purpose' => 'sale']) ?>"
               class="purpose-pill <?= $purpose === 'sale' ? 'active' : '' ?>">For Sale</a>
            <a href="<?= listingsURL(['purpose' => 'rent']) ?>"
               class="purpose-pill <?= $purpose === 'rent' ? 'active' : '' ?>">For Rent</a>
        </div>
    </div>

    <!-- Mobile filter trigger -->
    <div class="d-lg-none mb-3">
        <button class="btn-navy w-100" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas"
                style="display:flex;align-items:center;justify-content:center;gap:.5rem;">
            <i class="fa-solid fa-sliders me-2"></i>Show Filters
        </button>
    </div>

    <div class="row g-4">

        <!-- Desktop sidebar -->
        <div class="col-lg-3 d-none d-lg-block">
            <?php
            $priceLabel = ($purpose === 'rent') ? 'Monthly Rent (PKR)' : 'Price Range (PKR)';
            $priceMax   = ($purpose === 'rent') ? 500000 : 500000000;
            $priceStep  = ($purpose === 'rent') ? 5000   : ($category === 'commercial' ? 1000000 : 500000);

            $filter = [
                'formId'       => 'filterForm',
                'formAction'   => $b . '/listings.php',
                'category'     => null,                    // category is a tab link, not a hidden input
                'lockedPurpose'=> null,                    // purpose is a tab link too
                'showPurpose'  => false,
                'showType'     => !empty($typeOptions),
                'typeOptions'  => $typeOptions,
                'showPrice'    => true,
                'priceMax'     => $priceMax,
                'priceStep'    => $priceStep,
                'priceLabel'   => $priceLabel,
                'showArea'     => true,
                'areaMax'      => $category === 'commercial' ? 50000 : 500,
                'defaultAreaUnit' => $areaUnit,
                'showBedrooms' => $category !== 'commercial',
                'showFeatures' => $category !== 'commercial',
                'featureOptions' => $featureOptions,
                'showFloor'    => $category !== 'residential',
                'allCities'    => $allCities,
                'extraHidden'  => array_filter([
                    'category' => $category ?: null,
                    'purpose'  => $purpose  ?: null,
                    'sort'     => $sort !== 'newest' ? $sort : null,
                ]),
                'selected'     => [
                    'purpose'  => $purpose,
                    'city'     => $city,
                    'type'     => $type,
                    'minPrice' => $minPrice,
                    'maxPrice' => $maxPrice,
                    'minArea'  => $minArea,
                    'maxArea'  => $maxArea,
                    'areaUnit' => $areaUnit,
                    'bedrooms' => $bedrooms,
                    'features' => $features,
                    'floor'    => $floor,
                ],
            ];
            include __DIR__ . '/includes/_filter_sidebar.php';
            ?>
        </div>

        <!-- Results -->
        <div class="col-lg-9">

            <div class="results-bar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <span class="text-muted small">
                    <?php if ($totalCount > 0): ?>
                        Showing <strong><?= $showFrom ?>–<?= $showTo ?></strong> of
                        <strong><?= number_format($totalCount) ?></strong> properties
                    <?php else: ?>
                        <strong>0</strong> properties
                    <?php endif; ?>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <select id="sortSelect" class="sort-select" aria-label="Sort by">
                        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price ↑</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
                        <option value="area"       <?= $sort === 'area'       ? 'selected' : '' ?>>Largest Area</option>
                    </select>
                    <button id="viewGrid" class="view-toggle-btn active" title="Grid" aria-label="Grid view"><i class="fa-solid fa-grip"></i></button>
                    <button id="viewList" class="view-toggle-btn" title="List" aria-label="List view"><i class="fa-solid fa-list"></i></button>
                </div>
            </div>

            <div id="activeFilterChips" class="mb-3 d-none"></div>

            <?php if (!empty($properties)): ?>
                <div class="row g-3" id="propertiesGrid">
                    <?php foreach ($properties as $i => $prop): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="pagination-nav mt-4" aria-label="Property pages">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php if ($pageNum > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= pageURL($pageNum - 1) ?>"><i class="fa-solid fa-chevron-left"></i></a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li>
                        <?php endif; ?>

                        <?php
                        $range = 2;
                        for ($p = 1; $p <= $totalPages; $p++):
                            if ($p === 1 || $p === $totalPages || abs($p - $pageNum) <= $range):
                        ?>
                            <li class="page-item <?= $p === $pageNum ? 'active' : '' ?>">
                                <a class="page-link" href="<?= pageURL($p) ?>"><?= $p ?></a>
                            </li>
                        <?php
                            elseif (abs($p - $pageNum) === $range + 1):
                        ?>
                            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                        <?php
                            endif;
                        endfor;
                        ?>

                        <?php if ($pageNum < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= pageURL($pageNum + 1) ?>"><i class="fa-solid fa-chevron-right"></i></a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <div class="empty-title">No properties found</div>
                    <p class="empty-text">Try adjusting your filters or
                        <a href="<?= $b ?>/listings.php" class="fw-600">reset all filters</a>.
                    </p>
                    <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=<?= rawurlencode('Hi, I am looking for a property. Can you help?') ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn-navy mt-3" style="display:inline-flex;gap:.5rem;align-items:center;">
                        <i class="fa-brands fa-whatsapp"></i> Ask on WhatsApp
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Mobile filter offcanvas -->
<div class="offcanvas offcanvas-start filter-offcanvas" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="filterOffcanvasLabel">
            <i class="fa-solid fa-sliders me-2"></i>Filters
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?php
        $filter['formId'] = 'filterFormMobile';
        include __DIR__ . '/includes/_filter_sidebar.php';
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="<?= $b ?>/assets/js/listings.js?v=<?= file_exists(__DIR__ . '/assets/js/listings.js') ? filemtime(__DIR__ . '/assets/js/listings.js') : time() ?>"></script>
