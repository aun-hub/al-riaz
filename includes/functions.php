<?php
/**
 * Al-Riaz Associates — Shared Helper Functions
 * All general-purpose utilities used across public and admin pages.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

// ─────────────────────────────────────────────────────────────────────────────
// SMTP (DB-backed)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Read SMTP config from the `settings` table. Missing keys fall back to
 * sensible defaults so calling code never has to null-check.
 */
if (!function_exists('getSmtpConfig')) {
    function getSmtpConfig(bool $refresh = false): array {
        static $cache = null;
        if ($cache !== null && !$refresh) return $cache;

        $defaults = [
            'host'       => '',
            'port'       => 587,
            'user'       => '',
            'pass'       => '',
            'from_name'  => 'Al-Riaz Associates',
            'from_email' => '',
            'reply_to'   => '',
        ];
        $keys = ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','smtp_reply_to'];
        $map  = [];
        try {
            $db   = Database::getInstance();
            $in   = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($in)");
            $stmt->execute($keys);
            foreach ($stmt->fetchAll() as $row) {
                $map[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('[getSmtpConfig] ' . $e->getMessage());
        }
        $cache = [
            'host'       => $map['smtp_host']        ?? $defaults['host'],
            'port'       => (int)($map['smtp_port']  ?? $defaults['port']),
            'user'       => $map['smtp_user']        ?? $defaults['user'],
            'pass'       => $map['smtp_pass']        ?? $defaults['pass'],
            'from_name'  => $map['smtp_from_name']   ?? $defaults['from_name'],
            'from_email' => $map['smtp_from_email']  ?? $defaults['from_email'],
            'reply_to'   => $map['smtp_reply_to']    ?? $defaults['reply_to'],
        ];
        return $cache;
    }
}

/**
 * Upsert SMTP config into the `settings` table. Blank password is preserved
 * (so users don't have to retype it on every edit).
 */
if (!function_exists('saveSmtpConfig')) {
    function saveSmtpConfig(array $in): void {
        $map = [
            'smtp_host'       => (string)($in['host']       ?? ''),
            'smtp_port'       => (string)($in['port']       ?? 587),
            'smtp_user'       => (string)($in['user']       ?? ''),
            'smtp_from_name'  => (string)($in['from_name']  ?? ''),
            'smtp_from_email' => (string)($in['from_email'] ?? ''),
            'smtp_reply_to'   => (string)($in['reply_to']   ?? ''),
        ];
        if (!empty($in['pass'])) $map['smtp_pass'] = (string)$in['pass'];

        $db = Database::getInstance();
        $up = $db->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($map as $k => $v) {
            $up->execute([$k, $v]);
        }
        // Bust the in-request cache so subsequent reads see the new values.
        getSmtpConfig(true);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Agency Settings
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Load agency profile settings saved by admin/settings.php.
 * Values fall back to .env-sourced constants so public pages render correctly
 * before any admin save has occurred.
 */
if (!function_exists('getSettings')) {
    function getSettings(): array {
        static $cache = null;
        if ($cache !== null) return $cache;

        $defaults = [
            'agency_name'    => defined('SITE_NAME')     ? SITE_NAME     : '',
            'agency_tagline' => '',
            'phone'          => defined('SITE_PHONE')    ? SITE_PHONE    : '',
            'whatsapp'       => defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '',
            'email'          => defined('SITE_EMAIL')    ? SITE_EMAIL    : '',
            'address'        => '',
            'business_hours' => "Mon–Sat: 9:00 AM – 7:00 PM\nSunday: 11:00 AM – 4:00 PM",
            'website'        => defined('SITE_URL')      ? SITE_URL      : '',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'youtube_url'    => '',
            'logo_path'      => '',
        ];

        $file = __DIR__ . '/../config/settings.json';
        $saved = [];
        if (is_readable($file)) {
            $parsed = json_decode((string)file_get_contents($file), true);
            if (is_array($parsed)) $saved = $parsed;
        }

        $merged = array_merge($defaults, $saved);

        // A blank admin field should fall back to the default, not overwrite it with ''
        foreach ($defaults as $k => $v) {
            if ($v !== '' && (!isset($merged[$k]) || $merged[$k] === '')) {
                $merged[$k] = $v;
            }
        }

        $cache = $merged;
        return $cache;
    }
}

/**
 * Load all branch offices from the `branches` table, ordered for display.
 * Returns [] on any error so public pages fail closed rather than 500.
 */
if (!function_exists('getBranches')) {
    function getBranches(): array {
        static $cache = null;
        if ($cache !== null) return $cache;
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                'SELECT id, name, address, phone, hours, hours_schedule, is_hq, sort_order
                 FROM branches
                 ORDER BY sort_order ASC, id ASC'
            );
            $cache = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log('[getBranches] ' . $e->getMessage());
            $cache = [];
        }
        return $cache;
    }
}

/**
 * Return a default 7-day schedule (Mon–Sat 9AM–7PM, Sun 11AM–4PM).
 */
if (!function_exists('defaultBusinessHoursSchedule')) {
    function defaultBusinessHoursSchedule(): array {
        $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        $out = [];
        foreach ($days as $d) {
            $out[] = [
                'day'  => $d,
                'open' => true,
                'from' => $d === 'Sun' ? '11:00' : '09:00',
                'to'   => $d === 'Sun' ? '16:00' : '19:00',
            ];
        }
        return $out;
    }
}

/**
 * Coerce any JSON/array schedule input into a canonical 7-day array.
 * Missing days fall back to the default week.
 */
if (!function_exists('normalizeSchedule')) {
    function normalizeSchedule(mixed $input): array {
        $defaults = defaultBusinessHoursSchedule();
        if (is_string($input)) $input = json_decode($input, true);
        if (!is_array($input)) return $defaults;

        $byDay = [];
        foreach ($input as $row) {
            if (!is_array($row) || empty($row['day'])) continue;
            $byDay[$row['day']] = $row;
        }

        $out = [];
        foreach ($defaults as $d) {
            $saved = $byDay[$d['day']] ?? null;
            if (!$saved) { $out[] = $d; continue; }
            $out[] = [
                'day'  => $d['day'],
                'open' => !empty($saved['open']),
                'from' => preg_match('/^\d{2}:\d{2}$/', $saved['from'] ?? '') ? $saved['from'] : $d['from'],
                'to'   => preg_match('/^\d{2}:\d{2}$/', $saved['to']   ?? '') ? $saved['to']   : $d['to'],
            ];
        }
        return $out;
    }
}

/**
 * Take a $_POST['hours']-shaped array (keyed by day name with open/from/to)
 * and return a canonical schedule. Invalid times fall back to the defaults.
 */
if (!function_exists('parsePostedSchedule')) {
    function parsePostedSchedule(mixed $posted): array {
        if (!is_array($posted)) return defaultBusinessHoursSchedule();
        $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        $out = [];
        foreach ($days as $d) {
            $row  = $posted[$d] ?? [];
            $open = !empty($row['open']);
            $from = (string)($row['from'] ?? '09:00');
            $to   = (string)($row['to']   ?? '19:00');
            if (!preg_match('/^\d{2}:\d{2}$/', $from)) $from = '09:00';
            if (!preg_match('/^\d{2}:\d{2}$/', $to))   $to   = '19:00';
            $out[] = ['day'=>$d, 'open'=>$open, 'from'=>$from, 'to'=>$to];
        }
        return $out;
    }
}

/**
 * Return the list of nav items for a given location. Falls back to
 * built-in defaults so the site renders correctly before any admin save.
 *
 * $location is one of: 'header', 'footer_quick', 'footer_property_types'.
 * Each item: ['label' => string, 'url' => string].
 */
if (!function_exists('getNavItems')) {
    function getNavItems(string $location): array {
        static $defaults = [
            'header' => [
                ['label' => 'Home',       'url' => '/'],
                ['label' => 'Projects',   'url' => '/projects.php'],
                ['label' => 'Properties', 'url' => '/listings.php'],
                ['label' => 'About',      'url' => '/about.php'],
                ['label' => 'Contact',    'url' => '/contact.php'],
            ],
            'footer_quick' => [
                ['label' => 'Home',           'url' => '/'],
                ['label' => 'Projects',       'url' => '/projects.php'],
                ['label' => 'All Properties', 'url' => '/listings.php'],
                ['label' => 'Residential',    'url' => '/listings.php?category=residential'],
                ['label' => 'Commercial',     'url' => '/listings.php?category=commercial'],
                ['label' => 'For Rent',       'url' => '/listings.php?purpose=rent'],
                ['label' => 'About Us',       'url' => '/about.php'],
                ['label' => 'Contact',        'url' => '/contact.php'],
            ],
            'footer_property_types' => [
                ['label' => 'Houses',       'url' => '/listings.php?category=residential&type[]=house'],
                ['label' => 'Plots',        'url' => '/listings.php?category=residential&type[]=plot'],
                ['label' => 'Apartments',   'url' => '/listings.php?category=residential&type[]=flat'],
                ['label' => 'Farmhouses',   'url' => '/listings.php?category=residential&type[]=farmhouse'],
                ['label' => 'Penthouses',   'url' => '/listings.php?category=residential&type[]=penthouse'],
                ['label' => 'Commercial',   'url' => '/listings.php?category=commercial'],
            ],
        ];

        if (!array_key_exists($location, $defaults)) return [];

        $settings = getSettings();
        $key = 'nav_' . $location;
        $saved = $settings[$key] ?? null;
        if (is_string($saved)) $saved = json_decode($saved, true);
        if (!is_array($saved)) return $defaults[$location];

        $out = [];
        foreach ($saved as $row) {
            if (!is_array($row)) continue;
            $label = trim((string)($row['label'] ?? ''));
            $url   = trim((string)($row['url']   ?? ''));
            if ($label === '' || $url === '') continue;
            $out[] = ['label' => $label, 'url' => $url];
        }
        // If the admin wiped the list clean, fall back to defaults to avoid a broken nav.
        return $out ?: $defaults[$location];
    }
}

/**
 * Return the grouped option list used by the admin nav-item dropdown.
 * Keyed: ['Group Label' => [url => human label, ...], ...].
 * Projects are appended dynamically if the table is reachable.
 */
if (!function_exists('getNavUrlChoices')) {
    function getNavUrlChoices(): array {
        $choices = [
            'Pages' => [
                '/'             => 'Home',
                '/listings.php' => 'All Properties',
                '/projects.php' => 'All Projects',
                '/about.php'    => 'About',
                '/contact.php'  => 'Contact',
            ],
            'Listings filters' => [
                '/listings.php?category=residential' => 'Residential',
                '/listings.php?category=commercial'  => 'Commercial',
                '/listings.php?purpose=sale'         => 'For Sale',
                '/listings.php?purpose=rent'         => 'For Rent',
            ],
            'Property types' => [
                '/listings.php?category=residential&type[]=house'          => 'Houses',
                '/listings.php?category=residential&type[]=flat'           => 'Apartments (Flats)',
                '/listings.php?category=residential&type[]=upper_portion'  => 'Upper Portions',
                '/listings.php?category=residential&type[]=lower_portion'  => 'Lower Portions',
                '/listings.php?category=residential&type[]=farmhouse'      => 'Farmhouses',
                '/listings.php?category=residential&type[]=penthouse'      => 'Penthouses',
                '/listings.php?category=plot&type[]=plot'                  => 'Plots',
                '/listings.php?category=commercial&type[]=shop'            => 'Shops',
                '/listings.php?category=commercial&type[]=office'          => 'Offices',
                '/listings.php?category=commercial&type[]=warehouse'       => 'Warehouses',
            ],
        ];

        try {
            $db = Database::getInstance();
            $rows = $db->query("SELECT name, slug FROM projects WHERE is_published=1 ORDER BY name ASC")->fetchAll();
            if ($rows) {
                $choices['Projects'] = [];
                foreach ($rows as $p) {
                    $slug = trim((string)($p['slug'] ?? ''));
                    $name = trim((string)($p['name'] ?? ''));
                    if ($slug === '' || $name === '') continue;
                    $choices['Projects']['/project/' . $slug] = $name;
                }
            }
        } catch (Throwable $e) { /* projects table may not exist in fresh install */ }

        return $choices;
    }
}

/**
 * Resolve a stored media URL to something the browser can load.
 *   - Absolute / protocol-relative URLs (http, https, //…) pass through.
 *   - Rooted paths like "/assets/uploads/..." get BASE_PATH prefixed.
 *   - Bare filenames like "photo_abc.jpg" get BASE_PATH + "/assets/uploads/".
 * Returns '' for empty input.
 */
if (!function_exists('mediaUrl')) {
    function mediaUrl(?string $url): string {
        $url = trim((string)$url);
        if ($url === '') return '';
        if (preg_match('#^(https?:)?//#i', $url)) return $url;
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        if ($url[0] === '/') return $base . $url;
        return $base . '/assets/uploads/' . ltrim($url, '/');
    }
}

/**
 * Resolve a nav URL, prefixing BASE_PATH for root-relative paths while
 * leaving absolute URLs untouched.
 */
if (!function_exists('navLinkUrl')) {
    function navLinkUrl(string $url): string {
        $url = trim($url);
        if ($url === '') return '#';
        // Absolute (scheme or scheme-relative) or anchor / mailto / tel
        if (preg_match('#^(https?:)?//#i', $url) || $url[0] === '#'
            || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }
        if ($url[0] === '/') {
            $base = defined('BASE_PATH') ? BASE_PATH : '';
            return $base . $url;
        }
        return $url; // relative path — leave as-is
    }
}

/**
 * Main-office business hours schedule (from settings.json).
 */
if (!function_exists('getBusinessHoursSchedule')) {
    function getBusinessHoursSchedule(): array {
        $settings = getSettings();
        return normalizeSchedule($settings['business_hours_schedule'] ?? null);
    }
}

/**
 * Format a schedule array into human-readable lines, grouping consecutive
 * days with matching hours into ranges.
 *   [{Mon,open,9-19}, {Tue,open,9-19}, …, {Sun,closed}]
 *   → "Mon–Sat: 9:00 AM – 7:00 PM\nSunday: Closed"
 */
if (!function_exists('formatBusinessHours')) {
    function formatBusinessHours(array $schedule): string {
        $fmt12 = function(string $hhmm): string {
            [$h, $m] = array_pad(explode(':', $hhmm), 2, '00');
            $h = (int)$h; $m = (int)$m;
            $suffix = $h >= 12 ? 'PM' : 'AM';
            $h12 = $h % 12; if ($h12 === 0) $h12 = 12;
            return $m === 0
                ? sprintf('%d:00 %s', $h12, $suffix)
                : sprintf('%d:%02d %s', $h12, $m, $suffix);
        };
        $longName = [
            'Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday',
            'Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday',
        ];

        $lines = [];
        $i = 0;
        $n = count($schedule);
        while ($i < $n) {
            $row = $schedule[$i];
            $j = $i;
            // Extend as long as next day has identical hours
            while ($j + 1 < $n
                && !empty($row['open']) === !empty($schedule[$j+1]['open'])
                && ($row['from'] ?? '') === ($schedule[$j+1]['from'] ?? '')
                && ($row['to']   ?? '') === ($schedule[$j+1]['to']   ?? '')) {
                $j++;
            }

            $label = ($i === $j)
                ? ($longName[$row['day']] ?? $row['day'])
                : $row['day'] . '–' . $schedule[$j]['day'];

            if (empty($row['open'])) {
                $lines[] = "$label: Closed";
            } else {
                $lines[] = sprintf('%s: %s – %s', $label, $fmt12($row['from']), $fmt12($row['to']));
            }
            $i = $j + 1;
        }
        return implode("\n", $lines);
    }
}

/**
 * Resolve the HQ office. Returns a normalized row with
 *   ['name','address','phone','hours'] plus a ['source'] key of 'main' or 'branch'.
 * A branch flagged is_hq=1 wins; otherwise the main office from Agency Profile.
 */
if (!function_exists('getHqOffice')) {
    function getHqOffice(): array {
        $settings = getSettings();
        foreach (getBranches() as $b) {
            if (!empty($b['is_hq'])) {
                return [
                    'source'  => 'branch',
                    'id'      => (int)($b['id'] ?? 0),
                    'name'    => $b['name']    ?: 'Head Office',
                    'address' => $b['address'] ?? '',
                    'phone'   => $b['phone']   ?? '',
                    'hours'   => $b['hours']   ?? '',
                ];
            }
        }
        return [
            'source'  => 'main',
            'id'      => 0,
            'name'    => 'Main Office',
            'address' => $settings['address'] ?: 'Islamabad, Pakistan',
            'phone'   => $settings['phone']   ?: '',
            'hours'   => formatBusinessHours(getBusinessHoursSchedule()),
        ];
    }
}

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
        $isAbsolute = preg_match('#^(https?:)?//#i', $url);
        if (!$isAbsolute && $url !== '' && $url[0] === '/' && BASE_PATH !== ''
            && strpos($url, BASE_PATH . '/') !== 0 && $url !== BASE_PATH) {
            $url = BASE_PATH . $url;
        }
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
