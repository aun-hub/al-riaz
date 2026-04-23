<?php
/**
 * Al-Riaz Associates — Single Project Detail Page
 * URL: /project.php?slug=some-project-slug
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

/* ─── Slug Validation ───────────────────────────────────────────────────── */
$rawSlug = trim($_GET['slug'] ?? '');
$slug    = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));

if ($slug === '') {
    redirect('/projects.php');
}

/* ─── Fetch Project ─────────────────────────────────────────────────────── */
try {
    $stmt = $db->prepare('SELECT * FROM projects WHERE slug = ? AND is_published = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $project = $stmt->fetch();
} catch (Exception $e) {
    error_log('[project.php] project query: ' . $e->getMessage());
    $project = null;
}

if (!$project) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

/* ─── Decode Gallery ────────────────────────────────────────────────────── */
$gallery = [];
if (!empty($project['gallery'])) {
    $gallery = json_decode($project['gallery'], true) ?? [];
}

/* ─── Fetch Properties in this Project ──────────────────────────────────── */
try {
    $stmtProps = $db->prepare('
        SELECT p.id, p.title, p.slug, p.city, p.area_locality,
               p.price, p.price_on_demand, p.area_value, p.area_unit,
               p.bedrooms, p.bathrooms, p.category, p.purpose, p.listing_type,
               p.is_featured, p.possession_status,
               (SELECT pm.url FROM property_media pm
                WHERE pm.property_id = p.id AND pm.kind = \'image\'
                ORDER BY pm.sort_order ASC LIMIT 1) AS thumbnail
        FROM   properties p
        WHERE  p.project_id = ?
          AND  p.is_published = 1
          AND  p.is_sold = 0
        ORDER  BY p.is_featured DESC, p.created_at DESC
    ');
    $stmtProps->execute([$project['id']]);
    $projectProperties = $stmtProps->fetchAll();
} catch (Exception $e) {
    error_log('[project.php] properties query: ' . $e->getMessage());
    $projectProperties = [];
}

$listingCount = count($projectProperties);

/* ─── Status label helper (also needed in projects.php) ─────────────────── */
if (!function_exists('getStatusLabel')) {
    function getStatusLabel(string $status): string
    {
        $map = [
            'upcoming'          => 'Upcoming',
            'under_development' => 'Under Development',
            'ready'             => 'Ready',
            'possession'        => 'Possession',
        ];
        return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

/* ─── SEO / Meta ────────────────────────────────────────────────────────── */
$heroImage = $project['hero_image_url'] ? mediaUrl($project['hero_image_url']) : 'https://picsum.photos/id/1070/1200/630';
$descClean = strip_tags($project['description'] ?? '');
$metaDesc  = mb_substr($descClean ?: 'Al-Riaz Associates is an Authorised Dealer for ' . $project['name'] . ' in ' . $project['city'] . ', Pakistan.', 0, 160);
$pageTitle = htmlspecialchars($project['name']) . ' - Al-Riaz Associates';

/* ─── BASE_PATH ─────────────────────────────────────────────────────────── */
$b = defined('BASE_PATH') ? BASE_PATH : '';

/* ─── WhatsApp Phone ────────────────────────────────────────────────────── */
$waPhone = SITE_WHATSAPP;

/* ─── JSON-LD Schema ────────────────────────────────────────────────────── */
$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'RealEstateAgent',
    'name'        => 'Al-Riaz Associates',
    'description' => 'Authorised dealer for ' . $project['name'] . ' in ' . $project['city'],
    'url'         => SITE_URL . '/project.php?slug=' . urlencode($project['slug']),
    'image'       => $heroImage,
    'address'     => [
        '@type'           => 'PostalAddress',
        'addressLocality' => $project['city'],
        'addressCountry'  => 'PK',
    ],
    'telephone'   => SITE_PHONE,
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- JSON-LD -->
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<!-- Open Graph meta (supplemental — header.php covers basics) -->
<meta property="og:title"       content="<?= htmlspecialchars($project['name'] . ' | ' . SITE_NAME) ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($heroImage) ?>">
<meta property="og:url"         content="<?= htmlspecialchars(SITE_URL . '/project.php?slug=' . urlencode($project['slug'])) ?>">
<meta property="og:type"        content="article">
<link rel="canonical"           href="<?= htmlspecialchars(SITE_URL . '/project.php?slug=' . urlencode($project['slug'])) ?>">

<!-- ── Page Header (with cover image) ───────────────────────────────────── -->
<div class="page-header" style="background-image:url('<?= htmlspecialchars($project['cover_image'] ?? $heroImage) ?>');background-size:cover;background-position:center;position:relative;">
    <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(6,13,31,.85) 40%,rgba(6,13,31,.4));"></div>
    <div class="container" style="position:relative;z-index:1;">
        <?= generateBreadcrumb([['label'=>'Home','url'=>'/'],['label'=>'Projects','url'=>'/projects.php'],['label'=>htmlspecialchars($project['name'])]]) ?>
        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <h1 class="page-header-title mb-0"><?= htmlspecialchars($project['name']) ?></h1>
            <span class="prop-badge prop-badge-status-<?= htmlspecialchars($project['status'] ?? 'upcoming') ?>" style="font-size:.8rem;"><?= htmlspecialchars(getStatusLabel($project['status'])) ?></span>
            <span class="badge <?= $project['noc_status'] === 'approved' ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size:.75rem;">
                <i class="fa-solid fa-<?= $project['noc_status'] === 'approved' ? 'check-circle' : 'clock' ?> me-1"></i>
                NOC <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['noc_status']))) ?>
            </span>
        </div>
        <p class="page-header-sub">
            <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars(trim(($project['area_locality'] ? $project['area_locality'] . ', ' : '') . ($project['city'] ?? ''))) ?>
            <?php if (!empty($project['developer'])): ?>
            &nbsp;&bull;&nbsp;<i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($project['developer']) ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- ── Tabs + Content ─────────────────────────────────────────────────────── -->
