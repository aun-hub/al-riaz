/**
 * Al-Riaz Associates — Main Public JavaScript
 * Stack: Vanilla JS + jQuery 3.7.1
 * Loaded on all public pages.
 */

/* ==========================================================================
   0. DOCUMENT READY — Bootstrap all modules
   ========================================================================== */
$(function () {
    'use strict';

    initNavbarScroll();
    initMobileNav();
    initScrollReveal();
    initSearchWidget();
    initAutoComplete();
    initPhoneValidation();
    initEnquiryForm();
    initCopyPhone();
    initLazyImages();
    initCounterAnimation();
    initPropertyCardHover();
    initBackToTop();
    initWhatsAppTracking();
});

/* ==========================================================================
   1. NAVBAR SCROLL — transparent → scrolled glassmorphism
   ========================================================================== */
function initNavbarScroll() {
    var $navbar = $('#siteNavbar');
    if (!$navbar.length) return;

    var ticking = false;

    function handleScroll() {
        var y = window.scrollY || window.pageYOffset;
        if (y > 40) {
            $navbar.removeClass('transparent').addClass('scrolled');
        } else {
            $navbar.removeClass('scrolled').addClass('transparent');
        }
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(handleScroll);
            ticking = true;
        }
    }, { passive: true });

    // Run once on load
    handleScroll();
}

/* ==========================================================================
   2. MOBILE NAV DRAWER — hamburger open / close
   ========================================================================== */
function initMobileNav() {
    var $hamburger = $('#navHamburger');
    var $mobileNav = $('#mobileNav');
    var $closeBtn  = $('#mobileNavClose');

    if (!$hamburger.length) return;

    function openNav() {
        $mobileNav.addClass('open');
        $hamburger.addClass('open').attr('aria-expanded', 'true');
        $('body').css('overflow', 'hidden');
    }

    function closeNav() {
        $mobileNav.removeClass('open');
        $hamburger.removeClass('open').attr('aria-expanded', 'false');
        $('body').css('overflow', '');
    }

    $hamburger.on('click', openNav);
    $closeBtn.on('click', closeNav);

    // Close on backdrop click (clicking outside the drawer content area)
    $mobileNav.on('click', function (e) {
        if ($(e.target).is($mobileNav)) closeNav();
    });

    // Close on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeNav();
    });

    // Close when a link inside is clicked
    $mobileNav.find('a').on('click', closeNav);
}

/* ==========================================================================
   3. SCROLL-REVEAL — IntersectionObserver triggers .visible on .reveal*
   ========================================================================== */
function initScrollReveal() {
    if (!('IntersectionObserver' in window)) {
        // Fallback: make all elements visible immediately
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-stagger')
            .forEach(function (el) { el.classList.add('visible'); });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
    });

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-stagger')
        .forEach(function (el) { observer.observe(el); });
}

/* ==========================================================================
   4. SEARCH WIDGET — Buy/Rent toggle, city/type dropdowns, form submit
   ========================================================================== */
function initSearchWidget() {
    var $widget = $('.search-widget');
    if (!$widget.length) return;

    var $tabs        = $widget.find('.search-tab');
    var $typeSelect  = $widget.find('select[name="type"]');
    var $form        = $widget.find('form#search-form, form');

    var typeOptions = {
        sale: [
            { value: '',         label: 'All Types' },
            { value: 'house',    label: 'House' },
            { value: 'flat',     label: 'Flat / Apartment' },
            { value: 'plot',     label: 'Plot' },
            { value: 'shop',     label: 'Shop' },
            { value: 'office',   label: 'Office' },
        ],
        rent: [
            { value: '',         label: 'All Types' },
            { value: 'house',    label: 'House' },
            { value: 'flat',     label: 'Flat / Apartment' },
            { value: 'room',     label: 'Room' },
            { value: 'shop',     label: 'Shop' },
            { value: 'office',   label: 'Office' },
        ]
    };

    $tabs.on('click', function () {
        var purpose = $(this).attr('data-purpose') || 'sale';
        $tabs.removeClass('active');
        $(this).addClass('active');
        $widget.find('input[name="purpose"]').val(purpose);

        if ($typeSelect.length) {
            $typeSelect.empty();
            $.each(typeOptions[purpose] || typeOptions.sale, function (i, t) {
                $typeSelect.append($('<option>', { value: t.value, text: t.label }));
            });
        }
    });

    // Populate city dropdown via AJAX
    $.get('/api/v1/meta.php', { resource: 'cities' })
        .done(function (res) {
            var $citySelect = $widget.find('select[name="city"]');
            $citySelect.find('option:not(:first)').remove();
            if (res && res.data && Array.isArray(res.data)) {
                $.each(res.data, function (i, city) {
                    $citySelect.append($('<option>', {
                        value: city.value || city,
                        text:  city.label || city
                    }));
                });
            }
        })
        .fail(function () { /* cities already in HTML markup */ });

    if ($form.length) {
        $form.on('submit', function (e) {
            e.preventDefault();
            window.location.href = '/search.php?' + $form.serialize();
        });
    }
}

