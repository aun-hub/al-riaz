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
         ORDER BY sort_order ASC, created_at DESC
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
        'SELECT p.*, pm.url AS thumbnail
         FROM properties p
         LEFT JOIN property_media pm
             ON pm.property_id = p.id AND pm.kind = \'image\' AND pm.sort_order = 0
         WHERE p.is_featured = 1 AND p.is_published = 1
         ORDER BY p.created_at DESC
         LIMIT 9'
    );
    $stmt->execute();
    $featuredProperties = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[Home] Properties query: ' . $e->getMessage());
}

// Stats
$totalListings = 500;
$totalProjects = 15;
$happyClients  = 200;
$yearsActive   = 5;

try {
    $c = (int) $db->query('SELECT COUNT(*) FROM properties WHERE is_published = 1')->fetchColumn();
    if ($c > 0) $totalListings = $c;
} catch (Exception $e) {}

try {
    $c = (int) $db->query('SELECT COUNT(*) FROM projects WHERE is_published = 1')->fetchColumn();
    if ($c > 0) $totalProjects = $c;
} catch (Exception $e) {}

$pageTitle = 'Al-Riaz Associates — Islamabad & Rawalpindi Real Estate';
$metaDesc  = 'Al-Riaz Associates — Authorised dealer for top real estate projects in Islamabad, Rawalpindi, Lahore & Karachi. Buy, sell, or rent with Pakistan\'s trusted agency.';

require_once 'includes/header.php';
?>

<!-- ════════════════════════════════════════════════════════════
     HERO SECTION
     ════════════════════════════════════════════════════════════ -->
<section class="site-hero">

    <!-- Animated background blobs -->
    <div class="hero-blob hero-blob-1" aria-hidden="true"></div>
    <div class="hero-blob hero-blob-2" aria-hidden="true"></div>
    <div class="hero-blob hero-blob-3" aria-hidden="true"></div>
    <div class="hero-dots" aria-hidden="true"></div>

    <div class="container">
        <div class="row align-items-center g-5">

            <!-- Left: Heading + CTA -->
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    Pakistan's Trusted Real Estate Agency
                </div>

                <h1 class="hero-heading">
                    Find Your<br>Dream Property<br>
                    <span class="accent">in Pakistan</span>
                </h1>

                <p class="hero-sub">
                    Islamabad &middot; Rawalpindi &middot; Lahore &middot; Karachi.
                    Authorised dealer for Bahria Town, DHA, Capital Smart City &amp; more.
                </p>

                <div class="hero-cta">
                    <a href="/search.php" class="btn-gold">
                        <i class="fa-solid fa-search"></i> Browse Properties
                    </a>
                    <a href="<?= waLink(SITE_WHATSAPP, 'Hi, I\'m interested in a property. Can you help?') ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn-outline-white">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp Us
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

            <!-- Right: Search Card -->
            <div class="col-lg-6">
                <div class="hero-search-card search-widget">
                    <div class="search-tabs">
                        <button type="button" class="search-tab active" data-purpose="sale">Buy</button>
                        <button type="button" class="search-tab" data-purpose="rent">Rent</button>
                        <button type="button" class="search-tab" data-purpose="invest">Invest</button>
                    </div>

                    <form id="search-form" method="GET" action="/search.php" autocomplete="off">
                        <input type="hidden" name="purpose" value="sale">

                        <div class="search-fields">

                            <!-- Location -->
                            <div class="search-field">
                                <i class="fa-solid fa-magnifying-glass search-field-icon"></i>
                                <input type="text"
                                       name="q"
                                       class="search-input search-autocomplete-input"
                                       placeholder="Location, project, or keyword..."
                                       aria-label="Search location or keyword">
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                                <!-- City -->
                                <div class="search-field">
                                    <i class="fa-solid fa-city search-field-icon"></i>
                                    <select name="city" class="search-select" aria-label="City">
                                        <option value="">All Cities</option>
                                        <option value="islamabad">Islamabad</option>
                                        <option value="rawalpindi">Rawalpindi</option>
                                        <option value="lahore">Lahore</option>
                                        <option value="karachi">Karachi</option>
                                    </select>
                                </div>

                                <!-- Type -->
                                <div class="search-field">
                                    <i class="fa-solid fa-home search-field-icon"></i>
                                    <select name="type" class="search-select" aria-label="Property type">
                                        <option value="">All Types</option>
                                        <option value="house">House</option>
                                        <option value="flat">Flat / Apartment</option>
                                        <option value="plot">Plot</option>
                                        <option value="shop">Shop</option>
                                        <option value="office">Office</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Max Price -->
                            <div class="search-field">
                                <i class="fa-solid fa-tag search-field-icon"></i>
                                <select name="max_price" class="search-select" aria-label="Max price">
                                    <option value="">Any Price</option>
                                    <option value="5000000">Up to 50 Lakh</option>
                                    <option value="10000000">Up to 1 Crore</option>
                                    <option value="25000000">Up to 2.5 Crore</option>
                                    <option value="50000000">Up to 5 Crore</option>
                                    <option value="100000000">Up to 10 Crore</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-search">
                                <i class="fa-solid fa-search"></i> Search Properties
                            </button>
                        </div>
                    </form>

                    <!-- Popular tags -->
                    <div style="margin-top:1rem; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                        <span style="font-size:0.72rem; color:rgba(255,255,255,0.40); font-weight:500;">Popular:</span>
                        <a href="/search.php?city=islamabad&type=plot"
                           style="font-size:0.72rem; padding:0.2rem 0.7rem; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.30); border-radius:999px; color:var(--gold-light); text-decoration:none; font-weight:600; transition:all 0.2s;">
                            Plots Islamabad
                        </a>
                        <a href="/search.php?city=rawalpindi&type=house"
                           style="font-size:0.72rem; padding:0.2rem 0.7rem; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.30); border-radius:999px; color:var(--gold-light); text-decoration:none; font-weight:600; transition:all 0.2s;">
                            Houses Rawalpindi
                        </a>
                        <a href="/projects.php"
                           style="font-size:0.72rem; padding:0.2rem 0.7rem; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.30); border-radius:999px; color:var(--gold-light); text-decoration:none; font-weight:600; transition:all 0.2s;">
                            New Projects
                        </a>
                    </div>
                </div>
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<!-- ════════════════════════════════════════════════════════════
     TRUST STRIP
     ════════════════════════════════════════════════════════════ -->
