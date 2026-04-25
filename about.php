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
    $r = $db->query('SELECT COUNT(*) FROM properties WHERE is_published = 1 AND is_sold = 0');
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
    $agents = $db->query("SELECT id, name, phone, email, avatar_url, role FROM users WHERE role IN ('agent','admin') AND is_active = 1 ORDER BY role DESC, name ASC LIMIT 8")->fetchAll();
} catch (Exception $e) { $agents = []; }

// Authorized Dealers
$authorizedDealers = [];
try {
    $authorizedDealers = $db->query(
        'SELECT id, name, logo_url, website_url
         FROM authorized_dealers
         WHERE is_published = 1
         ORDER BY sort_order ASC, name ASC'
    )->fetchAll();
} catch (Exception $e) { $authorizedDealers = []; }

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
                        <i class="fa-solid fa-phone me-2"></i>Get in Touch
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
<section class="mv-section">
    <div class="mv-decor mv-decor-1" aria-hidden="true"></div>
    <div class="mv-decor mv-decor-2" aria-hidden="true"></div>

    <div class="container">
        <div class="section-header center reveal">
            <div class="section-label">Purpose</div>
            <h2 class="section-title">Our Mission &amp; Vision</h2>
            <p class="section-subtitle">Why we do what we do — and where we're headed next.</p>
        </div>

        <div class="row g-4 mv-grid">
            <!-- Mission -->
            <div class="col-12 col-lg-6">
                <article class="mv-card reveal reveal-left">
                    <div class="mv-card-head">
                        <div class="mv-icon">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                        <div class="mv-tag">01 — Mission</div>
                    </div>
                    <h3 class="mv-title">Empowering Pakistanis to make the best property decisions.</h3>
                    <p class="mv-text">
                        Transparent pricing, verified listings, and honest advice — the way real estate should have always been.
                        We turn paperwork, site visits, and payment plans into a process you can actually understand.
                    </p>
                    <ul class="mv-bullets">
                        <li><i class="fa-solid fa-check"></i> Verified ownership &amp; NOC on every listing</li>
                        <li><i class="fa-solid fa-check"></i> Clear, up-front brokerage disclosure</li>
                        <li><i class="fa-solid fa-check"></i> End-to-end support, from search to possession</li>
                    </ul>
                </article>
            </div>

            <!-- Vision -->
            <div class="col-12 col-lg-6">
                <article class="mv-card mv-card-alt reveal reveal-right">
                    <div class="mv-card-head">
                        <div class="mv-icon mv-icon-gold">
                            <i class="fa-solid fa-eye"></i>
                        </div>
                        <div class="mv-tag">02 — Vision</div>
                    </div>
                    <h3 class="mv-title">Becoming Pakistan's most trusted real estate partner.</h3>
                    <p class="mv-text">
                        We want "Al-Riaz" to mean the same thing in Islamabad as it does in Karachi — integrity, expertise,
                        and client-first service at every step. Built on relationships that outlast a single transaction.
                    </p>
                    <ul class="mv-bullets">
                        <li><i class="fa-solid fa-check"></i> Authorised dealer for Pakistan's top developments</li>
                        <li><i class="fa-solid fa-check"></i> Data-driven investment guidance</li>
                        <li><i class="fa-solid fa-check"></i> A team that picks up the phone</li>
                    </ul>
                </article>
            </div>
        </div>

        <!-- Core Values -->
        <div class="mv-values reveal-stagger">
            <?php
            $values = [
                ['fa-handshake',    'Integrity',    'We never compromise on honesty in our dealings.'],
                ['fa-check-circle', 'Transparency', 'Clear pricing, no hidden costs or surprises.'],
                ['fa-user-tie',     'Expertise',    'Seasoned consultants with deep market knowledge.'],
                ['fa-headset',      'Client First', 'Your satisfaction is our top priority, always.'],
            ];
            foreach ($values as $i => [$icon, $title, $desc]):
            ?>
            <div class="mv-value">
                <div class="mv-value-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
                <div class="mv-value-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                <div class="mv-value-title"><?= $title ?></div>
                <p class="mv-value-desc"><?= $desc ?></p>
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
            <?php foreach ($agents as $agent):
                $agentImg = !empty($agent['avatar_url'])
                    ? mediaUrl($agent['avatar_url'])
                    : 'https://picsum.photos/id/' . (60 + ($agent['id'] % 10)) . '/200/200';
                $roleLabel = ucwords(str_replace('_', ' ', $agent['role'] ?? 'agent'));
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="team-card reveal">
                    <img src="<?= htmlspecialchars($agentImg, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8') ?>" class="team-card-img">
                    <div class="team-card-body">
                        <h4><?= htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <p><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></p>
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
     AUTHORIZED DEALERS
     ============================================================ -->
