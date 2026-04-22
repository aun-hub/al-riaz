<?php
/**
 * Al-Riaz Associates — Single Property Detail Page
 * URL: /listing.php?slug=some-property-slug
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

/* ─── Slug Validation ───────────────────────────────────────────────────── */
$rawSlug = trim($_GET['slug'] ?? '');
$slug    = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));

if ($slug === '') {
    redirect('/residential.php');
}

/* ─── Fetch Property ────────────────────────────────────────────────────── */
try {
    $stmt = $db->prepare('
        SELECT p.*,
               u.name      AS agent_name,
               u.phone     AS agent_phone,
               u.email     AS agent_email,
               u.avatar_url AS agent_avatar,
               proj.name   AS project_name,
               proj.slug   AS project_slug
        FROM   properties p
        LEFT JOIN users    u    ON u.id    = p.agent_id
        LEFT JOIN projects proj ON proj.id = p.project_id
        WHERE  p.slug = ? AND p.is_published = 1
        LIMIT  1
    ');
    $stmt->execute([$slug]);
    $property = $stmt->fetch();
} catch (Exception $e) {
    error_log('[listing.php] property query: ' . $e->getMessage());
    $property = null;
}

if (!$property) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

/* ─── Fetch Media ───────────────────────────────────────────────────────── */
try {
    $stmtMedia = $db->prepare('
        SELECT * FROM property_media
        WHERE  property_id = ?
        ORDER  BY sort_order ASC
    ');
    $stmtMedia->execute([$property['id']]);
    $media = $stmtMedia->fetchAll();
} catch (Exception $e) {
    error_log('[listing.php] media query: ' . $e->getMessage());
    $media = [];
}

$images     = array_values(array_filter($media, fn($m) => $m['kind'] === 'image'));
$videos     = array_values(array_filter($media, fn($m) => $m['kind'] === 'video'));
$floorPlans = array_values(array_filter($media, fn($m) => $m['kind'] === 'floor_plan'));

/* ─── Fetch Similar Properties ──────────────────────────────────────────── */
try {
    $stmtSim = $db->prepare('
        SELECT p.id, p.title, p.slug, p.city, p.area_locality,
               p.price, p.price_on_demand, p.area_value, p.area_unit,
               p.bedrooms, p.bathrooms, p.category, p.purpose, p.listing_type,
               p.is_featured,
               (SELECT pm.url FROM property_media pm
                WHERE pm.property_id = p.id AND pm.kind = \'image\'
                ORDER BY pm.sort_order ASC LIMIT 1) AS thumbnail
        FROM   properties p
        WHERE  p.city = ?
          AND  p.category = ?
          AND  p.id != ?
          AND  p.is_published = 1
        ORDER  BY p.is_featured DESC, p.created_at DESC
        LIMIT  4
    ');
    $stmtSim->execute([$property['city'], $property['category'], $property['id']]);
    $similar = $stmtSim->fetchAll();
} catch (Exception $e) {
    error_log('[listing.php] similar query: ' . $e->getMessage());
    $similar = [];
}

/* ─── Decode Features ───────────────────────────────────────────────────── */
$features = [];
if (!empty($property['features'])) {
    $features = json_decode($property['features'], true) ?? [];
}

/* ─── SEO / Meta ────────────────────────────────────────────────────────── */
$ogImage     = $images[0]['url'] ?? 'https://picsum.photos/id/1029/1200/630';
$descClean   = strip_tags($property['description'] ?? '');
$metaDesc    = mb_substr($descClean, 0, 160);
$pageTitle   = htmlspecialchars($property['title']) . ' - Al-Riaz Associates';

/* ─── JSON-LD Schema ────────────────────────────────────────────────────── */
$schema = [
    '@context'     => 'https://schema.org',
    '@type'        => 'RealEstateListing',
    'name'         => $property['title'],
    'description'  => mb_substr($descClean, 0, 200),
    'url'          => SITE_URL . '/listing.php?slug=' . urlencode($property['slug']),
    'image'        => $ogImage,
    'price'        => $property['price'],
    'priceCurrency'=> 'PKR',
];

/* ─── Breadcrumb Category ───────────────────────────────────────────────── */
$catMap = [
    'residential' => ['label' => 'Residential', 'url' => '/residential.php'],
    'commercial'  => ['label' => 'Commercial',  'url' => '/commercial.php'],
    'plot'        => ['label' => 'Plots',        'url' => '/residential.php?category=plot'],
];
$catEntry = $catMap[$property['category']] ?? ['label' => ucfirst($property['category']), 'url' => '/residential.php'];

$breadcrumbItems = [
    ['label' => 'Home', 'url' => '/'],
    ['label' => $catEntry['label'], 'url' => $catEntry['url']],
    ['label' => htmlspecialchars($property['city']), 'url' => $catEntry['url'] . (strpos($catEntry['url'], '?') !== false ? '&' : '?') . 'city=' . urlencode($property['city'])],
    ['label' => mb_strimwidth(htmlspecialchars($property['title']), 0, 40, '…'), 'url' => null],
];

/* ─── Feature icon map ──────────────────────────────────────────────────── */
$featureIcons = [
    'parking'        => ['fa-car',          'Parking'],
    'gas'            => ['fa-fire',          'Gas'],
    'electricity'    => ['fa-bolt',          'Electricity'],
    'water'          => ['fa-tint',          'Water Supply'],
    'security'       => ['fa-shield-alt',    'Security'],
    'furnished'      => ['fa-couch',         'Furnished'],
    'corner'         => ['fa-border-all',    'Corner Plot'],
    'garden'         => ['fa-leaf',          'Garden'],
    'servant_quarter'=> ['fa-user',          'Servant Quarter'],
    'boundary_wall'  => ['fa-border-style',  'Boundary Wall'],
    'drawing_room'   => ['fa-door-open',     'Drawing Room'],
    'lift'           => ['fa-grip-lines',    'Lift / Elevator'],
    'generator'      => ['fa-plug',          'Generator'],
    'internet'       => ['fa-wifi',          'Internet'],
    'cctv'           => ['fa-video',         'CCTV'],
    'solar'          => ['fa-sun',           'Solar Energy'],
    'gym'            => ['fa-dumbbell',      'Gym'],
    'pool'           => ['fa-swimming-pool', 'Swimming Pool'],
    'store_room'     => ['fa-box',           'Store Room'],
];

/* ─── Extra <head> injections (OG + JSON-LD + canonical) ───────────────── */
$extraHead = '
    <meta property="og:title"       content="' . htmlspecialchars($property['title'] . ' | ' . SITE_NAME) . '">
    <meta property="og:description" content="' . htmlspecialchars($metaDesc) . '">
    <meta property="og:image"       content="' . htmlspecialchars($ogImage) . '">
    <meta property="og:url"         content="' . htmlspecialchars(SITE_URL . '/listing.php?slug=' . urlencode($property['slug'])) . '">
    <meta property="og:type"        content="article">
    <link rel="canonical" href="' . htmlspecialchars(SITE_URL . '/listing.php?slug=' . urlencode($property['slug'])) . '">
    <script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>
';

$b = defined('BASE_PATH') ? BASE_PATH : '';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page Header with hero background ─────────────────────────────────── -->
<div class="page-header" style="background-image: url('<?= htmlspecialchars($images[0]['url'] ?? '') ?>'); background-size:cover; background-position:center; position:relative;">
    <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(6,13,31,.85) 40%,rgba(6,13,31,.4));"></div>
    <div class="container" style="position:relative;z-index:1;">
        <?= generateBreadcrumb($breadcrumbItems) ?>
        <h1 class="page-header-title"><?= htmlspecialchars($property['title']) ?></h1>
        <p class="page-header-sub"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($property['area_locality']) ?>, <?= htmlspecialchars($property['city']) ?></p>
    </div>
</div>

<main id="main-content" data-property-id="<?= (int)$property['id'] ?>">
<div class="container-fluid px-0">

    <!-- ══════════════════════════════════════════════════════════════════════
         GALLERY SECTION
         ══════════════════════════════════════════════════════════════════ -->
    <section class="gallery-wrap" aria-label="Property photos">

        <?php if (!empty($images) || !empty($floorPlans)): ?>
        <!-- Bootstrap Carousel -->
        <div id="propertyCarousel" class="carousel slide" data-bs-ride="false" aria-label="Property gallery">
            <div class="carousel-inner">

                <?php foreach ($images as $i => $img): ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($img['url']) ?>"
                         class="gallery-carousel-img d-block w-100"
                         alt="<?= htmlspecialchars($img['alt_text'] ?: $property['title'] . ' photo ' . ($i + 1)) ?>"
                         loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                </div>
                <?php endforeach; ?>

                <?php foreach ($floorPlans as $i => $fp): ?>
                <div class="carousel-item" id="floorPlanSlide">
                    <img src="<?= htmlspecialchars($fp['url']) ?>"
                         alt="Floor Plan <?= $i + 1 ?>"
                         class="gallery-carousel-img d-block w-100"
                         loading="lazy"
                         style="object-fit: contain; background: #f5f5f5;">
                </div>
                <?php endforeach; ?>

            </div><!-- /.carousel-inner -->

            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev" aria-label="Previous photo">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next" aria-label="Next photo">
                <span class="carousel-control-next-icon"></span>
            </button>

            <!-- Photo counter -->
            <div class="photo-counter" id="photoCounter" aria-live="polite">
                1 / <?= count($images) + count($floorPlans) ?>
            </div>
        </div><!-- /#propertyCarousel -->

        <!-- Thumbnail strip -->
        <div class="gallery-thumb-strip" id="thumbStrip" role="list" aria-label="Photo thumbnails">
            <?php foreach ($images as $i => $img): ?>
            <img src="<?= htmlspecialchars($img['url']) ?>"
                 class="gallery-thumb-item <?= $i === 0 ? 'active' : '' ?>"
                 data-index="<?= $i ?>"
                 alt="<?= htmlspecialchars($img['alt_text'] ?: $property['title'] . ' thumbnail ' . ($i + 1)) ?>">
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Fallback placeholder -->
        <div style="height:400px; background:#e8e0d0; display:flex; align-items:center; justify-content:center;">
            <div class="text-center text-muted">
                <i class="fas fa-image fa-3x mb-3"></i>
                <p>No photos available</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Video(s) below gallery -->
        <?php if (!empty($videos)): ?>
        <div class="container py-3">
            <?php foreach ($videos as $vid): ?>
            <div class="ratio ratio-16x9 mb-3" style="max-width:700px; margin:0 auto;">
                <video controls preload="metadata"
                       src="<?= htmlspecialchars($vid['url']) ?>"
                       poster="<?= htmlspecialchars($images[0]['url'] ?? '') ?>">
                    Your browser does not support video playback.
                </video>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </section>

    <!-- ══════════════════════════════════════════════════════════════════════
         LIGHTBOX MODAL
         ══════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="galleryLightbox" tabindex="-1" aria-label="Photo lightbox" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0 py-2">
                    <span class="text-white small" id="lbCounter">1 / <?= count($images) ?></span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <img id="lbImage" src="" alt="Property photo" class="img-fluid w-100">
                    <button id="lbPrev" class="btn btn-dark position-absolute start-0 top-50 translate-middle-y ms-2" aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button id="lbNext" class="btn btn-dark position-absolute end-0 top-50 translate-middle-y me-2" aria-label="Next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.container-fluid -->

<!-- ══════════════════════════════════════════════════════════════════════════
     MAIN CONTENT + SIDEBAR
     ════════════════════════════════════════════════════════════════════════ -->
<div class="container py-4">
<div class="row g-4">

    <!-- ── MAIN CONTENT col-lg-8 ─────────────────────────────────────────── -->
    <article class="col-lg-8">

        <!-- a) HEADER ─────────────────────────────────────────────────────── -->
        <header class="mb-4">

            <!-- Badges row -->
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge badge-purpose-<?= htmlspecialchars($property['purpose']) ?> text-uppercase">
                    <?= getPurposeLabel($property['purpose']) ?>
                </span>
                <span class="badge bg-secondary text-uppercase"><?= getCategoryLabel($property['category']) ?></span>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars(getListingTypeLabel($property['listing_type'])) ?></span>
                <?php if ($property['possession_status'] === 'ready'): ?>
                    <span class="badge bg-success">Ready</span>
                <?php elseif ($property['possession_status'] === 'under_construction'): ?>
                    <span class="badge bg-warning text-dark">Under Construction</span>
                <?php endif; ?>
                <?php if ($property['is_featured']): ?>
                    <span class="badge badge-featured"><i class="fas fa-star me-1"></i>Featured</span>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h2 class="detail-title mb-1">
                <?= htmlspecialchars($property['title']) ?>
            </h2>

            <!-- Location -->
            <p class="text-muted mb-2">
                <i class="fa-solid fa-location-dot me-1" style="color:var(--gold-500);"></i>
                <?= htmlspecialchars($property['area_locality']) ?>,&nbsp;<?= htmlspecialchars($property['city']) ?>
            </p>

            <!-- Price -->
            <div class="mb-3">
                <?php if ($property['price_on_demand']): ?>
                    <p class="fw-semibold fs-5">
                        <i class="fas fa-tag me-1" style="color:var(--gold-500);"></i>Price on Demand
                        <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=<?= rawurlencode('Hi, I would like to know the price for: ' . $property['title']) ?>"
                           class="btn btn-sm btn-whatsapp ms-2" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp me-1"></i>Ask Price
                        </a>
                    </p>
                <?php elseif ($property['purpose'] === 'rent'): ?>
                    <p class="detail-price">
                        PKR <?= formatPKR((int)$property['price']) ?>
                        <small class="text-muted fs-6 fw-normal">
                            / <?= $property['rent_period'] === 'yearly' ? 'year' : 'month' ?>
                        </small>
                    </p>
                <?php else: ?>
                    <p class="detail-price">PKR <?= formatPKR((int)$property['price']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Share + Action buttons -->
            <div class="d-flex flex-wrap gap-2">
                <button id="shareBtn" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-share-alt me-1"></i>Share
                </button>
                <button id="shareWaBtn" class="btn btn-outline-success btn-sm">
                    <i class="fa-brands fa-whatsapp me-1"></i>Share on WhatsApp
                </button>
                <a href="tel:<?= htmlspecialchars($property['agent_phone'] ?: SITE_PHONE) ?>"
                   class="btn btn-outline-dark btn-sm copy-phone"
                   data-phone="<?= htmlspecialchars($property['agent_phone'] ?: SITE_PHONE) ?>">
                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($property['agent_phone'] ?: SITE_PHONE) ?>
                </a>
            </div>
        </header>

        <!-- b) KEY SPECS GRID ─────────────────────────────────────────────── -->
        <section aria-label="Key specifications" class="mb-4">
            <div class="detail-specs-grid">

                <!-- Area — always shown -->
                <div class="detail-spec-item">
                    <i class="fa-solid fa-vector-square"></i>
                    <span class="spec-value">
                        <?= htmlspecialchars(formatArea((float)$property['area_value'], $property['area_unit'])) ?>
                    </span>
                    <span class="spec-label">Area</span>
                    <?php if (in_array($property['area_unit'], ['marla', 'kanal'])): ?>
                    <div class="mt-1">
                        <a href="#" class="area-convert-toggle"
                           data-area-value="<?= (float)$property['area_value'] ?>"
                           data-area-unit="<?= htmlspecialchars($property['area_unit']) ?>"
                           title="Convert to Sq Ft" style="color:var(--gold-500); cursor:pointer; text-decoration:underline dotted; font-size:.82rem;">(show in Sq Ft)</a>
                        <span class="area-sqft-hint d-block" style="display:none!important; font-size:.82rem;"></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($property['category'] === 'residential'): ?>
                <!-- Bedrooms -->
                <div class="detail-spec-item">
                    <i class="fa-solid fa-bed"></i>
                    <span class="spec-value"><?= (int)$property['bedrooms'] ?: '—' ?></span>
                    <span class="spec-label">Bedrooms</span>
                </div>

                <!-- Bathrooms -->
                <div class="detail-spec-item">
                    <i class="fa-solid fa-bath"></i>
                    <span class="spec-value"><?= (int)$property['bathrooms'] ?: '—' ?></span>
                    <span class="spec-label">Bathrooms</span>
                </div>
                <?php endif; ?>

                <!-- Type -->
                <div class="detail-spec-item">
                    <i class="fa-solid fa-house"></i>
                    <span class="spec-value" style="font-size:.9rem;"><?= htmlspecialchars(getListingTypeLabel($property['listing_type'])) ?></span>
                    <span class="spec-label">Type</span>
                </div>

                <!-- Possession -->
                <div class="detail-spec-item">
                    <i class="fa-solid fa-key"></i>
                    <span class="spec-value" style="font-size:.9rem;">
                        <?php
                        $posMap = ['ready' => 'Ready', 'under_construction' => 'Under Construction', 'not_applicable' => 'N/A'];
                        echo htmlspecialchars($posMap[$property['possession_status']] ?? ucfirst($property['possession_status']));
                        ?>
                    </span>
                    <span class="spec-label">Possession</span>
                </div>

                <!-- Purpose -->
                <div class="detail-spec-item">
                    <i class="fa-solid fa-tag"></i>
                    <span class="spec-value"><?= htmlspecialchars(getPurposeLabel($property['purpose'])) ?></span>
                    <span class="spec-label">Purpose</span>
                </div>

                <?php if ($property['category'] === 'commercial' && !empty($features['parking'])): ?>
                <div class="detail-spec-item">
                    <i class="fa-solid fa-car"></i>
                    <span class="spec-value"><?= htmlspecialchars($features['parking']) ?></span>
                    <span class="spec-label">Parking</span>
                </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- c) DESCRIPTION ──────────────────────────────────────────────── -->
        <?php if (!empty($property['description'])): ?>
        <section class="mb-4" aria-label="Property description">
            <h2 class="content-heading">About This Property</h2>
            <div class="property-description" style="line-height:1.8; color:#333;">
                <?php
                // Allow basic HTML but ensure output is sane
                $desc = $property['description'];
                // If plain text (no tags), convert newlines to paragraphs
                if (strip_tags($desc) === $desc) {
                    $paras = array_filter(array_map('trim', explode("\n\n", $desc)));
                    foreach ($paras as $p) {
                        echo '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                    }
                } else {
                    // Trust HTML stored in DB (admin-entered)
                    echo $desc;
                }
                ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- d) FEATURES & AMENITIES ─────────────────────────────────────── -->
        <?php if (!empty($features)): ?>
        <section class="mb-4" aria-label="Features and amenities">
            <h2 class="content-heading">Features &amp; Amenities</h2>
            <div class="feature-chips">
                <?php foreach ($features as $key => $val):
                    // Support both boolean flags (true) and string values
                    if ($val === false || $val === null || $val === '' || $val === 0) continue;
                    $iconDef = $featureIcons[$key] ?? ['fa-check-circle', ucfirst(str_replace('_', ' ', $key))];
                    $icon    = $iconDef[0];
                    $label   = $iconDef[1];
                    $display = ($val === true || $val === 1 || $val === '1') ? $label : $label . ': ' . htmlspecialchars($val);
                ?>
                <span class="feature-chip">
                    <i class="fas <?= htmlspecialchars($icon) ?>"></i>
                    <?= htmlspecialchars($display) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- e) LOCATION ─────────────────────────────────────────────────── -->
        <section class="mb-4" aria-label="Property location">
            <h2 class="content-heading">Location</h2>
            <p class="text-muted mb-3">
                <i class="fa-solid fa-location-dot me-1" style="color:var(--gold-500);"></i>
                <?php
                $addrParts = array_filter([
                    $property['address_line'] ?? '',
                    $property['area_locality'] ?? '',
                    $property['city'] ?? '',
                ]);
                echo htmlspecialchars(implode(', ', $addrParts));
                ?>
            </p>

            <button id="showMapBtn" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-map-marked-alt me-1"></i>Show on Map
            </button>

            <div id="mapEmbed" class="d-none">
                <iframe
                    data-src="https://maps.google.com/maps?q=<?= urlencode(($property['area_locality'] ?? '') . ', ' . ($property['city'] ?? '') . ', Pakistan') ?>&output=embed"
                    width="100%" height="350" frameborder="0"
                    style="border:0; border-radius:8px;" loading="lazy"
                    allowfullscreen
                    title="Property location map"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </section>

        <!-- f) PROJECT LINK ─────────────────────────────────────────────── -->
        <?php if (!empty($property['project_id']) && !empty($property['project_name'])): ?>
        <section class="mb-4" aria-label="Associated project">
            <div class="card p-3 d-flex flex-row align-items-center gap-3" style="border-left:4px solid var(--gold-500);">
                <div style="flex:1;">
                    <p class="small text-muted mb-0">Part of Authorised Project</p>
                    <h5 class="mb-0 fw-bold" style="color:var(--navy-700);">
                        <i class="fas fa-building me-2" style="color:var(--gold-500);"></i>
                        <?= htmlspecialchars($property['project_name']) ?>
                    </h5>
                </div>
                <a href="<?= $b ?>/project.php?slug=<?= urlencode($property['project_slug']) ?>"
                   class="btn btn-gold btn-sm text-nowrap">
                    View Project
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- g) SIMILAR PROPERTIES ───────────────────────────────────────── -->
        <?php if (!empty($similar)): ?>
        <section class="mb-4" aria-label="Similar properties">
            <h2 class="content-heading">Similar Properties</h2>
            <div class="row g-3" id="similarSection">
                <?php foreach ($similar as $i => $sim):
                    $prop = $sim;
                    $waPhone = SITE_WHATSAPP; ?>
                <div class="col-sm-6">
                    <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </article><!-- /.col-lg-8 -->

    <!-- ── SIDEBAR col-lg-4 ──────────────────────────────────────────────── -->
    <aside class="col-lg-4" aria-label="Contact and enquiry">
        <div class="detail-sidebar">

            <!-- Agent Card ───────────────────────────────────────────────── -->
            <div class="agent-card card mb-3 shadow-sm">
                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($property['agent_avatar'] ?: 'https://picsum.photos/id/64/80/80') ?>"
                         alt="<?= htmlspecialchars($property['agent_name'] ?: 'Agent') ?>"
                         class="agent-avatar rounded-circle mb-2"
                         onerror="this.src='https://picsum.photos/id/64/80/80'"
                         style="width:80px; height:80px; object-fit:cover;">
                    <h5 class="mb-0 fw-bold">
                        <?= htmlspecialchars($property['agent_name'] ?: 'Al-Riaz Associate') ?>
                    </h5>
                    <p class="text-muted mb-3 small">Property Agent</p>

                    <a href="tel:<?= htmlspecialchars($property['agent_phone'] ?: '+923001234567') ?>"
                       class="btn btn-navy btn-sm w-100 mb-2 copy-phone"
                       data-phone="<?= htmlspecialchars($property['agent_phone'] ?: SITE_PHONE) ?>">
                        <i class="fas fa-phone me-1"></i>
                        <?= htmlspecialchars($property['agent_phone'] ?: SITE_PHONE) ?>
                    </a>

                    <a href="<?= htmlspecialchars(getWhatsAppLink(
                            preg_replace('/[^0-9]/', '', $property['agent_phone'] ?: '923001234567'),
                            'Hi, I\'m interested in: ' . $property['title'] . '. Please share more details.'
                        )) ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn btn-whatsapp btn-sm w-100">
                        <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp Agent
                    </a>
                </div>
            </div>

            <!-- Enquiry Form ─────────────────────────────────────────────── -->
            <div class="enquiry-card card shadow-sm">
                <div class="card-header" style="background:linear-gradient(135deg,var(--navy-800),var(--navy-600)); color:#fff;">
                    <h5 class="mb-0 fs-6"><i class="fas fa-paper-plane me-2"></i>Send Enquiry</h5>
                </div>
                <div class="card-body">

                    <form id="enquiryForm" method="post" action="<?= $b ?>/api/v1/inquiries.php" novalidate>
                        <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                        <input type="hidden" name="source"      value="listing">
                        <!-- Honeypot anti-spam field -->
                        <input type="text" name="website"
                               style="display:none; visibility:hidden;"
                               tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="mb-3">
                            <label for="inqName" class="form-label small fw-semibold">Your Name *</label>
                            <input type="text" id="inqName" name="name" class="form-control form-control-sm"
                                   placeholder="e.g. Ahmed Khan" required
                                   minlength="2" maxlength="120" autocomplete="name">
                        </div>

                        <div class="mb-3">
                            <label for="inqPhone" class="form-label small fw-semibold">Phone Number *</label>
                            <input type="tel" id="inqPhone" name="phone" class="form-control form-control-sm"
                                   placeholder="e.g. 0311 1234567" required
                                   autocomplete="tel">
                            <div class="invalid-feedback">
                                Please enter a valid Pakistani phone number (e.g. 0311 1234567)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="inqEmail" class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" id="inqEmail" name="email" class="form-control form-control-sm"
                                   placeholder="you@example.com" required
                                   maxlength="160" autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label for="inqTime" class="form-label small fw-semibold">Preferred Contact Time</label>
                            <select id="inqTime" name="preferred_contact_time" class="form-select form-select-sm">
                                <option value="">Any Time</option>
                                <option value="morning">Morning (9am–12pm)</option>
                                <option value="afternoon">Afternoon (12pm–5pm)</option>
                                <option value="evening">Evening (5pm–8pm)</option>
                                <option value="weekend">Weekend</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="inqMsg" class="form-label small fw-semibold">Message (optional)</label>
                            <textarea id="inqMsg" name="message" class="form-control form-control-sm"
                                      rows="3" maxlength="1000"
                                      placeholder="Any specific requirements or questions…"></textarea>
                        </div>

                        <button type="submit" class="btn btn-gold w-100" id="submitEnquiry">
                            <span class="btn-text">
                                <i class="fas fa-paper-plane me-1"></i>Send Enquiry
                            </span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-label="Sending…"></span>
                        </button>
                    </form>

                    <!-- Success state (hidden until AJAX success) -->
                    <div class="enquiry-success d-none text-center py-3">
                        <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                        <h5 class="fw-bold text-success">Enquiry Sent!</h5>
                        <p class="small text-muted">We'll contact you within 2–4 working hours.</p>
                        <a href="<?= htmlspecialchars(getWhatsAppLink('923001234567', 'Hi, I just submitted an enquiry for: ' . $property['title'] . '. Looking forward to your response.')) ?>"
                           class="btn btn-whatsapp btn-sm mt-1" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp for Faster Response
                        </a>
                    </div>

                </div><!-- /.card-body -->
            </div><!-- /.enquiry-card -->

        </div><!-- /.detail-sidebar -->
    </aside>

</div><!-- /.row -->
</div><!-- /.container -->
</main>

<!-- Mobile CTA Bar -->
<div class="mobile-cta-bar d-lg-none">
    <a href="tel:<?= htmlspecialchars($property['agent_phone'] ?: SITE_PHONE) ?>" class="cta-call">
        <i class="fa-solid fa-phone"></i> Call Agent
    </a>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $property['agent_phone'] ?: SITE_WHATSAPP) ?>?text=<?= rawurlencode('Hi, I am interested in: ' . $property['title']) ?>" target="_blank" class="cta-wa">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="<?= $b ?>/assets/js/detail.js"></script>
