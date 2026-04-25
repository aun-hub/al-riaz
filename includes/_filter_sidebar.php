<?php
/**
 * Al-Riaz Associates — Shared Filter Sidebar Partial
 * Identical UI on residential.php, commercial.php, rent.php (desktop + mobile offcanvas).
 * All filters auto-apply (debounced) — no "Apply" button.
 *
 * Expected variables (set before include):
 *   $filter = [
 *     'formId'          => 'filterForm' | 'filterFormMobile',
 *     'formAction'      => '/residential.php',
 *     'category'        => 'residential' | 'commercial' | null,  // hidden input if set
 *     'lockedPurpose'   => 'rent' | 'sale' | null,               // hidden input if set, hides purpose UI
 *     'showPurpose'     => bool,
 *     'showType'        => bool,
 *     'typeOptions'     => [ value => label, ... ],
 *     'showPrice'       => bool,
 *     'priceMax'        => int,   // slider ceiling (PKR)
 *     'priceStep'       => int,
 *     'priceLabel'      => string,// e.g. 'Price Range (PKR)' or 'Monthly Rent (PKR)'
 *     'showArea'        => bool,
 *     'areaMax'         => int,
 *     'defaultAreaUnit' => string,
 *     'showBedrooms'    => bool,
 *     'showFeatures'    => bool,
 *     'featureOptions'  => [ value => label, ... ],
 *     'showFloor'       => bool,
 *     'allCities'       => [ string ],
 *     'selected'        => [
 *         'purpose', 'city', 'type' (string or array),
 *         'minPrice', 'maxPrice', 'minArea', 'maxArea', 'areaUnit',
 *         'bedrooms', 'features' (array), 'floor'
 *     ],
 *     'extraHidden'     => [ name => value, ... ],   // extra hidden inputs (e.g. tab=residential)
 *   ]
 */

$filter = $filter ?? [];

/* ── Defaults ─────────────────────────────────────────────────────────── */
$f = array_merge([
    'formId'          => 'filterForm',
    'formAction'      => '/residential.php',
    'category'        => null,
    'lockedPurpose'   => null,
    'showPurpose'     => true,
    'showType'        => true,
    'typeOptions'     => [],
    'showPrice'       => true,
    'priceMax'        => 100000000,
    'priceStep'       => 500000,
    'priceLabel'      => 'Price Range (PKR)',
    'showArea'        => true,
    'areaMax'         => 500,
    'defaultAreaUnit' => 'marla',
    'showBedrooms'    => false,
    'showFeatures'    => false,
    'featureOptions'  => [],
    'showFloor'       => false,
    'allCities'       => [],
    'selected'        => [],
    'extraHidden'     => [],
], $filter);

$sel = array_merge([
    'purpose'   => '',
    'city'      => '',
    'type'      => '',
    'minPrice'  => 0,
    'maxPrice'  => 0,
    'minArea'   => 0,
    'maxArea'   => 0,
    'areaUnit'  => $f['defaultAreaUnit'],
    'bedrooms'  => 0,
    'features'  => [],
    'floor'     => 0,
], $f['selected']);

/* Type can be array (type[]) or single string — normalise for compare */
$selectedTypes = is_array($sel['type']) ? $sel['type'] : ($sel['type'] ? [$sel['type']] : []);