<div class="trust-strip">
    <div class="container">
        <div class="trust-strip-inner">
            <div class="trust-item"><i class="fa-solid fa-certificate"></i> Authorised Dealer</div>
            <div class="trust-divider"></div>
            <div class="trust-item"><i class="fa-solid fa-shield-halved"></i> Verified Listings</div>
            <div class="trust-divider"></div>
            <div class="trust-item"><i class="fa-brands fa-whatsapp"></i> WhatsApp-First Support</div>
            <div class="trust-divider"></div>
            <div class="trust-item"><i class="fa-solid fa-award"></i> <?= $yearsActive ?>+ Years Experience</div>
            <div class="trust-divider"></div>
            <div class="trust-item"><i class="fa-solid fa-users"></i> <?= $happyClients ?>+ Happy Clients</div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     FEATURED PROJECTS
     ════════════════════════════════════════════════════════════ -->
<section class="section">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div class="reveal">
                <div class="section-label">New Developments</div>
                <h2 class="section-title mb-0">Featured Projects</h2>
                <p class="section-subtitle mt-1">Authorised dealer for Pakistan's top real estate developments</p>
            </div>
            <a href="/projects.php" class="btn-outline-navy reveal" style="--delay:0.1s;">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (!empty($featuredProjects)) : ?>
        <div class="row g-4 reveal-stagger">
            <?php foreach (array_slice($featuredProjects, 0, 6) as $project) :
                $imgUrl   = !empty($project['hero_image_url'])
                            ? htmlspecialchars($project['hero_image_url'])
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
                        <a href="/project/<?= $slug ?>" aria-label="<?= $name ?>">
                            <img src="<?= $imgUrl ?>"
                                 alt="<?= $name ?>"
                                 loading="lazy"
                                 onerror="this.src='https://picsum.photos/id/1029/600/380'">
                        </a>
                    </div>
                    <div class="project-card-body">
                        <div class="project-status <?= $statusClass ?>">
                            <i class="fa-solid fa-circle" style="font-size:0.45rem;"></i>
                            <?= $statusLabel ?>
                        </div>
                        <div class="project-title">
                            <a href="/project/<?= $slug ?>" style="color:inherit; text-decoration:none;"><?= $name ?></a>
                        </div>
                        <?php if ($dev) : ?>
                        <div class="project-location" style="color:var(--navy-400); font-weight:600;">
                            <i class="fa-solid fa-building"></i> <?= $dev ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($city || $locality) : ?>
                        <div class="project-location">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= $locality ? "$locality, $city" : $city ?>
                        </div>
                        <?php endif; ?>
                        <div style="margin-top:1rem;">
                            <a href="/project/<?= $slug ?>" class="btn-navy" style="font-size:0.8rem; padding:0.55rem 1.25rem;">
                                View Project <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
            <div class="empty-title">Projects Coming Soon</div>
            <p class="empty-text">Check back soon for new project launches.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     STATS SECTION
     ════════════════════════════════════════════════════════════ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4 text-center reveal-stagger">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $totalListings ?>" data-count-suffix="">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Properties Listed</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $happyClients ?>" data-count-suffix="">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Happy Clients</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $totalProjects ?>" data-count-suffix="">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Projects Authorised</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <span data-count-to="<?= $yearsActive ?>" data-count-suffix="">0</span><span class="suffix">+</span>
                    </div>
                    <div class="stat-label">Years in Business</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     FEATURED PROPERTIES
     ════════════════════════════════════════════════════════════ -->
