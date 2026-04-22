<?php
/**
 * Al-Riaz Associates — About Us Page
 * Public URL: /about.php
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Fetch live stats for trust strip
$totalListings = 500;
$totalProjects = 15;
$happyClients  = 200;
$yearsActive   = 5;

try {
    $r = $db->query('SELECT COUNT(*) FROM properties WHERE is_published = 1');
    $c = (int) $r->fetchColumn();
    if ($c > 0) $totalListings = $c;
} catch (Exception $e) { /* use seed */ }

try {
    $r = $db->query('SELECT COUNT(*) FROM projects WHERE is_published = 1');
    $c = (int) $r->fetchColumn();
    if ($c > 0) $totalProjects = $c;
} catch (Exception $e) { /* use seed */ }

// Fetch agents from DB
try {
    $agents = $db->query("SELECT id, name, phone, email, avatar_url FROM users WHERE role IN ('agent','admin') AND is_active = 1 ORDER BY role DESC, name ASC LIMIT 8")->fetchAll();
} catch (Exception $e) { $agents = []; }

$pageTitle       = 'About Us - Al-Riaz Associates';
$pageDescription = 'Learn about Al-Riaz Associates — Pakistan\'s trusted real estate agency based in Islamabad. Authorised dealer for top developments with 5+ years of experience.';

require_once 'includes/header.php';
?>

<!-- ============================================================
     PAGE HEADER
     ============================================================ -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([['label'=>'Home','url'=>'/'],['label'=>'About Us']]) ?>
        <h1 class="page-header-title">About Al-Riaz Associates</h1>
        <p class="page-header-sub">Pakistan's trusted real estate partner since <?= date('Y') - $yearsActive ?></p>
    </div>
</div>

<!-- ============================================================
     AGENCY STORY
     ============================================================ -->
<section class="section-pad" style="background:#fff;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <span class="text-uppercase fw-semibold small" style="color:var(--gold); letter-spacing:1.5px;">Our Story</span>
                <h2 class="content-heading mt-2 mb-3" style="text-align:left;">Built on Trust &amp; Transparency</h2>
                <p class="text-muted lh-lg">
                    Al-Riaz Associates was founded with a clear mission: to bring transparency, integrity, and
                    professionalism to Pakistan's real estate market. Based in the heart of Islamabad, we began
                    as a small consultancy helping families find their dream homes in Rawalpindi and Islamabad.
                </p>
                <p class="text-muted lh-lg">
                    Over the years, we have grown into a full-service real estate agency, becoming an
                    <strong>authorised dealer</strong> for Pakistan's most prestigious developments including
                    Bahria Town, DHA, Capital Smart City, Gulberg Greens, and Blue World City.
                </p>
                <p class="text-muted lh-lg">
                    Today, our team of experienced property consultants serves clients across Islamabad,
                    Rawalpindi, Lahore, and Karachi — offering verified listings, transparent pricing, and
                    end-to-end support from property search to final possession.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="<?= $b ?>/contact.php" class="btn-gold">
                        <i class="fas fa-phone-alt me-2"></i>Get in Touch
                    </a>
                    <a href="<?= 'https://wa.me/' . SITE_WHATSAPP . '?text=' . rawurlencode("Hello! I'd like to know more about Al-Riaz Associates.") ?>"
                       target="_blank" rel="noopener" class="btn-whatsapp">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp Us
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="position-relative">
                    <img src="https://picsum.photos/id/1067/600/420"
                         alt="Al-Riaz Associates Office"
                         class="img-fluid rounded-3 shadow"
                         loading="lazy">
                    <!-- Floating stat card -->
                    <div class="position-absolute d-none d-md-flex align-items-center gap-3 bg-white rounded-3 shadow-sm p-3"
                         style="bottom:-20px; left:-20px; min-width:200px; border-left:4px solid var(--gold);">
                        <i class="fas fa-award fa-2x" style="color:var(--gold);"></i>
                        <div>
                            <div class="fw-bold" style="color:var(--navy-700); font-size:1.3rem;">
                                <?= $yearsActive ?>+ Years
                            </div>
                            <div class="text-muted small">In Real Estate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     KEY STATS
     ============================================================ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="stat-card reveal">
                    <div class="stat-number" data-count-to="<?= $totalListings ?>">0<span class="suffix">+</span></div>
                    <div class="stat-label">Properties Listed</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card reveal" style="--delay:0.1s">
                    <div class="stat-number" data-count-to="<?= $totalProjects ?>">0<span class="suffix">+</span></div>
                    <div class="stat-label">Active Projects</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card reveal" style="--delay:0.2s">
                    <div class="stat-number" data-count-to="<?= $happyClients ?>">0<span class="suffix">+</span></div>
                    <div class="stat-label">Happy Clients</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card reveal" style="--delay:0.3s">
                    <div class="stat-number" data-count-to="<?= $yearsActive ?>">0<span class="suffix">+</span></div>
                    <div class="stat-label">Years Active</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     MISSION & VISION
     ============================================================ -->