/* ==========================================================================
   5. AUTO-COMPLETE SEARCH — Debounced suggestions dropdown
   ========================================================================== */
function initAutoComplete() {
    var $inputs = $('input.search-autocomplete-input');
    if (!$inputs.length) return;

    var debounceTimer = null;
    var DEBOUNCE_MS   = 300;

    $inputs.each(function () {
        var $input  = $(this);
        var $parent = $input.parent();

        var $dropdown = $parent.find('.search-autocomplete');
        if (!$dropdown.length) {
            $dropdown = $('<div class="search-autocomplete"></div>');
            $parent.css('position', 'relative').append($dropdown);
        }

        $input.on('keyup', function () {
            var q = $.trim($input.val());
            clearTimeout(debounceTimer);

            if (q.length < 2) {
                $dropdown.removeClass('active').empty();
                return;
            }

            debounceTimer = setTimeout(function () {
                $.get('/api/v1/search.php', { q: q })
                    .done(function (res) {
                        $dropdown.empty();
                        if (res && res.data && res.data.length) {
                            $.each(res.data, function (i, item) {
                                var $item = $('<div class="autocomplete-item"></div>');
                                var icon  = item.type === 'project' ? 'fa-building' : 'fa-home';
                                $item.html('<i class="fas ' + icon + '"></i>' + escapeHtml(item.label));
                                $item.on('click', function () {
                                    $input.val(item.label);
                                    $dropdown.removeClass('active').empty();
                                    if (item.url) window.location.href = item.url;
                                });
                                $dropdown.append($item);
                            });
                            $dropdown.addClass('active');
                        } else {
                            $dropdown.removeClass('active').empty();
                        }
                    });
            }, DEBOUNCE_MS);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest($parent).length) {
                $dropdown.removeClass('active').empty();
            }
        });
    });
}

/* ==========================================================================
   6. PHONE VALIDATOR — Pakistani phone format (+923xx / 03xx)
   ========================================================================== */
function initPhoneValidation() {
    $(document).on('input', 'input[type="tel"], input.phone-input', function () {
        validatePhone($(this));
    });
}

function validatePhone($field) {
    var val = $.trim($field.val()).replace(/[\s\-]/g, '');
    var pkPattern = /^(\+92|92|0)3[0-9]{9}$/;

    $field.removeClass('phone-valid phone-invalid');
    if (val === '') return;

    if (pkPattern.test(val)) {
        $field.addClass('phone-valid');
    } else {
        $field.addClass('phone-invalid');
    }
}

/* ==========================================================================
   7. ENQUIRY FORM HANDLER — AJAX POST, success message
   ========================================================================== */
function initEnquiryForm() {
    $(document).on('submit', 'form.enquiry-form, form.contact-form', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $btn     = $form.find('[type="submit"]');
        var $success = $form.closest('.enquiry-card, .card').find('.form-success-msg');

        var $phone = $form.find('input[type="tel"], input.phone-input');
        if ($phone.length) {
            validatePhone($phone);
            if ($phone.hasClass('phone-invalid')) {
                $phone.focus();
                return;
            }
        }

        // Honeypot bot trap
        if ($form.find('input[name="website"]').val()) {
            showFormSuccess($form, $success, $btn);
            return;
        }

        var btnText = $btn.html();
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Sending...'
        );

        $.ajax({
            url:      '/api/v1/inquiries.php',
            method:   'POST',
            data:     $form.serialize(),
            dataType: 'json'
        })
        .done(function (res) {
            if (res && res.success) {
                showFormSuccess($form, $success, $btn);
            } else {
                var msg = (res && res.message) ? res.message : 'Something went wrong. Please try again.';
                showFormError($form, msg);
                $btn.prop('disabled', false).html(btnText);
            }
        })
        .fail(function (xhr) {
            var msg = 'Unable to submit. Please try again or contact us directly.';
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp && resp.message) msg = resp.message;
            } catch (err) { /* ignore */ }
            showFormError($form, msg);
            $btn.prop('disabled', false).html(btnText);
        });
    });
}

function showFormSuccess($form, $success, $btn) {
    $form[0].reset();
    $form.find('.phone-valid, .phone-invalid').removeClass('phone-valid phone-invalid');
    $btn.prop('disabled', false);

    if ($success.length) {
        $form.fadeOut(300, function () {
            $success.addClass('show').hide().fadeIn(400);
        });
    } else {
        var $alert = $('<div class="alert alert-success mt-3" role="alert">' +
            '<i class="fas fa-check-circle me-2"></i>Thank you! We\'ll be in touch shortly.</div>');
        $form.after($alert);
        setTimeout(function () { $alert.fadeOut(); }, 5000);
    }
}