<section style="background:var(--navy-50); padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-2">Authorized Dealers</h2>
        <p class="text-center text-muted mb-5">We are the official authorized dealers for these prestigious developments</p>
    </div>

    <?php if (!empty($authorizedDealers)): ?>
    <?php $aboutDealerDuration = max(20, count($authorizedDealers) * 5); ?>
    <div class="about-dealer-marquee">
        <div class="about-dealer-marquee-track" style="animation-duration: <?= (int)$aboutDealerDuration ?>s;">
            <?php for ($loop = 0; $loop < 2; $loop++): ?>
                <?php foreach ($authorizedDealers as $dealer):
                    $dName = htmlspecialchars($dealer['name'], ENT_QUOTES, 'UTF-8');
                    $dLogo = !empty($dealer['logo_url']) ? htmlspecialchars(mediaUrl($dealer['logo_url']), ENT_QUOTES, 'UTF-8') : '';
                    $dUrl  = !empty($dealer['website_url']) ? htmlspecialchars($dealer['website_url'], ENT_QUOTES, 'UTF-8') : '';
                    $ariaHidden = $loop === 1 ? 'aria-hidden="true" tabindex="-1"' : '';
                ?>
                <?php if ($dUrl): ?>
                <a href="<?= $dUrl ?>" target="_blank" rel="noopener" class="about-dealer-card" title="<?= $dName ?>" <?= $ariaHidden ?>>
                <?php else: ?>
                <div class="about-dealer-card" <?= $ariaHidden ?>>
                <?php endif; ?>
                    <div class="about-dealer-logo">
                        <?php if ($dLogo): ?>
                            <img src="<?= $dLogo ?>" alt="<?= $dName ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas fa-handshake"></i>
                        <?php endif; ?>
                    </div>
                    <div class="about-dealer-name"><?= $dName ?></div>
                <?php if ($dUrl): ?></a><?php else: ?></div><?php endif; ?>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="container">
        <p class="text-center text-muted fst-italic py-4">Authorized dealer list will appear here once added by an administrator.</p>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="text-center mt-4">
            <a href="<?= $b ?>/projects.php" class="btn-gold">
                <i class="fas fa-building me-2"></i>View All Projects
            </a>
        </div>
    </div>
