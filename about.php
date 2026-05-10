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

// Approved client reviews — featured first, then most recent.
$clientReviews   = [];
$reviewAvg       = 0.0;
$reviewCount     = 0;
try {
    $clientReviews = $db->query(
        "SELECT id, name, rating, title, body, created_at
         FROM reviews
         WHERE status = 'approved'
         ORDER BY is_featured DESC, created_at DESC
         LIMIT 12"
    )->fetchAll();

    $stat = $db->query("SELECT COUNT(*) AS c, AVG(rating) AS avg_rating FROM reviews WHERE status='approved'")->fetch();
    $reviewCount = (int)($stat['c'] ?? 0);
    $reviewAvg   = $reviewCount > 0 ? (float)$stat['avg_rating'] : 0.0;
} catch (Exception $e) { /* table may not exist yet — section degrades gracefully */ }

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
<?php
    // Pull editable copy from Settings → About Page (with sensible defaults
    // that preserve the original hardcoded text/badge if nothing is set).
    $storySettings = function_exists('getSettings') ? getSettings() : [];
    $storyLabel    = trim((string)($storySettings['about_story_label']   ?? '')) ?: 'Our Story';
    $storyHeading  = trim((string)($storySettings['about_story_heading'] ?? '')) ?: 'Built on Trust & Transparency';
    $storyBody     = (string)($storySettings['about_story_body'] ?? '');
    $storyImageRaw = trim((string)($storySettings['about_story_image']  ?? ''));
    $storyImage    = $storyImageRaw !== ''
        ? mediaUrl($storyImageRaw)
        : 'https://picsum.photos/id/1067/600/420';
    $storyBadgeVal = trim((string)($storySettings['about_story_badge_value'] ?? ''));
    $storyBadgeLbl = trim((string)($storySettings['about_story_badge_label'] ?? 'In Real Estate'));

    /**
     * Render the body: split on blank lines, escape, and convert **text** to <strong>.
     */
    $storyBodyHtml = '';
    foreach (preg_split('/\R\s*\R/', $storyBody) as $para) {
        $para = trim($para);
        if ($para === '') continue;
        $esc = htmlspecialchars($para, ENT_QUOTES, 'UTF-8');
        $esc = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $esc);
        $esc = nl2br($esc);
        $storyBodyHtml .= '<p class="text-muted lh-lg">' . $esc . '</p>';
    }