<section class="section" style="background:var(--gray-50);">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div class="reveal">
                <div class="section-label">Hot Listings</div>
                <h2 class="section-title mb-0">Featured Properties</h2>
                <p class="section-subtitle mt-1">Handpicked listings across Islamabad, Rawalpindi &amp; beyond</p>
            </div>
            <a href="/search.php" class="btn-outline-navy reveal">
                Browse All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (!empty($featuredProperties)) : ?>
        <div class="row g-4 reveal-stagger">
            <?php foreach (array_slice($featuredProperties, 0, 9) as $prop) :
                $thumb    = !empty($prop['thumbnail'])
                            ? htmlspecialchars($prop['thumbnail'])
                            : 'https://picsum.photos/id/' . (100 + (int)($prop['id'] ?? 0) % 50) . '/480/360';
                $slug     = htmlspecialchars($prop['slug'] ?? '#');
                $price    = formatPKR((float)($prop['price'] ?? 0));
                $title    = htmlspecialchars($prop['title'] ?? 'Property');
                $locality = htmlspecialchars($prop['area_locality'] ?? '');
                $city     = htmlspecialchars(ucfirst($prop['city'] ?? ''));
                $areaVal  = (float)($prop['area_value'] ?? 0);
                $areaUnit = $prop['area_unit'] ?? 'marla';
                $beds     = (int)($prop['bedrooms'] ?? 0);
                $baths    = (int)($prop['bathrooms'] ?? 0);
                $purpose  = $prop['purpose'] ?? 'sale';
                $propId   = (int)($prop['id'] ?? 0);
                $isNew    = !empty($prop['is_featured']);
                $waMsg    = rawurlencode("Hello! I'm interested in: $title – $price. Please share more details.");
                $waLink   = 'https://wa.me/' . SITE_WHATSAPP . '?text=' . $waMsg;
            ?>
            <div class="col-12 col-sm-6 col-xl-4" data-property-id="<?= $propId ?>">
                <div class="prop-card">
                    <div class="prop-card-img">
                        <a href="/property/<?= $slug ?>" aria-label="<?= $title ?>">
                            <img data-src="<?= $thumb ?>"
                                 src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3Crect fill='%23f3f4f6' width='4' height='3'/%3E%3C/svg%3E"
                                 alt="<?= $title ?>"
                                 class="lazy"
                                 loading="lazy">
                        </a>
                        <div class="prop-card-overlay" aria-hidden="true"></div>

                        <div class="prop-badges">
                            <span class="prop-badge <?= $purpose === 'rent' ? 'prop-badge-rent' : 'prop-badge-sale' ?>">
                                <?= $purpose === 'rent' ? 'Rent' : 'Sale' ?>
                            </span>
                            <?php if ($isNew) : ?>
                            <span class="prop-badge prop-badge-new">Featured</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?= $waLink ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="prop-wa-btn"
                           aria-label="WhatsApp enquiry for <?= $title ?>">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>

                    <div class="prop-card-body">
                        <div class="prop-price"><?= $price ?></div>
                        <div class="prop-title">
                            <a href="/property/<?= $slug ?>" style="color:inherit; text-decoration:none;"><?= $title ?></a>
                        </div>
                        <div class="prop-location">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= $locality ? "$locality, $city" : $city ?>
                        </div>

                        <div class="prop-specs">
                            <?php if ($beds > 0) : ?>
                            <div class="prop-spec">
                                <i class="fa-solid fa-bed"></i> <?= $beds ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($baths > 0) : ?>
                            <div class="prop-spec">
                                <i class="fa-solid fa-bath"></i> <?= $baths ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($areaVal > 0) : ?>
                            <div class="prop-spec">
                                <i class="fa-solid fa-vector-square"></i> <?= formatArea($areaVal, $areaUnit) ?>
                            </div>
                            <?php endif; ?>
                            <div class="prop-spec ms-auto">
                                <a href="/property/<?= $slug ?>" class="btn-navy" style="font-size:0.72rem; padding:0.35rem 0.875rem;">Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5 reveal">
            <a href="/search.php" class="btn-navy" style="padding:0.875rem 2.5rem; font-size:0.95rem;">
                <i class="fa-solid fa-th"></i> Browse All Properties
            </a>
        </div>

        <?php else : ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-home"></i></div>
            <div class="empty-title">No Properties Yet</div>
            <p class="empty-text">Listings will appear here once properties are added.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     WHY CHOOSE US
     ════════════════════════════════════════════════════════════ -->
