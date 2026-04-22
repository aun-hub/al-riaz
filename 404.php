<?php
/**
 * Al-Riaz Associates — Custom 404 Page
 * Public URL: /404.php
 *
 * To use as the server-level 404 handler, add to .htaccess:
 *   ErrorDocument 404 /404.php
 *
 * Or in PHP for programmatic 404:
 *   header("HTTP/1.0 404 Not Found");
 *   include '404.php';
 *   exit;
 */

require_once 'config/config.php';
require_once 'includes/functions.php';

http_response_code(404);

$pageTitle = 'Page Not Found (404)';
$metaDesc  = 'The page you are looking for could not be found. Browse our property listings or return to the Al-Riaz Associates homepage.';

require_once 'includes/header.php';

$b = defined('BASE_PATH') ? BASE_PATH : '';
$waHref = 'https://wa.me/' . SITE_WHATSAPP . '?text=' . rawurlencode("Hello! I couldn't find what I was looking for on your website. Can you help?");
?>

<div class="error-page-wrap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-7 text-center">

                <!-- Illustration -->
                <div style="max-width:320px; margin:0 auto 1.25rem;">
                    <svg viewBox="0 0 320 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <!-- House silhouette -->
                        <rect x="80" y="120" width="160" height="100" rx="6" fill="#F0F4FF" stroke="#0F2044" stroke-width="2"/>
                        <polygon points="60,120 160,40 260,120" fill="#F59E0B" opacity="0.9"/>
                        <!-- Door -->
                        <rect x="130" y="170" width="40" height="50" rx="3" fill="#0F2044"/>
                        <circle cx="162" cy="196" r="3" fill="#F59E0B"/>
                        <!-- Windows -->
                        <rect x="90" y="145" width="30" height="25" rx="2" fill="#fff" stroke="#0F2044" stroke-width="1.5"/>
                        <rect x="200" y="145" width="30" height="25" rx="2" fill="#fff" stroke="#0F2044" stroke-width="1.5"/>
                        <!-- Question mark -->
                        <text x="155" y="30" font-family="Plus Jakarta Sans, Arial" font-size="40" font-weight="900" fill="#F59E0B" text-anchor="middle">?</text>
                        <!-- Ground -->
                        <rect x="20" y="218" width="280" height="8" rx="4" fill="#F0F4FF"/>
                        <!-- Clouds -->
                        <ellipse cx="50" cy="60" rx="25" ry="12" fill="#DDE4F5"/>
                        <ellipse cx="70" cy="52" rx="20" ry="10" fill="#DDE4F5"/>
                        <ellipse cx="260" cy="70" rx="22" ry="10" fill="#DDE4F5"/>
                        <ellipse cx="280" cy="62" rx="18" ry="9" fill="#DDE4F5"/>
                    </svg>
                </div>

                <div class="error-404-num">404</div>
                <h1 class="error-404-title">Page Not Found</h1>
                <p style="color:var(--text-secondary); font-size:1.05rem; max-width:460px; margin:0 auto 2rem;">
                    Oops! The page you are looking for doesn't exist or may have been moved.
                    Let's get you back on track.
                </p>

                <!-- Action buttons -->
                <div class="d-flex flex-wrap gap-3 justify-content-center mb-5">
                    <a href="<?= $b ?>/" class="btn-navy px-4" style="font-size:.95rem; padding:.75rem 1.75rem;">
                        <i class="fa-solid fa-house"></i> Go to Homepage
                    </a>
                    <a href="<?= $b ?>/search.php" class="btn-gold px-4" style="font-size:.95rem; padding:.75rem 1.75rem;">
                        <i class="fa-solid fa-magnifying-glass"></i> Browse Properties
                    </a>
                </div>

                <!-- Quick links -->
                <div style="background:#fff; border:1px solid var(--navy-100); border-radius:var(--radius-lg); padding:1.5rem; box-shadow:var(--shadow-sm); text-align:left;">
                    <p style="font-weight:700; color:var(--navy-800); font-size:.9rem; text-align:center; margin-bottom:1rem;">
                        <i class="fa-solid fa-compass" style="color:var(--gold);"></i> Quick Links
                    </p>
                    <div class="error-quick-links">
                        <a href="<?= $b ?>/residential.php?type=house" class="error-quick-link">
                            <i class="fa-solid fa-house" style="color:var(--gold);"></i> Houses
                        </a>
                        <a href="<?= $b ?>/residential.php?type=plot" class="error-quick-link">
                            <i class="fa-solid fa-map" style="color:var(--gold);"></i> Plots
                        </a>
                        <a href="<?= $b ?>/residential.php?type=flat" class="error-quick-link">
                            <i class="fa-solid fa-building" style="color:var(--gold);"></i> Apartments
                        </a>
                        <a href="<?= $b ?>/commercial.php" class="error-quick-link">
                            <i class="fa-solid fa-store" style="color:var(--gold);"></i> Commercial
                        </a>
                        <a href="<?= $b ?>/projects.php" class="error-quick-link">
                            <i class="fa-solid fa-city" style="color:var(--gold);"></i> Projects
                        </a>
                        <a href="<?= $b ?>/contact.php" class="error-quick-link">
                            <i class="fa-solid fa-envelope" style="color:var(--gold);"></i> Contact
                        </a>
                    </div>
                </div>

                <!-- WhatsApp CTA -->
                <div style="margin-top:1.5rem; background:var(--navy-50); border:1px solid var(--navy-100); border-radius:var(--radius-lg); padding:1rem 1.25rem; display:flex; align-items:center; gap:1rem; justify-content:center; flex-wrap:wrap;">
                    <i class="fa-brands fa-whatsapp" style="color:#25D366; font-size:2rem;"></i>
                    <div style="text-align:left;">
                        <p style="font-weight:700; margin-bottom:0; font-size:.9rem; color:var(--navy-800);">Need help finding a property?</p>
                        <a href="<?= htmlspecialchars($waHref) ?>" target="_blank" rel="noopener noreferrer"
                           style="color:#25D366; font-weight:700; text-decoration:none; font-size:.88rem;">
                            Chat with us on WhatsApp →
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
