/**
 * Al-Riaz Associates — Detail Page JavaScript
 * Handles: Gallery, Lightbox, Enquiry Form AJAX, WhatsApp, Views Counter,
 *          Copy Phone, Map Toggle, Share, Tabs, Area Converter
 */

(function ($) {
    'use strict';

    /* =========================================================================
       1. PHOTO GALLERY — Enhanced Bootstrap Carousel + Lightbox
       ====================================================================== */

    var Gallery = {

        $carousel  : null,
        $thumbStrip: null,
        $lightbox  : null,
        images     : [],   // [{src, alt}]
        currentIdx : 0,

        init: function () {
            this.$carousel   = $('#propertyCarousel');
            this.$thumbStrip = $('#thumbStrip');
            this.$lightbox   = $('#galleryLightbox');

            if (!this.$carousel.length) return;

            this.buildImages();
            this.buildThumbs();
            this.buildLightbox();
            this.bindCarouselEvents();
            this.bindSwipe();
            this.bindKeyboard();
            this.updateCounter(0);
        },

        buildImages: function () {
            var self = this;
            this.$carousel.find('.carousel-item img').each(function (i) {
                self.images.push({
                    src: $(this).attr('src'),
                    alt: $(this).attr('alt') || 'Property Image ' + (i + 1)
                });
            });
        },

        buildThumbs: function () {
            // Thumbnails are emitted server-side as .gallery-thumb-item.
            // Just wire click handlers — don't re-append (would duplicate).
            if (!this.$thumbStrip.length) return;
            var self = this;
            this.$thumbStrip.find('.gallery-thumb-item').each(function (i) {
                $(this).on('click', function () { self.$carousel.carousel(i); });
            });
        },

        buildLightbox: function () {
            if (!this.$lightbox.length) return;
            var self = this;

            // Open lightbox on image click
            this.$carousel.find('.carousel-item').on('click', function () {
                self.currentIdx = self.$carousel.find('.carousel-item.active').index();
                self.openLightbox(self.currentIdx);
            }).css('cursor', 'zoom-in');

            // Lightbox prev/next
            $('#lbPrev').on('click', function () {
                self.lightboxGo(self.currentIdx - 1);
            });
            $('#lbNext').on('click', function () {
                self.lightboxGo(self.currentIdx + 1);
            });
        },

        openLightbox: function (idx) {
            this.lightboxGo(idx);
            var modal = new bootstrap.Modal(this.$lightbox[0]);
            modal.show();
        },

        lightboxGo: function (idx) {
            var total = this.images.length;
            if (total === 0) return;
            this.currentIdx = ((idx % total) + total) % total;

            var img = this.images[this.currentIdx];
            $('#lbImage').attr({ src: img.src, alt: img.alt });
            $('#lbCounter').text((this.currentIdx + 1) + ' / ' + total);
        },

        bindCarouselEvents: function () {
            var self = this;
            this.$carousel.on('slid.bs.carousel', function (e) {
                var idx = e.to;
                self.updateCounter(idx);
                var $thumbs = self.$thumbStrip.find('.gallery-thumb-item');
                $thumbs.removeClass('active');
                var $active = $thumbs.eq(idx).addClass('active');
                if ($active.length && self.$thumbStrip[0]) {
                    self.$thumbStrip[0].scrollLeft =
                        $active[0].offsetLeft - (self.$thumbStrip.width() / 2) + ($active.width() / 2);
                }
            });
        },

        bindSwipe: function () {
            var el = this.$carousel[0];
            if (!el) return;
            var startX = 0;

            el.addEventListener('touchstart', function (e) {
                startX = e.touches[0].clientX;
            }, { passive: true });

            el.addEventListener('touchend', function (e) {
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 50) {
                    $(el).carousel(dx < 0 ? 'next' : 'prev');
                }
            }, { passive: true });
        },

        bindKeyboard: function () {
            var self = this;
            $(document).on('keydown', function (e) {
                // Only when lightbox is closed — carousel arrows; or lightbox open
                if (e.key === 'ArrowLeft') {
                    if ($('#galleryLightbox').hasClass('show')) {
                        self.lightboxGo(self.currentIdx - 1);
                    } else {
                        self.$carousel.carousel('prev');
                    }
                }
                if (e.key === 'ArrowRight') {
                    if ($('#galleryLightbox').hasClass('show')) {
                        self.lightboxGo(self.currentIdx + 1);
                    } else {
                        self.$carousel.carousel('next');
                    }
                }
            });
        },

        updateCounter: function (idx) {
            var total = this.images.length;
            if (!total) return;
            $('#photoCounter').text((idx + 1) + ' / ' + total);
        }
    };

    /* =========================================================================
       2. ENQUIRY FORM — AJAX with real-time phone validation
       ====================================================================== */

    var EnquiryForm = {

        $form    : null,
        $btn     : null,
        $spinner : null,
        $btnText : null,
        $success : null,
        pkPhone  : /^(\+92|0)3[0-9]{9}$/,

        init: function () {
            this.$form    = $('#enquiryForm');
            this.$btn     = $('#submitEnquiry');
            this.$spinner = this.$btn.find('.spinner-border');
            this.$btnText = this.$btn.find('.btn-text');
            this.$success = $('.enquiry-success');

            if (!this.$form.length) return;

            this.bindPhoneValidation();
            this.bindSubmit();
            this.initStickySidebar();
        },

        bindPhoneValidation: function () {
            var self = this;
            this.$form.find('input[name="phone"]').on('input blur', function () {
                var val = $(this).val().replace(/\s+/g, '');
                var $fb = $(this).siblings('.invalid-feedback');
                if (val && !self.pkPhone.test(val)) {
                    $(this).addClass('is-invalid');
                    $fb.show();
                } else {
                    $(this).removeClass('is-invalid');
                    $fb.hide();
                }
            });
        },

        bindSubmit: function () {
            var self = this;
            this.$form.on('submit', function (e) {
                e.preventDefault();

                // Honeypot check
                if ($(this).find('[name="website"]').val()) return;

                // Phone validation
                var phone = $(this).find('[name="phone"]').val().replace(/\s+/g, '');
                if (!self.pkPhone.test(phone)) {
                    $(this).find('[name="phone"]').addClass('is-invalid').trigger('focus');
                    return;
                }

                self.setLoading(true);

                $.ajax({
                    url    : $(this).attr('action') || ((window.APP_BASE || '') + '/api/v1/inquiries.php'),
                    method : 'POST',
                    data   : $(this).serialize(),
                    dataType: 'json',
                    success: function (res) {
                        self.setLoading(false);
                        if (res && res.success) {
                            self.$form.addClass('d-none');
                            self.$success.removeClass('d-none');
                        } else {
                            self.showErrors(res.errors || {});
                        }
                    },
                    error: function () {
                        self.setLoading(false);
                        self.showGenericError();
                    }
                });
            });
        },

        setLoading: function (loading) {
            this.$btn.prop('disabled', loading);
            if (loading) {
                this.$btnText.addClass('d-none');
                this.$spinner.removeClass('d-none');
            } else {
                this.$btnText.removeClass('d-none');
                this.$spinner.addClass('d-none');
            }
        },

        showErrors: function (errors) {
            // Clear previous
            this.$form.find('.is-invalid').removeClass('is-invalid');
            this.$form.find('.server-error').remove();

            $.each(errors, function (field, msg) {
                var $input = $('[name="' + field + '"]');
                $input.addClass('is-invalid');
                $input.after('<div class="invalid-feedback server-error">' +
                    $('<div>').text(msg).html() + '</div>');
            });

            if (!Object.keys(errors).length) {
                this.showGenericError();
            }
        },

        showGenericError: function () {
            this.$form.find('.server-error').remove();
            var $err = $('<div class="alert alert-danger server-error mt-2 small">' +
                'Something went wrong. Please try again or call us directly.</div>');
            this.$form.prepend($err);
        },

        initStickySidebar: function () {
            // Activate Bootstrap sticky on desktop
            var $sidebar = $('.detail-sidebar');
            if (!$sidebar.length) return;

            if ($(window).width() >= 992) {
                $sidebar.addClass('sticky-top');
                $sidebar.css('top', '80px');
            }
        }
    };

    /* =========================================================================
       3. WHATSAPP INTEGRATION — pre-filled messages (built server-side via PHP,
          JS just ensures links open correctly; dynamic messages from data attrs)
       ====================================================================== */

    var WhatsApp = {
        init: function () {
            // Dynamic WA buttons with data-wa-message attribute
            $('[data-wa-message]').each(function () {
                var msg   = $(this).data('wa-message');
                var phone = $(this).data('wa-phone') || '923001234567';
                var url   = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
                $(this).attr('href', url);
            });
        }
    };

    /* =========================================================================
       4. VIEWS COUNTER — fire GET to increment views_count
       ====================================================================== */

    var ViewsCounter = {
        init: function () {
            var propertyId = $('body').data('property-id');
            var projectId  = $('body').data('project-id');

            var base = window.APP_BASE || '';
            if (propertyId) {
                $.get(base + '/api/v1/properties.php', { action: 'view', id: propertyId });
            }
            // Projects view tracking (if endpoint supports it)
            if (projectId) {
                $.get(base + '/api/v1/projects.php', { action: 'view', id: projectId }).fail(function () {
                    // Silently fail — endpoint may not exist yet
                });
            }
        }
    };

    /* =========================================================================
       5. COPY PHONE — click phone → clipboard + tooltip
       ====================================================================== */

    var CopyPhone = {
        init: function () {
            $(document).on('click', '.copy-phone', function (e) {
                e.preventDefault();
                var phone = $(this).data('phone') || $(this).text().trim();
                var $el   = $(this);

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(phone).then(function () {
                        CopyPhone.showCopied($el);
                    });
                } else {
                    // Fallback
                    var $tmp = $('<input>').val(phone).appendTo('body').select();
                    document.execCommand('copy');
                    $tmp.remove();
                    CopyPhone.showCopied($el);
                }
            });
        },

        showCopied: function ($el) {
            var original = $el.html();
            $el.html('<i class="fas fa-check me-1"></i>Copied!').addClass('text-success');
            setTimeout(function () {
                $el.html(original).removeClass('text-success');
            }, 2000);
        }
    };

    /* =========================================================================
       6. MAP EMBED TOGGLE
       ====================================================================== */

    var MapToggle = {
        init: function () {
            $('#showMapBtn').on('click', function () {
                var $map = $('#mapEmbed');
                var $btn = $(this);

                if ($map.hasClass('d-none')) {
                    $map.removeClass('d-none');
                    // Load iframe src from data-src (lazy)
                    $map.find('iframe[data-src]').each(function () {
                        if (!$(this).attr('src')) {
                            $(this).attr('src', $(this).data('src'));
                        }
                    });
                    $btn.html('<i class="fas fa-times me-1"></i>Hide Map');
                    $map[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    $map.addClass('d-none');
                    $btn.html('<i class="fas fa-map-marked-alt me-1"></i>Show on Map');
                }
            });
        }
    };

    /* =========================================================================
       7. SHARE BUTTONS
       ====================================================================== */

    var Share = {
        init: function () {
            $('#shareBtn').on('click', function () {
                var $btn  = $(this);
                var title = $btn.data('share-title') || $('h1.property-title').text().trim() || document.title;
                var text  = $btn.data('share-text')  || '';
                var image = $btn.data('share-image') || '';
                var url   = window.location.href;

                // Try native share with the listing image attached. Falls back
                // to share-without-file, then to copy-link, depending on what
                // the platform supports.
                function copyFallback() {
                    var $tmp = $('<input>').val(url).appendTo('body').select();
                    document.execCommand('copy');
                    $tmp.remove();
                    var orig = $btn.html();
                    $btn.html('<i class="fas fa-check me-1"></i>Link Copied!');
                    setTimeout(function () { $btn.html(orig); }, 2500);
                }

                function shareTextOnly() {
                    if (!navigator.share) return copyFallback();
                    navigator.share({ title: title, text: text, url: url }).catch(copyFallback);
                }

                if (image && navigator.canShare && window.fetch) {
                    fetch(image)
                        .then(function (r) { return r.ok ? r.blob() : Promise.reject(); })
                        .then(function (blob) {
                            var ext  = (blob.type.split('/')[1] || 'jpg').replace('jpeg', 'jpg');
                            var file = new File([blob], 'listing.' + ext, { type: blob.type });
                            var payload = { title: title, text: text + '\n' + url, url: url, files: [file] };
                            if (navigator.canShare(payload)) {
                                return navigator.share(payload).catch(function () {});
                            }
                            return shareTextOnly();
                        })
                        .catch(shareTextOnly);
                } else {
                    shareTextOnly();
                }
            });

            // WhatsApp share — include title, short text, and URL. WhatsApp
            // will fetch og:image from the URL to render the preview card.
            $('#shareWaBtn').on('click', function () {
                var $btn  = $(this);
                var title = $btn.data('share-title') || $('h1.property-title').text().trim() || document.title;
                var text  = $btn.data('share-text')  || '';
                var url   = window.location.href;
                var lines = [title];
                if (text) lines.push(text);
                lines.push(url);
                window.open('https://wa.me/?text=' + encodeURIComponent(lines.join('\n')), '_blank');
            });
        }
    };

    /* =========================================================================
       8. TABS — URL hash update for project detail
       ====================================================================== */

    var Tabs = {
        init: function () {
            var $tabs = $('#projectTabs .nav-link');
            if (!$tabs.length) return;

            // Restore tab from URL hash
            var hash = window.location.hash;
            if (hash) {
                var $target = $tabs.filter('[data-bs-target="' + hash + '"], [href="' + hash + '"]');
                if ($target.length) {
                    new bootstrap.Tab($target[0]).show();
                }
            }

            // Update hash on tab change
            $tabs.on('shown.bs.tab', function (e) {
                var id = $(e.target).data('bs-target') || $(e.target).attr('href');
                if (id && history.replaceState) {
                    history.replaceState(null, null, id);
                }
            });
        }
    };

    /* =========================================================================
       9. FLOOR PLAN TAB
       ====================================================================== */

    var FloorPlan = {
        init: function () {
            // Clicking floor plan thumbnail shows floor plan in main carousel
            $('#floorPlanThumb').on('click', function () {
                var src = $(this).data('src');
                if (!src) return;
                var $fp = $('#floorPlanSlide');
                if ($fp.length) {
                    var idx = $fp.closest('.carousel-item').index();
                    $('#propertyCarousel').carousel(idx);
                }
            });
        }
    };

    /* =========================================================================
       11. LISTING FILTER (project listings tab)
       ====================================================================== */

    var ListingFilter = {
        init: function () {
            $(document).on('click', '.listing-filter-pill', function () {
                var filter = $(this).data('filter');
                $('.listing-filter-pill').removeClass('active');
                $(this).addClass('active');

                if (filter === 'all') {
                    $('.project-listing-card').show();
                } else {
                    $('.project-listing-card').hide();
                    $('.project-listing-card[data-purpose="' + filter + '"]').show();
                }
            });
        }
    };

    /* =========================================================================
       DOCUMENT READY — initialise all modules
       ====================================================================== */

    $(function () {
        Gallery.init();
        EnquiryForm.init();
        WhatsApp.init();
        ViewsCounter.init();
        CopyPhone.init();
        MapToggle.init();
        Share.init();
        Tabs.init();
        FloorPlan.init();
        ListingFilter.init();
    });

}(jQuery));
