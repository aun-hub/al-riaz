<?php
/**
 * Al-Riaz Associates — Featured Content Endpoint
 * GET /api/v1/featured.php
 *
 * Returns featured projects (up to 6) and featured properties (up to 9).
 * Used on the homepage hero sections.
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

$pdo = db();

// ── Featured Projects ─────────────────────────────────────────────────────────
$projStmt = $pdo->prepare("
    SELECT
        p.id,
        p.slug,
        p.name,
        p.developer,
        p.city,
        p.area_locality,
        p.description,
        p.status,
        p.noc_status,
        p.hero_image_url,
        p.gallery,
        p.lat,
        p.lng,
        p.created_at,
        (SELECT COUNT(*) FROM properties pr
         WHERE pr.project_id = p.id AND pr.is_published = 1 AND pr.is_sold = 0) AS property_count
    FROM projects p
    WHERE p.is_featured  = 1
      AND p.is_published = 1
    ORDER BY p.created_at DESC
    LIMIT 6
");
$projStmt->execute();
$projects = $projStmt->fetchAll();

// Decode gallery JSON
foreach ($projects as &$proj) {
    $proj['gallery'] = json_decode($proj['gallery'] ?? '[]', true) ?? [];
}
unset($proj);

// ── Featured Properties ───────────────────────────────────────────────────────
$propStmt = $pdo->prepare("
    SELECT
        p.id,
        p.slug,
        p.title,
        p.listing_type,
        p.category,
        p.purpose,
        p.city,
        p.area_locality,
        p.price,
        p.price_on_demand,
        p.rent_period,
        p.area_value,
        p.area_unit,
        p.bedrooms,
        p.bathrooms,
        p.possession_status,
        p.created_at,
        u.name  AS agent_name,
        u.phone AS agent_phone,
        (SELECT pm.url
         FROM property_media pm
         WHERE pm.property_id = p.id AND pm.kind = 'image'
         ORDER BY pm.sort_order ASC, pm.id ASC
         LIMIT 1) AS image_url,
        (SELECT pm.thumbnail_url
         FROM property_media pm
         WHERE pm.property_id = p.id AND pm.kind = 'image'
         ORDER BY pm.sort_order ASC, pm.id ASC
         LIMIT 1) AS thumbnail
    FROM properties p
    LEFT JOIN users u ON u.id = p.agent_id
    WHERE p.is_featured  = 1
      AND p.is_published = 1
      AND p.is_sold      = 0
    ORDER BY p.created_at DESC
    LIMIT 9
");
$propStmt->execute();
$properties = $propStmt->fetchAll();

echo json_encode([
    'success'    => true,
    'projects'   => $projects,
    'properties' => $properties,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
