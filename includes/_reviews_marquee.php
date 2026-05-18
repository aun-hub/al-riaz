<?php
/**
 * Shared testimonial marquee — used by /index.php and /about.php.
 * Pass `$reviews` (array of rows with id, name, rating, title, body, created_at)
 * before include.
 *
 * Caller controls the surrounding section / heading. This file only renders
 * the marquee track + click-to-popup modal + scoped styles. The modal markup
 * and JS are emitted on first include only (guarded by a static flag) so
 * including the partial twice on the same page is safe.
 */

if (empty($reviews) || !is_array($reviews)) {
    return;
}

$mqReviews = array_merge($reviews, $reviews); // duplicate for seamless loop
?>
<div class="testimonial-marquee" aria-label="Client reviews scrolling">
    <div class="testimonial-track">
        <?php foreach ($mqReviews as $idx => $rv):
            $stars    = max(1, min(5, (int)$rv['rating']));
            $name     = trim((string)$rv['name']);
            $initials = mb_strtoupper(mb_substr($name !== '' ? $name : '?', 0, 1));
            $words    = preg_split('/\s+/', $name);
            if (count($words) > 1) $initials .= mb_strtoupper(mb_substr($words[count($words)-1], 0, 1));
            $bodyPreview = mb_strimwidth((string)$rv['body'], 0, 180, '…', 'UTF-8');
        ?>
        <button type="button" class="testimonial-card testimonial-card-btn"
                data-review-id="<?= (int)$rv['id'] ?>"
                data-review-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                data-review-rating="<?= $stars ?>"
                data-review-date="<?= htmlspecialchars(date('M j, Y', strtotime($rv['created_at'])), ENT_QUOTES, 'UTF-8') ?>"
                data-review-title="<?= htmlspecialchars((string)($rv['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-review-body="<?= htmlspecialchars((string)$rv['body'], ENT_QUOTES, 'UTF-8') ?>"
                data-review-initials="<?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>"
                aria-hidden="<?= $idx >= count($reviews) ? 'true' : 'false' ?>">
            <div class="testimonial-stars" aria-label="<?= $stars ?> out of 5 stars">
                <?php for ($s = 0; $s < $stars; $s++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
            </div>
            <p class="testimonial-text">&ldquo;<?= htmlspecialchars($bodyPreview, ENT_QUOTES, 'UTF-8') ?>&rdquo;</p>
            <div class="testimonial-author">
                <div class="testimonial-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                <div>
                    <div class="testimonial-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="testimonial-role"><?= htmlspecialchars(date('M Y', strtotime($rv['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<?php
// Emit shared CSS/JS + modal markup only once per page render.
static $marqueeAssetsEmitted = false;
if ($marqueeAssetsEmitted) return;
$marqueeAssetsEmitted = true;
?>
<style>
/* ── Section background (caller adds `.testimonial-marquee-section` to <section>) ── */
.testimonial-marquee-section {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 10% 15%, rgba(245,179,1,0.10) 0%, transparent 45%),
        radial-gradient(circle at 90% 85%, rgba(15,32,68,0.08) 0%, transparent 45%),
        linear-gradient(180deg, #f7faff 0%, #e9f0fb 100%);
}
.testimonial-marquee-section::before {
    content: ""; position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(ellipse at top, rgba(255,255,255,.5), rgba(255,255,255,0) 55%);
}
.testimonial-bg-orb {
    position: absolute; border-radius: 50%;
    filter: blur(64px); opacity: .55; pointer-events: none;
}
.testimonial-bg-orb-a { top:-100px; left:-80px; width:320px; height:320px; background: radial-gradient(circle, #fff2b0 0%, transparent 70%); }
.testimonial-bg-orb-b { bottom:-120px; right:-80px; width:380px; height:380px; background: radial-gradient(circle, #c8d3e8 0%, transparent 70%); }
.testimonial-marquee-section > .container,
.testimonial-marquee-section > .testimonial-marquee { position: relative; z-index: 1; }

/* ── Marquee track ── */
.testimonial-marquee {
    overflow: hidden;
    padding: 1rem 0 1.5rem;
    margin-top: 1.5rem;
    mask-image: linear-gradient(90deg, transparent 0, #000 60px, #000 calc(100% - 60px), transparent 100%);
    -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 60px, #000 calc(100% - 60px), transparent 100%);
}
.testimonial-track {
    display: flex;
    gap: 1.5rem;
    width: max-content;
    animation: testimonial-marquee-scroll 60s linear infinite;
    will-change: transform;
}
.testimonial-marquee:hover .testimonial-track,
.testimonial-marquee:focus-within .testimonial-track {
    animation-play-state: paused;
}
@keyframes testimonial-marquee-scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
.testimonial-card-btn {
    flex: 0 0 360px;
    max-width: 360px;
    text-align: left;
    border: 1px solid var(--border, #e6ebf2);
    cursor: pointer;
    font: inherit;
    color: inherit;
    background: #fff;
}
.testimonial-card-btn:focus-visible {
    outline: 2px solid var(--gold, #F5B301);
    outline-offset: 3px;
}
@media (max-width: 576px) {
    .testimonial-card-btn { flex-basis: 280px; max-width: 280px; }
    .testimonial-track    { animation-duration: 45s; }
}
@media (prefers-reduced-motion: reduce) {
    .testimonial-track { animation: none; }
}

/* ── Popup modal ── */
#reviewPopupModal .modal-content {
    border:0; border-radius:14px; overflow:hidden;
    box-shadow: 0 25px 60px -10px rgba(10,22,40,.45);
}
#reviewPopupModal .modal-header {
    background: linear-gradient(135deg, var(--navy-800, #0f2044) 0%, var(--navy-700, #1a3162) 60%, var(--navy-600, #25457f) 100%);
    color:#fff; border:0; border-bottom:3px solid var(--gold, #F5B301);
    padding:1rem 1.5rem;
}
#reviewPopupModal .modal-title { font-weight:700; color:#fff; }
#reviewPopupModal .btn-close { filter: invert(1) grayscale(1) brightness(2); opacity:.85; }
#reviewPopupModal .btn-close:hover { opacity:1; }
#reviewPopupModal .modal-body { padding:1.5rem; background:#fff; }
.review-popup-stars { color: var(--gold, #F5B301); font-size:1.05rem; letter-spacing:2px; margin-bottom:.5rem; }
.review-popup-title { font-weight:700; color: var(--navy-800, #0f2044); margin-bottom:.6rem; font-size:1.05rem; }
.review-popup-body  { color: var(--gray-700, #4a5568); line-height:1.7; white-space:pre-line; font-size:.98rem; }
.review-popup-meta  { display:flex; align-items:center; gap:.75rem; margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border, #e6ebf2); }
.review-popup-avatar {
    width:48px; height:48px; border-radius:50%;
    background: linear-gradient(135deg, var(--navy-50, #e6ebf2), var(--navy-100, #cfd8e5));
    color: var(--navy-700, #1a3162); font-weight:800;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.review-popup-name { font-weight:700; color: var(--navy-800, #0f2044); }
.review-popup-date { font-size:.82rem; color: var(--text-secondary, #6b7280); }
</style>

<div class="modal fade" id="reviewPopupModal" tabindex="-1" aria-labelledby="reviewPopupTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="reviewPopupTitle">Client Review</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reviewPopupBody"><!-- populated by JS --></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('reviewPopupModal');
    if (!modalEl) return;
    var modal   = new bootstrap.Modal(modalEl);
    var titleEl = document.getElementById('reviewPopupTitle');
    var bodyEl  = document.getElementById('reviewPopupBody');

    function star(active) {
        return '<i class="fa-' + (active ? 'solid' : 'regular') + ' fa-star"></i>';
    }

    document.querySelectorAll('.testimonial-card-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var rating  = parseInt(btn.dataset.reviewRating, 10) || 5;
            var name    = btn.dataset.reviewName     || 'Client review';
            var date    = btn.dataset.reviewDate     || '';
            var title   = btn.dataset.reviewTitle    || '';
            var body    = btn.dataset.reviewBody     || '';
            var initial = btn.dataset.reviewInitials || '?';

            titleEl.textContent = title || ('Review from ' + name);

            var starsHtml = '';
            for (var i = 1; i <= 5; i++) starsHtml += star(i <= rating);

            bodyEl.innerHTML =
                '<div class="review-popup-stars" aria-label="' + rating + ' out of 5 stars">' + starsHtml + '</div>' +
                (title ? '<div class="review-popup-title"></div>' : '') +
                '<div class="review-popup-body"></div>' +
                '<div class="review-popup-meta">' +
                    '<div class="review-popup-avatar"></div>' +
                    '<div>' +
                        '<div class="review-popup-name"></div>' +
                        '<div class="review-popup-date"></div>' +
                    '</div>' +
                '</div>';

            if (title) bodyEl.querySelector('.review-popup-title').textContent = title;
            bodyEl.querySelector('.review-popup-body').textContent   = body;
            bodyEl.querySelector('.review-popup-avatar').textContent = initial;
            bodyEl.querySelector('.review-popup-name').textContent   = name;
            bodyEl.querySelector('.review-popup-date').textContent   = date;

            modal.show();
        });
    });
});
</script>
