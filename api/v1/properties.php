<?php
/**
 * Al-Riaz Associates — Properties REST Endpoint
 * GET /api/v1/properties.php
 *
 * List mode   : GET /api/v1/properties.php?category=residential&city=Islamabad...
 * Detail mode : GET /api/v1/properties.php?slug=some-property-slug
 */

declare(strict_types=1);

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

// ── CORS Headers (development-friendly) ───────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ── Helper ────────────────────────────────────────────────────────────────────
function jsonOut(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function intParam(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? max(0, (int)$_GET[$key]) : $default;
}

function strParam(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim(strip_tags($_GET[$key])) : $default;
}

// ── Route: Detail by slug ─────────────────────────────────────────────────────
if (!empty($_GET['slug'])) {
    handleDetailRequest(strParam('slug'));
}

// ── Route: AJAX HTML fragment (for Load More on listing pages) ────────────────
if (!empty($_GET['ajax'])) {
    handleAjaxListRequest();
}

// ── Route: List ───────────────────────────────────────────────────────────────
handleListRequest();

// =============================================================================
// AJAX HTML FRAGMENT HANDLER — used by listings.js Load More
// =============================================================================
function handleAjaxListRequest(): never
{
    require_once __DIR__ . '/../../includes/functions.php';

    $pdo = db();

    $q        = strParam('q');
    $purpose  = strParam('purpose');
    $city     = strParam('city');
    $category = strParam('category');
    $type     = strParam('type');
    $areaUnit = strParam('area_unit');
    $sort     = strParam('sort', 'newest');
    $minPrice = intParam('min_price');
    $maxPrice = intParam('max_price');
    $minArea  = (float)($_GET['min_area'] ?? 0);
    $maxArea  = (float)($_GET['max_area'] ?? 0);
    $bedrooms = intParam('bedrooms', -1);
    $page     = max(1, intParam('page', 1));
    $limit    = min(50, max(1, intParam('limit', 12)));
    $offset   = ($page - 1) * $limit;

    $conditions = ['p.is_published = 1'];
    $params     = [];

    if ($category && in_array($category, ['residential', 'commercial', 'plot'], true)) {
        $conditions[] = 'p.category = ?'; $params[] = $category;
    }
    if ($purpose && in_array($purpose, ['sale', 'rent'], true)) {
        $conditions[] = 'p.purpose = ?'; $params[] = $purpose;
    }
    if ($city) { $conditions[] = 'p.city = ?'; $params[] = $city; }

    $validTypes = ['house','flat','upper_portion','lower_portion','room','farmhouse',
                   'penthouse','plot','shop','office','warehouse','showroom','building',
                   'factory','agricultural_land'];
    if ($type && in_array($type, $validTypes, true)) {
        $conditions[] = 'p.listing_type = ?'; $params[] = $type;
    }
    if ($minPrice > 0) { $conditions[] = 'p.price >= ?'; $params[] = $minPrice; }
    if ($maxPrice > 0) { $conditions[] = 'p.price <= ?'; $params[] = $maxPrice; }
    $validUnits = ['marla','kanal','sq_ft','sq_yard','acre'];
    if ($areaUnit && in_array($areaUnit, $validUnits, true) && ($minArea > 0 || $maxArea > 0)) {
        $conditions[] = 'p.area_unit = ?'; $params[] = $areaUnit;
    }
    if ($minArea > 0) { $conditions[] = 'p.area_value >= ?'; $params[] = $minArea; }
    if ($maxArea > 0) { $conditions[] = 'p.area_value <= ?'; $params[] = $maxArea; }
    if ($bedrooms >= 0) { $conditions[] = 'p.bedrooms >= ?'; $params[] = $bedrooms; }
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $conditions[] = '(p.title LIKE ? OR p.area_locality LIKE ? OR p.city LIKE ?)';
        $params[] = $like; $params[] = $like; $params[] = $like;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $conditions);
    $orderSQL = match ($sort) {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'area'       => 'p.area_value DESC',
        default      => 'p.published_at DESC',
    };

    try {
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM properties p $whereSQL");
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();
    } catch (Exception $e) {
        jsonOut(['error' => 'count error'], 500);
    }

    $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

    try {
        $sql = "
            SELECT p.id, p.slug, p.title, p.city, p.area_locality, p.listing_type,
                   p.purpose, p.category, p.price, p.price_on_demand,
                   p.area_value, p.area_unit, p.bedrooms, p.bathrooms, p.is_featured,
                   pm.url AS thumbnail
            FROM properties p
            LEFT JOIN property_media pm
                   ON pm.property_id = p.id AND pm.kind = 'image' AND pm.sort_order = 0
            $whereSQL
            ORDER BY p.is_featured DESC, $orderSQL
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $properties = $stmt->fetchAll();
    } catch (Exception $e) {
        jsonOut(['error' => 'fetch error'], 500);
    }

    ob_start();
    foreach ($properties as $i => $prop):
        $thumb = !empty($prop['thumbnail'])
            ? htmlspecialchars($prop['thumbnail'])
            : 'https://picsum.photos/id/' . (50 + $i + $offset) . '/400/250';
        ?>
<div class="col-12 col-md-6 col-xl-4 mb-4">
    <div class="property-card card h-100">
        <div class="card-img-wrapper position-relative">
            <img data-src="<?= $thumb ?>"
                 src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 250'%3E%3Crect width='400' height='250' fill='%23f0f0f0'/%3E%3C/svg%3E"
                 class="card-img-top lazy" alt="<?= htmlspecialchars($prop['title']) ?>" loading="lazy">
            <span class="badge-purpose badge-<?= htmlspecialchars($prop['purpose']) ?>">For <?= ucfirst(htmlspecialchars($prop['purpose'])) ?></span>
            <?php if (!empty($prop['is_featured'])): ?><span class="badge-featured"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
            <button class="btn-bookmark" data-id="<?= (int)$prop['id'] ?>"><i class="fa-regular fa-heart"></i></button>
            <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=<?= rawurlencode('Interested in: ' . $prop['title']) ?>" class="btn-wa-quick" target="_blank"><i class="fab fa-whatsapp"></i></a>
        </div>
        <div class="card-body">
            <div class="property-price">
                <?php if (!empty($prop['price_on_demand'])): ?><span class="price-on-demand">Price on Demand</span>
                <?php else: ?>PKR <?= formatPKR((float)$prop['price']) ?><?php if ($prop['purpose'] === 'rent'): ?><small class="fw-normal text-muted">/month</small><?php endif; ?><?php endif; ?>
            </div>
            <h5 class="property-title"><?= htmlspecialchars($prop['title']) ?></h5>
            <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(trim($prop['area_locality'] . ', ' . $prop['city'], ', ')) ?></p>
            <div class="property-specs">
                <?php if ((int)($prop['bedrooms'] ?? 0) > 0): ?><span><i class="fas fa-bed"></i> <?= (int)$prop['bedrooms'] ?> Beds</span><?php endif; ?>
                <?php if ((int)($prop['bathrooms'] ?? 0) > 0): ?><span><i class="fas fa-bath"></i> <?= (int)$prop['bathrooms'] ?> Baths</span><?php endif; ?>
                <span><i class="fas fa-ruler-combined"></i> <?= formatArea((float)$prop['area_value'], $prop['area_unit']) ?></span>
            </div>
        </div>
        <div class="card-footer">
            <a href="/listing.php?slug=<?= urlencode($prop['slug']) ?>" class="btn btn-gold btn-sm w-100">View Details <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
        <?php
    endforeach;
    $html = ob_get_clean();

    jsonOut([
        'html'        => $html,
        'has_more'    => $page < $totalPages,
        'total'       => $total,
        'page'        => $page,
        'total_pages' => $totalPages,
        'count'       => count($properties),
    ]);
}

