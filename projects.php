<?php
/**
 * Al-Riaz Associates — Projects Listing Page
 * Shows all authorised real estate projects.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

/* ─── Filter Parameters ─────────────────────────────────────────────────── */
$filterCity   = trim($_GET['city']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

/* ─── Validate allowed status values ────────────────────────────────────── */
$allowedStatuses = ['upcoming', 'under_development', 'ready', 'possession'];
if ($filterStatus && !in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

/* ─── Build WHERE Clause ─────────────────────────────────────────────────── */
$where  = ['p.is_published = 1'];
$params = [];

if ($filterCity !== '') {
    $where[]  = 'p.city = ?';
    $params[] = $filterCity;
}
if ($filterStatus !== '') {
    $where[]  = 'p.status = ?';
    $params[] = $filterStatus;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

/* ─── Count Total ────────────────────────────────────────────────────────── */
try {
    $stmtCount = $db->prepare('SELECT COUNT(*) FROM projects p ' . $whereSQL);
    $stmtCount->execute($params);
    $totalProjects = (int)$stmtCount->fetchColumn();
} catch (Exception $e) {
    error_log('[projects.php] count: ' . $e->getMessage());
    $totalProjects = 0;
}
$totalPages = max(1, (int)ceil($totalProjects / $perPage));
$page       = min($page, $totalPages);

/* ─── Fetch Projects ─────────────────────────────────────────────────────── */
try {
    $stmtProj = $db->prepare(
        'SELECT p.* FROM projects p
         ' . $whereSQL . '
         ORDER BY p.is_featured DESC, p.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmtProj->execute(array_merge($params, [$perPage, $offset]));
    $projects = $stmtProj->fetchAll();
} catch (Exception $e) {
    error_log('[projects.php] projects: ' . $e->getMessage());
    $projects = [];
}

/* ─── Distinct Cities for filter ────────────────────────────────────────── */
try {
    $stmtCities = $db->query('SELECT DISTINCT city FROM projects WHERE is_published = 1 ORDER BY city ASC');
    $cities     = $stmtCities->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log('[projects.php] cities: ' . $e->getMessage());
    $cities = [];
}

/* ─── Status labels ─────────────────────────────────────────────────────── */
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

/* ─── Page Meta ─────────────────────────────────────────────────────────── */
$pageTitle = 'Authorised Projects - Al-Riaz Associates';
$metaDesc  = 'We are authorised dealers for major real estate developments across Pakistan — Islamabad, Rawalpindi, Lahore, Karachi and more.';

$b = defined('BASE_PATH') ? BASE_PATH : '';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page Header ───────────────────────────────────────────────────────── -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([['label'=>'Home','url'=>'/'],['label'=>'Projects']]) ?>
        <h1 class="page-header-title">Real Estate Projects</h1>
        <p class="page-header-sub">Authorised developments across Pakistan</p>
    </div>
</div>

<!-- ── Status Filter Pills ───────────────────────────────────────────────── -->
<div style="background:var(--navy-50); padding:1rem 0; border-bottom:1px solid var(--navy-100);">
    <div class="container">
        <div class="purpose-pill-group">
            <a href="<?= $b ?>/projects.php" class="purpose-pill <?= $filterStatus==='' ? 'active' : '' ?>">All</a>
            <a href="<?= $b ?>/projects.php?status=upcoming" class="purpose-pill <?= $filterStatus==='upcoming' ? 'active' : '' ?>">Upcoming</a>
            <a href="<?= $b ?>/projects.php?status=under_development" class="purpose-pill <?= $filterStatus==='under_development' ? 'active' : '' ?>">Under Development</a>
            <a href="<?= $b ?>/projects.php?status=ready" class="purpose-pill <?= $filterStatus==='ready' ? 'active' : '' ?>">Ready</a>
            <a href="<?= $b ?>/projects.php?status=possession" class="purpose-pill <?= $filterStatus==='possession' ? 'active' : '' ?>">Possession</a>
        </div>
    </div>
</div>

<!-- ── Main Content ──────────────────────────────────────────────────────── -->
<main id="main-content">
<div class="container py-4">
    <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
        <form method="GET" action="<?= $b ?>/projects.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
            <select name="city" class="sort-select" onchange="this.form.submit()">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $filterCity===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <span class="text-muted small ms-auto"><?= number_format($totalProjects) ?> projects</span>
    </div>

    <?php if (!empty($projects)): ?>
    <div class="row g-4">
        <?php foreach ($projects as $proj):
            $projImg = ($proj['cover_image'] ?? null)
                       ?: (($proj['hero_image_url'] ?? null)
                       ?: 'https://picsum.photos/id/'.(30 + ((int)($proj['id'] ?? 0) % 20)).'/600/400');
        ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="project-card">
                <div class="project-card-img">
                    <a href="<?= $b ?>/project.php?slug=<?= urlencode($proj['slug']) ?>">
                        <img data-src="<?= htmlspecialchars($projImg) ?>"
                             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 10'%3E%3Crect fill='%23F0F4FF' width='16' height='10'/%3E%3C/svg%3E"
                             alt="<?= htmlspecialchars($proj['name']) ?>" class="lazy" loading="lazy">
                    </a>
                    <div class="prop-badges">
                        <span class="prop-badge project-status"><?= htmlspecialchars(getStatusLabel($proj['status'])) ?></span>
                        <?php if (!empty($proj['is_featured'])): ?>
                        <span class="prop-badge prop-badge-new">Featured</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="project-card-body">
                    <h3 class="project-card-title">
                        <a href="<?= $b ?>/project.php?slug=<?= urlencode($proj['slug']) ?>" style="color:inherit;text-decoration:none;">
                            <?= htmlspecialchars($proj['name']) ?>
                        </a>
                    </h3>
                    <p class="project-card-location">
                        <i class="fa-solid fa-location-dot"></i>
                        <?= htmlspecialchars(trim(($proj['area_locality'] ? $proj['area_locality'] . ', ' : '') . $proj['city'])) ?>
                    </p>
                    <?php if (!empty($proj['developer'])): ?>
                    <p style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.75rem;">
                        <i class="fa-solid fa-building" style="color:var(--navy-300);"></i>
                        <?= htmlspecialchars($proj['developer']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($proj['description'])): ?>
                    <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:1rem;line-height:1.5;">
                        <?= htmlspecialchars(mb_strimwidth($proj['description'], 0, 100, '…')) ?>
                    </p>
                    <?php endif; ?>
                    <a href="<?= $b ?>/project.php?slug=<?= urlencode($proj['slug']) ?>" class="btn-navy" style="font-size:.82rem;padding:.4rem 1rem;">
                        View Project <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination-nav mt-5" aria-label="Projects pages">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $b ?>/projects.php?page=<?= $page-1 ?>&status=<?= urlencode($filterStatus) ?>&city=<?= urlencode($filterCity) ?>"><i class="fa-solid fa-chevron-left"></i></a></li>
            <?php else: ?><li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li><?php endif; ?>

            <?php
            $range = 2;
            $start = max(1, $page - $range);
            $end   = min($totalPages, $page + $range);
            if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $b ?>/projects.php?page=1&status=<?= urlencode($filterStatus) ?>&city=<?= urlencode($filterCity) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
            endif; ?>

            <?php for ($p=$start; $p<=$end; $p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="<?= $b ?>/projects.php?page=<?= $p ?>&status=<?= urlencode($filterStatus) ?>&city=<?= urlencode($filterCity) ?>" <?= $p===$page?'aria-current="page"':'' ?>><?= $p ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($end < $totalPages):
                if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= $b ?>/projects.php?page=<?= $totalPages ?>&status=<?= urlencode($filterStatus) ?>&city=<?= urlencode($filterCity) ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="<?= $b ?>/projects.php?page=<?= $page+1 ?>&status=<?= urlencode($filterStatus) ?>&city=<?= urlencode($filterCity) ?>"><i class="fa-solid fa-chevron-right"></i></a></li>
            <?php else: ?><li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li><?php endif; ?>
        </ul>
        <p class="text-center text-muted small mt-2">Page <?= $page ?> of <?= $totalPages ?></p>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <i class="fa-solid fa-city"></i>
        <h4>No Projects Found</h4>
        <p>Try changing the status filter or city.</p>
        <a href="<?= $b ?>/projects.php" class="btn-navy mt-2">View All Projects</a>
    </div>
    <?php endif; ?>

</div><!-- /.container -->
</main>

<!-- ── CTA Banner ────────────────────────────────────────────────────────── -->
<section style="background:var(--navy-50); border-top:1px solid var(--navy-100); padding:3rem 0;">
    <div class="container text-center">
        <h2 class="fw-bold mb-2" style="color:var(--navy-900);">Looking for a Specific Project?</h2>
        <p class="text-muted mb-4">
            Contact our team — we work with 20+ authorised developers across Pakistan.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= htmlspecialchars(getWhatsAppLink(SITE_WHATSAPP, 'Hi, I\'m looking for a specific real estate project. Can you help me?')) ?>"
               target="_blank" rel="noopener noreferrer"
               class="btn-whatsapp">
                <i class="fa-brands fa-whatsapp me-2"></i>WhatsApp Us
            </a>
            <a href="<?= $b ?>/contact.php" class="btn-gold">
                <i class="fa-solid fa-headset me-2"></i>Get in Touch
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
