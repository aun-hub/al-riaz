<?php
/**
 * Al-Riaz Associates — Thank You Page (Post-Enquiry)
 * Public URL: /thank-you.php
 * Shown after a successful inquiry/contact form submission.
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

$propertyRef  = sanitize($_GET['ref']  ?? '');
$clientName   = sanitize($_GET['name'] ?? '');
$inquiryType  = sanitize($_GET['type'] ?? 'general');

$waMessages = [
    'property' => "Hello! I just submitted an inquiry about a property on your website. Looking forward to hearing from you.",
    'project'  => "Hello! I just enquired about a project on Al-Riaz Associates website. Please share more details.",
    'general'  => "Hello! I just filled in the contact form on your website. Can you please assist me?",
    'contact'  => "Hello! I just submitted a contact form on Al-Riaz Associates website.",
];
$waMessage = $waMessages[$inquiryType] ?? $waMessages['general'];
if ($propertyRef) {
    $waMessage .= " (Ref: $propertyRef)";
}

$pageTitle = 'Thank You';
$metaDesc  = 'Thank you for your inquiry. Al-Riaz Associates will contact you within 2-4 working hours.';

require_once 'includes/header.php';

$b = defined('BASE_PATH') ? BASE_PATH : '';
$waHref = 'https://wa.me/' . SITE_WHATSAPP . '?text=' . rawurlencode($waMessage);
?>

<div class="thank-you-wrap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-7">
                <div class="thank-you-card reveal">

                    <div class="check-circle-anim" aria-hidden="true">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <h1 style="color:var(--navy-800); font-weight:800; font-size:2rem; margin-bottom:0.5rem;">
                        Thank You<?= $clientName ? ', ' . htmlspecialchars($clientName) : '' ?>!
                    </h1>
                    <p style="color:var(--text-secondary); font-size:1.05rem; line-height:1.7; margin-bottom:1.75rem;">
                        Your inquiry has been received. Our team will review your message and
                        <strong>contact you within 2–4 working hours</strong>.
                    </p>

                    <!-- WhatsApp fast-track CTA -->
                    <div style="background:var(--navy-50); border:1px solid var(--navy-100); border-radius:var(--radius-lg); padding:1.25rem; margin-bottom:1.75rem;">
                        <p style="color:var(--navy-700); font-weight:600; font-size:.95rem; margin-bottom:.5rem;">
                            <i class="fa-brands fa-whatsapp" style="color:#25D366;"></i>
                            Want a faster response?
                        </p>
                        <p style="color:var(--text-secondary); font-size:.87rem; line-height:1.6; margin-bottom:1rem;">
                            Message us on WhatsApp and our team will respond
                            <strong>within minutes</strong> during business hours
                            (Mon–Sat 9AM–7PM, Sun 11AM–4PM).
                        </p>
                        <a href="<?= htmlspecialchars($waHref) ?>" target="_blank" rel="noopener noreferrer"
                           class="btn-whatsapp" style="display:inline-flex; align-items:center; gap:.5rem; justify-content:center; width:100%;">
                            <i class="fa-brands fa-whatsapp"></i> Message Us on WhatsApp
                        </a>
                    </div>

                    <?php if ($propertyRef): ?>
                    <div style="background:#F8FAFD; border:1px solid var(--navy-100); border-radius:var(--radius-md); padding:.6rem .85rem; color:var(--text-secondary); font-size:.82rem; margin-bottom:1.25rem;">
                        <i class="fa-solid fa-tag" style="color:var(--gold);"></i>
                        Reference: <strong style="color:var(--navy-700);"><?= htmlspecialchars($propertyRef) ?></strong>
                    </div>
                    <?php endif; ?>

                    <!-- What happens next -->
                    <div style="text-align:left; background:#F8FAFD; border:1px solid var(--navy-100); border-radius:var(--radius-lg); padding:1.25rem; margin-bottom:1.75rem;">
                        <p style="font-weight:700; color:var(--navy-800); font-size:.95rem; margin-bottom:.85rem;">
                            <i class="fa-solid fa-list-ul" style="color:var(--gold);"></i> What happens next
                        </p>
                        <ol class="next-steps-list">
                            <li><span class="step-num">1</span> Our team reviews your inquiry</li>
                            <li><span class="step-num">2</span> A consultant will call or WhatsApp you</li>
                            <li><span class="step-num">3</span> We schedule a property viewing or consultation</li>
                            <li><span class="step-num">4</span> We guide you through the buying/renting process</li>
                        </ol>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <a href="<?= $b ?>/search.php" class="btn-navy px-4">
                            <i class="fa-solid fa-magnifying-glass"></i> Browse More Properties
                        </a>
                        <a href="<?= $b ?>/" class="btn-gold px-4">
                            <i class="fa-solid fa-house"></i> Back to Home
                        </a>
                    </div>

                    <hr style="margin:1.75rem 0; border-color:var(--navy-100);">
                    <p style="color:var(--text-secondary); font-size:.85rem; margin-bottom:0;">
                        <i class="fa-solid fa-phone" style="color:var(--gold);"></i>
                        You can also call us directly at
                        <a href="tel:<?= SITE_PHONE ?>" style="color:var(--navy-700); font-weight:700; text-decoration:none;">
                            <?= htmlspecialchars(SITE_PHONE) ?>
                        </a>
                    </p>
                </div>

                <!-- Trust indicators below card -->
                <div class="d-flex align-items-center justify-content-center gap-4 mt-4 flex-wrap">
                    <div style="text-align:center; color:var(--text-secondary); font-size:.8rem;">
                        <i class="fa-solid fa-shield-halved" style="color:var(--gold); font-size:1.2rem; display:block; margin-bottom:.25rem;"></i>
                        Verified Agency
                    </div>
                    <div style="text-align:center; color:var(--text-secondary); font-size:.8rem;">
                        <i class="fa-solid fa-certificate" style="color:var(--gold); font-size:1.2rem; display:block; margin-bottom:.25rem;"></i>
                        Authorised Dealer
                    </div>
                    <div style="text-align:center; color:var(--text-secondary); font-size:.8rem;">
                        <i class="fa-solid fa-lock" style="color:var(--gold); font-size:1.2rem; display:block; margin-bottom:.25rem;"></i>
                        Data Secure
                    </div>
                    <div style="text-align:center; color:var(--text-secondary); font-size:.8rem;">
                        <i class="fa-solid fa-headset" style="color:var(--gold); font-size:1.2rem; display:block; margin-bottom:.25rem;"></i>
                        24/7 Support
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
