<?php
/**
 * Residential / Rent filter sidebar partial.
 * Variables expected from parent:
 *   $purpose, $city, $type, $minPrice, $maxPrice, $minArea, $maxArea,
 *   $areaUnit, $bedrooms, $features, $sort, $allCities
 *   $formAction  (string) — defaults to '/residential.php'
 *   $lockPurpose (bool)   — hides purpose radio (used by rent.php)
 */

$formAction  = $formAction  ?? '/residential.php';
$lockPurpose = $lockPurpose ?? false;

$resTypes = [
    'house'         => 'House',
    'flat'          => 'Flat / Apartment',
    'upper_portion' => 'Upper Portion',
    'lower_portion' => 'Lower Portion',
    'apartment'     => 'Apartment',
    'farmhouse'     => 'Farmhouse',
    'penthouse'     => 'Penthouse',
    'plot'          => 'Plot',
];

$featureOptions = [
    'parking'          => 'Parking',
    'gas'              => 'Gas',
    'electricity'      => 'Electricity',
    'furnished'        => 'Furnished',
    'corner'           => 'Corner Plot',
    'boundary_wall'    => 'Boundary Wall',
    'servant_quarters' => 'Servant Quarters',
    'garden'           => 'Garden',
];
?>
<form method="GET" action="<?= htmlspecialchars($formAction) ?>" id="filterForm" class="filter-sidebar">

    <!-- Hidden: keep category -->
    <?php if (!$lockPurpose): ?>
        <input type="hidden" name="category" value="residential">
    <?php else: ?>
        <input type="hidden" name="category" value="residential">
        <input type="hidden" name="purpose"  value="rent">
    <?php endif; ?>

    <!-- Purpose -->
    <?php if (!$lockPurpose): ?>
    <div class="filter-section">
        <div class="filter-section-title">Purpose</div>
        <div class="d-flex gap-2">
            <label class="flex-fill">
                <input type="radio" name="purpose" value="sale" class="btn-check"
                       id="purposeSale" <?= ($purpose === '' || $purpose === 'sale') ? 'checked' : '' ?>>
                <span class="btn btn-sm btn-outline-success w-100 d-block">For Sale</span>
            </label>
            <label class="flex-fill">
                <input type="radio" name="purpose" value="rent" class="btn-check"
                       id="purposeRent" <?= $purpose === 'rent' ? 'checked' : '' ?>>
                <span class="btn btn-sm btn-outline-warning w-100 d-block">For Rent</span>
            </label>
        </div>
    </div>
    <?php endif; ?>

    <!-- City -->
    <div class="filter-section">
        <div class="filter-section-title">City</div>
        <select name="city" class="form-select form-select-sm">
            <option value="">All Cities</option>
            <?php foreach ($allCities as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Property Type -->
    <div class="filter-section">
        <div class="filter-section-title">Property Type</div>
        <?php foreach ($resTypes as $val => $label): ?>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="type[]"
                       value="<?= htmlspecialchars($val) ?>"
                       id="type_<?= $val ?>"
                       <?= (is_array($_GET['type'] ?? null) && in_array($val, $_GET['type'])) || $type === $val ? 'checked' : '' ?>>
                <label class="form-check-label small" for="type_<?= $val ?>">
                    <?= htmlspecialchars($label) ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Price Range -->
    <div class="filter-section">
        <div class="filter-section-title">Price Range (PKR)</div>
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span id="priceMinLabel">Any</span>
            <span id="priceMaxLabel">Any</span>
        </div>
        <input type="range" class="form-range mb-2" id="priceMinSlider"
               min="0" max="100000000" step="500000"
               value="<?= $minPrice ?>">
        <input type="range" class="form-range mb-2" id="priceMaxSlider"
               min="0" max="100000000" step="500000"
               value="<?= $maxPrice ?: 100000000 ?>">
        <div class="row g-2 mt-1">
            <div class="col-6">
                <input type="number" class="form-control form-control-sm" id="priceMin"
                       name="min_price" placeholder="Min" value="<?= $minPrice ?: '' ?>">
            </div>
            <div class="col-6">
                <input type="number" class="form-control form-control-sm" id="priceMax"
                       name="max_price" placeholder="Max" value="<?= $maxPrice ?: '' ?>">
            </div>
        </div>
    </div>

    <!-- Area Range -->
    <div class="filter-section">
        <div class="filter-section-title">Area</div>
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span id="areaMinLabel">Any</span>
            <span id="areaMaxLabel">Any</span>
        </div>
        <input type="range" class="form-range mb-2" id="areaMinSlider"
               min="0" max="500" step="1" value="<?= $minArea ?>">
        <input type="range" class="form-range mb-2" id="areaMaxSlider"
               min="0" max="500" step="1" value="<?= $maxArea ?: 500 ?>">
        <div class="row g-2 mt-1">
            <div class="col-4">
                <select name="area_unit" class="form-select form-select-sm" id="areaUnit">
                    <option value="marla"   <?= $areaUnit === 'marla'   ? 'selected' : '' ?>>Marla</option>
                    <option value="kanal"   <?= $areaUnit === 'kanal'   ? 'selected' : '' ?>>Kanal</option>
                    <option value="sq_ft"   <?= $areaUnit === 'sq_ft'   ? 'selected' : '' ?>>Sq Ft</option>
                    <option value="sq_yard" <?= $areaUnit === 'sq_yard' ? 'selected' : '' ?>>Sq Yd</option>
                </select>
            </div>
            <div class="col-4">
                <input type="number" class="form-control form-control-sm" id="areaMin"
                       name="min_area" placeholder="Min" value="<?= $minArea ?: '' ?>">
            </div>
            <div class="col-4">
                <input type="number" class="form-control form-control-sm" id="areaMax"
                       name="max_area" placeholder="Max" value="<?= $maxArea ?: '' ?>">
            </div>
        </div>
    </div>

    <!-- Bedrooms -->
    <div class="filter-section">
        <div class="filter-section-title">Bedrooms</div>
        <input type="hidden" name="bedrooms" id="bedroomsInput" value="<?= $bedrooms ?: '' ?>">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ([1,2,3,4,'5+'] as $b): ?>
                <?php $bVal = $b === '5+' ? 5 : $b; ?>
                <button type="button" class="bed-btn <?= $bedrooms === (int)$bVal ? 'active' : '' ?>"
                        data-beds="<?= $bVal ?>"><?= $b ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Features -->
    <div class="filter-section">
        <div class="filter-section-title">Features</div>
        <?php foreach ($featureOptions as $fVal => $fLabel): ?>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="features[]"
                       value="<?= htmlspecialchars($fVal) ?>" id="feat_<?= $fVal ?>"
                       <?= in_array($fVal, $features, true) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="feat_<?= $fVal ?>">
                    <?= htmlspecialchars($fLabel) ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Buttons -->
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-gold btn-sm">
            <i class="fas fa-search me-1"></i>Apply Filters
        </button>
        <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-redo me-1"></i>Reset
        </button>
    </div>

</form>
