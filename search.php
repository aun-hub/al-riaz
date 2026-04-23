<?php
/**
 * Al-Riaz Associates — Unified Search Results Page
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ─── Input Sanitisation ────────────────────────────────────────────────────
$q        = trim($_GET['q'] ?? '');
$purpose  = in_array($_GET['purpose'] ?? '', ['sale', 'rent']) ? $_GET['purpose'] : '';
$city     = trim($_GET['city']      ?? '');
$category = in_array($_GET['category'] ?? '', ['residential','commercial','plot']) ? $_GET['category'] : '';
$type     = trim($_GET['type']      ?? '');
$minPrice = (int)($_GET['min_price'] ?? 0);
$maxPrice = (int)($_GET['max_price'] ?? 0);
$minArea  = (float)($_GET['min_area'] ?? 0);
$maxArea  = (float)($_GET['max_area'] ?? 0);
$areaUnit = in_array($_GET['area_unit'] ?? '', ['marla','kanal','sq_ft','sq_yard','acre'])
            ? $_GET['area_unit'] : 'marla';
$bedrooms = (int)($_GET['bedrooms'] ?? 0);
$sort     = in_array($_GET['sort'] ?? '', ['newest','price_asc','price_desc','area'])
            ? $_GET['sort'] : 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 12;
$offset   = ($page - 1) * $limit;

$hasFilters = ($q !== '' || $purpose !== '' || $city !== '' || $category !== ''
              || $type !== '' || $minPrice > 0 || $maxPrice > 0
              || $minArea > 0 || $maxArea > 0 || $bedrooms > 0);

$pageTitle = $q !== ''
    ? 'Search: ' . htmlspecialchars($q)
    : ($hasFilters ? 'Property Search' : 'Search Properties');

$metaDesc = $q !== ''
    ? 'Search results for "' . htmlspecialchars($q) . '" — properties and projects on Al-Riaz Associates.'
    : 'Search residential and commercial properties for sale and rent across Pakistan.';

// ─── DB ───────────────────────────────────────────────────────────────────
$db = Database::getInstance();

$knownCities = ['Islamabad', 'Rawalpindi', 'Lahore', 'Karachi'];
try {
    $citiesStmt = $db->query("SELECT DISTINCT city FROM properties WHERE is_published = 1 AND is_sold = 0 ORDER BY city");
    $dbCities   = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $allCities  = array_unique(array_merge($knownCities, $dbCities));
    sort($allCities);
} catch (Exception $e) {
    $allCities = $knownCities;
}

// ─── Build property WHERE ─────────────────────────────────────────────────
$propWhere  = ["p.is_published = 1", "p.is_sold = 0"];
$propParams = [];

if ($q !== '') {
    $propWhere[]  = "(p.title LIKE ? OR p.city LIKE ? OR p.area_locality LIKE ? OR p.description LIKE ?)";
    $like         = '%' . $q . '%';
    $propParams[] = $like;
    $propParams[] = $like;
    $propParams[] = $like;
    $propParams[] = $like;
}
if ($purpose !== '') {
    $propWhere[]  = "p.purpose = ?";
    $propParams[] = $purpose;
}
if ($city !== '') {
    $propWhere[]  = "p.city = ?";
    $propParams[] = $city;
}
if ($category !== '') {
    $propWhere[]  = "p.category = ?";
    $propParams[] = $category;
}
if ($type !== '') {
    $propWhere[]  = "p.listing_type = ?";
    $propParams[] = $type;
}
if ($minPrice > 0) {
    $propWhere[]  = "p.price >= ?";
    $propParams[] = $minPrice;
}
if ($maxPrice > 0) {
    $propWhere[]  = "p.price <= ?";
    $propParams[] = $maxPrice;
}
if ($minArea > 0) {
    $propWhere[]  = "p.area_value >= ?";
    $propParams[] = $minArea;
}
if ($maxArea > 0) {
    $propWhere[]  = "p.area_value <= ?";
    $propParams[] = $maxArea;
}
if ($areaUnit !== '' && ($minArea > 0 || $maxArea > 0)) {
    $propWhere[]  = "p.area_unit = ?";
    $propParams[] = $areaUnit;
}
if ($bedrooms > 0) {
    $propWhere[]  = "p.bedrooms >= ?";
    $propParams[] = $bedrooms;
}

$propWhereSQL = implode(' AND ', $propWhere);

$orderMap = [
    'newest'     => 'p.published_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'area'       => 'p.area_value DESC',
];
$orderSQL = $orderMap[$sort];

// Count properties
$totalProps = 0;
try {
    $cStmt = $db->prepare("SELECT COUNT(*) FROM properties p WHERE $propWhereSQL");
    $cStmt->execute($propParams);
    $totalProps = (int)$cStmt->fetchColumn();
} catch (Exception $e) {
    error_log('[search.php] prop count: ' . $e->getMessage());
}

$totalPages = $totalProps > 0 ? (int)ceil($totalProps / $limit) : 1;

// Fetch properties
$properties = [];
try {
    $sql = "
        SELECT
            p.id, p.slug, p.title, p.city, p.area_locality, p.listing_type,
            p.purpose, p.category, p.price, p.price_on_demand,
            p.area_value, p.area_unit, p.bedrooms, p.bathrooms,
            p.is_featured, p.published_at,
            u.name AS agent_name,
            pm.url AS thumbnail
        FROM properties p
        LEFT JOIN users u ON u.id = p.agent_id
        LEFT JOIN property_media pm
               ON pm.property_id = p.id
              AND pm.kind = 'image'
              AND pm.sort_order = 0
        WHERE $propWhereSQL
        ORDER BY p.is_featured DESC, $orderSQL
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($propParams);
    $properties = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[search.php] prop fetch: ' . $e->getMessage());
}

// ─── Projects search (only when q is set) ────────────────────────────────
$projects = [];
if ($q !== '') {
    try {
        $pjStmt = $db->prepare("
            SELECT pr.id, pr.slug, pr.name AS title, pr.city, pr.area_locality,
                   pr.status, pr.developer, pr.hero_image_url AS thumbnail
            FROM projects pr
            WHERE pr.is_published = 1
              AND (pr.name LIKE ? OR pr.city LIKE ? OR pr.area_locality LIKE ? OR pr.description LIKE ?)
            ORDER BY pr.is_featured DESC, pr.created_at DESC
            LIMIT 6
        ");
        $like = '%' . $q . '%';
        $pjStmt->execute([$like, $like, $like, $like]);
        $projects = $pjStmt->fetchAll();
    } catch (Exception $e) {
        error_log('[search.php] project fetch: ' . $e->getMessage());
        $projects = [];
    }
}

// ─── Featured properties for empty state ─────────────────────────────────
$featuredProps = [];
if (!$hasFilters || ($totalProps === 0 && empty($projects))) {
    try {
        $fStmt = $db->prepare("
            SELECT p.id, p.slug, p.title, p.city, p.area_locality, p.listing_type,
                   p.purpose, p.category, p.price, p.price_on_demand,
                   p.area_value, p.area_unit, p.bedrooms, p.bathrooms,
                   pm.url AS thumbnail
            FROM properties p
            LEFT JOIN property_media pm
                   ON pm.property_id = p.id
                  AND pm.kind = 'image'
                  AND pm.sort_order = 0
            WHERE p.is_published = 1 AND p.is_featured = 1 AND p.is_sold = 0
            ORDER BY p.published_at DESC
            LIMIT 4
        ");
        $fStmt->execute();
        $featuredProps = $fStmt->fetchAll();
    } catch (Exception $e) {
        $featuredProps = [];
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────
$b = defined('BASE_PATH') ? BASE_PATH : '';

function pageURL(int $p): string
{
    global $b;
    $params = $_GET;
    $params['page'] = $p;
    return $b . '/search.php?' . http_build_query($params);
}

$showFrom   = $totalProps > 0 ? $offset + 1 : 0;
$showTo     = min($offset + $limit, $totalProps);
$totalFound = $totalProps + count($projects);
$totalCount = $totalFound;

$propertyTypes = [
    'house'            => 'House',
    'flat'             => 'Flat / Apartment',
    'upper_portion'    => 'Upper Portion',
    'lower_portion'    => 'Lower Portion',
    'apartment'        => 'Apartment',
    'farmhouse'        => 'Farmhouse',
    'penthouse'        => 'Penthouse',
    'plot'             => 'Plot',
    'shop'             => 'Shop',
    'office'           => 'Office',
    'warehouse'        => 'Warehouse',
    'showroom'         => 'Showroom',
    'building'         => 'Building',
    'factory'          => 'Factory',
];

require_once 'includes/header.php';
?>

<!-- ── Page Header ──────────────────────────────────────────────────────── -->
<div class="page-header">
    <div class="container">
        <h1 class="page-header-title"><?= $q ? 'Results for "' . htmlspecialchars($q) . '"' : 'Search Properties' ?></h1>
        <p class="page-header-sub"><?= $totalCount > 0 ? number_format($totalCount) . ' properties found' : 'No properties match your search' ?></p>
    </div>
</div>

<!-- ── Search Input Bar ─────────────────────────────────────────────────── -->
<div style="background:var(--navy-50); border-bottom:1px solid var(--navy-100); padding:1rem 0;">
    <div class="container">
        <form method="GET" action="<?= $b ?>/search.php" class="d-flex gap-2">
            <input type="text" name="q" class="filter-input flex-grow-1" value="<?= htmlspecialchars($q) ?>" placeholder="Search properties, locations…">
            <button type="submit" class="btn-navy px-4"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>
</div>

<!-- ── Main Layout ──────────────────────────────────────────────────────── -->
<div class="container py-4">
<div class="row g-4">

    <!-- ── Filter Sidebar col-lg-3 ──────────────────────────────────────── -->
    <aside class="col-lg-3">
        <div class="filter-sidebar">
            <form method="GET" action="<?= $b ?>/search.php" id="filterForm">
                <?php if ($q !== ''): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                <?php endif; ?>

                <!-- Purpose -->
                <div class="filter-group">
                    <label class="filter-label">Purpose</label>
                    <select name="purpose" class="filter-select">
                        <option value="">Any Purpose</option>
                        <option value="sale" <?= $purpose === 'sale' ? 'selected' : '' ?>>For Sale</option>
                        <option value="rent" <?= $purpose === 'rent' ? 'selected' : '' ?>>For Rent</option>
                    </select>
                </div>

                <!-- City -->
                <div class="filter-group">
                    <label class="filter-label">City</label>
                    <select name="city" class="filter-select">
                        <option value="">Any City</option>
                        <?php foreach ($allCities as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category -->
                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select name="category" class="filter-select">
                        <option value="">Any Category</option>
                        <option value="residential" <?= $category === 'residential' ? 'selected' : '' ?>>Residential</option>
                        <option value="commercial"  <?= $category === 'commercial'  ? 'selected' : '' ?>>Commercial</option>
                        <option value="plot"        <?= $category === 'plot'        ? 'selected' : '' ?>>Plot</option>
                    </select>
                </div>

                <!-- Property Type -->
                <div class="filter-group">
                    <label class="filter-label">Property Type</label>
                    <select name="type" class="filter-select">
                        <option value="">Any Type</option>
                        <?php foreach ($propertyTypes as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $type === $val ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <label class="filter-label">Min Price (PKR)</label>
                    <input type="number" class="filter-input" name="min_price"
                           placeholder="e.g. 5000000" value="<?= $minPrice ?: '' ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Max Price (PKR)</label>
                    <input type="number" class="filter-input" name="max_price"
                           placeholder="e.g. 50000000" value="<?= $maxPrice ?: '' ?>">
                </div>

                <!-- Area -->
                <div class="filter-group">
                    <label class="filter-label">Min Area</label>
                    <input type="number" step="0.5" class="filter-input" name="min_area"
                           placeholder="e.g. 5" value="<?= $minArea ?: '' ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Max Area</label>
                    <input type="number" step="0.5" class="filter-input" name="max_area"
                           placeholder="e.g. 20" value="<?= $maxArea ?: '' ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Area Unit</label>
                    <select name="area_unit" class="filter-select">
                        <option value="marla"    <?= $areaUnit === 'marla'    ? 'selected' : '' ?>>Marla</option>
                        <option value="kanal"    <?= $areaUnit === 'kanal'    ? 'selected' : '' ?>>Kanal</option>
                        <option value="sq_ft"    <?= $areaUnit === 'sq_ft'    ? 'selected' : '' ?>>Sq Ft</option>
                        <option value="sq_yard"  <?= $areaUnit === 'sq_yard'  ? 'selected' : '' ?>>Sq Yard</option>
                        <option value="acre"     <?= $areaUnit === 'acre'     ? 'selected' : '' ?>>Acre</option>
                    </select>
                </div>

                <!-- Bedrooms -->
                <div class="filter-group">
                    <label class="filter-label">Bedrooms</label>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ([0, 1, 2, 3, 4, 5] as $bed): ?>
                        <button type="submit" name="bedrooms" value="<?= $bed ?>"
                                class="filter-bed-btn <?= $bedrooms === $bed ? 'active' : '' ?>">
                            <?= $bed === 0 ? 'Any' : $bed . '+' ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn-navy flex-grow-1">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Apply
                    </button>
                    <a href="<?= $b ?>/search.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>"
                       class="btn btn-outline-secondary btn-sm" id="resetFiltersBtn">
                        <i class="fas fa-rotate-right"></i>
                    </a>
                </div>
            </form>
        </div>
    </aside>

    <!-- ── Results Column col-lg-9 ──────────────────────────────────────── -->
    <div class="col-lg-9">

        <!-- Results bar -->
        <div class="results-bar mb-3">
            <div>
                <?php if ($hasFilters && $totalFound > 0): ?>
                    <?php if ($q !== ''): ?>
                        <span><?= number_format($totalFound) ?> result<?= $totalFound !== 1 ? 's' : '' ?> for <strong>"<?= htmlspecialchars($q) ?>"</strong></span>
                    <?php else: ?>
                        <span>Showing <?= $showFrom ?>–<?= $showTo ?> of <?= number_format($totalProps) ?> propert<?= $totalProps !== 1 ? 'ies' : 'y' ?></span>
                    <?php endif; ?>
                <?php elseif (!$hasFilters): ?>
                    <span>Browse all properties</span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="<?= $b ?>/search.php" class="d-flex align-items-center gap-1">
                    <?php foreach ($_GET as $k => $v): if ($k === 'sort') continue; ?>
                        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
                    <?php endforeach; ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price ↑</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
                        <option value="area"       <?= $sort === 'area'       ? 'selected' : '' ?>>Area ↓</option>
                    </select>
                </form>
                <button id="viewGrid" class="view-toggle-btn active" title="Grid view"><i class="fas fa-th"></i></button>
                <button id="viewList" class="view-toggle-btn" title="List view"><i class="fas fa-list"></i></button>
            </div>
        </div>

        <?php if ($hasFilters && $totalFound === 0): ?>
        <!-- ── Zero results ─────────────────────────────────────────────── -->
        <div class="empty-state">
            <i class="fa-solid fa-magnifying-glass fa-3x d-block mb-3"></i>
            <h2 class="h4">No Results Found</h2>
            <p class="text-muted">
                <?php if ($q !== ''): ?>
                    We couldn't find any properties or projects matching
                    <strong>"<?= htmlspecialchars($q) ?>"</strong>.<br>
                <?php else: ?>
                    No properties match your current filters.<br>
                <?php endif; ?>
                Try different keywords, broaden your filters, or explore our featured listings below.
            </p>
            <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                <a href="<?= $b ?>/search.php" class="btn-gold">
                    <i class="fas fa-rotate-right me-1"></i>Clear Search
                </a>
                <a href="<?= $b ?>/residential.php" class="btn btn-outline-secondary">
                    Browse All Properties
                </a>
            </div>
        </div>

        <!-- Suggestions: featured properties -->
        <?php if (!empty($featuredProps)): ?>
        <div class="mt-5">
            <h2 class="content-heading">Featured Properties</h2>
            <div class="row g-3">
                <?php foreach ($featuredProps as $i => $prop):
                    $waPhone = SITE_WHATSAPP; ?>
                <div class="col-sm-6 col-xl-3">
                    <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>

        <!-- ── Projects section ─────────────────────────────────────────── -->
        <?php if (!empty($projects)): ?>
        <div class="mb-5">
            <h2 class="content-heading">Projects matching "<?= htmlspecialchars($q) ?>"</h2>
            <p class="text-muted small mb-3"><?= count($projects) ?> project<?= count($projects) !== 1 ? 's' : '' ?> found</p>
            <div class="row g-3">
                <?php foreach ($projects as $i => $proj): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="card h-100" style="border:1px solid var(--navy-100); border-radius:10px; overflow:hidden; transition:box-shadow .25s, transform .25s;">
                        <div class="position-relative" style="aspect-ratio:16/9; overflow:hidden; background:#f5f5f5;">
                            <?php $thumb = !empty($proj['thumbnail']) ? htmlspecialchars(mediaUrl($proj['thumbnail'])) : 'https://picsum.photos/id/' . (100 + $i) . '/400/225'; ?>
                            <img data-src="<?= $thumb ?>"
                                 src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 225'%3E%3Crect width='400' height='225' fill='%23e8e8e8'/%3E%3C/svg%3E"
                                 class="w-100 h-100 lazy" style="object-fit:cover;" alt="<?= htmlspecialchars($proj['title']) ?>">
                            <?php if (!empty($proj['status'])): ?>
                            <span style="position:absolute; top:10px; left:10px; padding:.3em .7em; border-radius:20px; font-size:.7rem; font-weight:700; z-index:2; background:var(--navy-700); color:#fff;">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $proj['status']))) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold mb-1" style="font-size:.9rem; color:var(--navy-800);"><?= htmlspecialchars($proj['title']) ?></h5>
                            <p class="text-muted small mb-1">
                                <i class="fa-solid fa-location-dot me-1"></i>
                                <?= htmlspecialchars(trim(($proj['area_locality'] ?? '') . ', ' . $proj['city'], ', ')) ?>
                            </p>
                            <?php if (!empty($proj['developer'])): ?>
                            <p class="small text-muted mb-0">
                                <i class="fas fa-helmet-safety me-1"></i><?= htmlspecialchars($proj['developer']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                            <a href="<?= $b ?>/project.php?slug=<?= urlencode($proj['slug']) ?>" class="btn-navy d-block text-center" style="padding:.45rem 1rem; font-size:.85rem;">
                                View Project <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Properties section ───────────────────────────────────────── -->
        <?php if ($hasFilters || !empty($properties)): ?>
        <div>
            <?php if ($q !== ''): ?>
                <h2 class="content-heading">Properties matching "<?= htmlspecialchars($q) ?>"</h2>
            <?php elseif ($hasFilters): ?>
                <h2 class="content-heading">Properties</h2>
            <?php endif; ?>

            <?php if (!empty($properties)): ?>
            <div class="row g-3" id="propertiesGrid">
                <?php foreach ($properties as $i => $prop):
                    $waPhone = SITE_WHATSAPP; ?>
                <div class="col-sm-6 col-xl-4">
                    <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Search result pages" class="pagination-nav mt-4">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= pageURL($page - 1) ?>"><i class="fas fa-chevron-left"></i></a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>
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
                        <li class="page-item"><a class="page-link" href="<?= pageURL($page + 1) ?>"><i class="fas fa-chevron-right"></i></a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php elseif ($hasFilters): ?>
            <!-- Properties section empty with filters active -->
            <div class="empty-state py-4">
                <i class="fa-solid fa-house fa-3x d-block mb-3"></i>
                <p class="text-muted">No properties match your current criteria.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Landing state: no search/filters yet -->
        <?php if (!$hasFilters): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-magnifying-glass" style="font-size:4rem; color:var(--navy-200);"></i>
            <h2 class="h4 mt-3 text-muted">Start your property search</h2>
            <p class="text-muted">Enter a location, property name or keyword above to find properties across Pakistan.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
                <a href="<?= $b ?>/residential.php" class="btn-gold">
                    <i class="fas fa-home me-2"></i>Browse Residential
                </a>
                <a href="<?= $b ?>/commercial.php" class="btn btn-outline-secondary">
                    <i class="fas fa-building me-2"></i>Browse Commercial
                </a>
                <a href="<?= $b ?>/rent.php" class="btn btn-outline-secondary">
                    <i class="fas fa-key me-2"></i>Properties for Rent
                </a>
            </div>
        </div>

        <!-- Show featured when landing -->
        <?php if (!empty($featuredProps)): ?>
        <div class="mt-4">
            <h2 class="content-heading">Featured Properties</h2>
            <p class="text-muted small mb-3">Handpicked top listings</p>
            <div class="row g-3">
                <?php foreach ($featuredProps as $i => $prop):
                    $waPhone = SITE_WHATSAPP; ?>
                <div class="col-sm-6">
                    <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php endif; /* end hasFilters / zero results */ ?>

    </div><!-- /.col-lg-9 -->

</div><!-- /.row -->
</div><!-- /.container -->

<?php require_once 'includes/footer.php'; ?>
<script src="<?= $b ?>/assets/js/listings.js"></script>