$uid = $f['formId']; // used to namespace element ids so desktop + offcanvas coexist
?>
<form method="GET"
      action="<?= htmlspecialchars($f['formAction']) ?>"
      id="<?= htmlspecialchars($f['formId']) ?>"
      class="filter-sidebar<?= $f['formId'] === 'filterFormMobile' ? ' is-mobile' : '' ?>"
      autocomplete="off">

    <?php /* ── Hidden inputs ── */ ?>
    <?php if ($f['category']): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($f['category']) ?>">
    <?php endif; ?>
    <?php if ($f['lockedPurpose']): ?>
        <input type="hidden" name="purpose" value="<?= htmlspecialchars($f['lockedPurpose']) ?>">
    <?php endif; ?>
    <?php foreach ($f['extraHidden'] as $hkey => $hval): ?>
        <input type="hidden" name="<?= htmlspecialchars($hkey) ?>" value="<?= htmlspecialchars($hval) ?>">
    <?php endforeach; ?>

    <?php /* ── Header ── */ ?>
    <div class="filter-head">
        <div class="filter-head-title">
            <i class="fa-solid fa-sliders"></i>
            <span>Filters</span>
        </div>
        <button type="button" class="filter-reset-btn" data-filter-reset>
            <i class="fa-solid fa-rotate-right"></i> Reset
        </button>
    </div>

    <?php /* ── Purpose (Sale / Rent) ── */ ?>
    <?php if ($f['showPurpose'] && !$f['lockedPurpose']): ?>
    <div class="filter-group">
        <div class="filter-label">Purpose</div>
        <div class="filter-segment">
            <label>
                <input type="radio" name="purpose" value=""
                       <?= $sel['purpose'] === '' ? 'checked' : '' ?>>
                <span>All</span>
            </label>
            <label>
                <input type="radio" name="purpose" value="sale"
                       <?= $sel['purpose'] === 'sale' ? 'checked' : '' ?>>
                <span>Sale</span>
            </label>
            <label>
                <input type="radio" name="purpose" value="rent"
                       <?= $sel['purpose'] === 'rent' ? 'checked' : '' ?>>
                <span>Rent</span>
            </label>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── City ── */ ?>
    <div class="filter-group">
        <div class="filter-label">City</div>
        <div class="filter-field-wrap">
            <i class="fa-solid fa-city filter-field-icon" aria-hidden="true"></i>
            <input type="text" name="city" class="filter-select with-icon"
                   list="pkCityOptionsSidebar" autocomplete="off"
                   placeholder="All Cities — type to search"
                   value="<?= htmlspecialchars($sel['city'] ?? '') ?>">
            <datalist id="pkCityOptionsSidebar">
                <?php foreach (function_exists('getPakistanCities') ? getPakistanCities() : (array)$f['allCities'] as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
    </div>

    <?php /* ── Property Type (chip checkboxes) ── */ ?>
    <?php if ($f['showType'] && !empty($f['typeOptions'])): ?>
    <div class="filter-group">
        <div class="filter-label">Property Type</div>
        <div class="filter-chips">
            <?php foreach ($f['typeOptions'] as $val => $label): ?>
                <?php $checked = in_array($val, $selectedTypes, true); ?>
                <label class="filter-chip <?= $checked ? 'is-on' : '' ?>">
                    <input type="checkbox" name="type[]" value="<?= htmlspecialchars($val) ?>"
                           <?= $checked ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Price ── */ ?>
    <?php if ($f['showPrice']): ?>
    <div class="filter-group">
        <div class="filter-label"><?= htmlspecialchars($f['priceLabel']) ?></div>
        <div class="filter-range-row">
            <input type="number" class="filter-input" name="min_price"
                   id="<?= $uid ?>_priceMin"
                   placeholder="Min"
                   min="0" step="<?= (int)$f['priceStep'] ?>"
                   value="<?= $sel['minPrice'] > 0 ? (int)$sel['minPrice'] : '' ?>">
            <span class="filter-range-sep">—</span>
            <input type="number" class="filter-input" name="max_price"
                   id="<?= $uid ?>_priceMax"
                   placeholder="Max"
                   min="0" step="<?= (int)$f['priceStep'] ?>"
                   value="<?= $sel['maxPrice'] > 0 ? (int)$sel['maxPrice'] : '' ?>">
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Area ── */ ?>
    <?php if ($f['showArea']): ?>
    <div class="filter-group">
        <div class="filter-label">Area</div>
        <div class="filter-range-row">
            <input type="number" class="filter-input" name="min_area"
                   id="<?= $uid ?>_areaMin"
                   placeholder="Min"
                   min="0" step="1"
                   value="<?= $sel['minArea'] > 0 ? $sel['minArea'] : '' ?>">
            <span class="filter-range-sep">—</span>
            <input type="number" class="filter-input" name="max_area"
                   id="<?= $uid ?>_areaMax"
                   placeholder="Max"
                   min="0" step="1"
                   value="<?= $sel['maxArea'] > 0 ? $sel['maxArea'] : '' ?>">
        </div>
        <div class="filter-unit-row">
            <?php
                $units = [
                    'marla'   => 'Marla',
                    'kanal'   => 'Kanal',
                    'sq_ft'   => 'Sq Ft',
                    'sq_yard' => 'Sq Yd',
                ];
                foreach ($units as $uVal => $uLbl):
                    $checked = $sel['areaUnit'] === $uVal;
            ?>
                <label class="filter-chip-sm <?= $checked ? 'is-on' : '' ?>">
                    <input type="radio" name="area_unit" value="<?= $uVal ?>"
                           <?= $checked ? 'checked' : '' ?>>
                    <span><?= $uLbl ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Bedrooms ── */ ?>
    <?php if ($f['showBedrooms']): ?>
    <div class="filter-group">
        <div class="filter-label">Bedrooms</div>
        <input type="hidden" name="bedrooms" id="<?= $uid ?>_bedroomsInput"
               value="<?= $sel['bedrooms'] > 0 ? (int)$sel['bedrooms'] : '' ?>" data-filter-bed-input>
        <div class="filter-bed-row" data-filter-bed-group>
            <?php foreach ([1,2,3,4,5] as $bOpt):
                $label = $bOpt === 5 ? '5+' : (string)$bOpt;
                $active = (int)$sel['bedrooms'] === $bOpt;
            ?>
                <button type="button"
                        class="filter-bed-btn <?= $active ? 'is-on' : '' ?>"
                        data-beds="<?= $bOpt ?>">
                    <?= $label ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Floor (commercial) ── */ ?>
    <?php if ($f['showFloor']): ?>
    <div class="filter-group">
        <div class="filter-label">Floor Number</div>
        <input type="number" class="filter-input" name="floor"
               placeholder="e.g. 2" min="0" max="50"
               value="<?= $sel['floor'] > 0 ? (int)$sel['floor'] : '' ?>">
    </div>
    <?php endif; ?>

    <?php /* ── Features ── */ ?>
    <?php if ($f['showFeatures'] && !empty($f['featureOptions'])): ?>
    <div class="filter-group">
        <div class="filter-label">Features</div>
        <div class="filter-chips">
            <?php foreach ($f['featureOptions'] as $val => $label):
                $checked = in_array($val, $sel['features'], true);
            ?>
                <label class="filter-chip <?= $checked ? 'is-on' : '' ?>">
                    <input type="checkbox" name="features[]" value="<?= htmlspecialchars($val) ?>"
                           <?= $checked ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php /* Noscript fallback: a submit button so the form still works without JS */ ?>
    <noscript>
        <div class="filter-group">
            <button type="submit" class="btn-navy w-100" style="justify-content:center;">
                Apply Filters
            </button>
        </div>
    </noscript>
</form>
