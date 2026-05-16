<?php
/**
 * Al-Riaz Associates — Contact Us Page
 * Public URL: /contact.php
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

$pageTitle       = 'Contact Us - Al-Riaz Associates';
$pageDescription = 'Contact Al-Riaz Associates for real estate inquiries in Islamabad, Rawalpindi, Lahore & Karachi. Call, WhatsApp, or fill in our online contact form.';

$settings      = getSettings();
$agencyPhone   = $settings['phone']        ?: (defined('SITE_PHONE') ? SITE_PHONE : '');
$agencyWa      = $settings['whatsapp']     ?: (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '');
$agencyEmail   = $settings['email']        ?: (defined('SITE_EMAIL') ? SITE_EMAIL : '');
$agencyAddress = $settings['address']      ?: 'Islamabad, Pakistan';
$agencyHours   = formatBusinessHours(getBusinessHoursSchedule());
$hqOffice      = getHqOffice();
$facebookUrl   = $settings['facebook_url'] ?? '';
$instagramUrl  = $settings['instagram_url'] ?? '';
$youtubeUrl    = $settings['youtube_url']  ?? '';
$branches      = getBranches();

require_once 'includes/header.php';
?>

<!-- ============================================================
     PAGE HEADER
     ============================================================ -->
<div class="page-header">
    <div class="container">
        <?= generateBreadcrumb([['label'=>'Home','url'=>'/'],['label'=>'Contact Us']]) ?>
        <h1 class="page-header-title">Get in Touch</h1>
        <p class="page-header-sub">We're here to help — WhatsApp, phone, or email</p>
    </div>
</div>

<!-- ============================================================
     MAIN CONTACT SECTION
     ============================================================ -->
<section class="section-pad" style="background:#fff;">
    <div class="container">
        <div class="row g-5 align-items-start">

            <!-- LEFT: Contact Form -->
            <div class="col-12 col-lg-7">
                <div class="enquiry-card">
                    <div class="enquiry-card-header">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Us a Message
                    </div>
                    <div style="padding:2rem;">

                        <!-- Success Message (shown after submit) -->
                        <div class="form-success-msg" id="contact-success">
                            <div class="form-success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h5 class="fw-bold" style="color:var(--navy-700);">Message Sent!</h5>
                            <p class="text-muted mb-3">
                                Thank you for reaching out. Our team will contact you within
                                <strong>2–4 working hours</strong>.
                            </p>
                            <a href="<?= 'https://wa.me/' . htmlspecialchars($agencyWa) . '?text=' . rawurlencode("Hello! I just submitted a contact form on your website.") ?>"
                               target="_blank" rel="noopener"
                               class="btn-whatsapp d-inline-flex" style="max-width:260px;">
                                <i class="fab fa-whatsapp"></i> Faster? WhatsApp Us
                            </a>
                        </div>

                        <!-- Contact Form -->
                        <form id="contact-form"
                              class="enquiry-form contact-form"
                              method="POST"
                              action="<?= $b ?>/api/v1/inquiries.php"
                              novalidate>

                            <!-- Honeypot (bot trap) -->
                            <input type="text" name="website" style="display:none;" tabindex="-1" autocomplete="off">

                            <!-- hCaptcha placeholder -->
                            <!-- Add hCaptcha here before form submit for production -->

                            <input type="hidden" name="source" value="contact_page">
                            <input type="hidden" name="inquiry_type" value="general">

                            <div class="row g-3">
                                <!-- Full Name -->
                                <div class="col-12 col-sm-6">
                                    <label for="contact-name" class="form-label fw-semibold small">
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           id="contact-name"
                                           name="name"
                                           class="form-control"
                                           placeholder="Muhammad Ali"
                                           required
                                           maxlength="100">
                                    <div class="invalid-feedback">Please enter your name.</div>
                                </div>

                                <!-- Phone -->
                                <div class="col-12 col-sm-6">
                                    <label for="contact-phone" class="form-label fw-semibold small">
                                        Phone Number <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="background:var(--navy-50); border-color:var(--navy-100);">
                                            <i class="fas fa-phone text-muted small"></i>
                                        </span>
                                        <input type="tel"
                                               id="contact-phone"
                                               name="phone"
                                               class="form-control phone-input"
                                               placeholder="0300 1234567"
                                               required
                                               maxlength="20">
                                    </div>
                                    <div class="form-text text-muted" style="font-size:.75rem;">
                                        Format: 03xx-xxxxxxx or +923xxxxxxxxx
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-12 col-sm-6">
                                    <label for="contact-email" class="form-label fw-semibold small">
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input type="email"
                                           id="contact-email"
                                           name="email"
                                           class="form-control"
                                           placeholder="you@example.com"
                                           required
                                           maxlength="150">
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <!-- Subject -->
                                <div class="col-12 col-sm-6">
                                    <label for="contact-subject" class="form-label fw-semibold small">
                                        Subject
                                    </label>
                                    <select id="contact-subject" name="subject" class="form-select">
                                        <option value="">Select a topic...</option>
                                        <option value="buying">I want to buy a property</option>
                                        <option value="renting">I want to rent a property</option>
                                        <option value="selling">I want to sell/list my property</option>
                                        <option value="project">Inquiry about a project</option>
                                        <option value="investment">Investment advice</option>
                                        <option value="other">Other / General inquiry</option>
                                    </select>
                                </div>

                                <!-- Preferred Contact Time -->
                                <div class="col-12">
                                    <label for="contact-time" class="form-label fw-semibold small">
                                        Preferred Contact Time
                                    </label>
                                    <select id="contact-time" name="preferred_contact_time" class="form-select">
                                        <option value="">Any time</option>
                                        <option value="Morning (9am - 12pm)">Morning — 9:00 AM to 12:00 PM</option>
                                        <option value="Afternoon (12pm - 4pm)">Afternoon — 12:00 PM to 4:00 PM</option>
                                        <option value="Evening (5pm - 8pm)">Evening — 5:00 PM to 8:00 PM</option>
                                        <option value="Business Hours">Business Hours</option>
                                    </select>
                                </div>

                                <!-- Message -->
                                <div class="col-12">
                                    <label for="contact-message" class="form-label fw-semibold small">
                                        Message <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="contact-message"
                                              name="message"
                                              class="form-control"
                                              rows="5"
                                              placeholder="Tell us about the property you're looking for, your budget, preferred location..."
                                              required
                                              minlength="20"
                                              maxlength="2000"></textarea>
                                    <div class="form-text text-muted text-end" id="msg-char-count" style="font-size:.72rem;">
                                        0 / 2000
                                    </div>
                                    <div class="invalid-feedback">Please enter a message (at least 20 characters).</div>
                                </div>

                                <!-- Privacy note -->
                                <div class="col-12">
                                    <p class="text-muted mb-0" style="font-size:.78rem;">
                                        <i class="fas fa-lock me-1" style="color:var(--gold);"></i>
                                        Your information is safe with us and will never be shared with third parties.
                                    </p>
                                </div>

                                <!-- Submit -->
                                <div class="col-12">
                                    <button type="submit" id="contact-submit" class="btn-gold w-100 py-3 fw-bold" style="display:block; text-align:center; border:none; cursor:pointer;">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Contact Info + Map -->
            <div class="col-12 col-lg-5">

                <!-- Contact Info Card -->
                <div class="p-4 rounded-3 mb-4" style="background:var(--navy-800); color:#fff;">
                    <h5 class="fw-bold mb-4" style="color:#fff;">
                        <i class="fas fa-address-book me-2" style="color:var(--gold);"></i>Contact Information
                    </h5>

                    <!-- Address -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:2.5rem;height:2.5rem;background:var(--navy-700);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-location-dot" style="color:var(--gold);"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.85rem;color:rgba(255,255,255,.6);">Office Address</div>
                            <div style="color:#fff;"><?= nl2br(htmlspecialchars($agencyAddress)) ?></div>
                        </div>
                    </div>

                    <?php if ($agencyPhone): ?>
                    <!-- Phone -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:2.5rem;height:2.5rem;background:var(--navy-700);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-phone" style="color:var(--gold);"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.85rem;color:rgba(255,255,255,.6);">Phone</div>
                            <a href="tel:<?= htmlspecialchars($agencyPhone) ?>" style="color:#fff;font-weight:600;"><?= htmlspecialchars($agencyPhone) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($agencyWa):
                        // Show the actual WhatsApp number. If it matches the
                        // phone (after stripping formatting) just reuse the
                        // already-pretty Phone string; otherwise format the
                        // digit-only WhatsApp value as +92 3xx xxxxxxx.
                        $phoneDigits = preg_replace('/\D+/', '', (string)$agencyPhone);
                        if ($phoneDigits !== '' && $phoneDigits === $agencyWa) {
                            $waDisplay = $agencyPhone;
                        } elseif (strlen($agencyWa) === 12 && str_starts_with($agencyWa, '92')) {
                            $waDisplay = '+92 ' . substr($agencyWa, 2, 3) . ' ' . substr($agencyWa, 5);
                        } else {
                            $waDisplay = $agencyWa;
                        }
                    ?>
                    <!-- WhatsApp -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:2.5rem;height:2.5rem;background:rgba(37,211,102,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.85rem;color:rgba(255,255,255,.6);">WhatsApp</div>
                            <a href="<?= 'https://wa.me/' . htmlspecialchars($agencyWa) . '?text=' . rawurlencode("Hello! I found your contact page.") ?>"
                               target="_blank" rel="noopener" style="color:#fff;font-weight:600;">
                                <?= htmlspecialchars($waDisplay) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($agencyEmail): ?>
                    <!-- Email -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:2.5rem;height:2.5rem;background:var(--navy-700);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-envelope" style="color:var(--gold);"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.85rem;color:rgba(255,255,255,.6);">Email</div>
                            <a href="mailto:<?= htmlspecialchars($agencyEmail) ?>" style="color:#fff;font-weight:600;"><?= htmlspecialchars($agencyEmail) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (trim($agencyHours) !== ''): ?>
                    <!-- Business Hours -->
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div style="width:2.5rem;height:2.5rem;background:var(--navy-700);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-clock" style="color:var(--gold);"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.85rem;color:rgba(255,255,255,.6);">Business Hours</div>
                            <div style="color:#fff;"><?= nl2br(htmlspecialchars($agencyHours)) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Social Links -->
                    <?php if ($facebookUrl || $instagramUrl || $youtubeUrl): ?>
                    <hr style="border-color:rgba(255,255,255,.15); margin:1.25rem 0;">
                    <div class="footer-social justify-content-start">
                        <?php if ($facebookUrl): ?>
                        <a href="<?= htmlspecialchars($facebookUrl) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($instagramUrl): ?>
                        <a href="<?= htmlspecialchars($instagramUrl) ?>" target="_blank" rel="noopener" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($youtubeUrl): ?>
                        <a href="<?= htmlspecialchars($youtubeUrl) ?>" target="_blank" rel="noopener" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Map -->
                <div class="map-wrap">
                    <!-- Replace this comment block with actual Google Maps embed iframe for production -->
                    <!--
                    <iframe
                        src="https://www.google.com/maps/embed?pb=YOUR_EMBED_ID"
                        width="100%"
                        height="300"
                        style="border:0;"
                        allowfullscreen
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                    -->
                    <div class="map-placeholder">
                        <i class="fas fa-map-location-dot"></i>
                        <strong style="color:var(--navy-700); font-size:.95rem;"><?= htmlspecialchars($agencyAddress) ?></strong>
                        <a href="https://maps.google.com/?q=<?= rawurlencode($agencyAddress) ?>"
                           target="_blank" rel="noopener"
                           class="btn-gold mt-1" style="font-size:.82rem;">
                            <i class="fas fa-diamond-turn-right me-1"></i> Open in Google Maps
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     BRANCH OFFICES
     ============================================================ -->
<section class="section-pad-sm" style="background:var(--navy-50);">
    <div class="container">
        <h3 class="content-heading text-center mb-4">Our Office Locations</h3>
        <div class="row g-4 justify-content-center">
            <?php
            // Build the ordered office list: HQ first, then the main office
            // (if a branch is HQ), then remaining branches.
            $offices = [];
            $offices[] = [
                'is_hq'   => true,
                'icon'    => 'fas fa-building',
                'name'    => $hqOffice['name'] ?: 'Main Office',
                'address' => $hqOffice['address'],
                'phone'   => $hqOffice['phone'],
                'hours'   => $hqOffice['hours'],
            ];
            if ($hqOffice['source'] === 'branch') {
                // A branch is HQ — main office still shown as a regular card.
                $offices[] = [
                    'is_hq'   => false,
                    'icon'    => 'fas fa-building',
                    'name'    => 'Main Office',
                    'address' => $agencyAddress,
                    'phone'   => $agencyPhone,
                    'hours'   => $agencyHours,
                ];
            }
            foreach ($branches as $br) {
                if (!empty($br['is_hq'])) continue; // already the HQ card
                $bName    = trim((string)($br['name']    ?? ''));
                $bAddress = trim((string)($br['address'] ?? ''));
                $bPhone   = trim((string)($br['phone']   ?? ''));
                $bHours   = trim((string)($br['hours']   ?? ''));
                if ($bName === '' && $bAddress === '' && $bPhone === '' && $bHours === '') continue;
                $offices[] = [
                    'is_hq'   => false,
                    'icon'    => 'fas fa-store',
                    'name'    => $bName ?: 'Branch Office',
                    'address' => $bAddress,
                    'phone'   => $bPhone,
                    'hours'   => $bHours,
                ];
            }
            foreach ($offices as $off):
            ?>
            <div class="col-12 col-md-5">
                <div class="office-card h-100">
                    <div class="office-card-title">
                        <i class="<?= htmlspecialchars($off['icon']) ?>"></i>
                        <?= htmlspecialchars($off['name']) ?>
                        <?php if ($off['is_hq']): ?>
                        <span class="badge bg-success ms-auto" style="font-size:.68rem;">HQ</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($off['address']): ?>
                    <div class="office-info-row">
                        <i class="fas fa-location-dot"></i>
                        <span><?= htmlspecialchars($off['address']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($off['phone']): ?>
                    <div class="office-info-row">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= htmlspecialchars($off['phone']) ?>"><?= htmlspecialchars($off['phone']) ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if (trim((string)$off['hours']) !== ''): ?>
                    <div class="office-info-row">
                        <i class="fas fa-clock"></i>
                        <span><?= nl2br(htmlspecialchars($off['hours'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     INLINE SCRIPTS — Form character counter + validation
     ============================================================ -->
<script>
(function () {
    'use strict';

    // Character counter for message textarea
    var $msg       = document.getElementById('contact-message');
    var $charCount = document.getElementById('msg-char-count');

    if ($msg && $charCount) {
        $msg.addEventListener('input', function () {
            var len = this.value.length;
            $charCount.textContent = len + ' / 2000';
            $charCount.style.color = len > 1800 ? '#dc3545' : '#6c757d';
        });
    }

    // Client-side validation before AJAX
    var $form = document.getElementById('contact-form');
    if ($form) {
        $form.addEventListener('submit', function (e) {
            var valid = true;

            // Name
            var name = document.getElementById('contact-name');
            if (!name.value.trim()) {
                name.classList.add('is-invalid');
                valid = false;
            } else {
                name.classList.remove('is-invalid');
            }

            // Email
            var email = document.getElementById('contact-email');
            var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRe.test(email.value.trim())) {
                email.classList.add('is-invalid');
                valid = false;
            } else {
                email.classList.remove('is-invalid');
            }

            // Message
            var message = document.getElementById('contact-message');
            if (message.value.trim().length < 20) {
                message.classList.add('is-invalid');
                valid = false;
            } else {
                message.classList.remove('is-invalid');
            }

            if (!valid) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