<section style="background:var(--navy-50); padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-5">Our Mission &amp; Vision</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mission-card reveal reveal-left">
                    <div class="mission-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <h3>Our Mission</h3>
                    <p>To provide transparent, trustworthy real estate services that empower Pakistanis to make the best property decisions.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mission-card reveal reveal-right">
                    <div class="mission-icon"><i class="fa-solid fa-eye"></i></div>
                    <h3>Our Vision</h3>
                    <p>To become Pakistan's most respected real estate agency — known for integrity, expertise, and client-first service.</p>
                </div>
            </div>
        </div>

        <!-- Core Values -->
        <div class="row g-3 mt-3 justify-content-center">
            <?php
            $values = [
                ['fas fa-handshake',    'Integrity',    'We never compromise on honesty in our dealings.'],
                ['fas fa-check-circle', 'Transparency', 'Clear pricing, no hidden costs or surprises.'],
                ['fas fa-user-tie',     'Expertise',    'Seasoned consultants with deep market knowledge.'],
                ['fas fa-headset',      'Client First', 'Your satisfaction is our top priority, always.'],
            ];
            foreach ($values as $val) :
            ?>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 rounded-3" style="background:white; border:1px solid var(--navy-100);">
                    <i class="<?= $val[0] ?> fa-lg mb-2 d-block" style="color:var(--gold);"></i>
                    <div class="fw-bold small" style="color:var(--navy-700);"><?= $val[1] ?></div>
                    <div class="text-muted" style="font-size:.78rem;"><?= $val[2] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     MEET THE TEAM
     ============================================================ -->
<section style="padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-5">Meet Our Team</h2>
        <div class="row g-4">
            <?php foreach ($agents as $agent): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="team-card reveal">
                    <img src="<?= htmlspecialchars($agent['avatar_url'] ?: 'https://picsum.photos/id/'.(60+($agent['id']%10)).'/200/200') ?>"
                         alt="<?= htmlspecialchars($agent['name']) ?>" class="team-card-img">
                    <div class="team-card-body">
                        <h4><?= htmlspecialchars($agent['name']) ?></h4>
                        <p><?= ucfirst($agent['role'] ?? 'Agent') ?></p>
                        <?php if (!empty($agent['phone'])): ?>
                        <a href="tel:<?= htmlspecialchars($agent['phone']) ?>" class="btn-navy" style="font-size:.75rem;padding:.3rem .75rem;">
                            <i class="fa-solid fa-phone"></i> Call
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($agents)): ?>
            <!-- Placeholder team cards if no agents in DB -->
            <?php foreach ([['Ahmed Khan','Senior Agent'],['Sara Malik','Property Consultant'],['Omar Iqbal','Commercial Specialist']] as $tm): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="team-card reveal">
                    <img src="https://picsum.photos/id/<?= rand(60,90) ?>/200/200" alt="<?= $tm[0] ?>" class="team-card-img">
                    <div class="team-card-body">
                        <h4><?= $tm[0] ?></h4>
                        <p><?= $tm[1] ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     AUTHORISED PROJECTS
     ============================================================ -->
<section style="background:var(--navy-50); padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-5">Authorised Projects</h2>
        <p class="text-center text-muted mb-4">We are the official authorised dealers for these prestigious developments</p>
        <div class="row g-3 justify-content-center">
            <?php
            $projects = [
                ['fas fa-city',       'Bahria Town',         'Bahria Town Pvt Ltd',       'Islamabad / Rawalpindi'],
                ['fas fa-shield-alt', 'DHA',                 'Defence Housing Authority',  'Islamabad / Lahore'],
                ['fas fa-rocket',     'Capital Smart City',  'FDHL',                       'Islamabad'],
                ['fas fa-leaf',       'Gulberg Greens',      'Gulberg Inc.',               'Islamabad'],
                ['fas fa-globe',      'Blue World City',     'Blue Group of Companies',    'Rawalpindi'],
                ['fas fa-star',       'Park View City',      'Vision Group',               'Islamabad'],
            ];
            foreach ($projects as $proj) :
            ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="auth-logo-item text-center">
                    <div style="font-size:2rem; margin-bottom:.5rem; color:var(--navy-700);">
                        <i class="<?= $proj[0] ?>"></i>
                    </div>
                    <div class="fw-semibold" style="font-size:.85rem; color:var(--navy-800);"><?= $proj[1] ?></div>
                    <div class="text-muted" style="font-size:.75rem;"><?= $proj[2] ?></div>
                    <div class="text-muted mt-1" style="font-size:.72rem;">
                        <i class="fas fa-map-marker-alt" style="color:var(--gold);font-size:.65rem;"></i>
                        <?= $proj[3] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $b ?>/projects.php" class="btn-gold">
                <i class="fas fa-building me-2"></i>View All Projects
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     OFFICE LOCATIONS
     ============================================================ -->
