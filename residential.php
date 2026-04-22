<?php
/**
 * Al-Riaz Associates — Residential Property Listings
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Residential Properties';
$metaDesc  = 'Browse residential properties for sale and rent in Islamabad, Rawalpindi, Lahore and Karachi. Houses, flats, apartments, plots and more.';

// ─── Input Sanitisation ────────────────────────────────────────────────────
$purpose   = in_array($_GET['purpose'] ?? '', ['sale', 'rent']) ? $_GET['purpose'] : '';
$city      = trim($_GET['city']      ?? '');
$type      = trim($_GET['type']      ?? '');   // listing_type
$minPrice  = (int)($_GET['min_price'] ?? 0);
$maxPrice  = (int)($_GET['max_price'] ?? 0);
$minArea   = (float)($_GET['min_area'] ?? 0);
$maxArea   = (float)($_GET['max_area'] ?? 0);
$areaUnit  = in_array($_GET['area_unit'] ?? '', ['marla','kanal','sq_ft','sq_yard','acre'])
             ? $_GET['area_unit'] : 'marla';
$bedrooms  = (int)($_GET['bedrooms'] ?? 0);
$features  = isset($_GET['features']) && is_array($_GET['features'])
             ? array_map('trim', $_GET['features']) : [];
$sort      = in_array($_GET['sort'] ?? '', ['newest','price_asc','price_desc','area'])
             ? $_GET['sort'] : 'newest';
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 12;
$offset    = ($page - 1) * $limit;

// ─── DB ───────────────────────────────────────────────────────────────────
$db = Database::getInstance();

// ─── Cities for dropdown ──────────────────────────────────────────────────
$knownCities = ['Islamabad', 'Rawalpindi', 'Lahore', 'Karachi'];
try {
    $citiesStmt = $db->query("SELECT DISTINCT city FROM properties WHERE is_published = 1 AND category = 'residential' ORDER BY city");
    $dbCities   = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $allCities  = array_unique(array_merge($knownCities, $dbCities));
    sort($allCities);
} catch (Exception $e) {
    $allCities = $knownCities;
}

// ─── Build WHERE ──────────────────────────────────────────────────────────
$where  = ["p.is_published = 1", "p.category = 'residential'"];
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
if ($bedrooms > 0) {
    $where[]  = "p.bedrooms >= ?";
    $params[] = $bedrooms;
}

// Features is a JSON array column — use JSON_CONTAINS for filtering
$allowedFeatures = ['parking','gas','electricity','furnished','corner','boundary_wall','servant_quarters','garden'];
foreach ($features as $feat) {
    if (in_array($feat, $allowedFeatures, true)) {
        $where[]  = "JSON_CONTAINS(COALESCE(p.features,'[]'), ?, '$')";
        $params[] = json_encode($feat);
    }
}

$whereSQL = implode(' AND ', $where);

// ORDER BY
$orderMap = [
    'newest'     => 'p.published_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'area'       => 'p.area_value DESC',
];
$orderSQL = $orderMap[$sort];

// ─── Count total ──────────────────────────────────────────────────────────
try {
    $countSQL  = "SELECT COUNT(*) FROM properties p WHERE $whereSQL";
    $countStmt = $db->prepare($countSQL);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    error_log('[residential.php] count error: ' . $e->getMessage());
    $totalCount = 0;
}

$totalPages = $totalCount > 0 ? (int)ceil($totalCount / $limit) : 1;

// ─── Fetch properties ─────────────────────────────────────────────────────
$properties = [];
try {
    $sql = "
        SELECT
            p.id, p.slug, p.title, p.city, p.area_locality, p.listing_type,
            p.purpose, p.category, p.price, p.price_on_demand,
            p.area_value, p.area_unit, p.bedrooms, p.bathrooms,
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
    error_log('[residential.php] fetch error: ' . $e->getMessage());
    $properties = [];
}

// ─── Helper: build pagination URL ────────────────────────────────────────
function pageURL(int $p): string
{
    $q = $_GET;
    $q['page'] = $p;
    return (defined('BASE_PATH') ? BASE_PATH : '') . '/residential.php?' . http_build_query($q);
}

// ─── Helper: preserve current GET minus page ─────────────────────────────
function currentFiltersHidden(array $skip = ['page']): string
{
    $html = '';
    foreach ($_GET as $k => $v) {
        if (in_array($k, $skip, true)) continue;
        if (is_array($v)) {
            foreach ($v as $item) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '[]" value="' . htmlspecialchars($item) . '">';
            }
        } else {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
        }
    }
    return $html;
}

// showing range
$showFrom = $totalCount > 0 ? $offset + 1 : 0;
$showTo   = min($offset + $limit, $totalCount);

$b = defined('BASE_PATH') ? BASE_PATH : '';
$waPhone = SITE_WHATSAPP;

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([
            ['label' => 'Home', 'url' => $b . '/'],
            ['label' => 'Residential']
        ]) ?>
        <h1 class="page-header-title">Residential Properties for Sale &amp; Rent</h1>
        <p class="page-header-sub">
            <?php if ($totalCount > 0): ?>
                <strong><?= number_format($totalCount) ?></strong> properties found
            <?php else: ?>
                No properties found matching your criteria
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container py-4">

    <!-- Purpose Pills -->
    <div class="purpose-pill-group mb-4">
        <a href="<?= $b ?>/residential.php?purpose=sale" class="purpose-pill <?= $purpose !== 'rent' && $purpose !== '' ? 'active' : '' ?>">For Sale</a>
        <a href="<?= $b ?>/residential.php?purpose=rent" class="purpose-pill <?= $purpose === 'rent' ? 'active' : '' ?>">For Rent</a>
        <a href="<?= $b ?>/residential.php" class="purpose-pill <?= $purpose === '' ? 'active' : '' ?>">All</a>
    </div>

    <!-- Mobile: show filters button -->
    <div class="d-lg-none mb-3">
        <button class="btn-navy w-100" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" aria-controls="filterOffcanvas"
                style="display:flex;align-items:center;justify-content:center;gap:.5rem;">
            <i class="fa-solid fa-sliders me-2"></i>Show Filters
        </button>
    </div>

    <div class="row g-4">

        <!-- ── LEFT: Filter Sidebar (desktop) ────────────────────────────── -->
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/includes/_filter_residential.php'; ?>
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
                            <li class="page-item">
                                <a class="page-link" href="<?= pageURL($page - 1) ?>"><i class="fa-solid fa-chevron-left"></i></a>
                            </li>
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
                            <li class="page-item">
                                <a class="page-link" href="<?= pageURL($page + 1) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty state -->
                <div class="empty-state">
                    <i class="fa-solid fa-house-circle-xmark"></i>
                    <h4>No Properties Found</h4>
                    <p>Try adjusting your filters or <a href="<?= $b ?>/residential.php">reset all filters</a>.</p>
                    <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=<?= rawurlencode('Hi, I am looking for a residential property. Can you help?') ?>" target="_blank" class="btn-navy mt-3" style="display:inline-flex;gap:.5rem;align-items:center;"><i class="fa-brands fa-whatsapp"></i> Ask on WhatsApp</a>
                </div>
            <?php endif; ?>

        </div><!-- /.col-lg-9 -->
    </div><!-- /.row -->
</div><!-- /.container -->

<!-- ── Mobile Filter Offcanvas ──────────────────────────────────────────── -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header" style="background:var(--navy-800); color:#fff;">
        <h5 class="offcanvas-title" id="filterOffcanvasLabel" style="color:var(--gold);">
            <i class="fa-solid fa-sliders me-2"></i>Filters
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include __DIR__ . '/includes/_filter_residential.php'; ?>
    </div>
    <div class="p-3 border-top">
        <button id="offcanvasApplyFilters" class="filter-apply-btn w-100">
            <i class="fa-solid fa-check me-2"></i>Apply Filters
        </button>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="<?= $b ?>/assets/js/listings.js"></script>
