<?php
/**
 * Al-Riaz Associates — Shared Helper Functions
 * All general-purpose utilities used across public and admin pages.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

// ─────────────────────────────────────────────────────────────────────────────
// Price & Area Formatters
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return a human-readable PKR price string.
 *
 * @param  int|float|null $price Amount in PKR
 * @return string
 */
function getPKRFormatted(int|float|null $price): string
{
    if ($price === null || $price <= 0) {
        return 'Price on Demand';
    }

    $crore = 10_000_000;
    $lakh  = 100_000;

    if ($price >= $crore) {
        $val = $price / $crore;
        return rtrim(rtrim(number_format($val, 2), '0'), '.') . ' Crore';
    }

    if ($price >= $lakh) {
        $val = $price / $lakh;
        return rtrim(rtrim(number_format($val, 2), '0'), '.') . ' Lakh';
    }

    return 'PKR ' . number_format((int)$price);
}

/**
 * Return a human-readable area string.
 *
 * @param  int|float $value Numeric area value
 * @param  string    $unit  One of: marla, kanal, sq_ft, sq_yard, acre
 * @return string
 */
function getAreaFormatted(int|float $value, string $unit): string
{
    $labels = [
        'marla'   => 'Marla',
        'kanal'   => 'Kanal',
        'sq_ft'   => 'Sq Ft',
        'sq_yard' => 'Sq Yard',
        'acre'    => 'Acre',
    ];

    $label = $labels[strtolower($unit)] ?? ucfirst($unit);

    if ($value == (int)$value) {
        return number_format((int)$value) . ' ' . $label;
    }

    return rtrim(rtrim(number_format((float)$value, 2), '0'), '.') . ' ' . $label;
}

// ─────────────────────────────────────────────────────────────────────────────
// Label Resolvers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return the display label for a listing_type enum value.
 */
function getListingTypeLabel(string $type): string
{
    $map = [
        'house'            => 'House',
        'flat'             => 'Flat / Apartment',
        'upper_portion'    => 'Upper Portion',
        'lower_portion'    => 'Lower Portion',
        'room'             => 'Room',
        'farmhouse'        => 'Farmhouse',
        'penthouse'        => 'Penthouse',
        'plot'             => 'Plot',
        'shop'             => 'Shop',
        'office'           => 'Office',
        'warehouse'        => 'Warehouse',
        'showroom'         => 'Showroom',
        'building'         => 'Building',
        'factory'          => 'Factory',
        'agricultural_land'=> 'Agricultural Land',
    ];

    return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * Return the display label for a category enum value.
 */
function getCategoryLabel(string $cat): string
{
    $map = [
        'residential' => 'Residential',
        'commercial'  => 'Commercial',
        'plot'        => 'Plot',
    ];

    return $map[$cat] ?? ucfirst($cat);
}

/**
 * Return the display label for a purpose enum value.
 */
function getPurposeLabel(string $purpose): string
{
    $map = [
        'sale' => 'For Sale',
        'rent' => 'For Rent',
    ];

    return $map[$purpose] ?? ucfirst($purpose);
}

/**
 * Return an HTML Bootstrap badge for a property/inquiry status.
 *
 * @param  string $status  Status string (published, active, new, contacted, etc.)
 * @return string          HTML <span> badge
 */
function getStatusBadge(string $status): string
{
    $map = [
        // Property / Project statuses
        'published'          => ['bg-success',   'Published'],
        'unpublished'        => ['bg-secondary',  'Unpublished'],
        'upcoming'           => ['bg-info text-dark', 'Upcoming'],
        'under_development'  => ['bg-warning text-dark', 'Under Development'],
        'ready'              => ['bg-success',    'Ready'],
        'possession'         => ['bg-primary',    'Possession'],
        // Inquiry statuses
        'new'                => ['bg-primary',    'New'],
        'assigned'           => ['bg-info text-dark', 'Assigned'],
        'contacted'          => ['bg-warning text-dark', 'Contacted'],
        'qualified'          => ['bg-success',    'Qualified'],
        'closed_won'         => ['bg-success',    'Closed Won'],
        'closed_lost'        => ['bg-danger',     'Closed Lost'],
        // NOC statuses
        'approved'           => ['bg-success',    'Approved'],
        'pending'            => ['bg-warning text-dark', 'Pending'],
        'not_required'       => ['bg-secondary',  'Not Required'],
    ];

    [$classes, $label] = $map[$status] ?? ['bg-secondary', ucfirst(str_replace('_', ' ', $status))];

    return '<span class="badge ' . htmlspecialchars($classes) . '">'
         . htmlspecialchars($label)
         . '</span>';
}

// ─────────────────────────────────────────────────────────────────────────────
// URL Builders
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Generate a WhatsApp wa.me deep-link.
 *
 * @param  string $phone   International digits only (e.g. "923001234567")
 * @param  string $message Pre-filled message (plain text)
 * @return string          Full wa.me URL
 */
function getWhatsAppLink(string $phone, string $message = ''): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $url   = 'https://wa.me/' . $phone;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

// ─────────────────────────────────────────────────────────────────────────────
// HTML Builders
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Generate a Bootstrap breadcrumb nav.
 *
 * @param  array $items  Array of ['label' => string, 'url' => string|null]
 *                       The last item is treated as active (no link rendered).
 * @return string        HTML <nav> element
 */
function generateBreadcrumb(array $items): string
{
    if (empty($items)) {
        return '';
    }

    $html  = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $total = count($items);

    foreach ($items as $i => $item) {
        $label = htmlspecialchars($item['label'] ?? '');
        $isLast = ($i === $total - 1);

        if ($isLast || empty($item['url'])) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . $label . '</li>';
        } else {
            $url   = htmlspecialchars($item['url']);
            $html .= '<li class="breadcrumb-item"><a href="' . $url . '">' . $label . '</a></li>';
        }
    }

    $html .= '</ol></nav>';
    return $html;
}

// ─────────────────────────────────────────────────────────────────────────────
// Input Sanitization & Redirection
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Trim whitespace and escape HTML special characters from a string.
 *
 * @param  mixed $input
 * @return string
 */
function sanitize(mixed $input): string
{
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Perform an HTTP redirect and exit immediately.
 *
 * @param  string $url     Target URL (absolute or root-relative)
 * @param  int    $code    HTTP status code (default 302)
 */
if (!function_exists('redirect')) {
    function redirect(string $url, int $code = 302): never
    {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Session / Auth Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return true if an admin session is currently active.
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
}

/**
 * Redirect to the admin login page if no active session exists.
 */
if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (!isLoggedIn()) {
            redirect('/admin/login.php');
        }
    }
}
