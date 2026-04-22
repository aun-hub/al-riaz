/**
 * Al-Riaz Associates — Listings JS
 * Used by: residential.php, commercial.php, rent.php, search.php
 *
 * Dependencies: jQuery 3.7.1, Bootstrap 5.3.2 (loaded via footer)
 */

(function ($) {
    'use strict';

    // ─────────────────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────────────────
    var DEBOUNCE_MS   = 400;
    var FILTER_FORM   = '#filterForm';
    var RESULTS_GRID  = '#propertiesGrid';
    var LOAD_MORE_BTN = '#loadMoreBtn';
    var CHIPS_AREA    = '#activeFilterChips';
    var VIEW_KEY      = 'alriaz_listing_view'; // localStorage key

    // ─────────────────────────────────────────────────────────────────────────
    // Utility: debounce
    // ─────────────────────────────────────────────────────────────────────────
    function debounce(fn, delay) {
        var timer;
        return function () {
            var ctx  = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utility: format PKR for JS display
    // ─────────────────────────────────────────────────────────────────────────
    function formatPKR(n) {
        n = parseFloat(n) || 0;
        if (n >= 10000000) {
            var c = n / 10000000;
            return (Math.round(c * 100) / 100) + ' Crore';
        }
        if (n >= 100000) {
            var l = n / 100000;
            return (Math.round(l * 100) / 100) + ' Lakh';
        }
        return 'PKR ' + Math.round(n).toLocaleString();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utility: format Area for JS display
    // ─────────────────────────────────────────────────────────────────────────
    function formatArea(n, unit) {
        var labels = {
            marla:    'Marla',
            kanal:    'Kanal',
            sq_ft:    'Sq Ft',
            sq_yard:  'Sq Yard',
            acre:     'Acre'
        };
        var label = labels[unit] || unit;
        return (parseFloat(n) || 0).toLocaleString() + ' ' + label;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utility: build query string from form data, merging with current URL
    // ─────────────────────────────────────────────────────────────────────────
    function buildFilterURL(extraParams) {
        var params  = new URLSearchParams(window.location.search);
        var $form   = $(FILTER_FORM);

        if ($form.length) {
            // Reset current filter params from the form
            var formData = $form.serializeArray();

            // Collect checkbox names to wipe them first (multi-value)
            var checkboxNames = [];
            $form.find('input[type="checkbox"]').each(function () {
                checkboxNames.push(this.name);
            });
            // Remove old multi-value params
            $.each(checkboxNames, function (_, name) { params.delete(name); });

            // Reset page when filters change
            params.delete('page');

            // Apply all form fields
            $.each(formData, function (_, field) {
                if (field.value !== '') {
                    // For checkbox arrays (e.g. features[], type[])
                    if (field.name.endsWith('[]')) {
                        params.append(field.name, field.value);
                    } else {
                        params.set(field.name, field.value);
                    }
                } else {
                    params.delete(field.name);
                }
            });
        }

        if (extraParams) {
            $.each(extraParams, function (key, val) {
                if (val === null || val === '') {
                    params.delete(key);
                } else {
                    params.set(key, val);
                }
            });
        }

        return window.location.pathname + '?' + params.toString();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utility: save + restore scroll position
    // ─────────────────────────────────────────────────────────────────────────
    function saveScroll() {
        sessionStorage.setItem('alriaz_scroll_' + window.location.pathname, window.scrollY);
    }

    function restoreScroll() {
        var key = 'alriaz_scroll_' + window.location.pathname;
        var y   = parseInt(sessionStorage.getItem(key) || '0', 10);
        if (y > 0) {
            window.scrollTo(0, y);
            sessionStorage.removeItem(key);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Filter form handling — debounced auto-submit on change
    // ─────────────────────────────────────────────────────────────────────────
    function initFilterForm() {
        var $form = $(FILTER_FORM);
        if (!$form.length) return;

        var navigate = debounce(function () {
            saveScroll();
            window.location.href = buildFilterURL();
        }, DEBOUNCE_MS);

        // Auto-submit on select or checkbox changes
        $form.on('change', 'select, input[type="checkbox"], input[type="radio"]', navigate);

        // Apply Filters button
        $form.on('submit', function (e) {
            e.preventDefault();
            saveScroll();
            window.location.href = buildFilterURL();
        });

        // Reset button
        $(document).on('click', '#resetFiltersBtn', function (e) {
            e.preventDefault();
            sessionStorage.removeItem('alriaz_scroll_' + window.location.pathname);
            window.location.href = window.location.pathname;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Price range slider
    // ─────────────────────────────────────────────────────────────────────────
    function initPriceRangeSlider() {
        var $minInput  = $('#priceMin');
        var $maxInput  = $('#priceMax');
        var $minSlider = $('#priceMinSlider');
        var $maxSlider = $('#priceMaxSlider');
        var $minLabel  = $('#priceMinLabel');
        var $maxLabel  = $('#priceMaxLabel');

        if (!$minSlider.length && !$minInput.length) return;

        function updatePriceLabels() {
            if ($minLabel.length) $minLabel.text(formatPKR($minSlider.val() || $minInput.val() || 0));
            if ($maxLabel.length) $maxLabel.text(formatPKR($maxSlider.val() || $maxInput.val() || 0));
        }

        // Slider → input
        $minSlider.on('input', function () {
            $minInput.val(this.value);
            updatePriceLabels();
        });
        $maxSlider.on('input', function () {
            $maxInput.val(this.value);
            updatePriceLabels();
        });

        // Input → slider
        $minInput.on('input', function () {
            $minSlider.val(this.value);
            updatePriceLabels();
        });
        $maxInput.on('input', function () {
            $maxSlider.val(this.value);
            updatePriceLabels();
        });

        updatePriceLabels();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Area range slider
    // ─────────────────────────────────────────────────────────────────────────
    function initAreaRangeSlider() {
        var $minInput  = $('#areaMin');
        var $maxInput  = $('#areaMax');
        var $minSlider = $('#areaMinSlider');
        var $maxSlider = $('#areaMaxSlider');
        var $minLabel  = $('#areaMinLabel');
        var $maxLabel  = $('#areaMaxLabel');
        var $unitSel   = $('#areaUnit');

        if (!$minSlider.length && !$minInput.length) return;

        function getUnit() {
            return $unitSel.length ? $unitSel.val() : 'marla';
        }

        function updateAreaLabels() {
            var unit = getUnit();
            if ($minLabel.length) $minLabel.text(formatArea($minSlider.val() || $minInput.val() || 0, unit));
            if ($maxLabel.length) $maxLabel.text(formatArea($maxSlider.val() || $maxInput.val() || 0, unit));
        }

        $minSlider.on('input', function () {
            $minInput.val(this.value);
            updateAreaLabels();
        });
        $maxSlider.on('input', function () {
            $maxInput.val(this.value);
            updateAreaLabels();
        });
        $minInput.on('input', function () {
            $minSlider.val(this.value);
            updateAreaLabels();
        });
        $maxInput.on('input', function () {
            $maxSlider.val(this.value);
            updateAreaLabels();
        });
        $unitSel.on('change', updateAreaLabels);

        updateAreaLabels();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Sort — immediate reload on change
    // ─────────────────────────────────────────────────────────────────────────
    function initSort() {
        $(document).on('change', '#sortSelect', function () {
            saveScroll();
            window.location.href = buildFilterURL({ sort: $(this).val(), page: null });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. View toggle — Grid / List (localStorage)
    // ─────────────────────────────────────────────────────────────────────────
    function applyView(view) {
        var $grid = $(RESULTS_GRID);
        if (!$grid.length) return;

        if (view === 'list') {
            $grid.addClass('listing-view-list').removeClass('listing-view-grid');
            $('#viewGrid').removeClass('active');
            $('#viewList').addClass('active');
        } else {
            $grid.addClass('listing-view-grid').removeClass('listing-view-list');
            $('#viewGrid').addClass('active');
            $('#viewList').removeClass('active');
        }
    }

    function initViewToggle() {
        var saved = localStorage.getItem(VIEW_KEY) || 'grid';
        applyView(saved);

        $(document).on('click', '#viewGrid', function () {
            localStorage.setItem(VIEW_KEY, 'grid');
            applyView('grid');
        });

        $(document).on('click', '#viewList', function () {
            localStorage.setItem(VIEW_KEY, 'list');
            applyView('list');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Load More / infinite scroll via AJAX
    // ─────────────────────────────────────────────────────────────────────────
    function initLoadMore() {
        var $btn = $(LOAD_MORE_BTN);
        if (!$btn.length) return;

        var currentPage = parseInt($btn.data('current-page') || 1, 10);
        var totalPages  = parseInt($btn.data('total-pages')  || 1, 10);
        var isLoading   = false;

        function loadMore() {
            if (isLoading || currentPage >= totalPages) return;
            isLoading = true;
            currentPage++;

            var params = new URLSearchParams(window.location.search);
            params.set('page', currentPage);
            params.set('ajax', '1');

            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span>Loading...'
            );

            $.get('/api/v1/properties.php?' + params.toString())
                .done(function (data) {
                    if (data && data.html) {
                        $(RESULTS_GRID).append(data.html);
                        initLazyLoad();     // Re-init lazy for new cards
                        initCardEnhancements(); // Re-init enhancements
                    }
                    if (currentPage >= totalPages || !data.has_more) {
                        $btn.hide();
                    } else {
                        $btn.prop('disabled', false).html('<i class="fas fa-chevron-down me-2"></i>Load More Properties');
                    }
                    $btn.data('current-page', currentPage);
                })
                .fail(function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-chevron-down me-2"></i>Load More Properties');
                })
                .always(function () { isLoading = false; });
        }

        $btn.on('click', loadMore);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Active filter chips
    // ─────────────────────────────────────────────────────────────────────────
    var CHIP_LABELS = {
        purpose:    { label: 'Purpose',     valueMap: { sale: 'For Sale', rent: 'For Rent' } },
        city:       { label: 'City' },
        type:       { label: 'Type' },
        bedrooms:   { label: 'Beds' },
        min_price:  { label: 'Min Price',   format: 'pkr' },
        max_price:  { label: 'Max Price',   format: 'pkr' },
        min_area:   { label: 'Min Area',    format: 'area' },
        max_area:   { label: 'Max Area',    format: 'area' },
        q:          { label: 'Search' },
        category:   { label: 'Category' }
    };

    var SKIP_PARAMS = ['page', 'limit', 'area_unit', 'sort', 'ajax'];

    function buildChips() {
        var $area = $(CHIPS_AREA);
        if (!$area.length) return;

        var params   = new URLSearchParams(window.location.search);
        var areaUnit = params.get('area_unit') || 'marla';
        var chips    = [];

        params.forEach(function (value, key) {
            if (SKIP_PARAMS.indexOf(key) !== -1 || value === '') return;

            var conf  = CHIP_LABELS[key] || { label: key };
            var display = value;

            if (conf.valueMap && conf.valueMap[value]) {
                display = conf.valueMap[value];
            } else if (conf.format === 'pkr') {
                display = formatPKR(value);
            } else if (conf.format === 'area') {
                display = formatArea(value, areaUnit);
            } else {
                // Humanise snake_case
                display = value.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
            }

            chips.push({ key: key, value: value, text: conf.label + ': ' + display });
        });

        if (!chips.length) {
            $area.empty().addClass('d-none');
            return;
        }

        $area.removeClass('d-none').empty();
        $.each(chips, function (_, chip) {
            var $chip = $('<span>', {
                class: 'badge rounded-pill filter-chip me-2 mb-2',
                html: chip.text + ' <button type="button" class="btn-close btn-close-sm ms-1 chip-remove" aria-label="Remove filter" data-key="' + chip.key + '" data-value="' + encodeURIComponent(chip.value) + '"></button>'
            });
            $area.append($chip);
        });
    }

    function initChipRemove() {
        $(document).on('click', '.chip-remove', function () {
            var key    = $(this).data('key');
            var value  = decodeURIComponent($(this).data('value'));
            var params = new URLSearchParams(window.location.search);

            if (key.endsWith('[]')) {
                // Multi-value: only remove this specific value
                var vals = params.getAll(key).filter(function (v) { return v !== value; });
                params.delete(key);
                vals.forEach(function (v) { params.append(key, v); });
            } else {
                params.delete(key);
            }

            params.delete('page');
            saveScroll();
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. URL sync — already handled through buildFilterURL + GET form
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Mobile filter offcanvas
    // ─────────────────────────────────────────────────────────────────────────
    function initMobileFilter() {
        // "Apply" button inside offcanvas
        $(document).on('click', '#offcanvasApplyFilters', function () {
            var offcanvasEl = document.getElementById('filterOffcanvas');
            if (offcanvasEl) {
                var bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (bsOffcanvas) bsOffcanvas.hide();
            }
            saveScroll();
            window.location.href = buildFilterURL();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10a. Lazy load images via IntersectionObserver
    // ─────────────────────────────────────────────────────────────────────────
    function initLazyLoad() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: load all
            $('img[data-src]').each(function () {
                $(this).attr('src', $(this).data('src')).removeAttr('data-src');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        delete img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('lazy-loaded');
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '200px 0px' });

        $('img[data-src]').each(function () {
            observer.observe(this);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10b. WhatsApp quick-contact on hover + bookmark (heart) toggle
    // ─────────────────────────────────────────────────────────────────────────
    function initCardEnhancements() {
        // Bookmark / save toggle
        var saved = JSON.parse(localStorage.getItem('alriaz_saved') || '[]');

        function isSaved(id) {
            return saved.indexOf(String(id)) !== -1;
        }

        function toggleSave(id) {
            id = String(id);
            var idx = saved.indexOf(id);
            if (idx === -1) {
                saved.push(id);
            } else {
                saved.splice(idx, 1);
            }
            localStorage.setItem('alriaz_saved', JSON.stringify(saved));
            return idx === -1; // true = just saved
        }

        // Apply saved state on load
        $('.btn-bookmark[data-id]').each(function () {
            if (isSaved($(this).data('id'))) {
                $(this).addClass('saved').find('i').removeClass('fa-regular').addClass('fas');
            }
        });

        // Toggle on click
        $(document).off('click.alriaz-bookmark').on('click.alriaz-bookmark', '.btn-bookmark', function (e) {
            e.preventDefault();
            var $btn  = $(this);
            var id    = $btn.data('id');
            var nowSaved = toggleSave(id);
            $btn.toggleClass('saved', nowSaved);
            $btn.find('i').toggleClass('fa-regular', !nowSaved).toggleClass('fas', nowSaved);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Purpose tab switching (sale / rent)
    // ─────────────────────────────────────────────────────────────────────────
    function initPurposeTabs() {
        $(document).on('click', '.purpose-tab', function (e) {
            e.preventDefault();
            var purpose = $(this).data('purpose');
            var params  = new URLSearchParams(window.location.search);
            params.set('purpose', purpose);
            params.delete('page');
            saveScroll();
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Bedroom button group
    // ─────────────────────────────────────────────────────────────────────────
    function initBedroomBtns() {
        $(document).on('click', '.bed-btn', function () {
            var $btn = $(this);
            var val  = $btn.data('beds');
            var $input = $('#bedroomsInput');

            // Toggle — click active btn to deselect
            if ($btn.hasClass('active')) {
                $btn.removeClass('active');
                $input.val('');
            } else {
                $('.bed-btn').removeClass('active');
                $btn.addClass('active');
                $input.val(val);
            }
        });

        // Restore active state from URL
        var params = new URLSearchParams(window.location.search);
        var beds   = params.get('bedrooms');
        if (beds) {
            $('.bed-btn[data-beds="' + beds + '"]').addClass('active');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Init
    // ─────────────────────────────────────────────────────────────────────────
    $(function () {
        restoreScroll();
        initFilterForm();
        initPriceRangeSlider();
        initAreaRangeSlider();
        initSort();
        initViewToggle();
        initLoadMore();
        buildChips();
        initChipRemove();
        initMobileFilter();
        initLazyLoad();
        initCardEnhancements();
        initPurposeTabs();
        initBedroomBtns();
    });

}(jQuery));
