<?php
/**
 * Al-Riaz Associates — Global Search Endpoint
 * GET /api/v1/search.php?q=search+term
 *
 * Returns up to 5 matching projects and 5 matching properties.
 * Used by the navbar live-search / autocomplete feature.
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

// ── Query parameter ───────────────────────────────────────────────────────────
$q = trim(strip_tags($_GET['q'] ?? ''));

if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode([
        'success'    => true,
        'projects'   => [],
        'properties' => [],
        'query'      => $q,
    ]);
    exit;
}

// Enforce reasonable length
$q = mb_substr($q, 0, 100);

// Build LIKE wildcard
$like = '%' . addcslashes($q, '%_\\') . '%';

$pdo = db();

// ── Search Projects ───────────────────────────────────────────────────────────
$projStmt = $pdo->prepare("
    SELECT
        id,
        slug,
        name,
        developer,
        city,
        area_locality,
        status,
        hero_image_url,
        'project' AS result_type
    FROM projects
    WHERE is_published = 1
      AND (
          name          LIKE :q1 OR
          developer     LIKE :q2 OR
          city          LIKE :q3 OR
          area_locality LIKE :q4
      )
    ORDER BY is_featured DESC, name ASC
    LIMIT 5
");
$projStmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
$projects = $projStmt->fetchAll();

// ── Search Properties ─────────────────────────────────────────────────────────
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
        p.area_value,
        p.area_unit,
        p.bedrooms,
        p.bathrooms,
        'property' AS result_type,
        (SELECT pm.thumbnail_url
         FROM property_media pm
         WHERE pm.property_id = p.id AND pm.kind = 'image'
         ORDER BY pm.sort_order ASC, pm.id ASC
         LIMIT 1) AS thumbnail
    FROM properties p
    WHERE p.is_published = 1
      AND (
          p.title         LIKE :q1 OR
          p.city          LIKE :q2 OR
          p.area_locality LIKE :q3 OR
          p.description   LIKE :q4
      )
    ORDER BY p.is_featured DESC, p.created_at DESC
    LIMIT 5
");
$propStmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
$properties = $propStmt->fetchAll();

echo json_encode([
    'success'    => true,
    'query'      => $q,
    'projects'   => $projects,
    'properties' => $properties,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
