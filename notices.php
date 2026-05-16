<?php
/**
 * Al-Riaz Associates — Public Notices Page
 * Compact list of project notices. Each row opens in a modal popup.
 * The most recent notice auto-opens on first visit (per-browser, localStorage-gated).
 * Public URL: /notices.php
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

$pageTitle       = 'Notices - Al-Riaz Associates';
$pageDescription = 'Latest announcements, schedules, and updates from real estate projects we are authorised to sell.';

$projectFilter = isset($_GET['project']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$_GET['project'])) : '';

$now = date('Y-m-d H:i:s');

try {
    $sql = "
        SELECT n.id, n.title, n.body, n.source_url, n.attachment_url, n.severity, n.starts_at, n.ends_at, n.created_at,
               p.id AS project_id, p.name AS project_name, p.slug AS project_slug, p.website_url AS project_website_url
        FROM   project_notices n
        LEFT JOIN projects p ON p.id = n.project_id
        WHERE  n.is_published = 1
          AND (n.starts_at IS NULL OR n.starts_at <= ?)
          AND (n.ends_at   IS NULL OR n.ends_at   >= ?)
    ";
    $params = [$now, $now];
    if ($projectFilter !== '') {
        $sql .= " AND p.slug = ?";
        $params[] = $projectFilter;
    }
    // ORDER BY: newest first overall — also drives "latest notice" auto-popup.
    $sql .= " ORDER BY COALESCE(n.starts_at, n.created_at) DESC, n.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('[notices.php] query: ' . $e->getMessage());
    $notices = [];
}

$severityStyles = [
    'info'     => ['icon' => 'fa-circle-info',          'color' => '#3b82f6', 'label' => 'Info',     'badgeClass' => 'bg-info text-dark'],
    'warning'  => ['icon' => 'fa-triangle-exclamation', 'color' => '#f59e0b', 'label' => 'Warning',  'badgeClass' => 'bg-warning text-dark'],
    'critical' => ['icon' => 'fa-circle-exclamation',   'color' => '#dc2626', 'label' => 'Critical', 'badgeClass' => 'bg-danger'],
];

// Pre-render a notice's "modal body" HTML — reused below to populate the
// hidden template that the JS clones into the shared modal on click.
$renderNoticeBody = function (array $n) use ($severityStyles): string {
    ob_start();
    $body = trim((string)$n['body']);
    if (strip_tags($body) === $body) {
        foreach (array_filter(array_map('trim', explode("\n\n", $body))) as $para) {
            echo '<p>' . nl2br(htmlspecialchars($para, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
    } else {
        echo $body;
    }

    $attUrl   = (string)($n['attachment_url'] ?? '');
    $attExt   = $attUrl !== '' ? strtolower(pathinfo($attUrl, PATHINFO_EXTENSION)) : '';
    $attIsImg = in_array($attExt, ['jpg','jpeg','png','webp','gif'], true);
    $attIsPdf = $attExt === 'pdf';
    if ($attIsImg) {
        echo '<div class="notice-image-wrap">'
           . '<img src="' . htmlspecialchars(mediaUrl($attUrl), ENT_QUOTES, 'UTF-8') . '" '
           . 'alt="" loading="lazy" class="notice-image" '
           . 'title="Click to open zoomable preview"></div>';
    } elseif ($attIsPdf) {
        echo '<div class="text-center mt-3">'
           . '<a href="' . htmlspecialchars(mediaUrl($attUrl), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" '
           . 'class="btn btn-pdf-attachment">'
           . '<i class="fa-solid fa-file-pdf me-1"></i>Open PDF</a></div>';
    }
    return ob_get_clean();
};

require_once 'includes/header.php';
?>

<!-- ============================================================
     PAGE HEADER
     ============================================================ -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([['label'=>'Home','url'=>'/'],['label'=>'Notices']]) ?>
        <h1 class="page-header-title">Notices &amp; Announcements</h1>
        <p class="page-header-sub">Latest updates from the projects we represent</p>
    </div>
</div>

<style>
.notices-list {
  background:#fff; border:1px solid #e6ebf2; border-radius:12px;
  box-shadow:0 1px 2px rgba(10,22,40,.04); overflow:hidden;
}
.notice-row {
  display:flex; align-items:center; gap:1rem;
  padding:1rem 1.25rem; cursor:pointer;
  border-top:1px solid #eef1f6; transition:background-color .15s;
  text-align:left; width:100%; background:transparent; border-left:0; border-right:0; border-bottom:0;
}
.notice-row:first-child { border-top:0; }
.notice-row:hover, .notice-row:focus-visible { background:#f7f9fc; outline:none; }
.notice-row .severity-dot {
  width:36px; height:36px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:1rem; flex-shrink:0;
}
.notice-row.sev-info     .severity-dot { background:rgba(59,130,246,.12); color:#3b82f6; }
.notice-row.sev-warning  .severity-dot { background:rgba(245,158,11,.15); color:#b45309; }
.notice-row.sev-critical .severity-dot { background:rgba(220,38,38,.12); color:#dc2626; }
.notice-row .row-main { flex:1; min-width:0; }
.notice-row .row-title {
  color:var(--navy-800); font-weight:700; margin:0; font-size:1rem;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.notice-row .row-meta {
  color:var(--text-secondary); font-size:.78rem; margin-top:.1rem;
}
.notice-row .row-arrow {
  color:#a3a8b5; flex-shrink:0; font-size:.85rem;
}
.notice-row.is-unread .row-title::after {
  content:''; display:inline-block; width:7px; height:7px;
  border-radius:50%; background:var(--gold-500, #f5b301); margin-left:.45rem;
  vertical-align:middle;
}

.notice-banner {
  background:linear-gradient(135deg, #fff8e1, #fff3cd);
  border:1px solid #ffe9a8; border-left:4px solid var(--gold-500, #f5b301);
  border-radius:10px; padding:1rem 1.25rem; margin-bottom:1.25rem;
  display:flex; align-items:center; gap:1rem;
}
.notice-banner .banner-icon {
  width:44px; height:44px; border-radius:50%; flex-shrink:0;
  background:#fff; color:var(--gold-500, #b45309);
  display:inline-flex; align-items:center; justify-content:center; font-size:1.2rem;
  box-shadow:0 1px 2px rgba(0,0,0,.06);
}
.notice-banner .banner-text { flex:1; min-width:0; }
.notice-banner .banner-eyebrow { font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; color:#92400e; font-weight:700; }
.notice-banner .banner-title { color:var(--navy-800); font-weight:700; }

.notices-empty {
  background:#fff; border:1px dashed #e6ebf2;
  border-radius:12px; padding:3rem 1.5rem; text-align:center;
  color:var(--text-secondary);
}

/* Navy + gold themed notice modal */
#noticeModal .modal-content { border:0; border-radius:14px; overflow:hidden; box-shadow:0 25px 60px -10px rgba(10,22,40,.45); }
#noticeModal .modal-header {
  background: linear-gradient(135deg, var(--navy-800, #0f2044) 0%, var(--navy-700, #1a3162) 60%, var(--navy-600, #25457f) 100%);
  color:#fff; border:0; border-bottom:3px solid var(--gold-500, #f5b301);
  padding:1rem 1.5rem; align-items:center;
}
#noticeModal .modal-title { font-weight:700; color:#fff; letter-spacing:-.01em; }
#noticeModal .btn-close { filter: invert(1) grayscale(1) brightness(2); opacity:.85; }
#noticeModal .btn-close:hover { opacity:1; }
#noticeModal .modal-body { padding:1.25rem 1.5rem 1.5rem; background:#fff; }
#noticeModal .modal-meta {
  font-size:.82rem; color:var(--text-secondary, #6b7280);
  padding:.4rem .75rem; background:#f7f9fc; border-radius:8px;
  display:inline-block; margin-bottom:1rem;
}
#noticeModal .notice-actions {
  display:flex; flex-wrap:wrap; gap:.5rem 1.25rem;
  margin-top:1.25rem; padding-top:1rem; border-top:1px solid #eef1f6;
}
#noticeModal .notice-actions a {
  font-size:.88rem; font-weight:600; text-decoration:none;
  color:var(--navy-700, #1a3162);
}
#noticeModal .notice-actions a:hover { color:var(--gold-500, #b45309); }

