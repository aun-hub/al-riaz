<?php
/**
 * Al-Riaz Associates — Home Page
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Featured Projects
$featuredProjects = [];
try {
    $stmt = $db->prepare(
        'SELECT id, slug, name, developer, city, status, hero_image_url, area_locality
         FROM projects
         WHERE is_featured = 1 AND is_published = 1
         ORDER BY created_at DESC
         LIMIT 6'
    );
    $stmt->execute();
    $featuredProjects = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[Home] Projects query: ' . $e->getMessage());
}

// Featured Properties
$featuredProperties = [];
try {
    $stmt = $db->prepare(
        'SELECT p.*,
                (SELECT pm.url FROM property_media pm
                  WHERE pm.property_id = p.id AND pm.kind = \'image\'
                  ORDER BY pm.sort_order ASC, pm.id ASC LIMIT 1) AS thumbnail
         FROM properties p
         WHERE p.is_featured = 1 AND p.is_published = 1 AND p.is_sold = 0
         ORDER BY p.created_at DESC
         LIMIT 6'
    );
    $stmt->execute();
    $featuredProperties = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[Home] Properties query: ' . $e->getMessage());
}

// Authorized Dealers
$authorizedDealers = [];
try {
    $stmt = $db->query(
        'SELECT id, name, logo_url, website_url
         FROM authorized_dealers
         WHERE is_published = 1
         ORDER BY sort_order ASC, name ASC'
    );
    $authorizedDealers = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[Home] Dealers query: ' . $e->getMessage());
}

// Stats
$totalListings = 500;
$totalProjects = 15;
$happyClients  = 200;
$yearsActive   = 5;

try {
    $c = (int) $db->query('SELECT COUNT(*) FROM properties WHERE is_published = 1 AND is_sold = 0')->fetchColumn();
    if ($c > 0) $totalListings = $c;
} catch (Exception $e) {}

try {
    $c = (int) $db->query('SELECT COUNT(*) FROM projects WHERE is_published = 1')->fetchColumn();
    if ($c > 0) $totalProjects = $c;
} catch (Exception $e) {}

$pageTitle = 'Al-Riaz Associates — Islamabad & Rawalpindi Real Estate';
$metaDesc  = 'Al-Riaz Associates — Authorised dealer for top real estate projects in Islamabad, Rawalpindi, Lahore & Karachi. Buy, sell, or rent with Pakistan\'s trusted agency.';

// Editable banner copy (admin sets in /admin/settings.php → Banners).
$homeSettings = getSettings();

// Resolve a hero CTA URL. Admin can enter either a site-relative path
// (e.g. "/contact.php") or a full URL (https://, mailto:, tel:, wa.me/...).
// Empty → fall back to $default. Returns [href, isExternal].
$resolveCtaUrl = static function (string $value, string $default): array {
    $v = trim($value) !== '' ? trim($value) : $default;
    $isExternal = (bool)preg_match('#^(https?:|mailto:|tel:|//)#i', $v);
    if (!$isExternal && str_starts_with($v, '/')) {
        $v = BASE_PATH . $v;
    }
    return [$v, $isExternal];
};
[$heroPrimaryHref,   $heroPrimaryExt]   = $resolveCtaUrl($homeSettings['hero_cta_primary_url']   ?? '', '/listings.php');
[$heroSecondaryHref, $heroSecondaryExt] = $resolveCtaUrl($homeSettings['hero_cta_secondary_url'] ?? '', waLink(SITE_WHATSAPP, "Hi, I'm interested in a property. Can you help?"));

require_once 'includes/header.php';

// Stock hero images — replace once you upload real ones
$heroHero   = 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=80';
$heroTile1  = 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?auto=format&fit=crop&w=600&q=80';
$heroTile2  = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=600&q=80';
$heroTile3  = 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?auto=format&fit=crop&w=600&q=80';
?>

<!-- ════════════════════════════════════════════════════════════
     1. HERO
     ════════════════════════════════════════════════════════════ -->
<section class="site-hero">
    <div class="hero-blob hero-blob-1" aria-hidden="true"></div>
    <div class="hero-blob hero-blob-2" aria-hidden="true"></div>
    <div class="hero-blob hero-blob-3" aria-hidden="true"></div>
    <div class="hero-dots" aria-hidden="true"></div>

    <div class="container">
        <div class="row align-items-center g-5">

            <!-- LEFT — copy + CTA -->
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <?= htmlspecialchars($homeSettings['hero_badge'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </div>

                <h1 class="hero-heading">
                    <?= htmlspecialchars($homeSettings['hero_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                    <span class="accent"><?= htmlspecialchars($homeSettings['hero_heading_accent'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </h1>

                <p class="hero-sub">
                    <?= htmlspecialchars($homeSettings['hero_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="hero-cta">
                    <a href="<?= htmlspecialchars($heroPrimaryHref, ENT_QUOTES, 'UTF-8') ?>" class="btn-gold"<?= $heroPrimaryExt ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <i class="fa-solid fa-search"></i> <?= htmlspecialchars($homeSettings['hero_cta_primary_label'] ?? 'Browse Properties', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="<?= htmlspecialchars($heroSecondaryHref, ENT_QUOTES, 'UTF-8') ?>" class="btn-outline-white"<?= $heroSecondaryExt ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($homeSettings['hero_cta_secondary_label'] ?? 'WhatsApp Us', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>

                <div class="hero-stats">
                    <div>
                        <div class="hero-stat-value"><?= $totalListings ?>+</div>
                        <div class="hero-stat-label">Properties</div>
                    </div>
                    <div>
                        <div class="hero-stat-value"><?= $happyClients ?>+</div>
                        <div class="hero-stat-label">Happy Clients</div>
                    </div>
                    <div>
                        <div class="hero-stat-value"><?= $yearsActive ?>+</div>
                        <div class="hero-stat-label">Years Active</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT — decorative image mosaic -->
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-mosaic" aria-hidden="true">
                    <div class="hero-mosaic-main">
                        <img src="<?= $heroHero ?>" alt="" loading="lazy" onerror="this.src='https://picsum.photos/id/1040/900/1100'">
                        <div class="hero-mosaic-shine"></div>
                    </div>
                    <div class="hero-mosaic-tile hero-mosaic-tile-1">
                        <img src="<?= $heroTile1 ?>" alt="" loading="lazy" onerror="this.src='https://picsum.photos/id/164/600/400'">
                    </div>
                    <div class="hero-mosaic-tile hero-mosaic-tile-2">
                        <img src="<?= $heroTile2 ?>" alt="" loading="lazy" onerror="this.src='https://picsum.photos/id/1029/600/400'">
                    </div>

                    <!-- floating stat card -->
                    <div class="hero-float-card hero-float-card-rating">
                        <div class="hero-float-rating">
                            <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            <div class="hero-float-rating-score">4.9/5</div>
                        </div>
                        <div class="hero-float-rating-sub">From <strong><?= $happyClients ?>+</strong> happy clients</div>
                    </div>

                    <!-- floating developers card -->
                    <div class="hero-float-card hero-float-card-devs">
                        <div class="hero-float-devs-row">
                            <div class="hero-float-dev">BT</div>
                            <div class="hero-float-dev">DHA</div>
                            <div class="hero-float-dev">CSC</div>
                            <div class="hero-float-dev">+<?= $totalProjects ?></div>
                        </div>
                        <div class="hero-float-devs-label">Authorised Developers</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     2. SEARCH BAR (prominent, below hero)
     ════════════════════════════════════════════════════════════ -->
<section class="quick-search-wrap">
    <div class="container">
        <div class="quick-search-card search-widget reveal">
            <div class="search-tabs">
                <button type="button" class="search-tab active" data-purpose="sale">Buy</button>
                <button type="button" class="search-tab" data-purpose="rent">Rent</button>
                <button type="button" class="search-tab" data-purpose="invest">Invest</button>
            </div>

            <form id="search-form" method="GET" action="<?= $b ?>/listings.php" autocomplete="off" class="quick-search-form">
                <input type="hidden" name="purpose" value="sale">

                <div class="quick-search-field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" class="quick-search-input search-autocomplete-input"
                           placeholder="Location, project, or keyword…" aria-label="Search">
                </div>

                <div class="quick-search-field">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" name="city" class="quick-search-input" aria-label="City"
                           list="pkCityOptions" autocomplete="off"
                           placeholder="Any City — type to search">
                </div>
                <datalist id="pkCityOptions">
                    <?php foreach (getPakistanCities() as $cityName): ?>
                      <option value="<?= htmlspecialchars($cityName) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="quick-search-field">
                    <i class="fa-solid fa-house"></i>
                    <select name="type" class="quick-search-input" aria-label="Type">
                        <option value="">Any Type</option>
                        <option value="house">House</option>
                        <option value="flat">Apartment</option>
                        <option value="plot">Plot</option>
                        <option value="shop">Shop</option>
                        <option value="office">Office</option>
                    </select>
                </div>

                <div class="quick-search-field">
                    <i class="fa-solid fa-tag"></i>
                    <select name="max_price" class="quick-search-input" aria-label="Budget">
                        <option value="">Any Budget</option>
                        <option value="5000000">Up to 50 Lakh</option>
                        <option value="10000000">Up to 1 Crore</option>
                        <option value="25000000">Up to 2.5 Crore</option>
                        <option value="50000000">Up to 5 Crore</option>
                        <option value="100000000">Up to 10 Crore</option>
                    </select>
                </div>

                <button type="submit" class="quick-search-btn">
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="quick-search-tags">
                <span>Popular:</span>
                <a href="<?= $b ?>/listings.php?category=residential&amp;type%5B%5D=plot&amp;city=islamabad">Plots Islamabad</a>
                <a href="<?= $b ?>/listings.php?category=residential&amp;type%5B%5D=house&amp;city=rawalpindi">Houses Rawalpindi</a>
                <a href="<?= $b ?>/listings.php?purpose=rent">For Rent</a>
                <a href="<?= $b ?>/projects.php">New Projects</a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     3. INTENT TILES — "What are you looking for?"
     ════════════════════════════════════════════════════════════ -->
<section class="section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-label">Get Started</div>
            <h2 class="section-title">What are you looking for?</h2>
            <p class="section-subtitle">Pick your path — we'll take it from there.</p>
        </div>

        <div class="row g-4 reveal-stagger">
            <div class="col-12 col-md-4">
                <a href="<?= $b ?>/listings.php?purpose=sale" class="intent-card">
                    <div class="intent-icon"><i class="fa-solid fa-house-chimney"></i></div>
                    <div class="intent-body">
                        <div class="intent-title">Buy a Property</div>
                        <p class="intent-desc">Verified houses, flats, plots, and commercial units across Pakistan's top cities.</p>
                        <span class="intent-arrow">Browse to buy <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-4">
                <a href="<?= $b ?>/listings.php?purpose=rent" class="intent-card intent-card-gold">
                    <div class="intent-icon"><i class="fa-solid fa-key"></i></div>
                    <div class="intent-body">
                        <div class="intent-title">Rent a Place</div>
                        <p class="intent-desc">Monthly rentals — apartments, offices, shops, and more with verified owners.</p>
                        <span class="intent-arrow">Browse rentals <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-4">
                <a href="<?= $b ?>/projects.php" class="intent-card">
                    <div class="intent-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="intent-body">
                        <div class="intent-title">Invest in Projects</div>
                        <p class="intent-desc">Authorised launches from Bahria, DHA, Capital Smart City, and more — file-level entry points.</p>
                        <span class="intent-arrow">View projects <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     4. AUTHORIZED DEALERS
     ════════════════════════════════════════════════════════════ -->
<?php if (!empty($authorizedDealers)): ?>
<section class="section authorized-dealers-section" style="padding:3.5rem 0; background:#fff; border-top:1px solid var(--gray-100); border-bottom:1px solid var(--gray-100);" aria-label="Authorised dealers">
    <div class="container">
        <div class="section-header center reveal" style="margin-bottom:2rem;">
            <div class="section-label">Our Partners</div>
            <h2 class="section-title" style="font-size:1.75rem;">Authorized Dealer For</h2>
            <p class="section-subtitle">Officially authorized for Pakistan's leading real estate developments</p>
        </div>
    </div>
    <?php
        $dealerDuration = max(20, count($authorizedDealers) * 5);
    ?>
    <div class="dealer-marquee" data-paused="false">
        <div class="dealer-marquee-track" style="animation-duration: <?= (int)$dealerDuration ?>s;">
            <?php for ($loop = 0; $loop < 2; $loop++): ?>
                <?php foreach ($authorizedDealers as $dealer):
                    $dName = htmlspecialchars($dealer['name'], ENT_QUOTES, 'UTF-8');
                    $dLogo = !empty($dealer['logo_url']) ? htmlspecialchars(mediaUrl($dealer['logo_url']), ENT_QUOTES, 'UTF-8') : '';
                    $dUrl  = !empty($dealer['website_url']) ? htmlspecialchars($dealer['website_url'], ENT_QUOTES, 'UTF-8') : '';
                    $ariaHidden = $loop === 1 ? 'aria-hidden="true" tabindex="-1"' : '';
                ?>
                <?php if ($dUrl): ?>
                <a href="<?= $dUrl ?>" target="_blank" rel="noopener" class="dealer-marquee-item" title="<?= $dName ?>" <?= $ariaHidden ?>>
                <?php else: ?>
                <div class="dealer-marquee-item" title="<?= $dName ?>" <?= $ariaHidden ?>>
                <?php endif; ?>
                    <?php if ($dLogo): ?>
                        <img src="<?= $dLogo ?>" alt="<?= $dName ?>" loading="lazy">
                    <?php else: ?>
                        <span class="dealer-logo-text"><?= $dName ?></span>
                    <?php endif; ?>
                    <div class="dealer-logo-name"><?= $dName ?></div>
                <?php if ($dUrl): ?></a><?php else: ?></div><?php endif; ?>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</section>
<style>
.dealer-marquee {
    overflow:hidden;
    padding:1rem 0;
    -webkit-mask-image:linear-gradient(90deg, transparent 0, #000 6%, #000 94%, transparent 100%);
            mask-image:linear-gradient(90deg, transparent 0, #000 6%, #000 94%, transparent 100%);
}
.dealer-marquee-track {
    display:flex;
    gap:1.5rem;
    width:max-content;
    animation:dealer-marquee-scroll linear infinite;
    will-change:transform;
}
.dealer-marquee:hover .dealer-marquee-track,
.dealer-marquee:focus-within .dealer-marquee-track {
    animation-play-state:paused;
}
@keyframes dealer-marquee-scroll {
    from { transform:translateX(0); }
    to   { transform:translateX(-50%); }
}
.dealer-marquee-item {
    flex:0 0 180px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:0.75rem;
    padding:1.25rem 1rem;
    background:#fff;
    border:1px solid var(--gray-100);
    border-radius:12px;
    text-decoration:none;
    color:var(--navy-700);
    transition:transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    min-height:140px;
}
.dealer-marquee-item:hover {
    transform:translateY(-3px);
    border-color:var(--gold);
    box-shadow:0 8px 20px rgba(10,22,40,0.08);
    color:var(--navy-700);
    text-decoration:none;
}
.dealer-marquee-item img {
    max-width:100%;
    max-height:60px;
    width:auto;
    height:auto;
    object-fit:contain;
    filter:grayscale(20%);
    transition:filter 0.25s ease;
}
.dealer-marquee-item:hover img { filter:grayscale(0%); }
.dealer-logo-text {
    font-size:1.1rem;
    font-weight:700;
    color:var(--navy-700);
    text-align:center;
}
.dealer-logo-name {
    font-size:0.8rem;
    font-weight:600;
    color:var(--navy-400);
    text-align:center;
    line-height:1.3;
}
@media (prefers-reduced-motion: reduce) {
    .dealer-marquee-track { animation:none; }
    .dealer-marquee { overflow-x:auto; }
}
</style>
<?php else: ?>
<section class="partner-marquee" aria-label="Authorised developers">
    <div class="container">
        <div class="partner-marquee-head">
            <span class="partner-marquee-label">Authorised dealer for</span>
        </div>
    </div>
    <div class="partner-marquee-track" aria-hidden="true">
        <div class="partner-marquee-row">
            <?php
            $partners = ['Bahria Town','DHA','Capital Smart City','Blue World City','Gulberg Greens','Top City','Park View City','Eighteen Islamabad','Lake City','Nova City'];
            foreach (array_merge($partners, $partners) as $p): ?>
                <span class="partner-pill"><?= htmlspecialchars($p) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     5. FEATURED PROJECTS
     ════════════════════════════════════════════════════════════ -->
<section class="section" style="background:var(--gray-50);">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div class="reveal">
                <div class="section-label">New Developments</div>
                <h2 class="section-title mb-0">Featured Projects</h2>
                <p class="section-subtitle mt-1">Authorised dealer for Pakistan's top real estate developments</p>
            </div>
            <a href="<?= $b ?>/projects.php" class="btn-outline-navy reveal">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (!empty($featuredProjects)) : ?>
        <div class="row g-4 reveal-stagger">
            <?php foreach (array_slice($featuredProjects, 0, 6) as $project) :
                $imgUrl   = !empty($project['hero_image_url'])
                            ? htmlspecialchars(mediaUrl($project['hero_image_url']))
                            : 'https://picsum.photos/id/1029/600/380';
                $slug     = htmlspecialchars($project['slug'] ?? '#');
                $name     = htmlspecialchars($project['name'] ?? 'Project');
                $dev      = htmlspecialchars($project['developer'] ?? '');
                $city     = htmlspecialchars(ucfirst($project['city'] ?? ''));
                $locality = htmlspecialchars($project['area_locality'] ?? '');
                $status   = $project['status'] ?? 'upcoming';
                $statusMap = [
                    'ready'             => ['Ready to Move',    'project-status-active'],
                    'under_development' => ['Under Development','project-status-upcoming'],
                    'upcoming'          => ['Upcoming',         'project-status-upcoming'],
                    'possession'        => ['Possession Open',  'project-status-active'],
                    'sold_out'          => ['Sold Out',         'project-status-sold'],
                ];
                [$statusLabel, $statusClass] = $statusMap[$status] ?? [ucfirst(str_replace('_',' ',$status)), 'project-status-upcoming'];
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="project-card">
                    <div class="project-card-img">
                        <a href="<?= $b ?>/project.php?slug=<?= $slug ?>" aria-label="<?= $name ?>">
                            <img src="<?= $imgUrl ?>" alt="<?= $name ?>" loading="lazy" onerror="this.src='https://picsum.photos/id/1029/600/380'">
                        </a>
                    </div>
                    <div class="project-card-body">
                        <div class="project-status <?= $statusClass ?>">
                            <i class="fa-solid fa-circle" style="font-size:0.45rem;"></i>
                            <?= $statusLabel ?>
                        </div>
                        <div class="project-title">
                            <a href="<?= $b ?>/project.php?slug=<?= $slug ?>" style="color:inherit;"><?= $name ?></a>
                        </div>
                        <?php if ($dev): ?>
                        <div class="project-location" style="color:var(--navy-400); font-weight:600;">
                            <i class="fa-solid fa-building"></i> <?= $dev ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($city || $locality): ?>
                        <div class="project-location">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= $locality ? "$locality, $city" : $city ?>
                        </div>
                        <?php endif; ?>
                        <div style="margin-top:1rem;">
                            <a href="<?= $b ?>/project.php?slug=<?= $slug ?>" class="btn-navy" style="font-size:0.8rem; padding:0.55rem 1.25rem;">
                                View Project <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
            <div class="empty-title">Projects Coming Soon</div>
            <p class="empty-text">Check back soon for new project launches.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     6. STATS
     ════════════════════════════════════════════════════════════ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4 text-center reveal-stagger">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $totalListings ?>">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Properties Listed</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $happyClients ?>">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Happy Clients</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $totalProjects ?>">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Projects Authorised</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $yearsActive ?>">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Years in Business</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     7. FEATURED PROPERTIES
     ════════════════════════════════════════════════════════════ -->
<section class="section">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div class="reveal">
                <div class="section-label">Hot Listings</div>
                <h2 class="section-title mb-0">Featured Properties</h2>
                <p class="section-subtitle mt-1">Handpicked listings across Islamabad, Rawalpindi &amp; beyond</p>
            </div>
            <a href="<?= $b ?>/listings.php" class="btn-outline-navy reveal">
                Browse All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (!empty($featuredProperties)): ?>
        <div class="row g-4 reveal-stagger">
            <?php foreach (array_slice($featuredProperties, 0, 6) as $i => $prop):
                $waPhone = SITE_WHATSAPP;
            ?>
            <div class="col-12 col-sm-6 col-xl-4" data-property-id="<?= (int)($prop['id'] ?? 0) ?>">
                <?php include __DIR__ . '/includes/_prop_card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5 reveal">
            <a href="<?= $b ?>/listings.php" class="btn-navy" style="padding:0.875rem 2.5rem; font-size:0.95rem;">
                <i class="fa-solid fa-th"></i> Browse All Properties
            </a>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-home"></i></div>
            <div class="empty-title">No Properties Yet</div>
            <p class="empty-text">Listings will appear here once properties are added.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     8. WHY CHOOSE US — numbered cards
     ════════════════════════════════════════════════════════════ -->
<section class="section" style="background:var(--gray-50);">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-label">Why Us</div>
            <h2 class="section-title">Why Choose Al-Riaz Associates?</h2>
            <p class="section-subtitle">Your trusted real estate partner in Pakistan since <?= date('Y') - $yearsActive ?></p>
        </div>

        <div class="row g-4 reveal-stagger">
            <?php
            $reasons = [
                ['certificate',      'Authorised Dealer',         'Officially authorised for Bahria Town, DHA, Capital Smart City, Blue World City, Gulberg Greens, and more. All listings are verified and legitimate.'],
                ['brands whatsapp',  'WhatsApp-First Support',    'Reach us instantly via WhatsApp for inquiries, site visits, and payment plans. Our team responds within minutes during business hours.'],
                ['shield-halved',    'Verified Listings',         'Every property undergoes thorough verification. We ensure accurate pricing, legal status, and ownership documentation before listing.'],
                ['handshake',        'Expert Negotiation',        'Our seasoned agents negotiate the best deals on your behalf — whether you\'re buying, selling, or renting. We protect your investment.'],
                ['file-contract',    'Legal Documentation',       'We guide you through all paperwork — registry, NOC, transfer letters, and payment receipts — ensuring a smooth, stress-free transaction.'],
                ['chart-line',       'Investment Advice',         'Get expert guidance on high-yield investment opportunities, upcoming projects, and market trends to maximise your returns.'],
            ];
            foreach ($reasons as $idx => [$icon, $title, $text]):
                $prefix = str_contains($icon, 'brands') ? 'fa-brands' : 'fa-solid';
                $cls    = preg_replace('/^brands /', '', $icon);
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="reason-card">
                    <div class="reason-number"><?= str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="reason-icon"><i class="<?= $prefix ?> fa-<?= $cls ?>"></i></div>
                    <div class="reason-title"><?= $title ?></div>
                    <p class="reason-text"><?= $text ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     9. TESTIMONIALS
     ════════════════════════════════════════════════════════════ -->
<section class="section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-label">Client Voices</div>
            <h2 class="section-title">Loved by buyers, renters &amp; investors</h2>
            <p class="section-subtitle">Real feedback from real Al-Riaz clients across Pakistan.</p>
        </div>

        <div class="row g-4 reveal-stagger">
            <?php
            $testimonials = [
                ['AK', 'Ayesha Khan',    'Buyer, Islamabad',   'Booked a plot in Bahria Phase 8 through Al-Riaz. The whole process — from NOC check to transfer — was transparent and stress-free.', 5],
                ['OM', 'Omar Malik',     'Investor, Karachi',  'I\'ve invested in three of their projects. Their market reads are sharp, and the WhatsApp updates keep me in the loop without the noise.', 5],
                ['SR', 'Sana Rafiq',     'Renter, Rawalpindi', 'Found a great 2-bed apartment in under a week. The agent actually understood what I wanted instead of showing random places.', 5],
            ];
            foreach ($testimonials as [$initials, $name, $role, $text, $stars]):
            ?>
            <div class="col-12 col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <?php for ($s = 0; $s < $stars; $s++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                    </div>
                    <p class="testimonial-text">&ldquo;<?= $text ?>&rdquo;</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><?= $initials ?></div>
                        <div>
                            <div class="testimonial-name"><?= $name ?></div>
                            <div class="testimonial-role"><?= $role ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     10. FAQ
     ════════════════════════════════════════════════════════════ -->
<section class="section" style="background:var(--gray-50);">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5 reveal">
                <div class="section-label">FAQ</div>
                <h2 class="section-title">Questions, answered.</h2>
                <p class="section-subtitle">Still unsure? Message us on WhatsApp and a real agent will walk you through your case.</p>
                <a href="<?= waLink(SITE_WHATSAPP, 'Hi, I have a question before I start.') ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="btn-navy mt-3">
                    <i class="fa-brands fa-whatsapp"></i> Ask on WhatsApp
                </a>
            </div>

            <div class="col-lg-7 reveal">
                <?php
                $faqs = [
                    ['How do I know a listing is legitimate?',
                     'Every listing on Al-Riaz goes through a verification step — ownership docs, NOC, and pricing are cross-checked before it goes live. You\'ll see a "Verified" badge on cards that have cleared the process.'],
                    ['Do you charge a brokerage fee?',
                     'For standard transactions we follow Pakistan\'s market-standard brokerage. We always disclose the fee up front — no surprises at the signing table.'],
                    ['Can I invest without visiting Pakistan?',
                     'Yes — we support remote buyers with video site walks, digital document signing via our partners, and direct WhatsApp updates through every stage.'],
                    ['Which cities do you cover?',
                     'Islamabad, Rawalpindi, Lahore, and Karachi are our primary markets. We can source listings in other major Pakistani cities on request.'],
                    ['How fast do you respond to inquiries?',
                     'WhatsApp inquiries are typically acknowledged within 10 minutes during business hours (Mon–Sat, 9am–7pm). After-hours messages are replied to first thing next morning.'],
                ];
                foreach ($faqs as $i => [$q, $a]):
                    $open = $i === 0 ? 'open' : '';
                ?>
                <details class="faq-item" <?= $open ?>>
                    <summary>
                        <span class="faq-q"><?= htmlspecialchars($q) ?></span>
                        <span class="faq-icon"><i class="fa-solid fa-plus"></i></span>
                    </summary>
                    <div class="faq-a"><?= htmlspecialchars($a) ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     11. CTA BANNER
     ════════════════════════════════════════════════════════════ -->
<section class="final-cta-section">
    <div class="container">
        <div class="final-cta-card reveal">
            <div class="final-cta-backdrop" aria-hidden="true"></div>
            <div class="final-cta-inner">
                <div class="final-cta-left">
                    <div class="section-label on-dark" style="justify-content:flex-start;">Let's Talk</div>
                    <h2 class="final-cta-heading"><?= htmlspecialchars($homeSettings['cta_heading'] ?? 'Ready to buy, sell, or rent?', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="final-cta-sub">
                        <?= htmlspecialchars($homeSettings['cta_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <div class="final-cta-actions">
                        <a href="<?= waLink(SITE_WHATSAPP, "Hello! I'm interested in buying/renting a property. Can you help?") ?>"
                           target="_blank" rel="noopener noreferrer" class="btn-gold">
                            <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                        </a>
                        <a href="<?= $b ?>/contact.php" class="btn-outline-white">
                            <i class="fa-solid fa-envelope"></i> Contact Form
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
