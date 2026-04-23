<?php
/**
 * Al-Riaz Associates — Meta / Filter Dropdowns Endpoint
 * GET /api/v1/meta.php
 *
 * Returns data for populating search filter dropdowns:
 *   - Distinct cities from published properties + projects
 *   - All listing_type enum values with human-readable labels
 *   - Category options
 *   - Purpose options
 *   - Area unit options
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

// ── Distinct Cities ───────────────────────────────────────────────────────────
// Merge cities from both properties and projects tables
$cityStmt = $pdo->prepare("
    SELECT DISTINCT city FROM (
        SELECT city FROM properties WHERE is_published = 1 AND is_sold = 0 AND city != ''
        UNION
        SELECT city FROM projects  WHERE is_published = 1 AND city != ''
    ) AS combined
    ORDER BY city ASC
");
$cityStmt->execute();
$cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

// ── Listing Types ─────────────────────────────────────────────────────────────
$listingTypes = [
    // Residential
    ['value' => 'house',            'label' => 'House',             'group' => 'Residential'],
    ['value' => 'flat',             'label' => 'Flat / Apartment',  'group' => 'Residential'],
    ['value' => 'upper_portion',    'label' => 'Upper Portion',     'group' => 'Residential'],
    ['value' => 'lower_portion',    'label' => 'Lower Portion',     'group' => 'Residential'],
    ['value' => 'room',             'label' => 'Room',              'group' => 'Residential'],
    ['value' => 'farmhouse',        'label' => 'Farmhouse',         'group' => 'Residential'],
    ['value' => 'penthouse',        'label' => 'Penthouse',         'group' => 'Residential'],
    // Plots
    ['value' => 'plot',             'label' => 'Residential Plot',  'group' => 'Plot'],
    ['value' => 'agricultural_land','label' => 'Agricultural Land', 'group' => 'Plot'],
    // Commercial
    ['value' => 'shop',             'label' => 'Shop',              'group' => 'Commercial'],
    ['value' => 'office',           'label' => 'Office',            'group' => 'Commercial'],
    ['value' => 'warehouse',        'label' => 'Warehouse',         'group' => 'Commercial'],
    ['value' => 'showroom',         'label' => 'Showroom',          'group' => 'Commercial'],
    ['value' => 'building',         'label' => 'Building',          'group' => 'Commercial'],
    ['value' => 'factory',          'label' => 'Factory',           'group' => 'Commercial'],
];

// ── Categories ────────────────────────────────────────────────────────────────
$categories = [
    ['value' => 'residential', 'label' => 'Residential'],
    ['value' => 'commercial',  'label' => 'Commercial'],
    ['value' => 'plot',        'label' => 'Plot'],
];

// ── Purposes ──────────────────────────────────────────────────────────────────
$purposes = [
    ['value' => 'sale', 'label' => 'For Sale'],
    ['value' => 'rent', 'label' => 'For Rent'],
];

// ── Area Units ────────────────────────────────────────────────────────────────
$areaUnits = [
    ['value' => 'marla',   'label' => 'Marla'],
    ['value' => 'kanal',   'label' => 'Kanal'],
    ['value' => 'sq_ft',   'label' => 'Sq Ft'],
    ['value' => 'sq_yard', 'label' => 'Sq Yard'],
    ['value' => 'acre',    'label' => 'Acre'],
];

// ── Bedroom Options ───────────────────────────────────────────────────────────
$bedrooms = [
    ['value' => 1, 'label' => '1+'],
    ['value' => 2, 'label' => '2+'],
    ['value' => 3, 'label' => '3+'],
    ['value' => 4, 'label' => '4+'],
    ['value' => 5, 'label' => '5+'],
];

// ── Price Ranges (PKR, for dropdowns) ────────────────────────────────────────
$priceRanges = [
    ['min' => 0,          'max' => 5_000_000,   'label' => 'Under 50 Lakh'],
    ['min' => 5_000_000,  'max' => 10_000_000,  'label' => '50 Lakh – 1 Crore'],
    ['min' => 10_000_000, 'max' => 20_000_000,  'label' => '1 – 2 Crore'],
    ['min' => 20_000_000, 'max' => 30_000_000,  'label' => '2 – 3 Crore'],
    ['min' => 30_000_000, 'max' => 50_000_000,  'label' => '3 – 5 Crore'],
    ['min' => 50_000_000, 'max' => 100_000_000, 'label' => '5 – 10 Crore'],
    ['min' => 100_000_000,'max' => 0,            'label' => 'Above 10 Crore'],
];

// ── Sort Options ──────────────────────────────────────────────────────────────
$sortOptions = [
    ['value' => 'newest',     'label' => 'Newest First'],
    ['value' => 'price_asc',  'label' => 'Price: Low to High'],
    ['value' => 'price_desc', 'label' => 'Price: High to Low'],
    ['value' => 'featured',   'label' => 'Featured First'],
];

echo json_encode([
    'success'      => true,
    'cities'       => array_values($cities),
    'listing_types'=> $listingTypes,
    'categories'   => $categories,
    'purposes'     => $purposes,
    'area_units'   => $areaUnits,
    'bedrooms'     => $bedrooms,
    'price_ranges' => $priceRanges,
    'sort_options' => $sortOptions,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
