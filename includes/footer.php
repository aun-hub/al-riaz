<?php
/**
 * Al-Riaz Associates — Public Page Footer
 * Include at the bottom of every public-facing PHP page, just before </body>.
 */

$currentYear = date('Y');
$b = defined('BASE_PATH') ? BASE_PATH : '';
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
                        <div class="footer-brand-icon" aria-hidden="true">AR</div>
                        <div>
                            <div class="footer-brand-name"><?= htmlspecialchars(SITE_NAME) ?></div>
                            <div class="footer-brand-sub">Real Estate</div>
                        </div>
                    </div>
                    <p class="footer-desc">
                        Your trusted partner for real estate in Pakistan. We specialize in residential, commercial, and investment properties across Islamabad, Rawalpindi, and beyond.
                    </p>
                    <div class="footer-social" aria-label="Social media links">
                        <a href="#" class="footer-social-btn" aria-label="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#" class="footer-social-btn" aria-label="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" class="footer-social-btn" aria-label="YouTube">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <a href="https://wa.me/<?= SITE_WHATSAPP ?>" target="_blank" rel="noopener noreferrer"
                           class="footer-social-btn" aria-label="WhatsApp" style="background:#25D366; border-color:#25D366; color:#fff;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2 — Quick Links ────────────────────────────────────── -->
                <div class="col-lg-2 col-md-3 col-6 footer-col">
                    <div class="footer-col-title">Quick Links</div>
                    <a href="<?= $b ?>/"                class="footer-link">Home</a>
                    <a href="<?= $b ?>/projects.php"    class="footer-link">Projects</a>
                    <a href="<?= $b ?>/residential.php" class="footer-link">Residential</a>
                    <a href="<?= $b ?>/commercial.php"  class="footer-link">Commercial</a>
                    <a href="<?= $b ?>/rent.php"        class="footer-link">Rent Property</a>
                    <a href="<?= $b ?>/about.php"       class="footer-link">About Us</a>
                    <a href="<?= $b ?>/contact.php"     class="footer-link">Contact</a>
                </div>

                <!-- Col 3 — Property Types ─────────────────────────────────── -->
                <div class="col-lg-2 col-md-3 col-6 footer-col">
                    <div class="footer-col-title">Property Types</div>
                    <a href="<?= $b ?>/residential.php?type=house"      class="footer-link">Houses</a>
                    <a href="<?= $b ?>/residential.php?type=plot"       class="footer-link">Plots</a>
                    <a href="<?= $b ?>/residential.php?type=flat"       class="footer-link">Apartments</a>
                    <a href="<?= $b ?>/commercial.php"                  class="footer-link">Commercial</a>
                    <a href="<?= $b ?>/residential.php?type=farmhouse"  class="footer-link">Farmhouses</a>
                    <a href="<?= $b ?>/residential.php?type=penthouse"  class="footer-link">Penthouses</a>
                </div>

                <!-- Col 4 — Contact ────────────────────────────────────────── -->
                <div class="col-lg-4 col-md-6 footer-col">
                    <div class="footer-col-title">Contact Us</div>

                    <div class="footer-contact-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Office #5, Blue Area,<br>Islamabad, Pakistan</span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:<?= SITE_PHONE ?>" class="footer-link" style="margin:0;">
                            <?= htmlspecialchars(SITE_PHONE) ?>
                        </a>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:<?= SITE_EMAIL ?>" class="footer-link" style="margin:0;">
                            <?= htmlspecialchars(SITE_EMAIL) ?>
                        </a>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fa-brands fa-whatsapp" style="color:#25D366;"></i>
                        <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=<?= rawurlencode('Hi, I need assistance with a property.') ?>"
                           target="_blank" rel="noopener noreferrer" class="footer-link" style="margin:0;">
                            WhatsApp Us
                        </a>
                    </div>

                    <div style="margin-top:1.25rem;">
                        <a href="https://maps.google.com/?q=Blue+Area+Islamabad+Pakistan"
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
                    &copy; <?= $currentYear ?> <a href="<?= $b ?>/"><?= htmlspecialchars(SITE_NAME) ?></a>. All Rights Reserved.
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

<!-- Main JS -->
<script src="<?= $b ?>/assets/js/main.js"></script>

</body>
</html>