/* Image inside modal — themed frame, centered, zoomable */
.notice-image-wrap {
  display: flex !important; align-items: center; justify-content: center;
  margin: 1.25rem 0 .5rem; padding: 1rem;
  /* Subtle navy → cream gradient that matches the site's hero/footer accents. */
  background: linear-gradient(135deg, #f4f7fb 0%, #eaf0f9 60%, #fff7e0 100%);
  border: 1px solid #e6ebf2; border-radius: 12px;
  box-shadow: inset 0 1px 2px rgba(10,22,40,.04);
}
.notice-image {
  display: block !important; margin: 0 auto !important;
  max-width: 100%; max-height: 520px;
  border-radius: 10px; background:#fff;
  border: 1px solid rgba(10,22,40,.08);
  box-shadow: 0 6px 18px rgba(10,22,40,.14);
  cursor: zoom-in; transition: transform .2s ease, box-shadow .2s ease;
}
.notice-image:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(10,22,40,.22);
}

/* PDF button — gold-trimmed instead of stock outline-danger */
.btn-pdf-attachment {
  display:inline-flex; align-items:center; gap:.4rem;
  background:#fff; color:var(--navy-700, #1a3162);
  border:1px solid var(--gold-500, #f5b301);
  padding:.5rem 1.1rem; border-radius:8px; font-weight:600; font-size:.9rem;
  text-decoration:none; transition: all .15s;
}
.btn-pdf-attachment:hover {
  background:var(--gold-500, #f5b301); color:var(--navy-800, #0f2044); border-color:var(--gold-500, #f5b301);
}

/* ── Lightbox overlay (zoom in/out for notice images) ── */
.notice-lightbox {
  position: fixed; inset: 0; z-index: 1080;
  display: none; flex-direction: column;
  background: rgba(10, 22, 40, .96);
}
.notice-lightbox.open { display: flex; }
.notice-lightbox .lb-toolbar {
  background: linear-gradient(135deg, var(--navy-800, #0f2044), var(--navy-600, #25457f));
  border-bottom: 2px solid var(--gold-500, #f5b301);
  padding: .6rem 1rem; display: flex; align-items: center; gap: .5rem;
}
.notice-lightbox .lb-spacer { flex: 1; }
.notice-lightbox .lb-btn {
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.2);
  color:#fff; padding: .35rem .7rem; border-radius: 6px; font-size: .9rem;
  cursor: pointer; line-height: 1; min-width: 36px;
}
.notice-lightbox .lb-btn:hover { background: rgba(245,179,1,.2); border-color: var(--gold-500, #f5b301); }
.notice-lightbox .lb-btn[disabled] { opacity: .35; cursor: not-allowed; }
.notice-lightbox .lb-scale { color: #fff; font-size: .85rem; min-width: 56px; text-align: center; font-variant-numeric: tabular-nums; }
.notice-lightbox .lb-close {
  background: var(--gold-500, #f5b301); border-color: var(--gold-500, #f5b301);
  color: var(--navy-800, #0f2044); font-weight: 700;
}
.notice-lightbox .lb-close:hover { background:#e6a300; border-color:#e6a300; }
.notice-lightbox .lb-stage {
  flex: 1; overflow: auto; display: flex; align-items: center; justify-content: center;
  padding: 1rem; cursor: grab;
}
.notice-lightbox .lb-stage.is-grabbing { cursor: grabbing; }
.notice-lightbox .lb-img {
  max-width: none; max-height: none;
  transform-origin: center center; transition: transform .15s ease;
  user-select: none; -webkit-user-drag: none;
  box-shadow: 0 10px 40px rgba(0,0,0,.5);
}
</style>

<section class="section-pad" style="background:var(--body-bg, #f7f9fc);">
  <div class="container" style="max-width:780px;">

    <?php if ($projectFilter !== '' && !empty($notices) && !empty($notices[0]['project_name'])): ?>
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <div class="fs-13 text-muted">
        Showing notices for <strong><?= htmlspecialchars($notices[0]['project_name'], ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <a href="<?= BASE_PATH ?>/notices.php" class="fs-13 text-decoration-none">
        <i class="fa-solid fa-xmark me-1"></i>Clear filter
      </a>
    </div>
    <?php endif; ?>

    <?php if (empty($notices)): ?>
      <div class="notices-empty">
        <i class="fa-solid fa-bullhorn fa-2x mb-3 d-block" style="opacity:.4;"></i>
        <h3 class="fs-5 fw-bold mb-1" style="color:var(--navy-800);">No active notices</h3>
        <p class="mb-0">There are no published notices at the moment. Check back later.</p>
      </div>
    <?php else: ?>

      <!-- "Latest notice" banner — also the auto-popup target. -->
      <?php $latest = $notices[0]; ?>
      <button type="button" class="notice-banner w-100 text-start"
              style="border-width:1px; cursor:pointer;"
              data-notice-id="<?= (int)$latest['id'] ?>">
        <span class="banner-icon"><i class="fa-solid fa-bullhorn"></i></span>
        <span class="banner-text">
          <span class="banner-eyebrow">Latest Notice</span>
          <div class="banner-title"><?= htmlspecialchars($latest['title'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="fs-13 text-muted">
            <?php if (!empty($latest['project_name'])): ?>
              <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($latest['project_name'], ENT_QUOTES, 'UTF-8') ?>&nbsp;·&nbsp;
            <?php endif; ?>
            <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars(date('M j, Y', !empty($latest['starts_at']) ? strtotime($latest['starts_at']) : strtotime($latest['created_at'])), ENT_QUOTES, 'UTF-8') ?>
          </div>
        </span>
        <span class="text-muted"><i class="fa-solid fa-chevron-right"></i></span>
      </button>

      <!-- Compact list of all notices -->
      <div class="notices-list">
        <?php foreach ($notices as $n):
          $style = $severityStyles[$n['severity']] ?? $severityStyles['info'];
          $when  = !empty($n['starts_at']) ? strtotime($n['starts_at']) : strtotime($n['created_at']);
        ?>
        <button type="button" class="notice-row sev-<?= htmlspecialchars($n['severity'], ENT_QUOTES, 'UTF-8') ?>"
                data-notice-id="<?= (int)$n['id'] ?>">
          <span class="severity-dot"><i class="fa-solid <?= $style['icon'] ?>"></i></span>
          <div class="row-main">
            <div class="row-title"><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="row-meta">
              <?php if (!empty($n['project_name'])): ?>
                <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($n['project_name'], ENT_QUOTES, 'UTF-8') ?>&nbsp;·&nbsp;
              <?php endif; ?>
              <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars(date('M j, Y', $when), ENT_QUOTES, 'UTF-8') ?>
              <span class="badge <?= $style['badgeClass'] ?> ms-2" style="font-size:.65rem; padding:.18rem .45rem;"><?= $style['label'] ?></span>
            </div>
          </div>
          <span class="row-arrow"><i class="fa-solid fa-chevron-right"></i></span>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- Hidden per-notice content templates (cloned into the modal on click) -->
      <?php foreach ($notices as $n):
        $style = $severityStyles[$n['severity']] ?? $severityStyles['info'];
        $when  = !empty($n['starts_at']) ? strtotime($n['starts_at']) : strtotime($n['created_at']);
      ?>
      <template id="notice-tpl-<?= (int)$n['id'] ?>">
        <div class="modal-meta">
          <span class="badge <?= $style['badgeClass'] ?> me-2" style="font-size:.7rem;"><i class="fa-solid <?= $style['icon'] ?> me-1"></i><?= $style['label'] ?></span>
          <?php if (!empty($n['project_name'])): ?>
            <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($n['project_name'], ENT_QUOTES, 'UTF-8') ?>&nbsp;·&nbsp;
          <?php endif; ?>
          <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars(date('M j, Y', $when), ENT_QUOTES, 'UTF-8') ?>
          <?php if (!empty($n['ends_at'])): ?>
            &nbsp;·&nbsp;<span>until <?= htmlspecialchars(date('M j, Y', strtotime($n['ends_at'])), ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div style="color:#333; line-height:1.7;">
          <?= $renderNoticeBody($n) ?>
        </div>

        <div class="notice-actions mt-3 pt-2 border-top">
          <?php if (!empty($n['source_url'])): ?>
            <a href="<?= htmlspecialchars($n['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View on developer site
            </a>
          <?php elseif (!empty($n['project_website_url'])): ?>
            <a href="<?= htmlspecialchars($n['project_website_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
              <i class="fa-solid fa-globe me-1"></i>Visit project website
            </a>
          <?php endif; ?>

          <?php if (!empty($n['project_id']) && !empty($n['project_slug'])):
            $pvl = projectViewLink([
              'slug'        => $n['project_slug'],
              'website_url' => $n['project_website_url'] ?? '',
            ], BASE_PATH);
          ?>
            <a href="<?= htmlspecialchars($pvl['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $pvl['target'] ? ' target="'.$pvl['target'].'" rel="'.$pvl['rel'].'"' : '' ?>>
              <i class="fa-solid <?= $pvl['external'] ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' ?> me-1"></i>About this project
            </a>
          <?php endif; ?>
        </div>
      </template>

      <span data-notice-title-for="<?= (int)$n['id'] ?>" hidden><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></span>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Shared notice modal — populated dynamically from <template id="notice-tpl-N"> -->
<div class="modal fade" id="noticeModal" tabindex="-1" aria-labelledby="noticeModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="noticeModalTitle">Notice</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="noticeModalBody">
        <!-- populated by JS -->
      </div>
    </div>
  </div>
</div>

<!-- Zoomable lightbox overlay for notice images -->
<div class="notice-lightbox" id="noticeLightbox" role="dialog" aria-modal="true" aria-label="Image preview">
  <div class="lb-toolbar">
    <button type="button" class="lb-btn" id="lbZoomOut" aria-label="Zoom out" title="Zoom out (−)"><i class="fa-solid fa-minus"></i></button>
    <span class="lb-scale" id="lbScale">100%</span>
    <button type="button" class="lb-btn" id="lbZoomIn" aria-label="Zoom in" title="Zoom in (+)"><i class="fa-solid fa-plus"></i></button>
    <button type="button" class="lb-btn" id="lbReset" title="Reset to 100%">1:1</button>
    <span class="lb-spacer"></span>
    <button type="button" class="lb-btn lb-close" id="lbClose" aria-label="Close preview" title="Close (Esc)"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="lb-stage" id="lbStage">
    <img id="lbImg" class="lb-img" alt="" />
  </div>
</div>

<script>
// Bootstrap is loaded in footer.php *after* this script tag, so wire up on
// DOMContentLoaded — by that point bootstrap.bundle.min.js will have parsed.
document.addEventListener('DOMContentLoaded', function () {
  if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
    console.warn('[notices] Bootstrap not loaded — notice popups disabled.');
    return;
  }
  var modalEl = document.getElementById('noticeModal');
  if (!modalEl) return;
  var modal   = new bootstrap.Modal(modalEl);
  var bodyEl  = document.getElementById('noticeModalBody');
  var titleEl = document.getElementById('noticeModalTitle');

  function openNotice(id) {
    if (!id) return;
    var tpl = document.getElementById('notice-tpl-' + id);
    if (!tpl) return;
    var titleNode = document.querySelector('[data-notice-title-for="' + id + '"]');
    titleEl.textContent = titleNode ? titleNode.textContent.trim() : 'Notice';
    bodyEl.innerHTML = '';
    bodyEl.appendChild(tpl.content.cloneNode(true));
    modal.show();
  }

  // Click any notice row, the latest-notice banner, or anything tagged with
  // data-notice-id → open the corresponding notice in the shared modal.
  document.querySelectorAll('[data-notice-id]').forEach(function (el) {
    el.addEventListener('click', function () {
      openNotice(this.getAttribute('data-notice-id'));
    });
  });

  // Auto-open the latest notice every time the page is visited.
  var latestBtn = document.querySelector('.notice-banner[data-notice-id]');
  if (latestBtn) {
    var latestId = latestBtn.getAttribute('data-notice-id');
    // Small delay so the modal animation kicks in after the page paints.
    setTimeout(function () { openNotice(latestId); }, 250);
  }

  /* ── Lightbox: click image → open zoomable preview ────────────── */
  var lb        = document.getElementById('noticeLightbox');
  var lbImg     = document.getElementById('lbImg');
  var lbStage   = document.getElementById('lbStage');
  var lbScaleEl = document.getElementById('lbScale');
  var lbZoomIn  = document.getElementById('lbZoomIn');
  var lbZoomOut = document.getElementById('lbZoomOut');
  var lbReset   = document.getElementById('lbReset');
  var lbClose   = document.getElementById('lbClose');
  var scale = 1;
  var MIN_SCALE = 0.25, MAX_SCALE = 5, STEP = 0.25;

  function applyScale() {
    lbImg.style.transform = 'scale(' + scale + ')';
    lbScaleEl.textContent = Math.round(scale * 100) + '%';
    lbZoomOut.disabled = scale <= MIN_SCALE + 0.001;
    lbZoomIn.disabled  = scale >= MAX_SCALE - 0.001;
  }
  function setScale(s) {
    scale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, s));
    applyScale();
  }
  function openLightbox(src) {
    if (!src) return;
    lbImg.src = src;
    scale = 1; applyScale();
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeLightbox() {
    lb.classList.remove('open');
    lbImg.removeAttribute('src');
    document.body.style.overflow = '';
  }

  // Delegated click: any .notice-image rendered inside the modal opens the LB.
  document.addEventListener('click', function (e) {
    var img = e.target.closest && e.target.closest('.notice-image');
    if (img) {
      e.preventDefault();
      openLightbox(img.src || img.getAttribute('src'));
    }
  });

  lbZoomIn.addEventListener('click',  function () { setScale(scale + STEP); });
  lbZoomOut.addEventListener('click', function () { setScale(scale - STEP); });
  lbReset.addEventListener('click',   function () { setScale(1); lbStage.scrollTo(0, 0); });
  lbClose.addEventListener('click',   closeLightbox);

  // Click on the empty area of the stage (outside the image) closes the LB.
  lbStage.addEventListener('click', function (e) {
    if (e.target === lbStage) closeLightbox();
  });

  // Wheel-to-zoom (with Ctrl/Cmd to feel like native image viewers).
  lbStage.addEventListener('wheel', function (e) {
    if (!lb.classList.contains('open')) return;
    if (!(e.ctrlKey || e.metaKey)) return;
    e.preventDefault();
    setScale(scale + (e.deltaY < 0 ? STEP : -STEP));
  }, { passive: false });

  // Esc closes the lightbox first; if it's not open, let Bootstrap handle modal.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (lb.classList.contains('open')) { closeLightbox(); e.stopPropagation(); }
  }, true);
});
</script>

<?php require_once 'includes/footer.php'; ?>
