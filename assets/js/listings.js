/**
 * Al-Riaz Associates — Listings JS
 * Used by: residential.php, commercial.php, rent.php, search.php
 *
 * - All filters auto-apply. No explicit "Apply" button.
 * - Text/number inputs debounce 500ms; selects / checkboxes / radios submit immediately.
 * - Bedroom buttons toggle a hidden input and trigger submit.
 * - Reset button clears every filter but preserves `tab` / `purpose` (if locked).
 *
 * Dependencies: jQuery 3.7.1, Bootstrap 5.3.2
 */
(function ($) {
    'use strict';

    var DEBOUNCE_MS   = 500;
    var RESULTS_GRID  = '#propertiesGrid';
    var LOAD_MORE_BTN = '#loadMoreBtn';
    var CHIPS_AREA    = '#activeFilterChips';
    var VIEW_KEY      = 'alriaz_listing_view';

    /* ─── utilities ──────────────────────────────────────────────────── */
    function debounce(fn, delay) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    function formatPKR(n) {
        n = parseFloat(n) || 0;
        if (n >= 10000000) return (Math.round((n / 10000000) * 100) / 100) + ' Crore';
        if (n >= 100000)   return (Math.round((n / 100000)   * 100) / 100) + ' Lakh';
        return 'PKR ' + Math.round(n).toLocaleString();
    }

    function formatArea(n, unit) {
        var labels = { marla:'Marla', kanal:'Kanal', sq_ft:'Sq Ft', sq_yard:'Sq Yard', acre:'Acre' };
        return (parseFloat(n) || 0).toLocaleString() + ' ' + (labels[unit] || unit);
    }

    function saveScroll() {
        sessionStorage.setItem('alriaz_scroll_' + window.location.pathname, window.scrollY);
    }
    function restoreScroll() {
        var key = 'alriaz_scroll_' + window.location.pathname;
        var y   = parseInt(sessionStorage.getItem(key) || '0', 10);
        if (y > 0) { window.scrollTo(0, y); sessionStorage.removeItem(key); }
    }

    /* ─── preserve focus + caret across the auto-submit reload ────────── */
    var FOCUS_KEY = 'alriaz_filter_focus_' + window.location.pathname;
    function saveFocus() {
        var el = document.activeElement;
        if (!el || !el.name) return;
        var tag = (el.tagName || '').toLowerCase();
        if (tag !== 'input' && tag !== 'textarea') return;
        // Only meaningful for typeable inputs.
        var t = (el.type || '').toLowerCase();
        if (['text','search','number','email','tel','url','password','textarea'].indexOf(t) === -1) return;
        var caret = -1;
        try { caret = el.selectionStart; } catch (e) { /* some types don't support selection */ }
        sessionStorage.setItem(FOCUS_KEY, JSON.stringify({
            name:   el.name,
            formId: el.form ? el.form.id : '',
            caret:  caret,
        }));
    }
    function restoreFocus() {
        var raw = sessionStorage.getItem(FOCUS_KEY);
        if (!raw) return;
        sessionStorage.removeItem(FOCUS_KEY);
        var data;
        try { data = JSON.parse(raw); } catch (e) { return; }
        if (!data || !data.name) return;
        var sel = '[name="' + data.name.replace(/"/g, '\\"') + '"]';
        if (data.formId) sel = '#' + data.formId + ' ' + sel;
        var el = document.querySelector(sel);
        if (!el) return;
        el.focus();
        if (typeof data.caret === 'number' && data.caret >= 0) {
            try { el.setSelectionRange(data.caret, data.caret); } catch (e) { /* not supported */ }
        }
    }

    /* ─── build query from ANY filter form on the page ───────────────── */
    function formURL($form) {
        if (!$form || !$form.length) return window.location.pathname;

        var params = new URLSearchParams();
        var data   = $form.serializeArray();

        $.each(data, function (_, f) {
            if (f.value === '' || f.value == null) return;
            if (f.name.endsWith('[]')) {
                params.append(f.name, f.value);
            } else {
                params.set(f.name, f.value);
            }
        });
        params.delete('page');

        return ($form.attr('action') || window.location.pathname) + '?' + params.toString();
    }

    /* ─── busy overlay on the results grid while we navigate ────────── */
    function showBusy() {
        var $grid = $(RESULTS_GRID);
        if (!$grid.length) return;
        if (!$grid.next('.listings-busy').length) {
            $grid.after('<div class="listings-busy"><div class="spinner"></div></div>');
        }
        $grid.addClass('is-busy');
    }

    /* ─── dynamic auto-submit wiring ─────────────────────────────────── */
    function initFilterForm($form) {
        if (!$form || !$form.length) return;

        var go = function () {
            showBusy();
            saveScroll();
            saveFocus();
            window.location.href = formURL($form);
        };
        var goDebounced = debounce(go, DEBOUNCE_MS);

        // Immediate: selects, checkboxes, radios
        $form.on('change', 'select, input[type="checkbox"], input[type="radio"]', go);

        // Datalist-backed inputs (e.g. city): only submit when the typed value
        // matches a datalist option exactly (user picked a suggestion) or is
        // cleared. Prevents partial typing like "Lah" from triggering a search
        // that returns zero results before the user finishes selecting "Lahore".
        function datalistMatches($input) {
            var value = $.trim($input.val() || '');
            if (value === '') return true;
            var listId = $input.attr('list');
            if (!listId) return true;
            var matched = false;
            $('#' + listId).find('option').each(function () {
                if (this.value === value) { matched = true; return false; }
            });
            return matched;
        }

        $form.on('input', 'input[list]', function () {
            if (datalistMatches($(this))) go();
        });
        $form.on('change', 'input[list]', function () {
            if (datalistMatches($(this))) go();
        });

        // Debounced: text / number inputs — input fires on every keystroke.
        // Excludes datalist-backed inputs (handled above).
        $form.on('input', 'input[type="text"]:not([list]), input[type="number"], input[type="search"]:not([list])', goDebounced);

        // Visual chip state
        $form.on('change', '.filter-chip input[type="checkbox"], .filter-chip input[type="radio"], .filter-chip-sm input[type="radio"]', function () {
            var $lbl = $(this).closest('.filter-chip, .filter-chip-sm');
            if (this.type === 'radio') {
                $form.find('.filter-chip-sm').removeClass('is-on');
                $lbl.toggleClass('is-on', this.checked);
            } else {
                $lbl.toggleClass('is-on', this.checked);
            }
        });

        // Prevent accidental Enter-submit scrolling
        $form.on('submit', function (e) { e.preventDefault(); go(); });

        // Bedroom pill buttons
        $form.on('click', '.filter-bed-btn', function () {
            var $btn   = $(this);
            var val    = String($btn.data('beds') || '');
            var $input = $form.find('[data-filter-bed-input]');
            var $group = $btn.closest('[data-filter-bed-group]');

            if ($btn.hasClass('is-on')) {
                $btn.removeClass('is-on');
                $input.val('');
            } else {
                $group.find('.filter-bed-btn').removeClass('is-on');
                $btn.addClass('is-on');
                $input.val(val);
            }
            go();
        });

        // Reset
        $form.on('click', '[data-filter-reset]', function (e) {
            e.preventDefault();
            var keep = {};
            $form.find('input[type="hidden"]').each(function () {
                keep[this.name] = this.value;
            });
            showBusy();
            sessionStorage.removeItem('alriaz_scroll_' + window.location.pathname);
            var qs = new URLSearchParams(keep).toString();
            window.location.href = ($form.attr('action') || window.location.pathname) + (qs ? '?' + qs : '');
        });
    }

    /* ─── sort select (top of results bar) ───────────────────────────── */
    function initSort() {
        $(document).on('change', '#sortSelect', function () {
            var params = new URLSearchParams(window.location.search);
            params.set('sort', $(this).val());
            params.delete('page');
            saveScroll();
            showBusy();
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    /* ─── grid / list view ───────────────────────────────────────────── */
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
        applyView(localStorage.getItem(VIEW_KEY) || 'grid');
        $(document).on('click', '#viewGrid', function () { localStorage.setItem(VIEW_KEY, 'grid'); applyView('grid'); });
        $(document).on('click', '#viewList', function () { localStorage.setItem(VIEW_KEY, 'list'); applyView('list'); });
    }

    /* ─── active filter chips (above results) ────────────────────────── */
    var CHIP_LABELS = {
        purpose:   { label: 'Purpose',   valueMap: { sale: 'For Sale', rent: 'For Rent' } },
        city:      { label: 'City' },
        type:      { label: 'Type' },
        bedrooms:  { label: 'Beds' },
        min_price: { label: 'Min Price', format: 'pkr' },
        max_price: { label: 'Max Price', format: 'pkr' },
        min_area:  { label: 'Min Area',  format: 'area' },
        max_area:  { label: 'Max Area',  format: 'area' },
        floor:     { label: 'Floor' },
        q:         { label: 'Search' },
        category:  { label: 'Category' }
    };
    var SKIP_PARAMS = ['page','limit','area_unit','sort','ajax','tab'];

    function buildChips() {
        var $area = $(CHIPS_AREA);
        if (!$area.length) return;
        var params   = new URLSearchParams(window.location.search);
        var areaUnit = params.get('area_unit') || 'marla';
        var chips    = [];

        params.forEach(function (value, key) {
            if (SKIP_PARAMS.indexOf(key) !== -1 || value === '') return;
            var conf    = CHIP_LABELS[key] || { label: key };
            var display = value;
            if (conf.valueMap && conf.valueMap[value]) {
                display = conf.valueMap[value];
            } else if (conf.format === 'pkr') {
                display = formatPKR(value);
            } else if (conf.format === 'area') {
                display = formatArea(value, areaUnit);
            } else {
                display = value.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
            }
            chips.push({ key: key, value: value, text: conf.label + ': ' + display });
        });

        if (!chips.length) { $area.empty().addClass('d-none'); return; }

        $area.removeClass('d-none').empty();
        $.each(chips, function (_, chip) {
            var $chip = $('<span>', {
                class: 'active-filter-chip',
                html:  '<span>' + chip.text + '</span><button type="button" class="chip-remove" aria-label="Remove filter" data-key="' + chip.key + '" data-value="' + encodeURIComponent(chip.value) + '"><i class="fa-solid fa-xmark"></i></button>'
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
                var vals = params.getAll(key).filter(function (v) { return v !== value; });
                params.delete(key);
                vals.forEach(function (v) { params.append(key, v); });
            } else {
                params.delete(key);
            }
            params.delete('page');
            saveScroll();
            showBusy();
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    /* ─── mobile offcanvas — auto-close after a change (UX nicety) ──── */
    function initMobileOffcanvas() {
        $(document).on('change', '#filterFormMobile select, #filterFormMobile input[type="checkbox"], #filterFormMobile input[type="radio"]', function () {
            // nothing; form handler already submits
        });
    }

    /* ─── lazy load (same as before) ─────────────────────────────────── */
    function initLazyLoad() {
        if (!('IntersectionObserver' in window)) {
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
        $('img[data-src]').each(function () { observer.observe(this); });
    }

    /* ─── bookmark hearts (if present) ───────────────────────────────── */
    function initCardEnhancements() {
        var saved = JSON.parse(localStorage.getItem('alriaz_saved') || '[]');
        function isSaved(id) { return saved.indexOf(String(id)) !== -1; }
        $('.btn-bookmark[data-id]').each(function () {
            if (isSaved($(this).data('id'))) {
                $(this).addClass('saved').find('i').removeClass('fa-regular').addClass('fas');
            }
        });
        $(document).off('click.alriaz-bookmark').on('click.alriaz-bookmark', '.btn-bookmark', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var id   = String($btn.data('id'));
            var idx  = saved.indexOf(id);
            if (idx === -1) saved.push(id); else saved.splice(idx, 1);
            localStorage.setItem('alriaz_saved', JSON.stringify(saved));
            var nowSaved = idx === -1;
            $btn.toggleClass('saved', nowSaved);
            $btn.find('i').toggleClass('fa-regular', !nowSaved).toggleClass('fas', nowSaved);
        });
    }

    /* ─── init ───────────────────────────────────────────────────────── */
    $(function () {
        restoreScroll();
        restoreFocus();
        initFilterForm($('#filterForm'));
        initFilterForm($('#filterFormMobile'));
        initSort();
        initViewToggle();
        buildChips();
        initChipRemove();
        initMobileOffcanvas();
        initLazyLoad();
        initCardEnhancements();
    });

}(jQuery));
