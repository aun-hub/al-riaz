<?php
/**
 * Al-Riaz Associates — Public Page Header
 * Include at the top of every public-facing PHP page.
 *
 * Expected variables (set before including this file):
 *   $pageTitle  string  — <title> text (without site name suffix)
 *   $metaDesc   string  — optional meta description
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = isset($pageTitle) ? htmlspecialchars(trim($pageTitle)) : '';
$metaDesc  = isset($metaDesc)  ? htmlspecialchars(trim($metaDesc))  : 'Al-Riaz Associates — Trusted Real Estate Agency in Pakistan. Find houses, plots, apartments and commercial properties for sale and rent in Islamabad, Rawalpindi and across Pakistan.';

// Strip BASE_PATH prefix from REQUEST_URI so active-nav detection still works
// when the app lives in a subdirectory like /al-riaz/.
$requestUri  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$currentUri  = BASE_PATH !== '' && str_starts_with($requestUri, BASE_PATH)
               ? substr($requestUri, strlen(BASE_PATH)) ?: '/'
               : $requestUri;

// Admin-editable nav items (Settings → Navigation tab). Shape: ['label', 'url'].
$navLinks = [];
foreach (getNavItems('header') as $item) {
    $navLinks[] = ['href' => $item['url'], 'label' => $item['label']];
}

// Auto-append Notices if the admin hasn't added it themselves yet.
$hasNotices = false;
foreach ($navLinks as $link) {
    if (preg_match('~/?notices\.php$~', (string)$link['href'])) { $hasNotices = true; break; }
}
if (!$hasNotices) {
    $navLinks[] = ['href' => '/notices.php', 'label' => 'Notices'];
}

// Legacy single-category entry points should still light up the Properties nav.
$propertiesAliases = ['/listings.php', '/residential.php', '/commercial.php', '/rent.php'];

function isActiveNav(string $href, string $currentUri): bool
{
    global $propertiesAliases;
    if ($href === '/' && $currentUri === '/') return true;
    if ($href === '/listings.php') {
        foreach ($propertiesAliases as $alias) {
            if (str_starts_with($currentUri, $alias)) return true;
        }
        return false;
    }
    if ($href !== '/' && str_starts_with($currentUri, $href)) return true;
    return false;
}

$b    = BASE_PATH; // shorthand for use inside HTML
$waUrl = 'https://wa.me/' . SITE_WHATSAPP . '?text=' . rawurlencode('Hi, I am interested in a property. Can you help me?');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="theme-color" content="#0F2044">

    <title><?= $pageTitle ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME ?></title>

    <!-- Favicon (admin logo when configured, bundled default otherwise) -->
    <?php $fav = faviconAsset(); ?>
    <link rel="icon" type="<?= htmlspecialchars($fav['mime'], ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($fav['url'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($fav['url'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- Google Fonts: Plus Jakarta Sans + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap">

    <!-- Bootstrap 5.3.2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome 6.5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Main stylesheet (auto-busts on file change) -->
    <?php
        $cssPath = __DIR__ . '/../assets/css/style.css';
        $cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
    ?>
    <link rel="stylesheet" href="<?= $b ?>/assets/css/style.css?v=<?= $cssVer ?>">

    <?php
        // ── Dynamic theme overrides ─────────────────────────────────────
        // Admin sets these in /admin/settings.php → Theme. We override the
        // two base brand vars (--gold accent, --navy-700 brand) and derive
        // the rest of the gold / navy scale from them via color-mix().
        $themeSettings  = function_exists('getSettings') ? getSettings() : [];
        $themePrimary   = $themeSettings['theme_primary']   ?? '#F5B301';
        $themeSecondary = $themeSettings['theme_secondary'] ?? '#0F2044';
        // Defensive: must be a 6-digit hex; ignore anything else.
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$themePrimary))   $themePrimary   = '#F5B301';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$themeSecondary)) $themeSecondary = '#0F2044';
    ?>
    <style id="dynamicTheme">
      :root {
        /* Admin-picked brand colors */
        --gold:     <?= $themePrimary ?>;
        --navy-700: <?= $themeSecondary ?>;

        /* Gold shades derived from --gold */
        --gold-light: color-mix(in srgb, var(--gold) 70%, white);
        --gold-dark:  color-mix(in srgb, var(--gold) 75%, black);
        --gold-soft:  color-mix(in srgb, var(--gold) 15%, white);

        /* Navy shades lighter than --navy-700 */
        --navy-600: color-mix(in srgb, var(--navy-700) 85%, white);
        --navy-500: color-mix(in srgb, var(--navy-700) 75%, white);
        --navy-400: color-mix(in srgb, var(--navy-700) 60%, white);
        --navy-300: color-mix(in srgb, var(--navy-700) 45%, white);
        --navy-200: color-mix(in srgb, var(--navy-700) 30%, white);
        --navy-100: color-mix(in srgb, var(--navy-700) 15%, white);
        --navy-50:  color-mix(in srgb, var(--navy-700)  5%, white);

        /* Navy shades darker than --navy-700 */
        --navy-800: color-mix(in srgb, var(--navy-700) 80%, black);
        --navy-900: color-mix(in srgb, var(--navy-700) 50%, black);
        --navy-950: color-mix(in srgb, var(--navy-700) 25%, black);

        /* Focus ring */
        --ring-gold: 0 0 0 3px color-mix(in srgb, var(--gold) 25%, transparent);
      }
    </style>

    <!-- Open Graph -->
    <meta property="og:title"       content="<?= $pageTitle ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="<?= htmlspecialchars(SITE_NAME) ?>">

    <!-- Base path for JS AJAX calls -->
    <script>window.APP_BASE = <?= json_encode(BASE_PATH) ?>;</script>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     MAIN NAVBAR
     ══════════════════════════════════════════════════════════ -->
