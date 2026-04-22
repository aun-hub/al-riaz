<?php
/**
 * Al-Riaz Associates — Master Configuration
 * Reads credentials from the root .env file.
 */

// ── Load .env ─────────────────────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and blank lines
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        // Parse KEY=VALUE (value may contain = signs)
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key   = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        // Strip inline comments (e.g. VAR=value # comment)
        if (($hash = strpos($value, ' #')) !== false) {
            $value = trim(substr($value, 0, $hash));
        }
        // Strip surrounding quotes if present
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[-1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        // Set in $_ENV and putenv (do not overwrite real env vars)
        if (!isset($_ENV[$key])) {
            $_ENV[$key]  = $value;
            putenv("$key=$value");
        }
    }
}

// Shorthand helper — reads from $_ENV with a fallback default
function env(string $key, mixed $default = ''): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'alriaz_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// ── App ───────────────────────────────────────────────────────────────────────
define('APP_ENV',   env('APP_ENV',   'local'));
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');
define('APP_URL',   env('APP_URL',   'http://localhost/al-riaz'));

// Base path extracted from APP_URL — used to prefix asset hrefs in views.
// e.g. http://localhost/al-riaz  →  /al-riaz
// e.g. http://mysite.com         →  (empty string)
define('BASE_PATH', rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/'));

// ── Site Identity ─────────────────────────────────────────────────────────────
define('SITE_NAME',      env('SITE_NAME',      'Al-Riaz Associates'));
define('SITE_URL',       env('APP_URL',        'http://localhost/al-riaz'));
define('SITE_PHONE',     env('SITE_PHONE',     '+92 300 123 4567'));
define('SITE_WHATSAPP',  env('SITE_WHATSAPP',  '923001234567'));
define('SITE_EMAIL',     env('SITE_EMAIL',     'info@alriazassociates.pk'));

// ── Mail ──────────────────────────────────────────────────────────────────────
define('MAIL_HOST',         env('MAIL_HOST',         'smtp.gmail.com'));
define('MAIL_PORT',         env('MAIL_PORT',         '587'));
define('MAIL_USERNAME',     env('MAIL_USERNAME',     ''));
define('MAIL_PASSWORD',     env('MAIL_PASSWORD',     ''));
define('MAIL_FROM_NAME',    env('MAIL_FROM_NAME',    'Al-Riaz Associates'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'info@alriazassociates.pk'));
define('MAIL_REPLY_TO',     env('MAIL_REPLY_TO',     'info@alriazassociates.pk'));

// ── File Uploads ──────────────────────────────────────────────────────────────
define('UPLOAD_DIR',          __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE',       (int) env('MAX_FILE_SIZE_MB', '5') * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// ── Error Handling ────────────────────────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ─────────────────────────────────────────────────────────────────────────────
// Utility Functions
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Format a PKR amount into Pakistani denominations.
 * formatPKR(35000000) → "3.5 Crore"
 * formatPKR(4500000)  → "45 Lakh"
 */
function formatPKR(int|float $amount): string
{
    if ($amount <= 0) {
        return 'Price on Demand';
    }
    $crore = 10_000_000;
    $lakh  = 100_000;
    if ($amount >= $crore) {
        $val = $amount / $crore;
        return rtrim(rtrim(number_format($val, 2), '0'), '.') . ' Crore';
    }
    if ($amount >= $lakh) {
        $val = $amount / $lakh;
        return rtrim(rtrim(number_format($val, 2), '0'), '.') . ' Lakh';
    }
    return 'PKR ' . number_format((int) $amount);
}

/**
 * Format a property area measurement.
 * formatArea(10, 'marla') → "10 Marla"
 */
function formatArea(int|float $value, string $unit): string
{
    $labels = [
        'marla'   => 'Marla',
        'kanal'   => 'Kanal',
        'sq_ft'   => 'Sq Ft',
        'sq_yard' => 'Sq Yard',
        'acre'    => 'Acre',
    ];
    $label = $labels[strtolower($unit)] ?? ucfirst($unit);
    if ($value == (int) $value) {
        return number_format((int) $value) . ' ' . $label;
    }
    return rtrim(rtrim(number_format($value, 2), '0'), '.') . ' ' . $label;
}

/**
 * Build a WhatsApp wa.me deep-link with a pre-filled message.
 */
function waLink(string $phone, string $message = ''): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $url   = 'https://wa.me/' . $phone;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

/**
 * Convert a string to a URL-safe slug.
 * makeSlug("Bahria Town Phase 8") → "bahria-town-phase-8"
 */
function makeSlug(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}
