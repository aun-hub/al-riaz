<?php
/**
 * Al-Riaz Associates — Projects REST Endpoint
 * GET /api/v1/projects.php
 *
 * List mode   : GET /api/v1/projects.php?city=Islamabad&status=ready
 * Detail mode : GET /api/v1/projects.php?slug=bahria-town-phase-8-rawalpindi
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

// ── CORS ──────────────────────────────────────────────────────────────────────
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

function jsonOut(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function strParam(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim(strip_tags($_GET[$key])) : $default;
}

function intParam(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? max(0, (int)$_GET[$key]) : $default;
}

// ── Route: Detail ─────────────────────────────────────────────────────────────
if (!empty($_GET['slug'])) {
    handleDetailRequest(strParam('slug'));
}

// ── Route: List ───────────────────────────────────────────────────────────────
handleListRequest();

// =============================================================================
// DETAIL HANDLER
// =============================================================================
function handleDetailRequest(string $slug): never
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            u.name AS created_by_name
        FROM projects p
        LEFT JOIN users u ON u.id = p.created_by
        WHERE p.slug = :slug
          AND p.is_published = 1
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $project = $stmt->fetch();

    if (!$project) {
        jsonOut(['success' => false, 'message' => 'Project not found'], 404);
    }

    // Decode gallery JSON
    $project['gallery'] = json_decode($project['gallery'] ?? '[]', true) ?? [];

    // Properties belonging to this project
    $propStmt = $pdo->prepare("
        SELECT
            pr.id, pr.slug, pr.title, pr.listing_type, pr.category, pr.purpose,
            pr.city, pr.area_locality, pr.price, pr.price_on_demand, pr.rent_period,
            pr.area_value, pr.area_unit, pr.bedrooms, pr.bathrooms, pr.is_featured,
            pr.possession_status, pr.created_at,
            u.name AS agent_name,
            (SELECT pm.thumbnail_url
             FROM property_media pm
             WHERE pm.property_id = pr.id AND pm.kind = 'image'
             ORDER BY pm.sort_order ASC, pm.id ASC
             LIMIT 1) AS thumbnail
        FROM properties pr
        LEFT JOIN users u ON u.id = pr.agent_id
        WHERE pr.project_id = :project_id
          AND pr.is_published = 1
          AND pr.is_sold = 0
        ORDER BY pr.is_featured DESC, pr.created_at DESC
    ");
    $propStmt->execute([':project_id' => $project['id']]);
    $project['properties'] = $propStmt->fetchAll();

    jsonOut(['success' => true, 'data' => $project]);
}

// =============================================================================
// LIST HANDLER
// =============================================================================
function handleListRequest(): never
{
    $pdo = db();

    $city   = strParam('city');
    $status = strParam('status');
    $page   = max(1, intParam('page', 1));
    $limit  = min(50, max(1, intParam('limit', 12)));
    $offset = ($page - 1) * $limit;

    $conditions = ['p.is_published = 1'];
    $params     = [];

    if ($city) {
        $conditions[] = 'p.city = :city';
        $params[':city'] = $city;
    }

    $validStatuses = ['upcoming', 'under_development', 'ready', 'possession'];
    if ($status && in_array($status, $validStatuses, true)) {
        $conditions[] = 'p.status = :status';
        $params[':status'] = $status;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $conditions);

    // Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM projects p $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch
    $listSQL = "
        SELECT
            p.id, p.slug, p.name, p.developer, p.city, p.area_locality,
            p.status, p.noc_status, p.hero_image_url, p.gallery,
            p.lat, p.lng, p.is_featured, p.created_at,
            (SELECT COUNT(*) FROM properties pr
             WHERE pr.project_id = p.id AND pr.is_published = 1 AND pr.is_sold = 0) AS property_count
        FROM projects p
        $whereSQL
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $listStmt = $pdo->prepare($listSQL);
    foreach ($params as $key => $val) {
        $listStmt->bindValue($key, $val);
    }
    $listStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStmt->execute();

    $projects = $listStmt->fetchAll();

    // Decode gallery JSON for each project
    foreach ($projects as &$project) {
        $project['gallery'] = json_decode($project['gallery'] ?? '[]', true) ?? [];
    }
    unset($project);

    $pages = $total > 0 ? (int)ceil($total / $limit) : 0;

    jsonOut([
        'success' => true,
        'data'    => $projects,
        'pagination' => [
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'pages'  => $pages,
        ],
    ]);
}
