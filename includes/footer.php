<?php
/**
 * Al-Riaz Associates — Public Page Footer
 * Include at the bottom of every public-facing PHP page, just before </body>.
 */

$currentYear = date('Y');
$b = defined('BASE_PATH') ? BASE_PATH : '';
$settings = function_exists('getSettings') ? getSettings() : [];
$s = function(string $k, string $fallback = '') use ($settings): string {
    return !empty($settings[$k]) ? (string)$settings[$k] : $fallback;
};
$agencyName    = $s('agency_name', defined('SITE_NAME') ? SITE_NAME : 'Al-Riaz Associates');
$agencyPhone   = $s('phone',       defined('SITE_PHONE') ? SITE_PHONE : '');
$agencyWa      = $s('whatsapp',    defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '');
$agencyEmail   = $s('email',       defined('SITE_EMAIL') ? SITE_EMAIL : '');
$agencyAddress = $s('address',     'Islamabad, Pakistan');
$facebookUrl   = $s('facebook_url');
$instagramUrl  = $s('instagram_url');
$youtubeUrl    = $s('youtube_url');
?>

<!-- ══════════════════════════════════════════════════════════
     MAIN FOOTER
     ══════════════════════════════════════════════════════════ -->
<footer class="site-footer" aria-label="Site footer">

    <div class="footer-gradient-bar" aria-hidden="true"></div>

    <div class="footer-body">
        <div class="container">
            <div class="row g-5">

                <!-- Col 1 — Brand ─────────────────────────────────────────── -->
                <div class="col-lg-4 col-md-6 footer-brand-col">
                    <div class="footer-brand">
                        <?php $footerLogo = $settings['logo_path'] ?? ''; ?>
                        <?php if ($footerLogo): ?>
                            <img class="footer-brand-icon" src="<?= htmlspecialchars($b . $footerLogo) ?>?v=<?= @filemtime(__DIR__ . '/..' . $footerLogo) ?: '' ?>"
                                 alt="<?= htmlspecialchars($agencyName) ?>"
                                 style="object-fit:contain; background:transparent; box-shadow:none;">
                        <?php else: ?>
                            <div class="footer-brand-icon" aria-hidden="true">AR</div>
                        <?php endif; ?>
                        <div>
                            <div class="footer-brand-name"><?= htmlspecialchars($agencyName) ?></div>
                            <div class="footer-brand-sub">Real Estate</div>
                        </div>
                    </div>
                    <p class="footer-desc">
                        Your trusted partner for real estate in Pakistan. We specialize in residential, commercial, and investment properties across Islamabad, Rawalpindi, and beyond.
                    </p>
                    <div class="footer-social" aria-label="Social media links">
                        <?php if ($facebookUrl): ?>
                        <a href="<?= htmlspecialchars($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" aria-label="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($instagramUrl): ?>
                        <a href="<?= htmlspecialchars($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" aria-label="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($youtubeUrl): ?>
                        <a href="<?= htmlspecialchars($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" aria-label="YouTube">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($agencyWa): ?>
                        <a href="https://wa.me/<?= htmlspecialchars($agencyWa) ?>" target="_blank" rel="noopener noreferrer"
                           class="footer-social-btn" aria-label="WhatsApp" style="background:#25D366; border-color:#25D366; color:#fff;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Col 2 — Quick Links ────────────────────────────────────── -->
                <div class="col-lg-2 col-md-3 col-6 footer-col">
                    <div class="footer-col-title">Quick Links</div>
                    <?php foreach (getNavItems('footer_quick') as $item): ?>
                    <a href="<?= htmlspecialchars(navLinkUrl($item['url'])) ?>" class="footer-link"><?= htmlspecialchars($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- Col 3 — Property Types ─────────────────────────────────── -->
                <div class="col-lg-2 col-md-3 col-6 footer-col">
                    <div class="footer-col-title">Property Types</div>
                    <?php foreach (getNavItems('footer_property_types') as $item): ?>
                    <a href="<?= htmlspecialchars(navLinkUrl($item['url'])) ?>" class="footer-link"><?= htmlspecialchars($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- Col 4 — Contact ────────────────────────────────────────── -->
                <div class="col-lg-4 col-md-6 footer-col">
                    <div class="footer-col-title">Contact Us</div>

                    <div class="footer-contact-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?= nl2br(htmlspecialchars($agencyAddress)) ?></span>
                    </div>
                    <?php if ($agencyPhone): ?>
                    <div class="footer-contact-item">
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:<?= htmlspecialchars($agencyPhone) ?>" class="footer-link" style="margin:0;">
                            <?= htmlspecialchars($agencyPhone) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($agencyEmail): ?>
                    <div class="footer-contact-item">
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:<?= htmlspecialchars($agencyEmail) ?>" class="footer-link" style="margin:0;">
                            <?= htmlspecialchars($agencyEmail) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($agencyWa): ?>
                    <div class="footer-contact-item">
                        <i class="fa-brands fa-whatsapp" style="color:#25D366;"></i>
                        <a href="https://wa.me/<?= htmlspecialchars($agencyWa) ?>?text=<?= rawurlencode('Hi, I need assistance with a property.') ?>"
                           target="_blank" rel="noopener noreferrer" class="footer-link" style="margin:0;">
                            WhatsApp Us
                        </a>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:1.25rem;">
                        <a href="https://maps.google.com/?q=<?= rawurlencode($agencyAddress) ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="btn-outline-white" style="font-size:0.8rem; padding:0.5rem 1rem;">
                            <i class="fa-solid fa-map"></i> Get Directions
                        </a>
                    </div>
                </div>

            </div><!-- /.row -->
        </div><!-- /.container -->
    </div><!-- /.footer-body -->

    <!-- Bottom bar -->
    <div class="footer-bottom">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="footer-bottom-text mb-0">
                    &copy; <?= $currentYear ?> <a href="<?= $b ?>/"><?= htmlspecialchars($agencyName) ?></a>. All Rights Reserved.
                </p>
                <p class="footer-bottom-text mb-0">
                    Designed &amp; Developed with <i class="fa-solid fa-heart" style="color:var(--gold);"></i> in Pakistan
                </p>
            </div>
        </div>
    </div>

</footer>

<!-- ══════════════════════════════════════════════════════════
     SCRIPTS
     ══════════════════════════════════════════════════════════ -->

<!-- jQuery 3.7.1 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Bootstrap 5.3.2 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Main JS (auto-busts on file change) -->
<?php
    $jsPath = __DIR__ . '/../assets/js/main.js';
    $jsVer  = file_exists($jsPath) ? filemtime($jsPath) : time();
?>
<script src="<?= $b ?>/assets/js/main.js?v=<?= $jsVer ?>"></script>

</body>
</html>
