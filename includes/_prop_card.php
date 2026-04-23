<?php
/**
 * Al-Riaz Associates — Shared Property Card Partial
 * Include this inside a foreach loop.
 *
 * Required variables (set before including):
 *   $prop          array  — property row from DB
 *   $i             int    — loop index (for fallback image IDs)
 *   $b             string — BASE_PATH constant (for hrefs)
 *   $waPhone       string — SITE_WHATSAPP constant
 *
 * Optional variables:
 *   $showAgent     bool   — show agent name below location (default false)
 */

$thumb   = !empty($prop['thumbnail'])
           ? htmlspecialchars(mediaUrl($prop['thumbnail']))
           : 'https://picsum.photos/id/' . (80 + ($i % 40)) . '/480/360';
$slug    = htmlspecialchars($prop['slug'] ?? '#');
$price   = formatPKR((float)($prop['price'] ?? 0));
$podFlag = !empty($prop['price_on_demand']);
$title   = htmlspecialchars($prop['title'] ?? 'Property');
$locality = htmlspecialchars($prop['area_locality'] ?? '');
$city    = htmlspecialchars(ucfirst($prop['city'] ?? ''));
$areaVal = (float)($prop['area_value'] ?? 0);
$areaUnit = $prop['area_unit'] ?? 'marla';
$beds    = (int)($prop['bedrooms']  ?? 0);
$baths   = (int)($prop['bathrooms'] ?? 0);
$purpose = $prop['purpose'] ?? 'sale';
$propId  = (int)($prop['id'] ?? 0);
$isFeatured = !empty($prop['is_featured']);
$posStatus  = $prop['possession_status'] ?? '';
$agentName  = htmlspecialchars($prop['agent_name'] ?? '');
$waMsg  = rawurlencode("Hello! I'm interested in: $title" . ($podFlag ? '' : " — PKR $price") . ". Please share more details.");
$waLink = 'https://wa.me/' . ($waPhone ?? SITE_WHATSAPP) . '?text=' . $waMsg;
$showAgent = $showAgent ?? false;
$b = defined('BASE_PATH') ? BASE_PATH : '';
?>
<div class="prop-card">
    <div class="prop-card-img">
        <a href="<?= $b ?>/listing.php?slug=<?= urlencode($prop['slug'] ?? '') ?>" aria-label="<?= $title ?>">
            <img src="<?= $thumb ?>"
                 alt="<?= $title ?>"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='https://picsum.photos/id/<?= 80 + ($i % 40) ?>/480/360';">
        </a>
        <div class="prop-card-overlay" aria-hidden="true"></div>

        <div class="prop-badges">
            <span class="prop-badge <?= $purpose === 'rent' ? 'prop-badge-rent' : 'prop-badge-sale' ?>">
                <?= $purpose === 'rent' ? 'Rent' : 'Sale' ?>
            </span>
            <?php if ($isFeatured): ?>
                <span class="prop-badge prop-badge-featured"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</span>
            <?php endif; ?>
            <?php if ($posStatus === 'ready'): ?>
                <span class="prop-badge" style="background:#10B981; color:#fff;">Ready</span>
            <?php elseif ($posStatus === 'under_construction'): ?>
                <span class="prop-badge" style="background:#F59E0B; color:#111;">Under Const.</span>
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
        <div class="prop-price">
            <?php if ($podFlag): ?>
                <span style="color:var(--gray-500); font-size:1rem; font-weight:600;">Price on Demand</span>
            <?php else: ?>
                PKR <?= $price ?>
                <?php if ($purpose === 'rent'): ?>
                    <span style="font-size:0.72rem; font-weight:500; color:var(--text-secondary);">/mo</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="prop-title">
            <a href="<?= $b ?>/listing.php?slug=<?= urlencode($prop['slug'] ?? '') ?>" style="color:inherit; text-decoration:none;"><?= $title ?></a>
        </div>
        <div class="prop-location">
            <i class="fa-solid fa-location-dot"></i>
            <?= $locality ? "$locality, $city" : $city ?>
        </div>
        <?php if ($showAgent && $agentName): ?>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem;">
            <i class="fa-solid fa-user" style="color:var(--navy-300);"></i> <?= $agentName ?>
        </div>
        <?php endif; ?>

        <div class="prop-specs">
            <?php if ($beds > 0): ?>
            <div class="prop-spec"><i class="fa-solid fa-bed"></i> <?= $beds ?></div>
            <?php endif; ?>
            <?php if ($baths > 0): ?>
            <div class="prop-spec"><i class="fa-solid fa-bath"></i> <?= $baths ?></div>
            <?php endif; ?>
            <?php if ($areaVal > 0): ?>
            <div class="prop-spec"><i class="fa-solid fa-vector-square"></i> <?= formatArea($areaVal, $areaUnit) ?></div>
            <?php endif; ?>
            <div class="prop-spec ms-auto">
                <a href="<?= $b ?>/listing.php?slug=<?= urlencode($prop['slug'] ?? '') ?>"
                   class="btn-navy" style="font-size:0.72rem; padding:0.3rem 0.75rem;">
                    Details
                </a>
            </div>
        </div>
    </div>
</div>