function showFormError($form, message) {
    $form.find('.form-error-alert').remove();
    var $alert = $('<div class="alert alert-danger mt-3 form-error-alert" role="alert">' +
        '<i class="fas fa-exclamation-circle me-2"></i>' + escapeHtml(message) + '</div>');
    $form.append($alert);
    setTimeout(function () { $alert.fadeOut(400, function () { $(this).remove(); }); }, 6000);
}

/* ==========================================================================
   8. COPY TO CLIPBOARD — Phone numbers with tooltip
   ========================================================================== */
function initCopyPhone() {
    $(document).on('click', '.copy-phone-btn', function () {
        var $btn = $(this);
        var text = $btn.attr('data-copy') || $btn.closest('[data-phone]').attr('data-phone') || '';
        if (!text) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { showCopiedTooltip($btn); });
        } else {
            var $temp = $('<input type="text">').val(text).appendTo('body').select();
            document.execCommand('copy');
            $temp.remove();
            showCopiedTooltip($btn);
        }
    });
}

function showCopiedTooltip($btn) {
    $btn.addClass('copied');
    var originalTitle = $btn.attr('title') || '';
    $btn.attr('title', 'Copied!').tooltip('show');
    setTimeout(function () {
        $btn.removeClass('copied').attr('title', originalTitle).tooltip('hide');
    }, 1800);
}

/* ==========================================================================
   9. WHATSAPP CLICK TRACKING
   ========================================================================== */
function initWhatsAppTracking() {
    $(document).on('click', 'a[href*="wa.me"], a[href*="whatsapp"], .wa-float', function () {
        var $el     = $(this);
        var context = $el.closest('[data-property-id]').attr('data-property-id') || 'general';
        var page    = window.location.pathname;

        if (typeof gtag === 'function') {
            gtag('event', 'whatsapp_click', {
                event_category: 'Lead',
                event_label:    page + ' | ' + context
            });
        }

        $.post('/api/v1/track.php', {
            event:       'whatsapp_click',
            property_id: context,
            page:        page
        }).fail(function () { /* ignore */ });
    });
}

/* ==========================================================================
   10. LAZY LOADING — IntersectionObserver for images with data-src
   ========================================================================== */
function initLazyImages() {
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.remove('lazy');
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '0px 0px 200px 0px', threshold: 0.01 });

        document.querySelectorAll('img[data-src]').forEach(function (img) {
            observer.observe(img);
        });
    } else {
        document.querySelectorAll('img[data-src]').forEach(function (img) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

/* ==========================================================================
   11. COUNTER ANIMATION — Numbers count up when scrolled into view
   ========================================================================== */
function initCounterAnimation() {
    var $counters = $('[data-count-to]');
    if (!$counters.length) return;

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter($(entry.target));
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        $counters.each(function () { observer.observe(this); });
    } else {
        $counters.each(function () { animateCounter($(this)); });
    }
}

function animateCounter($el) {
    var target   = parseInt($el.attr('data-count-to'), 10);
    var suffix   = $el.attr('data-count-suffix') || '';
    var duration = 1800;
    var startTs  = null;

    function step(ts) {
        if (!startTs) startTs = ts;
        var progress = Math.min((ts - startTs) / duration, 1);
        var eased    = 1 - Math.pow(1 - progress, 3);
        $el.text(Math.floor(eased * target) + suffix);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            $el.text(target + suffix);
        }
    }

    window.requestAnimationFrame(step);
}

/* ==========================================================================
   12. PROPERTY CARD HOVER — Keyboard accessibility for WA button
   ========================================================================== */
function initPropertyCardHover() {
    $(document).on('focus', '.prop-card .prop-wa-btn', function () {
        $(this).closest('.prop-card').addClass('wa-focus');
    });
    $(document).on('blur', '.prop-card .prop-wa-btn', function () {
        $(this).closest('.prop-card').removeClass('wa-focus');
    });
}

/* ==========================================================================
   13. BACK TO TOP — Appears after 400px scroll
   ========================================================================== */
function initBackToTop() {
    var $btn = $('.back-to-top');
    if (!$btn.length) return;

    window.addEventListener('scroll', function () {
        if (window.scrollY > 400) {
            $btn.addClass('visible');
        } else {
            $btn.removeClass('visible');
        }
    }, { passive: true });

    $btn.on('click', function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/* ==========================================================================
   UTILITY FUNCTIONS
   ========================================================================== */

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function debounce(fn, delay) {
    var timer;
    return function () {
        var args    = arguments;
        var context = this;
        clearTimeout(timer);
        timer = setTimeout(function () { fn.apply(context, args); }, delay);
    };
}

function formatNumber(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatPKR(amount) {
    if (!amount || amount <= 0) return 'Price on Demand';
    var crore = 10000000;
    var lakh  = 100000;
    if (amount >= crore) {
        return (amount / crore).toFixed(2).replace(/\.?0+$/, '') + ' Crore';
    }
    if (amount >= lakh) {
        return (amount / lakh).toFixed(2).replace(/\.?0+$/, '') + ' Lakh';
    }
    return 'PKR ' + formatNumber(Math.floor(amount));
}