</section>
<style>
.about-dealer-marquee {
    overflow:hidden;
    padding:1rem 0;
    -webkit-mask-image:linear-gradient(90deg, transparent 0, #000 6%, #000 94%, transparent 100%);
            mask-image:linear-gradient(90deg, transparent 0, #000 6%, #000 94%, transparent 100%);
}
.about-dealer-marquee-track {
    display:flex;
    gap:1.25rem;
    width:max-content;
    animation:about-dealer-scroll linear infinite;
    will-change:transform;
}
.about-dealer-marquee:hover .about-dealer-marquee-track,
.about-dealer-marquee:focus-within .about-dealer-marquee-track {
    animation-play-state:paused;
}
@keyframes about-dealer-scroll {
    from { transform:translateX(0); }
    to   { transform:translateX(-50%); }
}
.about-dealer-card {
    flex:0 0 180px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:0.75rem;
    padding:1.25rem 0.75rem;
    background:#fff;
    border:1px solid rgba(10,22,40,0.08);
    border-radius:12px;
    text-decoration:none;
    color:var(--navy-800);
    min-height:140px;
    transition:transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}
.about-dealer-card:hover {
    transform:translateY(-3px);
    border-color:var(--gold);
    box-shadow:0 8px 20px rgba(10,22,40,0.08);
    color:var(--navy-800);
    text-decoration:none;
}
.about-dealer-logo {
    display:flex;
    align-items:center;
    justify-content:center;
    height:60px;
    width:100%;
}
.about-dealer-logo img {
    max-height:60px;
    max-width:100%;
    width:auto;
    height:auto;
    object-fit:contain;
}
.about-dealer-logo i {
    font-size:2rem;
    color:var(--navy-700);
}
.about-dealer-name {
    font-size:.85rem;
    font-weight:600;
    text-align:center;
    line-height:1.3;
}
@media (prefers-reduced-motion: reduce) {
    .about-dealer-marquee-track { animation:none; }
    .about-dealer-marquee { overflow-x:auto; }
}
</style>

<!-- ============================================================
     OFFICE LOCATIONS
     ============================================================ -->
<?php
// Data source: admin → Settings → Agency Profile + Branches (HQ flag)
$aboutSettings = getSettings();
$aboutHq       = getHqOffice();
$aboutBranches = getBranches();
$aboutEmail    = $aboutSettings['email']    ?: (defined('SITE_EMAIL')    ? SITE_EMAIL    : '');
$aboutWa       = $aboutSettings['whatsapp'] ?: (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '');
$aboutMainAddr = $aboutSettings['address']  ?: '';
$aboutMainPhone= $aboutSettings['phone']    ?: '';
$aboutMainHrs  = formatBusinessHours(getBusinessHoursSchedule());

// Build ordered office list: HQ first, then main (if a branch is HQ), then remaining branches.
$aboutOffices = [];
$aboutOffices[] = [
    'is_hq'   => true,
    'icon'    => 'fas fa-building',
    'name'    => $aboutHq['name'] ?: 'Main Office',
    'address' => $aboutHq['address'],
    'phone'   => $aboutHq['phone'],
    'hours'   => $aboutHq['hours'],
];
if ($aboutHq['source'] === 'branch') {
    $aboutOffices[] = [
        'is_hq'   => false,
        'icon'    => 'fas fa-building',
        'name'    => 'Main Office',
        'address' => $aboutMainAddr,
        'phone'   => $aboutMainPhone,
        'hours'   => $aboutMainHrs,
    ];
}
foreach ($aboutBranches as $br) {
    if (!empty($br['is_hq'])) continue;
    $bName    = trim((string)($br['name']    ?? ''));
    $bAddress = trim((string)($br['address'] ?? ''));
    $bPhone   = trim((string)($br['phone']   ?? ''));
    $bHours   = trim((string)($br['hours']   ?? ''));
    if ($bName === '' && $bAddress === '' && $bPhone === '' && $bHours === '') continue;
    $aboutOffices[] = [
        'is_hq'   => false,
        'icon'    => 'fas fa-store',
        'name'    => $bName ?: 'Branch Office',
        'address' => $bAddress,
        'phone'   => $bPhone,
        'hours'   => $bHours,
    ];
}
?>
<section style="background:#fff; padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-2">Our Offices</h2>
        <p class="text-center text-muted mb-5">Visit us at any of our locations</p>
        <div class="row g-4">
            <?php foreach ($aboutOffices as $off):
                $addrLines   = preg_split('/\r\n|\r|\n/', (string)$off['address']);
                $firstToken  = trim(preg_split('/[,\n]/', (string)$off['address'])[0] ?? '');
                $mapQuery    = rawurlencode((string)$off['address'] ?: $firstToken);
            ?>
            <div class="col-12 col-md-6">
                <div class="office-card h-100">
                    <div class="office-card-title">
                        <i class="<?= htmlspecialchars($off['icon']) ?>"></i>
                        <?= htmlspecialchars($off['name']) ?>
                        <?php if ($off['is_hq']): ?>
                        <span class="badge bg-success ms-auto" style="font-size:.7rem;">Headquarters</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($off['address']): ?>
                    <div class="office-info-row">
                        <i class="fas fa-location-dot"></i>
                        <div><?= nl2br(htmlspecialchars($off['address'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($off['phone']): ?>
                    <div class="office-info-row">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= htmlspecialchars($off['phone']) ?>"><?= htmlspecialchars($off['phone']) ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($aboutWa): ?>
                    <div class="office-info-row">
                        <i class="fab fa-whatsapp"></i>
                        <a href="<?= 'https://wa.me/' . htmlspecialchars($aboutWa) ?>" target="_blank" rel="noopener">
                            Chat on WhatsApp
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($aboutEmail): ?>
                    <div class="office-info-row">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= htmlspecialchars($aboutEmail) ?>"><?= htmlspecialchars($aboutEmail) ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if (trim((string)$off['hours']) !== ''): ?>
                    <div class="office-info-row">
                        <i class="fas fa-clock"></i>
                        <div><?= nl2br(htmlspecialchars($off['hours'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($mapQuery !== ''): ?>
                    <div class="map-wrap mt-3">
                        <div class="map-placeholder">
                            <i class="fas fa-map-location-dot"></i>
                            <span><?= htmlspecialchars($firstToken ?: $off['address']) ?></span>
                            <a href="https://maps.google.com/?q=<?= $mapQuery ?>"
                               target="_blank" rel="noopener" class="btn-outline-navy" style="font-size:.82rem; margin-top:.5rem;">
                                <i class="fas fa-diamond-turn-right me-1"></i>Get Directions
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
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
<section class="final-cta-section">
    <div class="container">
        <div class="final-cta-card reveal">
            <div class="final-cta-backdrop" aria-hidden="true"></div>
            <div class="final-cta-inner">
                <div class="final-cta-left">
                    <div class="section-label on-dark" style="justify-content:flex-start;">Let's Talk</div>
                    <h2 class="final-cta-heading">Start your property journey today.</h2>
                    <p class="final-cta-sub">
                        Speak to an expert consultant — no obligation, completely free advice. A real person
                        will reply within minutes during business hours.
                    </p>
                    <div class="final-cta-actions">
                        <a href="<?= waLink(SITE_WHATSAPP, "Hello! I visited your website and would like to discuss a property.") ?>"
                           target="_blank" rel="noopener noreferrer" class="btn-gold">
                            <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                        </a>
                        <a href="<?= $b ?>/contact.php" class="btn-outline-white">
                            <i class="fa-solid fa-envelope"></i> Send a Message
                        </a>
                    </div>
                    <div class="final-cta-hours">
                        <i class="fa-solid fa-clock"></i>
                        Mon–Sat 9am–7pm · Sun 11am–4pm
                    </div>
                </div>
                <div class="final-cta-right" aria-hidden="true">
                    <div class="final-cta-orb"></div>
                    <div class="final-cta-badge">
                        <div class="final-cta-badge-num"><?= $yearsActive ?>+</div>
                        <div class="final-cta-badge-lbl">Years<br>Trusted</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