<section class="section">
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-label">Why Us</div>
            <h2 class="section-title">Why Choose Al-Riaz Associates?</h2>
            <p class="section-subtitle">Your trusted real estate partner in Pakistan since <?= date('Y') - $yearsActive ?></p>
        </div>

        <div class="row g-4 reveal-stagger">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-certificate"></i></div>
                    <div class="feature-title">Authorised Dealer</div>
                    <p class="feature-text">Officially authorised for Bahria Town, DHA, Capital Smart City, Blue World City, Gulberg Greens, and more. All listings are verified and legitimate.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-brands fa-whatsapp"></i></div>
                    <div class="feature-title">WhatsApp-First Support</div>
                    <p class="feature-text">Reach us instantly via WhatsApp for inquiries, site visits, and payment plans. Our team responds within minutes during business hours.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="feature-title">Verified Listings</div>
                    <p class="feature-text">Every property undergoes thorough verification. We ensure accurate pricing, legal status, and ownership documentation before listing.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-handshake"></i></div>
                    <div class="feature-title">Expert Negotiation</div>
                    <p class="feature-text">Our seasoned agents negotiate the best deals on your behalf — whether you're buying, selling, or renting. We protect your investment.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-file-contract"></i></div>
                    <div class="feature-title">Legal Documentation</div>
                    <p class="feature-text">We guide you through all paperwork — registry, NOC, transfer letters, and payment receipts — ensuring a smooth, stress-free transaction.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="feature-title">Investment Advice</div>
                    <p class="feature-text">Get expert guidance on high-yield investment opportunities, upcoming projects, and market trends to maximise your returns.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     CTA BANNER
     ════════════════════════════════════════════════════════════ -->
<section class="section" style="background:var(--gradient-navy); position:relative; overflow:hidden;">
    <div style="position:absolute; inset:0; background-image:radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size:28px 28px; pointer-events:none;"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="row justify-content-center text-center">
            <div class="col-12 col-lg-7 reveal">
                <div class="section-label" style="justify-content:center;">Let's Talk</div>
                <h2 class="section-title" style="color:var(--white);">Ready to Buy, Sell, or Rent?</h2>
                <p style="color:rgba(255,255,255,0.65); font-size:1.05rem; margin-bottom:2rem;">
                    Contact our expert team today. We'll help you find the perfect property or get the best value for your investment.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="<?= waLink(SITE_WHATSAPP, "Hello! I'm interested in buying/renting a property. Can you help?") ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn-gold" style="font-size:0.95rem; padding:0.875rem 2rem;">
                        <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                    </a>
                    <a href="/contact.php" class="btn-outline-white" style="font-size:0.95rem; padding:0.875rem 2rem;">
                        <i class="fa-solid fa-envelope"></i> Contact Us
                    </a>
                </div>
                <p style="margin-top:1.5rem; font-size:0.82rem; color:rgba(255,255,255,0.40);">
                    <i class="fa-solid fa-clock"></i> Mon–Sat 9am–7pm &nbsp;&middot;&nbsp; Sun 11am–4pm
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