<section style="background:#fff; padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-5">Our Offices</h2>
        <p class="text-center text-muted mb-4">Visit us in Islamabad or Rawalpindi</p>
        <div class="row g-4">
            <!-- Main Office -->
            <div class="col-12 col-md-6">
                <div class="office-card h-100">
                    <div class="office-card-title">
                        <i class="fas fa-building"></i>
                        Main Office — Islamabad
                        <span class="badge bg-success ms-auto" style="font-size:.7rem;">Headquarters</span>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>Office #5, Amin Center, Blue Area,<br>Islamabad 44000, Pakistan</div>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-phone-alt"></i>
                        <a href="tel:+923001234567"><?= SITE_PHONE ?></a>
                    </div>
                    <div class="office-info-row">
                        <i class="fab fa-whatsapp"></i>
                        <a href="<?= 'https://wa.me/' . SITE_WHATSAPP ?>" target="_blank" rel="noopener">
                            Chat on WhatsApp
                        </a>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-clock"></i>
                        <div>Mon–Sat: 9:00 AM – 7:00 PM<br>Sunday: 11:00 AM – 4:00 PM</div>
                    </div>
                    <div class="map-wrap mt-3">
                        <div class="map-placeholder">
                            <i class="fas fa-map-marked-alt"></i>
                            <span>Blue Area, Islamabad</span>
                            <a href="https://maps.google.com/?q=Blue+Area+Islamabad"
                               target="_blank" rel="noopener" class="btn-outline-navy" style="font-size:.82rem; margin-top:.5rem;">
                                <i class="fas fa-directions me-1"></i>Get Directions
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branch Office -->
            <div class="col-12 col-md-6">
                <div class="office-card h-100">
                    <div class="office-card-title">
                        <i class="fas fa-store"></i>
                        Branch Office — Rawalpindi
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>Shop #12, Haider Center, Saddar,<br>Rawalpindi, Pakistan</div>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-phone-alt"></i>
                        <a href="tel:+923001234567"><?= SITE_PHONE ?></a>
                    </div>
                    <div class="office-info-row">
                        <i class="fab fa-whatsapp"></i>
                        <a href="<?= 'https://wa.me/' . SITE_WHATSAPP ?>" target="_blank" rel="noopener">
                            Chat on WhatsApp
                        </a>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>
                    </div>
                    <div class="office-info-row">
                        <i class="fas fa-clock"></i>
                        <div>Mon–Sat: 9:00 AM – 7:00 PM<br>Sunday: 11:00 AM – 4:00 PM</div>
                    </div>
                    <div class="map-wrap mt-3">
                        <div class="map-placeholder">
                            <i class="fas fa-map-marked-alt"></i>
                            <span>Saddar, Rawalpindi</span>
                            <a href="https://maps.google.com/?q=Saddar+Rawalpindi"
                               target="_blank" rel="noopener" class="btn-outline-navy" style="font-size:.82rem; margin-top:.5rem;">
                                <i class="fas fa-directions me-1"></i>Get Directions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     AWARDS & RECOGNITION
     ============================================================ -->
<section style="background:var(--navy-50); padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-5">Awards &amp; Recognition</h2>
        <p class="text-center text-muted mb-4">Our commitment to excellence, recognized by the industry</p>
        <div class="row g-3 justify-content-center">
            <?php
            $awards = [
                ['fas fa-trophy',    'Best Authorised Dealer 2024',    'Awarded by Bahria Town Pvt Ltd'],
                ['fas fa-medal',     'Top Sales Partner 2023',         'DHA Islamabad Regional Award'],
                ['fas fa-star',      'Client Satisfaction Award 2023', 'Capital Smart City Recognition'],
                ['fas fa-handshake', 'Trusted Agency 2022',            'Pakistan Real Estate Forum'],
            ];
            foreach ($awards as $award) :
            ?>
            <div class="col-6 col-md-3">
                <div class="award-card">
                    <div class="award-icon"><i class="<?= $award[0] ?>"></i></div>
                    <div class="award-title"><?= $award[1] ?></div>
                    <div class="award-desc"><?= $award[2] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     CTA
     ============================================================ -->
<section class="cta-banner">
    <div class="container" style="position:relative; z-index:2;">
        <h2>Start Your Property Journey Today</h2>
        <p>Speak to our expert consultants — no obligation, completely free advice.</p>
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="<?= 'https://wa.me/' . SITE_WHATSAPP . '?text=' . rawurlencode("Hello! I visited your website and would like to discuss a property.") ?>"
               target="_blank" rel="noopener" class="btn-whatsapp">
                <i class="fab fa-whatsapp fa-lg me-2"></i> Chat on WhatsApp
            </a>
            <a href="<?= $b ?>/contact.php" class="btn btn-outline-light btn-lg px-4 fw-semibold">
                <i class="fas fa-envelope me-2"></i>Send a Message
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