?>
<section class="section-pad" style="background:#fff;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <span class="text-uppercase fw-semibold small" style="color:var(--gold); letter-spacing:1.5px;"><?= htmlspecialchars($storyLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <h2 class="content-heading mt-2 mb-3" style="text-align:left;"><?= htmlspecialchars($storyHeading, ENT_QUOTES, 'UTF-8') ?></h2>
                <?= $storyBodyHtml ?>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="<?= $b ?>/contact.php" class="btn-gold">
                        <i class="fa-solid fa-phone me-2"></i>Get in Touch
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="position-relative">
                    <img src="<?= htmlspecialchars($storyImage, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($storyHeading, ENT_QUOTES, 'UTF-8') ?>"
                         class="img-fluid rounded-3 shadow"
                         loading="lazy">
                    <?php if ($storyBadgeVal !== ''): ?>
                    <!-- Floating stat card -->
                    <div class="position-absolute d-none d-md-flex align-items-center gap-3 bg-white rounded-3 shadow-sm p-3"
                         style="bottom:-20px; left:-20px; min-width:200px; border-left:4px solid var(--gold);">
                        <i class="fas fa-award fa-2x" style="color:var(--gold);"></i>
                        <div>
                            <div class="fw-bold" style="color:var(--navy-700); font-size:1.3rem;">
                                <?= htmlspecialchars($storyBadgeVal, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if ($storyBadgeLbl !== ''): ?>
                            <div class="text-muted small"><?= htmlspecialchars($storyBadgeLbl, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     KEY STATS
     ============================================================ -->
<?php
    // Editable from Settings → About Page → Key Stats. If a value is left
    // blank, fall back to the auto-derived/seed defaults below so the
    // section stays populated.
    $statFallbacks = [$totalListings, $totalProjects, $happyClients, $yearsActive];
    $statLabelsDefault = ['Properties Listed', 'Active Projects', 'Happy Clients', 'Years Active'];
    $aboutStats = $storySettings['about_stats'] ?? [];
?>
<section class="stats-section">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php for ($i = 0; $i < 4; $i++):
                $row = $aboutStats[$i] ?? [];
                $rawVal = trim((string)($row['value'] ?? ''));
                $label  = trim((string)($row['label'] ?? '')) ?: $statLabelsDefault[$i];
                $value  = $rawVal !== '' ? $rawVal : (string)$statFallbacks[$i];
                // Pull the leading number out for the count-up animation; fall
                // back to the full string if it isn't a clean number ("5+ Yrs").
                preg_match('/^\d+/', $value, $m);
                $countTo = $m[0] ?? $value;
                $delay   = $i === 0 ? '' : ' style="--delay:0.' . $i . 's"';
            ?>
            <div class="col-6 col-md-3">
                <div class="stat-card reveal"<?= $delay ?>>
                    <div class="stat-number" data-count-to="<?= htmlspecialchars($countTo, ENT_QUOTES, 'UTF-8') ?>">0<span class="suffix">+</span></div>
                    <div class="stat-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     MISSION & VISION
     ============================================================ -->
<section class="mv-section">
    <div class="mv-decor mv-decor-1" aria-hidden="true"></div>
    <div class="mv-decor mv-decor-2" aria-hidden="true"></div>

    <?php
        // Mission/Vision section header — editable from Settings → About Page.
        $mvLabel    = trim((string)($storySettings['about_mv_label']    ?? '')) ?: 'Purpose';
        $mvTitle    = trim((string)($storySettings['about_mv_title']    ?? '')) ?: 'Our Mission & Vision';
        $mvSubtitle = trim((string)($storySettings['about_mv_subtitle'] ?? '')) ?: "Why we do what we do — and where we're headed next.";

        // Mission and Vision cards.
        $missionCard = $storySettings['about_mission'] ?? [];
        $visionCard  = $storySettings['about_vision']  ?? [];
        $cardDefaults = [
            'mission' => [
                'icon'    => 'fa-bullseye',
                'tag'     => '01 — Mission',
                'title'   => 'Empowering Pakistanis to make the best property decisions.',
                'body'    => 'Transparent pricing, verified listings, and honest advice — the way real estate should have always been. We turn paperwork, site visits, and payment plans into a process you can actually understand.',
                'bullets' => [
                    'Verified ownership & NOC on every listing',
                    'Clear, up-front brokerage disclosure',
                    'End-to-end support, from search to possession',
                ],
            ],
            'vision' => [
                'icon'    => 'fa-eye',
                'tag'     => '02 — Vision',
                'title'   => "Becoming Pakistan's most trusted real estate partner.",
                'body'    => 'We want "Al-Riaz" to mean the same thing in Islamabad as it does in Karachi — integrity, expertise, and client-first service at every step. Built on relationships that outlast a single transaction.',
                'bullets' => [
                    "Authorised dealer for Pakistan's top developments",
                    'Data-driven investment guidance',
                    'A team that picks up the phone',
                ],
            ],
        ];
        $missionCard = $missionCard + $cardDefaults['mission'];
        $visionCard  = $visionCard  + $cardDefaults['vision'];
        if (empty($missionCard['bullets']) || !is_array($missionCard['bullets'])) $missionCard['bullets'] = $cardDefaults['mission']['bullets'];
        if (empty($visionCard['bullets'])  || !is_array($visionCard['bullets']))  $visionCard['bullets']  = $cardDefaults['vision']['bullets'];
    ?>
    <div class="container">
        <div class="section-header center reveal">
            <div class="section-label"><?= htmlspecialchars($mvLabel, ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="section-title"><?= htmlspecialchars($mvTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="section-subtitle"><?= htmlspecialchars($mvSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="row g-4 mv-grid">
            <!-- Mission -->
            <div class="col-12 col-lg-6">
                <article class="mv-card reveal reveal-left">
                    <div class="mv-card-head">
                        <div class="mv-icon">
                            <i class="fa-solid <?= htmlspecialchars($missionCard['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>
                        <div class="mv-tag"><?= htmlspecialchars($missionCard['tag'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <h3 class="mv-title"><?= htmlspecialchars($missionCard['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="mv-text"><?= nl2br(htmlspecialchars($missionCard['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <ul class="mv-bullets">
                        <?php foreach ($missionCard['bullets'] as $bullet): if (trim($bullet) === '') continue; ?>
                        <li><i class="fa-solid fa-check"></i> <?= htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </div>

            <!-- Vision -->
            <div class="col-12 col-lg-6">
                <article class="mv-card mv-card-alt reveal reveal-right">
                    <div class="mv-card-head">
                        <div class="mv-icon mv-icon-gold">
                            <i class="fa-solid <?= htmlspecialchars($visionCard['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>
                        <div class="mv-tag"><?= htmlspecialchars($visionCard['tag'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <h3 class="mv-title"><?= htmlspecialchars($visionCard['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="mv-text"><?= nl2br(htmlspecialchars($visionCard['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <ul class="mv-bullets">
                        <?php foreach ($visionCard['bullets'] as $bullet): if (trim($bullet) === '') continue; ?>
                        <li><i class="fa-solid fa-check"></i> <?= htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </div>
        </div>

        <!-- Core Values -->
        <div class="mv-values reveal-stagger">
            <?php
            $valueDefaults = [
                ['fa-handshake',    'Integrity',    'We never compromise on honesty in our dealings.'],
                ['fa-check-circle', 'Transparency', 'Clear pricing, no hidden costs or surprises.'],
                ['fa-user-tie',     'Expertise',    'Seasoned consultants with deep market knowledge.'],
                ['fa-headset',      'Client First', 'Your satisfaction is our top priority, always.'],
            ];
            $aboutValues = $storySettings['about_values'] ?? [];
            for ($i = 0; $i < 4; $i++):
                $vRow = $aboutValues[$i] ?? [];
                $vIcon  = trim((string)($vRow['icon']  ?? '')) ?: $valueDefaults[$i][0];
                $vTitle = trim((string)($vRow['title'] ?? '')) ?: $valueDefaults[$i][1];
                $vDesc  = trim((string)($vRow['desc']  ?? '')) ?: $valueDefaults[$i][2];
            ?>
            <div class="mv-value">
                <div class="mv-value-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
                <div class="mv-value-icon"><i class="fa-solid <?= htmlspecialchars($vIcon, ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div class="mv-value-title"><?= htmlspecialchars($vTitle, ENT_QUOTES, 'UTF-8') ?></div>
                <p class="mv-value-desc"><?= htmlspecialchars($vDesc, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     CEO MESSAGE — editable from Settings → About Page → CEO Message
     ============================================================ -->
<?php
    $ceoShow    = ($storySettings['about_ceo_show'] ?? '1') === '1';
    $ceoLabel   = trim((string)($storySettings['about_ceo_label']   ?? '')) ?: 'A Message from Our CEO';
    $ceoHeading = trim((string)($storySettings['about_ceo_heading'] ?? ''));
    $ceoMsg     = (string)($storySettings['about_ceo_message'] ?? '');
    $ceoImgRaw  = trim((string)($storySettings['about_ceo_image']  ?? ''));
    $ceoImage   = $ceoImgRaw !== '' ? mediaUrl($ceoImgRaw) : '';
    $ceoName    = trim((string)($storySettings['about_ceo_name']  ?? ''));
    $ceoTitle   = trim((string)($storySettings['about_ceo_title'] ?? ''));

    // Render the message body the same way as the story body: paragraphs by
    // blank lines, with **bold** support (HTML-escaped first, so it's safe).
    $ceoMsgHtml = '';
    foreach (preg_split('/\R\s*\R/', $ceoMsg) as $para) {
        $para = trim($para);
        if ($para === '') continue;
        $esc = htmlspecialchars($para, ENT_QUOTES, 'UTF-8');
        $esc = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $esc);
        $esc = nl2br($esc);
        $ceoMsgHtml .= '<p class="text-muted lh-lg mb-3">' . $esc . '</p>';
    }
?>
<?php if ($ceoShow && ($ceoMsgHtml !== '' || $ceoName !== '' || $ceoImage !== '')): ?>
<section style="padding:5rem 0; background:#fff;">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Photo + signature card -->
            <div class="col-12 col-lg-5 text-center text-lg-start">
                <div class="position-relative d-inline-block">
                    <?php if ($ceoImage !== ''): ?>
                        <img src="<?= htmlspecialchars($ceoImage, ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($ceoName ?: 'CEO', ENT_QUOTES, 'UTF-8') ?>"
                             class="rounded-3 shadow"
                             style="width:100%;max-width:380px;aspect-ratio:1/1;object-fit:cover;border:1px solid rgba(10,22,40,0.08);"
                             loading="lazy">
                    <?php else: ?>
                        <div class="rounded-3 shadow d-flex align-items-center justify-content-center"
                             style="width:100%;max-width:380px;aspect-ratio:1/1;background:var(--navy-50);border:1px dashed rgba(10,22,40,0.12);">
                            <i class="fa-solid fa-user-tie" style="font-size:5rem;color:var(--navy-200);"></i>
                        </div>
                    <?php endif; ?>
                    <!-- Decorative quote badge -->
                    <div class="position-absolute d-none d-md-flex align-items-center justify-content-center"
                         style="bottom:-18px;right:-18px;width:64px;height:64px;border-radius:50%;background:var(--gold);box-shadow:0 6px 18px rgba(245,179,1,0.4);">
                        <i class="fa-solid fa-quote-right" style="color:#0A1628;font-size:1.5rem;"></i>
                    </div>
                </div>
                <?php if ($ceoName !== '' || $ceoTitle !== ''): ?>
                <div class="mt-4">
                    <?php if ($ceoName !== ''): ?>
                    <div class="fw-bold" style="color:var(--navy-900);font-size:1.2rem;">
                        <?= htmlspecialchars($ceoName, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($ceoTitle !== ''): ?>
                    <div class="text-uppercase fw-semibold small" style="color:var(--gold);letter-spacing:1.2px;">
                        <?= htmlspecialchars($ceoTitle, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Message body -->
            <div class="col-12 col-lg-7">
                <span class="text-uppercase fw-semibold small" style="color:var(--gold);letter-spacing:1.5px;">
                    <?= htmlspecialchars($ceoLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if ($ceoHeading !== ''): ?>
                <h2 class="content-heading mt-2 mb-4" style="text-align:left;">
                    <?= htmlspecialchars($ceoHeading, ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <?php endif; ?>
                <?= $ceoMsgHtml ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     MEET THE TEAM
     ============================================================ -->
<section style="padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-5">Meet Our Team</h2>
        <div class="row g-4">
            <?php foreach ($agents as $agent):
                $roleLabel = ucwords(str_replace('_', ' ', $agent['role'] ?? 'agent'));
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="team-card reveal">
                    <img src="<?= htmlspecialchars(userAvatarUrl($agent['avatar_url'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8') ?>"
                         class="team-card-img"
                         onerror="this.onerror=null;this.src='<?= htmlspecialchars(defaultAvatarUrl(), ENT_QUOTES, 'UTF-8') ?>';">
                    <div class="team-card-body">
                        <h4><?= htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <p><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($agent['email'])): ?>
                        <div class="text-muted small mb-2 text-truncate" title="<?= htmlspecialchars($agent['email'], ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($agent['email'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php endif; ?>
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
     CLIENT REVIEWS
     ============================================================ -->
<section id="reviews" style="background:#fff; padding:5rem 0;">
    <div class="container">
        <h2 class="content-heading text-center mb-2">What Our Clients Say</h2>
        <p class="text-center text-muted mb-4">
            <?php if ($reviewCount > 0): ?>
                <strong><?= number_format($reviewAvg, 1) ?>/5</strong>
                from <?= (int)$reviewCount ?> verified review<?= $reviewCount === 1 ? '' : 's' ?>
            <?php else: ?>
                Be the first to share your experience with Al-Riaz Associates
            <?php endif; ?>
        </p>

        <style>
          .review-card {
            background: var(--navy-50, #f6f9fc);
            border: 1px solid #e6ebf2;
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: .8rem;
            transition: transform .15s ease, box-shadow .15s ease;
          }
          .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(10,22,40,.08);
          }
          .review-stars { color: #F5B301; font-size: 1rem; letter-spacing: 1px; }
          .review-title { font-weight: 700; color: var(--navy-700, #0A1628); font-size: 1rem; }
          .review-body  { color: #4a5568; font-size: .95rem; line-height: 1.55; flex: 1; white-space: pre-line; }
          .review-meta  { display:flex; align-items:center; gap:.6rem; font-size:.85rem; color:#6c757d; }
          .review-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--sidebar-bg, #0A1628); color: #F5B301;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; flex-shrink:0;
          }

          .review-form-card {
            background:#fff; border:1px solid #e6ebf2; border-radius:14px;
            padding:2rem; box-shadow:0 4px 20px rgba(10,22,40,.04);
          }
          .review-form-card h3 { font-size:1.25rem; color:var(--navy-700,#0A1628); margin-bottom:1rem; }
          .star-rating { display:inline-flex; flex-direction:row-reverse; gap:.25rem; }
          .star-rating input { display:none; }
          .star-rating label {
            cursor:pointer; font-size:1.6rem; color:#cbd5e0; transition: color .1s ease;
          }
          .star-rating label:hover,
          .star-rating label:hover ~ label,
          .star-rating input:checked ~ label { color:#F5B301; }
          .review-success {
            display:none; padding:1.25rem; background:#d1e7dd; border-radius:10px;
            color:#0a3622; text-align:center;
          }
          .review-success.is-visible { display:block; }
        </style>

        <?php if (!empty($clientReviews)): ?>
        <div class="row g-3 mb-5">
            <?php foreach ($clientReviews as $rv):
                $initial = mb_strtoupper(mb_substr($rv['name'] ?? '?', 0, 1));
                $stars   = max(1, min(5, (int)$rv['rating']));
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="review-card">
                    <div class="review-stars" aria-label="<?= $stars ?> out of 5 stars">
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if (!empty($rv['title'])): ?>
                    <div class="review-title"><?= htmlspecialchars($rv['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="review-body"><?= htmlspecialchars($rv['body'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="review-meta">
                        <div class="review-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                        <div>
                            <div style="font-weight:600; color:var(--navy-700,#0A1628);">
                                <?= htmlspecialchars($rv['name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div><?= htmlspecialchars(date('M Y', strtotime($rv['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Submission form -->
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="review-form-card">
                    <h3><i class="fa-solid fa-pen-to-square me-2" style="color:var(--gold,#F5B301);"></i>Share Your Experience</h3>
                    <p class="text-muted mb-4" style="font-size:.92rem;">
                        Your review helps us improve and helps others find a trusted real-estate partner.
                        Submissions appear publicly after a quick check by our team.
                    </p>

                    <div class="review-success" id="reviewSuccess" role="status" aria-live="polite">
                        <i class="fa-solid fa-circle-check fa-lg me-2"></i>
                        <span id="reviewSuccessMsg">Thank you! Your review has been submitted.</span>
                    </div>

                    <form id="reviewForm" method="POST" action="<?= $b ?>/api/v1/reviews.php" novalidate>
                        <!-- Honeypot (hidden from users; bots fill it) -->
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               style="position:absolute; left:-9999px; opacity:0;" aria-hidden="true">

                        <div class="mb-3">
                            <label class="form-label fw-600">Your Rating *</label>
                            <div class="star-rating" id="starRating">
                                <?php for ($i=5; $i>=1; $i--): ?>
                                    <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                                    <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>"><i class="fa-solid fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                            <div class="invalid-feedback d-block" id="err_rating"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-600">Your Name *</label>
                                <input type="text" name="name" class="form-control" required maxlength="120"
                                       placeholder="e.g. Ahmed Raza">
                                <div class="invalid-feedback d-block" id="err_name"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-600">Email <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="email" name="email" class="form-control" maxlength="160"
                                       placeholder="you@example.com">
                                <div class="invalid-feedback d-block" id="err_email"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-600">Headline <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="title" class="form-control" maxlength="160"
                                       placeholder="A short summary of your experience">
                                <div class="invalid-feedback d-block" id="err_title"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-600">Your Review *</label>
                                <textarea name="body" class="form-control" rows="4" required
                                          minlength="10" maxlength="2000"
                                          placeholder="Tell us about your experience working with Al-Riaz Associates..."></textarea>
                                <div class="invalid-feedback d-block" id="err_body"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn-gold" id="reviewSubmitBtn">
                                <i class="fa-solid fa-paper-plane me-1"></i> Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var form    = document.getElementById('reviewForm');
    if (!form) return;
    var btn     = document.getElementById('reviewSubmitBtn');
    var success = document.getElementById('reviewSuccess');
    var successMsg = document.getElementById('reviewSuccessMsg');
    var errorFields = ['rating','name','email','title','body'];

    function clearErrors() {
        errorFields.forEach(function (k) {
            var el = document.getElementById('err_' + k);
            if (el) el.textContent = '';
        });
    }
    function showErrors(errors) {
        Object.keys(errors).forEach(function (k) {
            var el = document.getElementById('err_' + k);
            if (el) el.textContent = errors[k];
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();
        btn.disabled = true;
        var origLabel = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...';

        var fd = new FormData(form);
        var lastStatus = 0;
        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            lastStatus = res.status;
            return res.text().then(function (text) {
                try {
                    return { status: res.status, body: JSON.parse(text), raw: text };
                } catch (err) {
                    // Surface a useful preview of whatever the server actually returned.
                    var preview = (text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200);
                    throw new Error('Server returned non-JSON (HTTP ' + res.status + '): ' + (preview || 'empty response'));
                }
            });
        })
        .then(function (r) {
            if (r.body && r.body.success) {
                successMsg.textContent = r.body.message || 'Thank you! Your review has been submitted.';
                success.classList.add('is-visible');
                form.reset();
                success.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (r.body && r.body.errors) {
                showErrors(r.body.errors);
            } else {
                showErrors({ body: (r.body && r.body.message) || 'Could not submit review. Please try again.' });
            }
        })
        .catch(function (err) {
            console.error('[reviews] submit failed:', err);
            showErrors({ body: (err && err.message) || ('Network error (HTTP ' + lastStatus + '). Please try again.') });
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = origLabel;
        });
    });
})();
</script>

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