<main id="main-content" data-project-id="<?= (int)$project['id'] ?>">
<div class="container py-4">
<div class="row g-4">

    <!-- ── Left: Tabs (col-lg-8) ────────────────────────────────────────── -->
    <div class="col-lg-8">

        <!-- Tab Navigation — purpose-pill-group style -->
        <div class="purpose-pill-group mb-4" id="projectTabs" role="tablist" aria-label="Project sections">
            <button class="purpose-pill active" id="tab-overview"
                    data-bs-toggle="tab" data-bs-target="#overview"
                    type="button" role="tab" aria-controls="overview" aria-selected="true">
                <i class="fa-solid fa-circle-info me-1 d-none d-sm-inline"></i>Overview
            </button>
            <button class="purpose-pill" id="tab-gallery"
                    data-bs-toggle="tab" data-bs-target="#gallery"
                    type="button" role="tab" aria-controls="gallery" aria-selected="false">
                <i class="fa-solid fa-images me-1 d-none d-sm-inline"></i>Gallery
                <?php if (!empty($gallery) || !empty($project['master_plan_url'])): ?>
                <span class="badge bg-secondary ms-1" style="font-size:.68rem;">
                    <?= count($gallery) + (!empty($project['master_plan_url']) ? 1 : 0) ?>
                </span>
                <?php endif; ?>
            </button>
            <button class="purpose-pill" id="tab-listings"
                    data-bs-toggle="tab" data-bs-target="#listings"
                    type="button" role="tab" aria-controls="listings" aria-selected="false">
                <i class="fa-solid fa-list me-1 d-none d-sm-inline"></i>Listings
                <?php if ($listingCount > 0): ?>
                <span class="badge bg-secondary ms-1" style="font-size:.68rem;"><?= $listingCount ?></span>
                <?php endif; ?>
            </button>
            <button class="purpose-pill" id="tab-location"
                    data-bs-toggle="tab" data-bs-target="#location"
                    type="button" role="tab" aria-controls="location" aria-selected="false">
                <i class="fa-solid fa-map-location-dot me-1 d-none d-sm-inline"></i>Location
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="projectTabContent">

            <!-- ══════════════════════════════════════════════════════════
                 TAB 1 — OVERVIEW
                 ════════════════════════════════════════════════════════ -->
            <section class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="tab-overview">

                <!-- Project Details Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header py-2" style="background:linear-gradient(135deg,var(--navy-800),var(--navy-600)); color:#fff;">
                        <h2 class="mb-0 fs-6"><i class="fa-solid fa-building me-2"></i>Project Details</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0" style="font-size:.88rem;">
                            <tbody>
                                <?php if (!empty($project['developer'])): ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">Developer</td>
                                    <td style="color:#222; font-weight:500;"><?= htmlspecialchars($project['developer']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">City</td>
                                    <td style="color:#222; font-weight:500;"><?= htmlspecialchars($project['city']) ?></td>
                                </tr>
                                <?php if (!empty($project['area_locality'])): ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">Location</td>
                                    <td style="color:#222; font-weight:500;"><?= htmlspecialchars($project['area_locality']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">Status</td>
                                    <td style="color:#222; font-weight:500;">
                                        <span class="prop-badge prop-badge-status-<?= htmlspecialchars($project['status'] ?? 'upcoming') ?>" style="font-size:.75rem;">
                                            <?= htmlspecialchars(getStatusLabel($project['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">NOC Status</td>
                                    <td style="color:#222; font-weight:500;" class="<?= $project['noc_status'] === 'approved' ? 'text-success' : 'text-warning' ?>">
                                        <i class="fa-solid fa-<?= $project['noc_status'] === 'approved' ? 'check-circle' : 'clock' ?> me-1"></i>
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['noc_status']))) ?>
                                    </td>
                                </tr>
                                <?php if (!empty($project['noc_ref'])): ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">NOC Reference</td>
                                    <td style="color:#222; font-weight:500;"><?= htmlspecialchars($project['noc_ref']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($project['authorised_since'])): ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">Authorised Since</td>
                                    <td style="color:#222; font-weight:500;"><?= htmlspecialchars(date('d M Y', strtotime($project['authorised_since']))) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($project['authorisation_ref'])): ?>
                                <tr>
                                    <td style="width:180px; color:#666; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; vertical-align:top; padding-top:.6rem;">Authorisation Ref.</td>
                                    <td style="color:#222; font-weight:500;"><?= htmlspecialchars($project['authorisation_ref']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Description -->
                <?php if (!empty($project['description'])): ?>
                <div class="mb-4">
                    <h2 class="content-heading">About <?= htmlspecialchars($project['name']) ?></h2>
                    <div style="line-height:1.8; color:#333;">
                        <?php
                        $desc = $project['description'];
                        if (strip_tags($desc) === $desc) {
                            $paras = array_filter(array_map('trim', explode("\n\n", $desc)));
                            foreach ($paras as $p) {
                                echo '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                            }
                        } else {
                            echo $desc;
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons (Brochure + Master Plan) -->
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <?php if (!empty($project['brochure_url'])): ?>
                    <a href="<?= htmlspecialchars($project['brochure_url']) ?>"
                       class="btn-navy" target="_blank" rel="noopener noreferrer" download>
                        <i class="fa-solid fa-file-pdf me-2"></i>Download Brochure PDF
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($project['master_plan_url'])): ?>
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#masterPlanModal">
                        <i class="fa-solid fa-map me-2"></i>View Master Plan
                    </button>
                    <?php endif; ?>

                    <a href="<?= htmlspecialchars(getWhatsAppLink(SITE_WHATSAPP, 'Hi, I\'m interested in ' . $project['name'] . '. Please share more details.')) ?>"
                       class="btn-whatsapp" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-whatsapp me-2"></i>WhatsApp for Details
                    </a>
                </div>

                <!-- Authorised Dealer Certificate Card -->
                <div class="card mb-4 shadow-sm" style="border:2px solid var(--gold); border-radius:10px; background:#fffdf5; position:relative; overflow:hidden;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0" style="font-size:2.5rem; color:var(--gold);">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-1" style="color:var(--navy-800);">
                                    Authorised Dealer Certificate
                                </h3>
                                <p class="text-muted small mb-2">
                                    Al-Riaz Associates is an Authorised Dealer for
                                    <strong><?= htmlspecialchars($project['name']) ?></strong>.
                                    All transactions are conducted with full transparency and official documentation.
                                </p>
                                <ul class="list-unstyled small mb-0">
                                    <?php if (!empty($project['developer'])): ?>
                                    <li class="mb-1">
                                        <i class="fa-solid fa-building me-1" style="color:var(--gold);"></i>
                                        <strong>Issued by:</strong> <?= htmlspecialchars($project['developer']) ?>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (!empty($project['authorisation_ref'])): ?>
                                    <li class="mb-1">
                                        <i class="fa-solid fa-hashtag me-1" style="color:var(--gold);"></i>
                                        <strong>Authorisation Ref:</strong> <?= htmlspecialchars($project['authorisation_ref']) ?>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (!empty($project['authorised_since'])): ?>
                                    <li class="mb-1">
                                        <i class="fa-solid fa-calendar-check me-1" style="color:var(--gold);"></i>
                                        <strong>Since:</strong> <?= htmlspecialchars(date('d M Y', strtotime($project['authorised_since']))) ?>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enquiry form (mobile only, shown below tabs) -->
                <div class="card shadow-sm mb-4 d-lg-none">
                    <div class="card-header py-2" style="background:linear-gradient(135deg,var(--navy-800),var(--navy-600)); color:#fff;">
                        <h3 class="mb-0 fs-6"><i class="fa-solid fa-paper-plane me-2"></i>Send Enquiry</h3>
                    </div>
                    <div class="card-body">
                        <form id="enquiryFormMobile" method="post" action="<?= $b ?>/api/v1/inquiries.php" novalidate>
                            <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                            <input type="hidden" name="source"     value="project">
                            <input type="text" name="website" style="display:none; visibility:hidden;"
                                   tabindex="-1" autocomplete="off" aria-hidden="true">

                            <div class="mb-2">
                                <input type="text" name="name" class="form-control form-control-sm"
                                       placeholder="Your Name *" required minlength="2" maxlength="120" autocomplete="name">
                            </div>
                            <div class="mb-2">
                                <input type="tel" name="phone" class="form-control form-control-sm"
                                       placeholder="Phone * (e.g. 0311 1234567)" required autocomplete="tel">
                                <div class="invalid-feedback">Please enter a valid Pakistani phone number</div>
                            </div>
                            <div class="mb-2">
                                <input type="email" name="email" class="form-control form-control-sm"
                                       placeholder="Email Address *" required maxlength="160" autocomplete="email">
                            </div>
                            <div class="mb-2">
                                <select name="preferred_contact_time" class="form-select form-select-sm">
                                    <option value="">Preferred Contact Time</option>
                                    <option value="Morning (9am - 12pm)">Morning (9am–12pm)</option>
                                    <option value="Afternoon (12pm - 4pm)">Afternoon (12pm–4pm)</option>
                                    <option value="Evening (5pm - 8pm)">Evening (5pm–8pm)</option>
                                    <option value="Anytime">Anytime</option>
                                    <option value="Business Hours">Business Hours</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <textarea name="message" class="form-control form-control-sm"
                                          rows="3" maxlength="1000"
                                          placeholder="Your message or requirements…"></textarea>
                            </div>
                            <button type="submit" class="btn-navy w-100" style="font-size:.85rem;">
                                <i class="fa-solid fa-paper-plane me-1"></i>Send Enquiry
                            </button>
                        </form>
                    </div>
                </div>

            </section><!-- /#overview -->

            <!-- ══════════════════════════════════════════════════════════
                 TAB 2 — GALLERY
                 ════════════════════════════════════════════════════════ -->
            <section class="tab-pane fade" id="gallery" role="tabpanel" aria-labelledby="tab-gallery">

                <?php if (empty($gallery) && empty($project['master_plan_url'])): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-images"></i>
                    <h4>Gallery Coming Soon</h4>
                    <p>Gallery images coming soon. <a href="<?= $b ?>/contact.php">Contact us</a> for the full project brochure.</p>
                </div>

                <?php else:
                    // Normalise gallery entries + optional master plan into a single slide list
                    $slides = [];
                    if (!empty($project['master_plan_url'])) {
                        $slides[] = [
                            'src'     => $project['master_plan_url'],
                            'alt'     => 'Master Plan — ' . $project['name'],
                            'contain' => true,
                            'label'   => 'Master Plan',
                        ];
                    }
                    foreach ($gallery as $gi => $galleryItem) {
                        $galSrc = is_string($galleryItem) ? $galleryItem : ($galleryItem['url'] ?? '');
                        if (!$galSrc) continue;
                        $slides[] = [
                            'src'     => $galSrc,
                            'alt'     => is_array($galleryItem) ? ($galleryItem['alt'] ?? $project['name'] . ' photo ' . ($gi + 1)) : $project['name'] . ' photo ' . ($gi + 1),
                            'contain' => false,
                            'label'   => null,
                        ];
                    }
                ?>
                <div class="gallery-wrap" style="margin:0; padding:0; max-width:none;">
                    <div id="projectCarousel" class="carousel slide" data-bs-ride="false" aria-label="Project gallery">
                        <div class="carousel-inner">
                            <?php foreach ($slides as $si => $slide): ?>
                            <div class="carousel-item <?= $si === 0 ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($slide['src']) ?>"
                                     class="gallery-carousel-img d-block w-100"
                                     alt="<?= htmlspecialchars($slide['alt']) ?>"
                                     loading="<?= $si === 0 ? 'eager' : 'lazy' ?>"
                                     onerror="this.src='https://picsum.photos/id/<?= 120 + $si * 5 ?>/1200/800'"
                                     <?= $slide['contain'] ? 'style="object-fit:contain;background:#f5f5f5;"' : '' ?>>
                                <?php if (!empty($slide['label'])): ?>
                                <span class="carousel-slide-label">
                                    <i class="fa-solid fa-map me-1"></i><?= htmlspecialchars($slide['label']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($slides) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#projectCarousel" data-bs-slide="prev" aria-label="Previous photo">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#projectCarousel" data-bs-slide="next" aria-label="Next photo">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                        <div class="photo-counter" id="projectPhotoCounter" aria-live="polite">1 / <?= count($slides) ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($slides) > 1): ?>
                    <div class="gallery-thumb-strip" id="projectThumbStrip" role="list" aria-label="Photo thumbnails">
                        <?php foreach ($slides as $si => $slide): ?>
                        <img src="<?= htmlspecialchars($slide['src']) ?>"
                             alt="<?= htmlspecialchars($slide['alt']) ?>"
                             class="gallery-thumb-item <?= $si === 0 ? 'active' : '' ?>"
                             data-bs-target="#projectCarousel"
                             data-bs-slide-to="<?= $si ?>"
                             onerror="this.src='https://picsum.photos/id/<?= 120 + $si * 5 ?>/180/120'">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <script>
                (function () {
                    var c = document.getElementById('projectCarousel');
                    if (!c) return;
                    var thumbs  = c.parentNode.querySelectorAll('.gallery-thumb-item');
                    var counter = document.getElementById('projectPhotoCounter');
                    var total   = c.querySelectorAll('.carousel-item').length;

                    c.addEventListener('slid.bs.carousel', function (e) {
                        thumbs.forEach(function (t, i) { t.classList.toggle('active', i === e.to); });
                        if (counter) counter.textContent = (e.to + 1) + ' / ' + total;
                        var active = thumbs[e.to];
                        if (active && active.parentNode.scrollTo) {
                            active.parentNode.scrollTo({
                                left: active.offsetLeft - (active.parentNode.clientWidth / 2) + (active.clientWidth / 2),
                                behavior: 'smooth'
                            });
                        }
                    });
                })();
                </script>
                <?php endif; /* end outer if(empty)/else */ ?>

            </section><!-- /#gallery -->

            <!-- ══════════════════════════════════════════════════════════
                 TAB 3 — LISTINGS
                 ════════════════════════════════════════════════════════ -->
            <section class="tab-pane fade" id="listings" role="tabpanel" aria-labelledby="tab-listings">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <h2 class="content-heading mb-0">
                        <?= $listingCount > 0
                            ? number_format($listingCount) . ' ' . ($listingCount === 1 ? 'Property' : 'Properties') . ' Available'
                            : 'Properties in ' . htmlspecialchars($project['name'])
                        ?>
                    </h2>

                    <?php if ($listingCount > 0): ?>
                    <div class="purpose-pill-group">
                        <span class="purpose-pill active" data-filter="all" role="button" tabindex="0">All</span>
                        <span class="purpose-pill" data-filter="sale" role="button" tabindex="0">For Sale</span>
                        <span class="purpose-pill" data-filter="rent" role="button" tabindex="0">For Rent</span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($projectProperties)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-house"></i>
                    <h4>No Listings Currently Available</h4>
                    <p>Contact us to be notified when new units become available in <?= htmlspecialchars($project['name']) ?>.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap mt-3">
                        <a href="<?= htmlspecialchars(getWhatsAppLink(SITE_WHATSAPP, 'Hi, I\'m interested in ' . $project['name'] . '. Please notify me of available units.')) ?>"
                           class="btn-whatsapp" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp me-2"></i>WhatsApp for Availability
                        </a>
                        <a href="<?= $b ?>/contact.php" class="btn-navy">
                            <i class="fa-solid fa-envelope me-2"></i>Send Message
                        </a>
                    </div>
                </div>

                <?php else: ?>
                <div class="row g-3" id="projectListingsGrid">
                    <?php foreach ($projectProperties as $i => $prop): ?>
                    <div class="col-sm-6 col-md-4 project-listing-card"
                         data-purpose="<?= htmlspecialchars($prop['purpose']) ?>">
                        <?php include __DIR__ . '/includes/_prop_card.php'; ?>
                    </div>
                    <?php endforeach; ?>
                </div><!-- /#projectListingsGrid -->
                <?php endif; ?>

            </section><!-- /#listings -->

            <!-- ══════════════════════════════════════════════════════════
                 TAB 4 — LOCATION
                 ════════════════════════════════════════════════════════ -->
            <section class="tab-pane fade" id="location" role="tabpanel" aria-labelledby="tab-location">

                <!-- Address -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h2 class="content-heading">Project Address</h2>
                        <p class="mb-1">
                            <i class="fa-solid fa-location-dot me-2" style="color:var(--gold);"></i>
                            <strong>
                                <?= htmlspecialchars(trim(($project['area_locality'] ? $project['area_locality'] . ', ' : '') . $project['city'] . ', Pakistan')) ?>
                            </strong>
                        </p>
                    </div>
                </div>

                <!-- Map -->
                <div class="mb-4">
                    <h2 class="content-heading">Map</h2>
                    <div style="border-radius:8px; overflow:hidden;">
                        <iframe
                            src="https://maps.google.com/maps?q=<?= urlencode(($project['area_locality'] ?? '') . ', ' . $project['city'] . ', Pakistan') ?>&output=embed"
                            width="100%" height="380" frameborder="0" style="border:0;" loading="lazy"
                            allowfullscreen title="Project location map"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>

                <!-- Nearby Landmarks -->
                <div class="mb-4">
                    <h2 class="content-heading">Nearby Areas &amp; Landmarks</h2>
                    <ul class="list-unstyled">
                        <li class="py-2 border-bottom" style="font-size:.9rem;"><i class="fa-solid fa-road me-2" style="color:var(--gold); width:20px;"></i>Major Ring Road Access</li>
                        <li class="py-2 border-bottom" style="font-size:.9rem;"><i class="fa-solid fa-graduation-cap me-2" style="color:var(--gold); width:20px;"></i>Top Schools &amp; Universities Nearby</li>
                        <li class="py-2 border-bottom" style="font-size:.9rem;"><i class="fa-solid fa-hospital me-2" style="color:var(--gold); width:20px;"></i>Hospitals &amp; Medical Centres</li>
                        <li class="py-2 border-bottom" style="font-size:.9rem;"><i class="fa-solid fa-bag-shopping me-2" style="color:var(--gold); width:20px;"></i>Shopping Malls &amp; Commercial Areas</li>
                        <li class="py-2 border-bottom" style="font-size:.9rem;"><i class="fa-solid fa-mosque me-2" style="color:var(--gold); width:20px;"></i>Mosques &amp; Community Centres</li>
                        <li class="py-2 border-bottom" style="font-size:.9rem;"><i class="fa-solid fa-bus me-2" style="color:var(--gold); width:20px;"></i>Public Transport Links</li>
                        <li class="py-2" style="font-size:.9rem;"><i class="fa-solid fa-tree me-2" style="color:var(--gold); width:20px;"></i>Parks &amp; Green Spaces</li>
                    </ul>
                    <p class="text-muted small">
                        <i class="fa-solid fa-circle-info me-1" style="color:var(--gold);"></i>
                        Exact landmark distances available on request.
                        <a href="<?= htmlspecialchars(getWhatsAppLink(SITE_WHATSAPP, 'Hi, I need location details for ' . $project['name'] . '. Can you help?')) ?>"
                           target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color:var(--gold);">
                            Ask our team
                        </a>.
                    </p>
                </div>

            </section><!-- /#location -->

        </div><!-- /.tab-content -->
    </div><!-- /.col-lg-8 -->

    <!-- ── Right: Sticky Sidebar (col-lg-4) ──────────────────────────────── -->
    <aside class="col-lg-4" aria-label="Project info and enquiry">
        <div class="project-sidebar" style="position:sticky; top:80px;">

            <!-- Quick Facts ─────────────────────────────────────────────── -->
            <div class="card mb-3 shadow-sm">
                <div class="card-header py-2" style="background:var(--navy-50); border-bottom:1px solid var(--navy-100);">
                    <h3 class="h6 fw-bold mb-0" style="color:var(--navy-800); letter-spacing:.04em; text-transform:uppercase;">
                        <i class="fa-solid fa-circle-info me-1" style="color:var(--gold);"></i>
                        Quick Facts
                    </h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-unstyled mb-0">
                        <?php if (!empty($project['developer'])): ?>
                        <li class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom" style="font-size:.88rem;">
                            <span class="text-muted" style="min-width:110px;">Developer</span>
                            <span class="fw-600 text-end" style="color:var(--navy-800);"><?= htmlspecialchars($project['developer']) ?></span>
                        </li>
                        <?php endif; ?>
                        <li class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom" style="font-size:.88rem;">
                            <span class="text-muted" style="min-width:110px;">Location</span>
                            <span class="fw-600 text-end" style="color:var(--navy-800);">
                                <?= htmlspecialchars(trim(($project['area_locality'] ? $project['area_locality'] . ', ' : '') . $project['city'])) ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom" style="font-size:.88rem;">
                            <span class="text-muted" style="min-width:110px;">Status</span>
                            <span class="fw-600 text-end">
                                <span class="prop-badge prop-badge-status-<?= htmlspecialchars($project['status'] ?? 'upcoming') ?>" style="font-size:.7rem;">
                                    <?= htmlspecialchars(getStatusLabel($project['status'])) ?>
                                </span>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom" style="font-size:.88rem;">
                            <span class="text-muted" style="min-width:110px;">NOC</span>
                            <span class="fw-600 text-end <?= $project['noc_status'] === 'approved' ? 'text-success' : 'text-warning' ?>">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['noc_status']))) ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom" style="font-size:.88rem;">
                            <span class="text-muted" style="min-width:110px;">Listings</span>
                            <span class="fw-600 text-end" style="color:var(--navy-800);">
                                <?= $listingCount > 0 ? number_format($listingCount) . ' Available' : 'Contact Us' ?>
                            </span>
                        </li>
                        <?php if (!empty($project['authorised_since'])): ?>
                        <li class="d-flex justify-content-between align-items-start px-3 py-2" style="font-size:.88rem;">
                            <span class="text-muted" style="min-width:110px;">Auth. Since</span>
                            <span class="fw-600 text-end" style="color:var(--navy-800);"><?= htmlspecialchars(date('Y', strtotime($project['authorised_since']))) ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Agent / Contact Card ─────────────────────────────────── -->
            <div class="card mb-3 shadow-sm">
                <div class="card-body text-center">
                    <img src="https://picsum.photos/id/64/80/80"
                         alt="Al-Riaz Associates Agent"
                         class="rounded-circle mb-2"
                         width="80" height="80" style="object-fit:cover;">
                    <h4 class="h5 mb-0 fw-bold">Al-Riaz Associates</h4>
                    <p class="text-muted mb-3 small">Authorised Sales Team</p>

                    <a href="tel:<?= SITE_PHONE ?>"
                       class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars(SITE_PHONE) ?>
                    </a>

                    <a href="<?= htmlspecialchars(getWhatsAppLink(SITE_WHATSAPP, 'Hi, I\'m interested in ' . $project['name'] . '. Please share more details.')) ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn-whatsapp w-100" style="display:block; text-align:center;">
                        <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp Us
                    </a>
                </div>
            </div>

            <!-- Enquiry Form Sidebar (desktop) ──────────────────────── -->
            <div class="enquiry-card d-none d-lg-block">
                <div class="enquiry-card-header">
                    <h3 class="mb-0 fs-6"><i class="fa-solid fa-paper-plane me-2"></i>Send Enquiry</h3>
                </div>
                <div class="enquiry-card-body">

                    <form id="enquiryForm" method="post" action="<?= $b ?>/api/v1/inquiries.php" novalidate>
                        <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                        <input type="hidden" name="source"     value="project">
                        <!-- Honeypot -->
                        <input type="text" name="website"
                               style="display:none; visibility:hidden;"
                               tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="mb-2">
                            <input type="text" name="name" class="form-control form-control-sm"
                                   placeholder="Your Name *" required minlength="2" maxlength="120"
                                   autocomplete="name">
                        </div>

                        <div class="mb-2">
                            <input type="tel" name="phone" class="form-control form-control-sm"
                                   placeholder="Phone * (e.g. 0311 1234567)" required
                                   autocomplete="tel">
                            <div class="invalid-feedback">
                                Please enter a valid Pakistani phone number
                            </div>
                        </div>

                        <div class="mb-2">
                            <input type="email" name="email" class="form-control form-control-sm"
                                   placeholder="Email Address *" required maxlength="160"
                                   autocomplete="email">
                        </div>

                        <div class="mb-2">
                            <select name="preferred_contact_time" class="form-select form-select-sm">
                                <option value="">Preferred Contact Time</option>
                                <option value="Morning (9am - 12pm)">Morning (9am–12pm)</option>
                                <option value="Afternoon (12pm - 4pm)">Afternoon (12pm–4pm)</option>
                                <option value="Evening (5pm - 8pm)">Evening (5pm–8pm)</option>
                                <option value="Anytime">Anytime</option>
                                <option value="Business Hours">Business Hours</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <textarea name="message" class="form-control form-control-sm"
                                      rows="3" maxlength="1000"
                                      placeholder="Your message or requirements…"></textarea>
                        </div>

                        <button type="submit" class="btn-navy w-100" style="font-size:.85rem;" id="submitEnquiry">
                            <span class="btn-text">
                                <i class="fa-solid fa-paper-plane me-1"></i>Send Enquiry
                            </span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-label="Sending…"></span>
                        </button>
                    </form>

                    <!-- Success state -->
                    <div class="enquiry-success d-none text-center py-3">
                        <i class="fa-solid fa-circle-check text-success fa-3x mb-2"></i>
                        <h4 class="h5 fw-bold text-success">Enquiry Sent!</h4>
                        <p class="small text-muted">We'll contact you within 2–4 working hours.</p>
                        <a href="<?= htmlspecialchars(getWhatsAppLink(SITE_WHATSAPP, 'Hi, I just sent an enquiry for ' . $project['name'] . '. Looking forward to your response.')) ?>"
                           class="btn-whatsapp mt-1" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp for Faster Response
                        </a>
                    </div>

                </div>
            </div>

        </div><!-- /.project-sidebar -->
    </aside>

</div><!-- /.row -->
</div><!-- /.container -->
</main>

<!-- ── Master Plan Modal ─────────────────────────────────────────────────── -->
<?php if (!empty($project['master_plan_url'])): ?>
<div class="modal fade" id="masterPlanModal" tabindex="-1"
     aria-labelledby="masterPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:linear-gradient(135deg,var(--navy-800),var(--navy-600)); color:#fff;">
                <h5 class="modal-title" id="masterPlanModalLabel">
                    <i class="fa-solid fa-map me-2"></i>Master Plan — <?= htmlspecialchars($project['name']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" style="text-align:center;">
                <img src="<?= htmlspecialchars(mediaUrl($project['master_plan_url'])) ?>"
                     alt="Master Plan of <?= htmlspecialchars($project['name']) ?>"
                     class="img-fluid"
                     style="max-height:80vh; object-fit:contain;"
                     loading="lazy">
            </div>
            <div class="modal-footer py-2">
                <a href="<?= htmlspecialchars(mediaUrl($project['master_plan_url'])) ?>"
                   download target="_blank" rel="noopener noreferrer"
                   class="btn-gold">
                    <i class="fa-solid fa-download me-1"></i>Download Master Plan
                </a>
                <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Gallery Lightbox Modal ────────────────────────────────────────────── -->
<div class="modal fade" id="projectLightbox" tabindex="-1"
     aria-label="Gallery lightbox" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:90vw;">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0 py-2">
                <span class="text-white small" id="projLbCaption"></span>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-1 position-relative">
                <img id="projLbImage" src="" alt="Gallery photo"
                     class="img-fluid d-block mx-auto"
                     style="max-height:80vh; object-fit:contain;">
                <button id="projLbPrev"
                        class="btn btn-dark position-absolute start-0 top-50 translate-middle-y ms-2"
                        aria-label="Previous photo">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button id="projLbNext"
                        class="btn btn-dark position-absolute end-0 top-50 translate-middle-y me-2"
                        aria-label="Next photo">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="<?= $b ?>/assets/js/detail.js"></script>

<script>
/* ── Project Gallery Lightbox (inline, depends on detail.js jQuery) ──────── */
(function ($) {
    'use strict';

    var projImages = [];
    var projIdx    = 0;

    // Collect all gallery items
    $('.gallery-item').each(function () {
        projImages.push({
            src    : $(this).data('lb-src'),
            caption: $(this).data('lb-caption') || ''
        });
    });

    function projLbOpen(idx) {
        projIdx = ((idx % projImages.length) + projImages.length) % projImages.length;
        var item = projImages[projIdx];
        $('#projLbImage').attr({ src: item.src, alt: item.caption });
        $('#projLbCaption').text(item.caption + ' (' + (projIdx + 1) + '/' + projImages.length + ')');
    }

    // Open on click
    $(document).on('click keydown', '.gallery-item', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter') return;
        var clickedSrc = $(this).data('lb-src');
        var idx = projImages.findIndex(function (img) { return img.src === clickedSrc; });
        projLbOpen(idx >= 0 ? idx : 0);
        new bootstrap.Modal(document.getElementById('projectLightbox')).show();
    });

    $('#projLbPrev').on('click', function () { projLbOpen(projIdx - 1); });
    $('#projLbNext').on('click', function () { projLbOpen(projIdx + 1); });

    // Keyboard nav in lightbox
    $(document).on('keydown', function (e) {
        if (!$('#projectLightbox').hasClass('show')) return;
        if (e.key === 'ArrowLeft')  projLbOpen(projIdx - 1);
        if (e.key === 'ArrowRight') projLbOpen(projIdx + 1);
    });

    // Listings filter pills
    $(document).on('click', '#listings .purpose-pill[data-filter]', function () {
        var filter = $(this).data('filter');
        $('#listings .purpose-pill[data-filter]').removeClass('active');
        $(this).addClass('active');
        if (filter === 'all') {
            $('.project-listing-card').show();
        } else {
            $('.project-listing-card').each(function () {
                $(this).toggle($(this).data('purpose') === filter);
            });
        }
    });

}(jQuery));
</script>