// =============================================================================
// DETAIL HANDLER
// =============================================================================
function handleDetailRequest(string $slug): never
{
    $pdo = db();

    // Main property row with agent info
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            u.name  AS agent_name,
            u.phone AS agent_phone,
            u.email AS agent_email,
            u.avatar_url AS agent_avatar
        FROM properties p
        LEFT JOIN users u ON u.id = p.agent_id
        WHERE p.slug = :slug
          AND p.is_published = 1
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $property = $stmt->fetch();

    if (!$property) {
        jsonOut(['success' => false, 'message' => 'Property not found'], 404);
    }

    // Decode JSON columns
    $property['features'] = json_decode($property['features'] ?? '[]', true) ?? [];

    // Increment view count (fire-and-forget; ignore errors)
    try {
        $pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = ?")
            ->execute([$property['id']]);
    } catch (PDOException) {}

    // All media for this property
    $mediaStmt = $pdo->prepare("
        SELECT id, kind, url, thumbnail_url, alt_text, sort_order
        FROM property_media
        WHERE property_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $mediaStmt->execute([$property['id']]);
    $property['media'] = $mediaStmt->fetchAll();

    // Project info (if any)
    $property['project'] = null;
    if ($property['project_id']) {
        $projStmt = $pdo->prepare("
            SELECT id, slug, name, developer, city, area_locality, status, hero_image_url
            FROM projects
            WHERE id = ? AND is_published = 1
            LIMIT 1
        ");
        $projStmt->execute([$property['project_id']]);
        $property['project'] = $projStmt->fetch() ?: null;
    }

    // Similar properties (same city + category, exclude current)
    $simStmt = $pdo->prepare("
        SELECT
            p.id, p.slug, p.title, p.listing_type, p.category, p.purpose,
            p.city, p.area_locality, p.price, p.price_on_demand,
            p.area_value, p.area_unit, p.bedrooms, p.bathrooms, p.is_featured,
            (SELECT pm.thumbnail_url
             FROM property_media pm
             WHERE pm.property_id = p.id AND pm.kind = 'image'
             ORDER BY pm.sort_order ASC, pm.id ASC
             LIMIT 1) AS thumbnail
        FROM properties p
        WHERE p.is_published = 1
          AND p.city     = :city
          AND p.category = :category
          AND p.id      != :id
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT 4
    ");
    $simStmt->execute([
        ':city'     => $property['city'],
        ':category' => $property['category'],
        ':id'       => $property['id'],
    ]);
    $property['similar'] = $simStmt->fetchAll();

    jsonOut(['success' => true, 'data' => $property]);
}

// =============================================================================
// LIST HANDLER
// =============================================================================
function handleListRequest(): never
{
    $pdo = db();

    // ── Filter parameters ────────────────────────────────────────────────────
    $category  = strParam('category');
    $purpose   = strParam('purpose');
    $city      = strParam('city');
    $type      = strParam('type');
    $areaUnit  = strParam('area_unit');
    $sort      = strParam('sort', 'newest');
    $q         = strParam('q');
    $minPrice  = intParam('min_price');
    $maxPrice  = intParam('max_price');
    $minArea   = (float)($_GET['min_area'] ?? 0);
    $maxArea   = (float)($_GET['max_area'] ?? 0);
    $bedrooms  = intParam('bedrooms', -1);
    $page      = max(1, intParam('page', 1));
    $limit     = min(50, max(1, intParam('limit', 12)));
    $offset    = ($page - 1) * $limit;

    // ── Build WHERE clause ───────────────────────────────────────────────────
    $conditions = ['p.is_published = 1'];
    $params     = [];

    if ($category && in_array($category, ['residential', 'commercial', 'plot'], true)) {
        $conditions[] = 'p.category = :category';
        $params[':category'] = $category;
    }

    if ($purpose && in_array($purpose, ['sale', 'rent'], true)) {
        $conditions[] = 'p.purpose = :purpose';
        $params[':purpose'] = $purpose;
    }

    if ($city) {
        $conditions[] = 'p.city = :city';
        $params[':city'] = $city;
    }

    // listing_type whitelist
    $validTypes = [
        'house','flat','upper_portion','lower_portion','room','farmhouse',
        'penthouse','plot','shop','office','warehouse','showroom','building',
        'factory','agricultural_land',
    ];
    if ($type && in_array($type, $validTypes, true)) {
        $conditions[] = 'p.listing_type = :listing_type';
        $params[':listing_type'] = $type;
    }

    if ($minPrice > 0) {
        $conditions[] = 'p.price >= :min_price';
        $params[':min_price'] = $minPrice;
    }

    if ($maxPrice > 0) {
        $conditions[] = 'p.price <= :max_price';
        $params[':max_price'] = $maxPrice;
    }

    $validUnits = ['marla', 'kanal', 'sq_ft', 'sq_yard', 'acre'];
    if ($areaUnit && in_array($areaUnit, $validUnits, true)) {
        $conditions[] = 'p.area_unit = :area_unit';
        $params[':area_unit'] = $areaUnit;
    }

    if ($minArea > 0) {
        $conditions[] = 'p.area_value >= :min_area';
        $params[':min_area'] = $minArea;
    }

    if ($maxArea > 0) {
        $conditions[] = 'p.area_value <= :max_area';
        $params[':max_area'] = $maxArea;
    }

    if ($bedrooms >= 0) {
        $conditions[] = 'p.bedrooms >= :bedrooms';
        $params[':bedrooms'] = $bedrooms;
    }

    // ── Full-text / keyword search ───────────────────────────────────────────
    if ($q !== '') {
        // Try MATCH AGAINST first; fall back to LIKE handled in the same query
        // using LIKE guarantees compatibility even if FT index is not yet built.
        $likeQ = '%' . addcslashes($q, '%_\\') . '%';
        $conditions[] = '(p.title LIKE :q OR p.area_locality LIKE :q2 OR p.city LIKE :q3 OR p.description LIKE :q4)';
        $params[':q']  = $likeQ;
        $params[':q2'] = $likeQ;
        $params[':q3'] = $likeQ;
        $params[':q4'] = $likeQ;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $conditions);

    // ── ORDER BY ─────────────────────────────────────────────────────────────
    $orderSQL = match ($sort) {
        'price_asc'  => 'ORDER BY p.price ASC',
        'price_desc' => 'ORDER BY p.price DESC',
        'featured'   => 'ORDER BY p.is_featured DESC, p.created_at DESC',
        default      => 'ORDER BY p.created_at DESC',
    };

    // ── Count total (for pagination) ─────────────────────────────────────────
    $countSQL  = "SELECT COUNT(*) FROM properties p $whereSQL";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // ── Fetch properties ──────────────────────────────────────────────────────
    $listSQL = "
        SELECT
            p.id, p.slug, p.title, p.listing_type, p.category, p.purpose,
            p.city, p.area_locality, p.price, p.price_on_demand, p.rent_period,
            p.area_value, p.area_unit, p.bedrooms, p.bathrooms, p.is_featured,
            p.possession_status, p.created_at,
            u.name  AS agent_name,
            u.phone AS agent_phone,
            (SELECT pm.thumbnail_url
             FROM property_media pm
             WHERE pm.property_id = p.id AND pm.kind = 'image'
             ORDER BY pm.sort_order ASC, pm.id ASC
             LIMIT 1) AS thumbnail
        FROM properties p
        LEFT JOIN users u ON u.id = p.agent_id
        $whereSQL
        $orderSQL
        LIMIT :limit OFFSET :offset
    ";

    $listStmt = $pdo->prepare($listSQL);

    foreach ($params as $key => $val) {
        $listStmt->bindValue($key, $val);
    }
    $listStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStmt->execute();

    $properties = $listStmt->fetchAll();

    $pages = $total > 0 ? (int)ceil($total / $limit) : 0;

    jsonOut([
        'success' => true,
        'data'    => $properties,
        'pagination' => [
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'pages'  => $pages,
        ],
    ]);
}