<nav class="site-navbar transparent" id="siteNavbar" aria-label="Main navigation">
    <div class="container">
        <div class="navbar-inner">

            <!-- Brand -->
            <?php $brandSettings = getSettings(); $brandLogo = $brandSettings['logo_path'] ?? ''; $brandName = $brandSettings['agency_name'] ?: SITE_NAME; ?>
            <a class="brand-logo" href="<?= $b ?>/" aria-label="<?= htmlspecialchars($brandName) ?> — Home">
                <?php if ($brandLogo): ?>
                    <img class="brand-icon" src="<?= htmlspecialchars($b . $brandLogo) ?>?v=<?= @filemtime(__DIR__ . '/..' . $brandLogo) ?: '' ?>"
                         alt="<?= htmlspecialchars($brandName) ?>"
                         style="object-fit:contain; background:transparent; box-shadow:none;">
                <?php else: ?>
                    <div class="brand-icon" aria-hidden="true">AR</div>
                <?php endif; ?>
                <div>
                    <div class="brand-name"><?= htmlspecialchars($brandName) ?></div>
                    <div class="brand-tagline">Real Estate</div>
                </div>
            </a>

            <!-- Desktop nav links -->
            <ul class="nav-links" role="list">
                <?php foreach ($navLinks as $link): ?>
                    <?php $active = isActiveNav($link['href'], $currentUri); ?>
                    <li>
                        <a href="<?= htmlspecialchars(navLinkUrl($link['href'])) ?>"
                           class="nav-link-item<?= $active ? ' active' : '' ?>"
                           <?= $active ? 'aria-current="page"' : '' ?>>
                            <?= htmlspecialchars($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Desktop CTA + hamburger -->
            <div class="nav-actions">
                <a href="<?= $b ?>/contact.php" class="btn-gold d-none d-lg-inline-flex" style="font-size:0.825rem; padding:0.6rem 1.25rem;">
                    <i class="fa-solid fa-headset"></i> Get in Touch
                </a>
                <button class="nav-hamburger" id="navHamburger" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileNav">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

        </div><!-- /.navbar-inner -->
    </div><!-- /.container -->
</nav>

<!-- ══════════════════════════════════════════════════════════
     MOBILE NAV DRAWER
     ══════════════════════════════════════════════════════════ -->
<div class="mobile-nav" id="mobileNav" role="dialog" aria-modal="true" aria-label="Navigation menu">
    <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close navigation menu">
        <i class="fa-solid fa-xmark"></i>
    </button>

    <nav>
        <?php foreach ($navLinks as $link): ?>
            <?php $active = isActiveNav($link['href'], $currentUri); ?>
            <a href="<?= htmlspecialchars(navLinkUrl($link['href'])) ?>"
               class="mobile-nav-link<?= $active ? ' active' : '' ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
                <?= htmlspecialchars($link['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div style="margin-top:2rem;">
        <a href="<?= $waUrl ?>" target="_blank" rel="noopener noreferrer"
           class="btn-gold" style="display:inline-flex; width:100%; justify-content:center;">
            <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     FLOATING WHATSAPP BUTTON
     ══════════════════════════════════════════════════════════ -->
<a href="<?= $waUrl ?>"
   target="_blank"
   rel="noopener noreferrer"
   class="wa-float"
   aria-label="Chat with us on WhatsApp"
   title="Chat on WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
</a>

<!-- ══════════════════════════════════════════════════════════
     BACK TO TOP
     ══════════════════════════════════════════════════════════ -->
<button type="button" class="back-to-top" aria-label="Back to top" title="Back to top">
    <i class="fa-solid fa-arrow-up"></i>
</button>
