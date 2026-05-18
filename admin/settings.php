<?php
/**
 * Al-Riaz Associates — Site Settings
 */
$pageTitle = 'Settings';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance();
$settingsFile = __DIR__ . '/../config/settings.json';

// Load settings
function loadSettings(string $file): array {
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return [];
}

function saveSettings(string $file, array $data): bool {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

$settings = loadSettings($settingsFile);

$defaults = [
    'agency_name'    => 'Al-Riaz Associates',
    'agency_tagline' => 'Your Trusted Real Estate Partner in Pakistan',
    'brand_subtitle' => 'Real Estate',
    'phone'          => '+92 300 123 4567',
    'whatsapp'       => '923001234567',
    'email'          => 'info@alriazassociates.pk',
    'address'        => 'Islamabad, Pakistan',
    'address_lat'    => '',
    'address_lng'    => '',
    'business_hours' => "Mon–Sat: 9:00 AM – 7:00 PM\nSunday: 11:00 AM – 4:00 PM",
    'website'        => 'https://alriazassociates.pk',
    'facebook_url'   => '',
    'instagram_url'  => '',
    'youtube_url'    => '',
    'notify_email_recipients' => '',
    'notify_on_inquiry' => '1',
    'notify_on_listing' => '0',
    'default_city'   => 'islamabad',
    'watermark'      => '0',
    'currency_format'=> 'pakistan', // pakistan = Lakh/Crore, raw = number
    'logo_path'      => '',
    // About-page "Our Story" section (editable on Settings → About Page)
    // Top page-header banner (title + subtitle that appear above breadcrumbs)
    'about_header_title'      => 'About Al-Riaz Associates',
    'about_header_sub'        => '',
    'about_story_label'       => 'Our Story',
    'about_story_heading'     => 'Built on Trust & Transparency',
    'about_story_body'        => "Al-Riaz Associates was founded with a clear mission: to bring transparency, integrity, and professionalism to Pakistan's real estate market. Based in the heart of Islamabad, we began as a small consultancy helping families find their dream homes in Rawalpindi and Islamabad.\n\nOver the years, we have grown into a full-service real estate agency, becoming an **authorised dealer** for Pakistan's most prestigious developments including Bahria Town, DHA, Capital Smart City, Gulberg Greens, and Blue World City.\n\nToday, our team of experienced property consultants serves clients across Islamabad, Rawalpindi, Lahore, and Karachi — offering verified listings, transparent pricing, and end-to-end support from property search to final possession.",
    'about_story_image'       => '',
    'about_story_badge_value' => '5+ Years',
    'about_story_badge_label' => 'In Real Estate',

    // About-page Mission & Vision section header.
    'about_mv_label'    => 'Purpose',
    'about_mv_title'    => 'Our Mission & Vision',
    'about_mv_subtitle' => "Why we do what we do — and where we're headed next.",

    // Mission card.
    'about_mission' => [
        'icon'    => 'fa-bullseye',
        'tag'     => '01 — Mission',
        'title'   => 'Empowering Pakistanis to make the best property decisions.',
        'body'    => "Transparent pricing, verified listings, and honest advice — the way real estate should have always been. We turn paperwork, site visits, and payment plans into a process you can actually understand.",
        'bullets' => [
            'Verified ownership & NOC on every listing',
            'Clear, up-front brokerage disclosure',
            'End-to-end support, from search to possession',
        ],
    ],

    // Vision card.
    'about_vision' => [
        'icon'    => 'fa-eye',
        'tag'     => '02 — Vision',
        'title'   => "Becoming Pakistan's most trusted real estate partner.",
        'body'    => "We want \"Al-Riaz\" to mean the same thing in Islamabad as it does in Karachi — integrity, expertise, and client-first service at every step. Built on relationships that outlast a single transaction.",
        'bullets' => [
            "Authorised dealer for Pakistan's top developments",
            'Data-driven investment guidance',
            'A team that picks up the phone',
        ],
    ],

    // CEO message section.
    'about_ceo_show'    => '1',
    'about_ceo_label'   => 'A Message from Our CEO',
    'about_ceo_heading' => "Welcome to Al-Riaz Associates",
    'about_ceo_message' => "Real estate is one of the most important decisions a family ever makes. At Al-Riaz Associates, we treat that responsibility with the seriousness it deserves — verifying every listing, disclosing every fee, and walking with our clients from the first site visit to the final possession.\n\nThank you for considering us. We look forward to earning your trust.",
    'about_ceo_image'   => '',
    'about_ceo_name'    => '',
    'about_ceo_title'   => 'Founder & CEO',

    // Awards & Recognition strip (4 cards) on /about.php.
    // `about_awards_enabled` is the public-site visibility toggle —
    // unchecking it hides the entire strip without losing the saved cards.
    'about_awards_enabled' => 1,
    'about_awards_heading' => 'Awards & Recognition',
    'about_awards_sub'     => 'Our commitment to excellence, recognized by the industry',
    'about_awards' => [
        ['icon' => 'fa-trophy',    'title' => 'Best Authorised Dealer 2024',    'desc' => 'Awarded by Bahria Town Pvt Ltd'],
        ['icon' => 'fa-medal',     'title' => 'Top Sales Partner 2023',         'desc' => 'DHA Islamabad Regional Award'],
        ['icon' => 'fa-star',      'title' => 'Client Satisfaction Award 2023', 'desc' => 'Capital Smart City Recognition'],
        ['icon' => 'fa-handshake', 'title' => 'Trusted Agency 2022',            'desc' => 'Pakistan Real Estate Forum'],
    ],

    // Core Values strip (4 cards).
    'about_values' => [
        ['icon' => 'fa-handshake',    'title' => 'Integrity',    'desc' => 'We never compromise on honesty in our dealings.'],
        ['icon' => 'fa-check-circle', 'title' => 'Transparency', 'desc' => 'Clear pricing, no hidden costs or surprises.'],
        ['icon' => 'fa-user-tie',     'title' => 'Expertise',    'desc' => 'Seasoned consultants with deep market knowledge.'],
        ['icon' => 'fa-headset',      'title' => 'Client First', 'desc' => 'Your satisfaction is our top priority, always.'],
    ],

    // Bottom CTA card on /about.php (navy gradient panel above the footer).
    'about_cta_label'           => "Let's Talk",
    'about_cta_heading'         => 'Start your property journey today.',
    'about_cta_sub'             => 'Speak to an expert consultant — no obligation, completely free advice. A real person will reply within minutes during business hours.',
    'about_cta_primary_label'   => 'Chat on WhatsApp',
    'about_cta_primary_url'     => '',
    'about_cta_secondary_label' => 'Send a Message',
    'about_cta_secondary_url'   => '',
    'about_cta_hours'           => 'Mon–Sat 9am–7pm · Sun 11am–4pm',
    'about_cta_badge_value'     => '',
    'about_cta_badge_label'     => 'Years Trusted',

    // ── Custom Listing Types, Possession Statuses & Project Statuses (Settings → Listings) ──
    'listing_types_custom'             => [],
    'listing_types_disabled_core'      => [], // built-in slugs hidden from the picker
    'possession_statuses_custom'       => [],
    'possession_statuses_disabled_core'=> [],
    'project_statuses_custom'          => [],
    'project_statuses_disabled_core'   => [],

    // ── Projects page header banner & bottom CTA (on /projects.php) ──
    'projects_header_title'       => 'Real Estate Projects',
    'projects_header_sub'         => 'Authorised developments across Pakistan',
    'projects_cta_heading'        => 'Looking for a Specific Project?',
    'projects_cta_sub'            => 'Contact our team — we work with 20+ authorised developers across Pakistan.',
    'projects_cta_primary_label'  => 'Get in Touch',
    'projects_cta_primary_url'    => '/contact.php',

    // ── Theme (live colour picker; injected as inline CSS in header.php) ──
    'theme_primary'   => '#F5B301',   // gold accent (overrides --gold)
    'theme_secondary' => '#0F2044',   // brand navy (overrides --navy-700)

    // ── Homepage banners (hero + final CTA) ──
    'hero_badge'      => "Pakistan's Trusted Real Estate Agency",
    'hero_heading'    => "Smart real estate",
    'hero_heading_accent' => "starts here.",
    'hero_sub'        => "Authorised dealer for Bahria Town, DHA, Capital Smart City and Pakistan's leading developments. Verified listings across Islamabad, Rawalpindi, Lahore and Karachi.",
    'hero_cta_primary_label'   => 'Browse Properties',
    'hero_cta_primary_url'     => '',
    'hero_cta_secondary_label' => 'WhatsApp Us',
    'hero_cta_secondary_url'   => '',
    'cta_label'             => "Let's Talk",
    'cta_heading'           => 'Ready to buy, sell, or rent?',
    'cta_sub'               => "Message us. A real person will reply within minutes during business hours — no bots, no templates, no fluff.",
    'cta_primary_label'     => 'Chat on WhatsApp',
    'cta_primary_url'       => '',
    'cta_secondary_label'   => 'Contact Form',
    'cta_secondary_url'     => '',
    'cta_hours'             => 'Mon–Sat 9am–7pm · Sun 11am–4pm',
    'cta_badge_value'       => '',
    'cta_badge_label'       => 'Years Trusted',

    // Hero stats strip (3 cells below the CTA). Empty value → auto-derived.
    'home_stats' => [
        ['value' => '', 'label' => 'Properties'],
        ['value' => '', 'label' => 'Happy Clients'],
        ['value' => '', 'label' => 'Years Active'],
    ],

    // Wide stats band lower on the page (4 cells). Empty value → auto-derived.
    'home_strip_stats' => [
        ['value' => '', 'label' => 'Properties Listed'],
        ['value' => '', 'label' => 'Happy Clients'],
        ['value' => '', 'label' => 'Projects Authorised'],
        ['value' => '', 'label' => 'Years in Business'],
    ],

    // Hero mosaic — three photos on the right of the hero. Empty falls back to stock.
    'hero_mosaic_main'  => '',
    'hero_mosaic_tile1' => '',
    'hero_mosaic_tile2' => '',

    // Floating rating card (overlays the mosaic).
    'hero_rating_score'   => '',                          // empty → average from reviews
    'hero_rating_caption' => 'From {count}+ happy clients', // {count} → review count, {clients} → happy_clients

    // Floating "Authorised Developers" card.
    'hero_dev_tags'           => ['BT', 'DHA', 'CSC'],
    'hero_dev_count_override' => '', // empty → use total published projects
    'hero_dev_label'          => 'Authorised Developers',

    // Intent tiles section ("What are you looking for?")
    'intent_label'   => 'Get Started',
    'intent_heading' => 'What are you looking for?',
    'intent_sub'     => "Pick your path — we'll take it from there.",
    'intent_cards' => [
        ['icon'=>'fa-house-chimney', 'title'=>'Buy a Property',     'desc'=>"Verified houses, flats, plots, and commercial units across Pakistan's top cities.",                                       'cta_label'=>'Browse to buy',  'cta_url'=>'/listings.php?purpose=sale', 'highlight'=>'0'],
        ['icon'=>'fa-key',           'title'=>'Rent a Place',       'desc'=>'Monthly rentals — apartments, offices, shops, and more with verified owners.',                                            'cta_label'=>'Browse rentals', 'cta_url'=>'/listings.php?purpose=rent', 'highlight'=>'1'],
        ['icon'=>'fa-chart-line',    'title'=>'Invest in Projects', 'desc'=>'Authorised launches from Bahria, DHA, Capital Smart City, and more — file-level entry points.',                          'cta_label'=>'View projects',  'cta_url'=>'/projects.php',              'highlight'=>'0'],
    ],

    // Featured Projects section header (on /index.php).
    'home_featured_label'      => 'New Developments',
    'home_featured_heading'    => 'Featured Projects',
    'home_featured_sub'        => "Authorised dealer for Pakistan's top real estate developments",
    'home_featured_cta_label'  => 'View All',

    // Featured Properties section header (on /index.php).
    'home_props_label'             => 'Hot Listings',
    'home_props_heading'           => 'Featured Properties',
    'home_props_sub'               => 'Handpicked listings across Islamabad, Rawalpindi & beyond',
    'home_props_cta_label'         => 'Browse All',
    'home_props_bottom_cta_label'  => 'Browse All Properties',

    // "Why Choose ..." section.
    'why_label'   => 'Why Us',
    'why_heading' => 'Why Choose Al-Riaz Associates?',
    'why_sub'     => '', // empty → auto "Your trusted real estate partner in Pakistan since {YEAR}"
    'why_cards' => [
        ['icon'=>'fa-certificate',     'title'=>'Authorised Dealer',      'desc'=>'Officially authorised for Bahria Town, DHA, Capital Smart City, Blue World City, Gulberg Greens, and more. All listings are verified and legitimate.'],
        ['icon'=>'fa-brands fa-whatsapp','title'=>'WhatsApp-First Support', 'desc'=>'Reach us instantly via WhatsApp for inquiries, site visits, and payment plans. Our team responds within minutes during business hours.'],
        ['icon'=>'fa-shield-halved',   'title'=>'Verified Listings',      'desc'=>'Every property undergoes thorough verification. We ensure accurate pricing, legal status, and ownership documentation before listing.'],
        ['icon'=>'fa-handshake',       'title'=>'Expert Negotiation',     'desc'=>"Our seasoned agents negotiate the best deals on your behalf — whether you're buying, selling, or renting. We protect your investment."],
        ['icon'=>'fa-file-contract',   'title'=>'Legal Documentation',    'desc'=>'We guide you through all paperwork — registry, NOC, transfer letters, and payment receipts — ensuring a smooth, stress-free transaction.'],
        ['icon'=>'fa-chart-line',      'title'=>'Investment Advice',      'desc'=>'Get expert guidance on high-yield investment opportunities, upcoming projects, and market trends to maximise your returns.'],
    ],
];

$settings = array_merge($defaults, $settings);

$activeTab = $_GET['tab'] ?? 'banners';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $tab = $_POST['tab'] ?? 'agency';

    if ($tab === 'agency') {
        $fields = ['agency_name','agency_tagline','brand_subtitle','phone','whatsapp','email','address','website','facebook_url','instagram_url','youtube_url'];
        foreach ($fields as $f) {
            $settings[$f] = trim($_POST[$f] ?? $settings[$f]);
        }

        // Main-office coordinates — accept only valid numbers in range.
        $latIn = trim((string)($_POST['address_lat'] ?? ''));
        $lngIn = trim((string)($_POST['address_lng'] ?? ''));
        $settings['address_lat'] = ($latIn !== '' && is_numeric($latIn) && $latIn >=  -90 && $latIn <=  90)  ? $latIn : '';
        $settings['address_lng'] = ($lngIn !== '' && is_numeric($lngIn) && $lngIn >= -180 && $lngIn <= 180) ? $lngIn : '';

        // WhatsApp number: if admin left it empty, auto-derive from the phone
        // (strip non-digits; normalise a leading "0" to Pakistan country code
        // 92 so "0300 123 4567" still produces a working wa.me link).
        // If admin entered a value, keep it as-is but still strip non-digits
        // because wa.me only accepts digits. Branch phones are unaffected.
        $waRaw = preg_replace('/\D+/', '', (string)($settings['whatsapp'] ?? ''));
        if ($waRaw === '') {
            $waRaw = preg_replace('/\D+/', '', (string)($settings['phone'] ?? ''));
            if ($waRaw !== '' && str_starts_with($waRaw, '0')) {
                $waRaw = '92' . substr($waRaw, 1);
            }
        }
        $settings['whatsapp'] = $waRaw;

        // Business hours — 7-day structured schedule.
        $schedule = parsePostedSchedule($_POST['hours'] ?? null);
        $settings['business_hours_schedule'] = $schedule;
        // Also keep a formatted string for any legacy readers.
        $settings['business_hours'] = formatBusinessHours($schedule);

        // Branches (repeater) → replace all rows in `branches` table.
        // Small table, <20 rows typical — delete + reinsert is simpler than diffing.
        try {
            $db->beginTransaction();
            $db->exec('DELETE FROM branches');
            $ins = $db->prepare(
                'INSERT INTO branches (name, address, lat, lng, phone, hours, hours_schedule, is_hq, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $hqPick = isset($_POST['hq_pick']) ? (string)$_POST['hq_pick'] : 'main';
            $order  = 0;
            $branchIdx = 0;
            if (!empty($_POST['branches']) && is_array($_POST['branches'])) {
                foreach ($_POST['branches'] as $row) {
                    if (!is_array($row)) continue;
                    $name    = trim($row['name']    ?? '');
                    $address = trim($row['address'] ?? '');
                    $phone   = trim($row['phone']   ?? '');
                    $brSched = parsePostedSchedule($row['hours_schedule'] ?? null);
                    $brHoursText = formatBusinessHours($brSched);
                    if ($name === '' && $address === '' && $phone === '') continue;

                    $rowLat = trim((string)($row['lat'] ?? ''));
                    $rowLng = trim((string)($row['lng'] ?? ''));
                    $rowLat = ($rowLat !== '' && is_numeric($rowLat) && $rowLat >=  -90 && $rowLat <=  90)  ? $rowLat : null;
                    $rowLng = ($rowLng !== '' && is_numeric($rowLng) && $rowLng >= -180 && $rowLng <= 180) ? $rowLng : null;

                    $isHq = ($hqPick === 'branch-' . $branchIdx) ? 1 : 0;
                    $ins->execute([
                        $name,
                        $address,
                        $rowLat,
                        $rowLng,
                        $phone,
                        $brHoursText,
                        json_encode($brSched, JSON_UNESCAPED_UNICODE),
                        $isHq,
                        $order++,
                    ]);
                    $branchIdx++;
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[settings branches] ' . $e->getMessage());
            setFlash('danger', 'Agency profile saved, but branches could not be updated: ' . $e->getMessage());
        }

        // Logo upload
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $mime = mime_content_type($_FILES['logo']['tmp_name']);
            if (in_array($mime, ALLOWED_IMAGE_TYPES)) {
                $ext  = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $fname= 'logo.' . $ext;
                $dir  = __DIR__ . '/../assets/images/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fname)) {
                    $settings['logo_path'] = '/assets/images/' . $fname;
                }
            }
        }

        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated agency profile settings');
        setFlash('success', 'Agency profile settings saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=agency'); exit;
    }

    if ($tab === 'navigation') {
        $locations = ['header', 'footer_quick', 'footer_property_types'];
        foreach ($locations as $loc) {
            $rows = [];
            $posted = $_POST['nav_' . $loc] ?? [];
            if (is_array($posted)) {
                foreach ($posted as $item) {
                    if (!is_array($item)) continue;
                    $label  = trim((string)($item['label'] ?? ''));
                    $preset = trim((string)($item['url_preset'] ?? ''));
                    $custom = trim((string)($item['url_custom'] ?? ''));
                    $url    = ($preset !== '' && $preset !== '__custom__') ? $preset : $custom;
                    if ($label === '' && $url === '') continue; // drop fully blank rows
                    $rows[] = ['label' => $label, 'url' => $url];
                }
            }
            $settings['nav_' . $loc] = $rows;
        }
        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated navigation menus');
        setFlash('success', 'Navigation menus saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=navigation'); exit;
    }

    if ($tab === 'smtp') {
        // Always persist the posted fields first, so "Test" uses what's on screen.
        saveSmtpConfig([
            'host'       => trim($_POST['smtp_host']       ?? ''),
            'port'       => trim($_POST['smtp_port']       ?? '587'),
            'user'       => trim($_POST['smtp_user']       ?? ''),
            'pass'       => $_POST['smtp_pass']            ?? '', // blank = keep current
            'from_name'  => trim($_POST['smtp_from_name']  ?? ''),
            'from_email' => trim($_POST['smtp_from_email'] ?? ''),
            'reply_to'   => trim($_POST['smtp_reply_to']   ?? ''),
        ]);

        $action = $_POST['smtp_action'] ?? 'save';
        if ($action === 'test') {
            $to = $_SESSION['admin_email'] ?? '';
            if ($to === '') {
                setFlash('danger', 'No admin email on your session — cannot send a test.');
            } else {
                $result = sendMail(
                    $to,
                    'Test Email — Al-Riaz Associates Admin',
                    "<p>This is a test email from the Al-Riaz Associates admin panel.</p>\n"
                    . '<p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>',
                    ['html' => true, 'alt_body' => "Test email from admin panel. Sent at: " . date('Y-m-d H:i:s')]
                );
                if ($result['ok']) {
                    setFlash('success', "Test email sent to $to. Check your inbox (or Mailtrap/Mailpit).");
                } else {
                    setFlash('danger', 'Test email failed: ' . $result['error']);
                }
            }
        } else {
            auditLog('update','settings',0,'Updated SMTP configuration');
            setFlash('success', 'SMTP settings saved.');
        }
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=smtp'); exit;
    }

    if ($tab === 'notifications') {
        $settings['notify_email_recipients'] = trim($_POST['notify_email_recipients'] ?? '');
        $settings['notify_on_inquiry']        = isset($_POST['notify_on_inquiry']) ? '1' : '0';
        $settings['notify_on_listing']        = isset($_POST['notify_on_listing'])  ? '1' : '0';
        saveSettings($settingsFile, $settings);
        setFlash('success', 'Notification settings saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=notifications'); exit;
    }

    if ($tab === 'preferences') {
        $settings['default_city']   = trim($_POST['default_city']   ?? $settings['default_city']);
        $settings['watermark']      = isset($_POST['watermark'])  ? '1' : '0';
        $settings['currency_format']= $_POST['currency_format'] === 'raw' ? 'raw' : 'pakistan';
        saveSettings($settingsFile, $settings);
        setFlash('success', 'Preferences saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=preferences'); exit;
    }

    if ($tab === 'theme') {
        $isHex = static fn(string $v): bool => (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $v);
        $primary   = trim((string)($_POST['theme_primary']   ?? ''));
        $secondary = trim((string)($_POST['theme_secondary'] ?? ''));
        if (isset($_POST['reset_defaults'])) {
            $primary   = '#F5B301';
            $secondary = '#0F2044';
        }
        if (!$isHex($primary))   $primary   = $settings['theme_primary']   ?? '#F5B301';
        if (!$isHex($secondary)) $secondary = $settings['theme_secondary'] ?? '#0F2044';
        $settings['theme_primary']   = strtolower($primary);
        $settings['theme_secondary'] = strtolower($secondary);
        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated theme colors');
        setFlash('success', isset($_POST['reset_defaults']) ? 'Theme reset to defaults.' : 'Theme saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=theme'); exit;
    }

    if ($tab === 'banners') {
        $fields = [
            'hero_badge','hero_heading','hero_heading_accent','hero_sub',
            'hero_cta_primary_label','hero_cta_primary_url',
            'hero_cta_secondary_label','hero_cta_secondary_url',
            'cta_label','cta_heading','cta_sub',
            'cta_primary_label','cta_primary_url',
            'cta_secondary_label','cta_secondary_url',
            'cta_hours','cta_badge_value','cta_badge_label',
            'hero_rating_score','hero_rating_caption',
            'hero_dev_label','hero_dev_count_override',
            'intent_label','intent_heading','intent_sub',
            'home_featured_label','home_featured_heading','home_featured_sub','home_featured_cta_label',
            'home_props_label','home_props_heading','home_props_sub','home_props_cta_label','home_props_bottom_cta_label',
            'why_label','why_heading','why_sub',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }

        // Hero stats strip (3 cells).
        $statsIn = $_POST['home_stats'] ?? [];
        $homeStats = [];
        for ($i = 0; $i < 3; $i++) {
            $row = is_array($statsIn[$i] ?? null) ? $statsIn[$i] : [];
            $homeStats[] = [
                'value' => trim((string)($row['value'] ?? '')),
                'label' => trim((string)($row['label'] ?? '')),
            ];
        }
        $settings['home_stats'] = $homeStats;

        // Wide stats band (4 cells).
        $stripIn = $_POST['home_strip_stats'] ?? [];
        $stripStats = [];
        for ($i = 0; $i < 4; $i++) {
            $row = is_array($stripIn[$i] ?? null) ? $stripIn[$i] : [];
            $stripStats[] = [
                'value' => trim((string)($row['value'] ?? '')),
                'label' => trim((string)($row['label'] ?? '')),
            ];
        }
        $settings['home_strip_stats'] = $stripStats;

        // Hero floating dev pills (3 tags).
        $tagsIn = $_POST['hero_dev_tags'] ?? [];
        $devTags = [];
        for ($i = 0; $i < 3; $i++) {
            $devTags[] = trim((string)($tagsIn[$i] ?? ''));
        }
        $settings['hero_dev_tags'] = $devTags;

        // Intent cards (3 items).
        $intentIn = $_POST['intent_cards'] ?? [];
        $intent = [];
        for ($i = 0; $i < 3; $i++) {
            $row = is_array($intentIn[$i] ?? null) ? $intentIn[$i] : [];
            $intent[] = [
                'icon'      => preg_replace('/[^a-z0-9_-]/i', '', trim((string)($row['icon']  ?? ''))) ?: 'fa-house',
                'title'     => trim((string)($row['title']     ?? '')),
                'desc'      => trim((string)($row['desc']      ?? '')),
                'cta_label' => trim((string)($row['cta_label'] ?? '')),
                'cta_url'   => trim((string)($row['cta_url']   ?? '')),
                'highlight' => isset($row['highlight']) ? '1' : '0',
            ];
        }
        $settings['intent_cards'] = $intent;

        // Why-choose cards (6 items).
        $whyIn = $_POST['why_cards'] ?? [];
        $why = [];
        for ($i = 0; $i < 6; $i++) {
            $row = is_array($whyIn[$i] ?? null) ? $whyIn[$i] : [];
            $rawIcon = trim((string)($row['icon'] ?? ''));
            // Accept "fa-foo" or "fa-brands fa-foo" — only sanitise the icon name parts.
            $iconParts = preg_split('/\s+/', $rawIcon);
            $iconClean = implode(' ', array_filter(array_map(static fn($p) => preg_replace('/[^a-z0-9_-]/i', '', $p), $iconParts)));
            $why[] = [
                'icon'  => $iconClean ?: 'fa-circle',
                'title' => trim((string)($row['title'] ?? '')),
                'desc'  => trim((string)($row['desc']  ?? '')),
            ];
        }
        $settings['why_cards'] = $why;

        // Clear-image checkboxes — admin can remove an uploaded mosaic image
        // to revert that slot to the stock fallback. Runs before upload so a
        // simultaneous "clear + new upload" replaces cleanly.
        foreach (['hero_mosaic_main','hero_mosaic_tile1','hero_mosaic_tile2'] as $slot) {
            if (empty($_POST[$slot . '_clear'])) continue;
            $old = (string)($settings[$slot] ?? '');
            if ($old && str_starts_with($old, '/assets/images/' . $slot . '_')) {
                $oldFs = __DIR__ . '/..' . $old;
                if (is_file($oldFs)) @unlink($oldFs);
            }
            $settings[$slot] = '';
        }

        // Optional mosaic image uploads (3 slots).
        foreach (['hero_mosaic_main','hero_mosaic_tile1','hero_mosaic_tile2'] as $slot) {
            if (empty($_FILES[$slot]['tmp_name']) || $_FILES[$slot]['error'] !== UPLOAD_ERR_OK) continue;
            if ($_FILES[$slot]['size'] > MAX_FILE_SIZE) {
                setFlash('danger', ucwords(str_replace('_',' ',$slot)) . ' exceeds the file size limit.');
                continue;
            }
            $mime = mime_content_type($_FILES[$slot]['tmp_name']);
            if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
                setFlash('danger', ucwords(str_replace('_',' ',$slot)) . ' must be JPEG, PNG, WebP, or GIF.');
                continue;
            }
            $ext   = strtolower(pathinfo($_FILES[$slot]['name'], PATHINFO_EXTENSION));
            $fname = $slot . '_' . uniqid() . '.' . $ext;
            $dir   = __DIR__ . '/../assets/images/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES[$slot]['tmp_name'], $dir . $fname)) {
                $old = (string)($settings[$slot] ?? '');
                if ($old && str_starts_with($old, '/assets/images/' . $slot . '_')) {
                    $oldFs = __DIR__ . '/..' . $old;
                    if (is_file($oldFs)) @unlink($oldFs);
                }
                $settings[$slot] = '/assets/images/' . $fname;
            }
        }

        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated homepage banners');
        setFlash('success', 'Homepage content saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=banners'); exit;
    }

    if ($tab === 'about') {
        // ── Story sub-section ───────────────────────────────────────
        foreach ([
            'about_header_title','about_header_sub',
            'about_story_label','about_story_heading','about_story_body','about_story_badge_value','about_story_badge_label',
            'about_cta_label','about_cta_heading','about_cta_sub',
            'about_cta_primary_label','about_cta_primary_url',
            'about_cta_secondary_label','about_cta_secondary_url',
            'about_cta_hours','about_cta_badge_value','about_cta_badge_label',
        ] as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }

        // ── Mission & Vision header ────────────────────────────────
        foreach (['about_mv_label','about_mv_title','about_mv_subtitle'] as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }

        // ── Mission card / Vision card ─────────────────────────────
        foreach (['about_mission', 'about_vision'] as $key) {
            $in = $_POST[$key] ?? [];
            $bulletsIn = is_array($in['bullets'] ?? null) ? $in['bullets'] : [];
            $bullets = [];
            foreach ($bulletsIn as $b) {
                $b = trim((string)$b);
                if ($b !== '') $bullets[] = $b;
            }
            $settings[$key] = [
                'icon'    => preg_replace('/[^a-z0-9_-]/i', '', trim((string)($in['icon'] ?? ''))) ?: ($settings[$key]['icon'] ?? 'fa-circle'),
                'tag'     => trim((string)($in['tag']   ?? '')),
                'title'   => trim((string)($in['title'] ?? '')),
                'body'    => trim((string)($in['body']  ?? '')),
                'bullets' => $bullets,
            ];
        }

        // ── CEO Message ────────────────────────────────────────────
        $settings['about_ceo_show'] = isset($_POST['about_ceo_show']) ? '1' : '0';
        foreach (['about_ceo_label','about_ceo_heading','about_ceo_message','about_ceo_name','about_ceo_title'] as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }
        if (!empty($_FILES['about_ceo_image']['tmp_name']) && $_FILES['about_ceo_image']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['about_ceo_image']['size'] > MAX_FILE_SIZE) {
                setFlash('danger', 'CEO image exceeds the file size limit.');
            } else {
                $mime = mime_content_type($_FILES['about_ceo_image']['tmp_name']);
                if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
                    setFlash('danger', 'CEO image must be JPEG, PNG, WebP, or GIF.');
                } else {
                    $ext   = strtolower(pathinfo($_FILES['about_ceo_image']['name'], PATHINFO_EXTENSION));
                    $fname = 'about_ceo_' . uniqid() . '.' . $ext;
                    $dir   = __DIR__ . '/../assets/images/';
                    if (!is_dir($dir)) @mkdir($dir, 0755, true);
                    if (move_uploaded_file($_FILES['about_ceo_image']['tmp_name'], $dir . $fname)) {
                        $old = (string)($settings['about_ceo_image'] ?? '');
                        if ($old && str_starts_with($old, '/assets/images/about_ceo_')) {
                            $oldFs = __DIR__ . '/..' . $old;
                            if (is_file($oldFs)) @unlink($oldFs);
                        }
                        $settings['about_ceo_image'] = '/assets/images/' . $fname;
                    }
                }
            }
        }

        // ── Core Values (4 cards) ──────────────────────────────────
        $valuesIn = $_POST['about_values'] ?? [];
        $values = [];
        for ($i = 0; $i < 4; $i++) {
            $row = is_array($valuesIn[$i] ?? null) ? $valuesIn[$i] : [];
            $values[] = [
                'icon'  => preg_replace('/[^a-z0-9_-]/i', '', trim((string)($row['icon'] ?? ''))) ?: 'fa-circle',
                'title' => trim((string)($row['title'] ?? '')),
                'desc'  => trim((string)($row['desc']  ?? '')),
            ];
        }
        $settings['about_values'] = $values;

        // ── Awards & Recognition (heading + 4 cards) ───────────────
        $settings['about_awards_enabled'] = isset($_POST['about_awards_enabled']) ? 1 : 0;
        foreach (['about_awards_heading','about_awards_sub'] as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }
        $awardsIn = $_POST['about_awards'] ?? [];
        $awards   = [];
        for ($i = 0; $i < 4; $i++) {
            $row = is_array($awardsIn[$i] ?? null) ? $awardsIn[$i] : [];
            $awards[] = [
                'icon'  => preg_replace('/[^a-z0-9_-]/i', '', trim((string)($row['icon'] ?? ''))) ?: 'fa-trophy',
                'title' => trim((string)($row['title'] ?? '')),
                'desc'  => trim((string)($row['desc']  ?? '')),
            ];
        }
        $settings['about_awards'] = $awards;

        // Optional image upload — overwrites the previous about-story image.
        if (!empty($_FILES['about_story_image']['tmp_name']) && $_FILES['about_story_image']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['about_story_image']['size'] > MAX_FILE_SIZE) {
                setFlash('danger', 'Story image exceeds the file size limit.');
            } else {
                $mime = mime_content_type($_FILES['about_story_image']['tmp_name']);
                if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
                    setFlash('danger', 'Story image must be JPEG, PNG, WebP, or GIF.');
                } else {
                    $ext   = strtolower(pathinfo($_FILES['about_story_image']['name'], PATHINFO_EXTENSION));
                    $fname = 'about_story_' . uniqid() . '.' . $ext;
                    $dir   = __DIR__ . '/../assets/images/';
                    if (!is_dir($dir)) @mkdir($dir, 0755, true);
                    if (move_uploaded_file($_FILES['about_story_image']['tmp_name'], $dir . $fname)) {
                        // Best-effort cleanup of the previous local upload.
                        $old = (string)($settings['about_story_image'] ?? '');
                        if ($old && str_starts_with($old, '/assets/images/about_story_')) {
                            $oldFs = __DIR__ . '/..' . $old;
                            if (is_file($oldFs)) @unlink($oldFs);
                        }
                        $settings['about_story_image'] = '/assets/images/' . $fname;
                    }
                }
            }
        }

        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated About-page story section');
        setFlash('success', 'About-page settings saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=about'); exit;
    }

    if ($tab === 'projects') {
        foreach ([
            'projects_header_title','projects_header_sub',
            'projects_cta_heading','projects_cta_sub',
            'projects_cta_primary_label','projects_cta_primary_url',
        ] as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }
        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated Projects-page content');
        setFlash('success', 'Projects-page settings saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=projects'); exit;
    }

    if ($tab === 'listings') {
        $slugify = static function (string $s, string $fallbackSeed = ''): string {
            $s = strtolower(trim($s));
            $s = preg_replace('/[^a-z0-9]+/', '_', $s);
            $s = trim((string)$s, '_');
            if ($s === '' && $fallbackSeed !== '') $s = $fallbackSeed;
            return $s !== '' ? $s : 'custom_' . substr(md5((string)microtime(true)), 0, 6);
        };

        // ── Built-in (core) enable/disable toggles ────────────────
        // A built-in slug is "disabled" if its checkbox wasn't ticked. We
        // store the disabled list (not the enabled list) so that future core
        // additions default to enabled without each admin having to re-tick.
        $allCoreListingSlugs    = array_keys(getCoreListingTypes());
        $enabledCoreListing     = (array)($_POST['listing_types_core_enabled'] ?? []);
        $disabledCoreListing    = array_values(array_diff($allCoreListingSlugs, array_map('strval', $enabledCoreListing)));
        $settings['listing_types_disabled_core'] = $disabledCoreListing;

        $allCorePossessionSlugs = array_keys(getCorePossessionStatuses());
        $enabledCorePossession  = (array)($_POST['possession_statuses_core_enabled'] ?? []);
        $disabledCorePossession = array_values(array_diff($allCorePossessionSlugs, array_map('strval', $enabledCorePossession)));
        $settings['possession_statuses_disabled_core'] = $disabledCorePossession;

        // Reserve core slugs so custom rows can't shadow them.
        $coreListingSlugsMap    = array_fill_keys($allCoreListingSlugs, true);
        $corePossessionSlugsMap = array_fill_keys($allCorePossessionSlugs, true);

        // ── Custom Listing Types ──────────────────────────────────
        $rowsIn = $_POST['listing_types_custom'] ?? [];
        $list   = [];
        $seen   = [];
        if (is_array($rowsIn)) {
            foreach ($rowsIn as $row) {
                if (!is_array($row)) continue;
                $label = trim((string)($row['label'] ?? ''));
                $slug  = $slugify((string)($row['slug'] ?? ''), $slugify($label));
                if ($label === '' || $slug === '') continue;
                if (isset($coreListingSlugsMap[$slug]) || isset($seen[$slug])) continue;

                $categories = is_array($row['categories'] ?? null) ? $row['categories'] : [];
                $categories = array_values(array_intersect($categories, ['residential','commercial']));
                if (empty($categories)) $categories = ['residential'];

                $purposes = is_array($row['purposes'] ?? null) ? $row['purposes'] : [];
                $purposes = array_values(array_intersect($purposes, ['sale','rent']));
                if (empty($purposes)) $purposes = ['sale','rent'];

                $seen[$slug] = true;
                $list[] = [
                    'slug'       => $slug,
                    'label'      => $label,
                    'categories' => $categories,
                    'purposes'   => $purposes,
                ];
            }
        }
        $settings['listing_types_custom'] = $list;

        // ── Custom Possession Statuses ────────────────────────────
        $rowsIn = $_POST['possession_statuses_custom'] ?? [];
        $pos    = [];
        $seen   = [];
        if (is_array($rowsIn)) {
            foreach ($rowsIn as $row) {
                if (!is_array($row)) continue;
                $label = trim((string)($row['label'] ?? ''));
                $slug  = $slugify((string)($row['slug'] ?? ''), $slugify($label));
                if ($label === '' || $slug === '') continue;
                if (isset($corePossessionSlugsMap[$slug]) || isset($seen[$slug])) continue;
                $seen[$slug] = true;
                $pos[] = ['slug' => $slug, 'label' => $label];
            }
        }
        $settings['possession_statuses_custom'] = $pos;

        // ── Project Statuses (built-in toggles + custom) ──────────
        $allCoreProjectSlugs = array_keys(getCoreProjectStatuses());
        $enabledCoreProject  = (array)($_POST['project_statuses_core_enabled'] ?? []);
        $disabledCoreProject = array_values(array_diff($allCoreProjectSlugs, array_map('strval', $enabledCoreProject)));
        $settings['project_statuses_disabled_core'] = $disabledCoreProject;

        $coreProjectSlugsMap = array_fill_keys($allCoreProjectSlugs, true);

        $rowsIn = $_POST['project_statuses_custom'] ?? [];
        $proj   = [];
        $seen   = [];
        if (is_array($rowsIn)) {
            foreach ($rowsIn as $row) {
                if (!is_array($row)) continue;
                $label = trim((string)($row['label'] ?? ''));
                $slug  = $slugify((string)($row['slug'] ?? ''), $slugify($label));
                if ($label === '' || $slug === '') continue;
                if (isset($coreProjectSlugsMap[$slug]) || isset($seen[$slug])) continue;
                $seen[$slug] = true;
                $proj[] = ['slug' => $slug, 'label' => $label];
            }
        }
        $settings['project_statuses_custom'] = $proj;

        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated listing taxonomy (types / possession)');
        setFlash('success', 'Listing taxonomy saved.');
        header('Location: ' . BASE_PATH . '/admin/settings.php?tab=listings'); exit;
    }
}

$csrf = csrfToken();

/**
 * Render the 7-day schedule editor. $namePrefix is used as the input-name
 * prefix so the same helper works for main-office hours and per-branch hours.
 *   $namePrefix="hours"                      → hours[Mon][open], hours[Mon][from], ...
 *   $namePrefix="branches[2][hours_schedule]" → branches[2][hours_schedule][Mon][open], ...
 */
/**
 * Render the URL <select> for a nav-item row. Groups options by category and
 * always appends a "Custom URL…" option. Marks $currentUrl as selected if it
 * matches a known preset; otherwise leaves "__custom__" selected.
 */
function renderNavUrlSelect(string $name, string $currentUrl, array $choices): void {
    $matched = false;
    echo '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-select form-select-sm nav-url-preset">';
    foreach ($choices as $group => $opts) {
        echo '<optgroup label="' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($opts as $val => $label) {
            $sel = ($val === $currentUrl) ? ' selected' : '';
            if ($sel) $matched = true;
            echo '<option value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
               . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        echo '</optgroup>';
    }
    $customSel = $matched ? '' : ' selected';
    echo '<option value="__custom__"' . $customSel . '>Custom URL…</option>';
    echo '</select>';
}

/**
 * Return true if $url matches any preset value across the grouped choices.
 */
function isPresetNavUrl(string $url, array $choices): bool {
    foreach ($choices as $opts) {
        if (array_key_exists($url, $opts)) return true;
    }
    return false;
}

function renderScheduleEditor(string $namePrefix, array $schedule): void {
    foreach ($schedule as $row) {
        $d    = $row['day'];
        $open = !empty($row['open']);
        $from = htmlspecialchars($row['from'], ENT_QUOTES, 'UTF-8');
        $to   = htmlspecialchars($row['to'],   ENT_QUOTES, 'UTF-8');
        ?>
        <div class="row g-2 align-items-center mb-1 hours-row">
          <div class="col-3 col-md-2"><strong class="fs-13"><?= $d ?></strong></div>
          <div class="col-4 col-md-2">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input hours-open-toggle" type="checkbox"
                     name="<?= htmlspecialchars($namePrefix, ENT_QUOTES, 'UTF-8') ?>[<?= $d ?>][open]"
                     value="1" <?= $open ? 'checked' : '' ?>>
              <label class="form-check-label fs-13"><?= $open ? 'Open' : 'Closed' ?></label>
            </div>
          </div>
          <div class="col-5 col-md-3">
            <input type="time"
                   name="<?= htmlspecialchars($namePrefix, ENT_QUOTES, 'UTF-8') ?>[<?= $d ?>][from]"
                   class="form-control form-control-sm"
                   value="<?= $from ?>" <?= $open ? '' : 'disabled' ?>>
          </div>
          <div class="col-auto d-none d-md-block text-muted">–</div>
          <div class="col-5 col-md-3">
            <input type="time"
                   name="<?= htmlspecialchars($namePrefix, ENT_QUOTES, 'UTF-8') ?>[<?= $d ?>][to]"
                   class="form-control form-control-sm"
                   value="<?= $to ?>" <?= $open ? '' : 'disabled' ?>>
          </div>
        </div>
        <?php
    }
}

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-gear me-2" style="color:var(--gold)"></i>Settings</h1>
    <p class="text-muted mb-0 fs-13">Configure your website and admin preferences</p>
  </div>
</div>

<div class="row g-0">
  <div class="col-12">
    <!-- Tabs Nav -->
    <ul class="nav nav-tabs settings-nav mb-0 border-bottom-0" style="border-bottom:1px solid #dee2e6;">
      <?php
      // Tabs are arranged in three natural groups so the bar reads left → right:
      //   1) Public-facing pages (edited most often)
      //   2) Brand & site-wide layout
      //   3) System config (rarely touched after setup)
      $tabs = [
        // — Pages —
        'banners'       => ['icon'=>'fa-house',          'label'=>'Home Page'],
        'about'         => ['icon'=>'fa-circle-info',    'label'=>'About Page'],
        'projects'      => ['icon'=>'fa-diagram-project','label'=>'Projects Page'],
        'listings'      => ['icon'=>'fa-list',           'label'=>'Listings'],
        // — Brand & layout —
        'agency'        => ['icon'=>'fa-building',       'label'=>'Agency Profile'],
        'navigation'    => ['icon'=>'fa-bars',           'label'=>'Navigation'],
        'theme'         => ['icon'=>'fa-palette',        'label'=>'Theme'],
        // — System —
        'smtp'          => ['icon'=>'fa-envelope',       'label'=>'SMTP Config'],
        'notifications' => ['icon'=>'fa-bell',           'label'=>'Notifications'],
        'preferences'   => ['icon'=>'fa-sliders',        'label'=>'Preferences'],
      ];
      foreach ($tabs as $tk => $td):
      ?>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab===$tk?'active':'' ?>"
           href="<?= BASE_PATH ?>/admin/settings.php?tab=<?= $tk ?>">
          <i class="fa-solid <?= $td['icon'] ?> me-1"></i><?= $td['label'] ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

    <!-- Tab Content -->
    <div class="card-plain" style="border-radius: 0 0 10px 10px;">

      <?php if ($activeTab === 'agency'): ?>
      <!-- Agency Profile Tab -->
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="tab" value="agency">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Agency Name</label>
            <input type="text" name="agency_name" class="form-control"
                   value="<?= htmlspecialchars($settings['agency_name'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Tagline</label>
            <input type="text" name="agency_tagline" class="form-control"
                   value="<?= htmlspecialchars($settings['agency_tagline'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Brand Subtitle</label>
            <input type="text" name="brand_subtitle" class="form-control" maxlength="60"
                   placeholder="Real Estate"
                   value="<?= htmlspecialchars($settings['brand_subtitle'] ?? '', ENT_QUOTES,'UTF-8') ?>">
            <div class="form-text">Small label shown under the agency name in the header and footer (e.g. "Real Estate").</div>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Logo</label>
            <?php if ($settings['logo_path']): ?>
              <div class="mb-2">
                <img src="<?= htmlspecialchars(BASE_PATH . $settings['logo_path'], ENT_QUOTES,'UTF-8') ?>?v=<?= @filemtime(__DIR__ . '/..' . $settings['logo_path']) ?: time() ?>" alt="Logo" style="max-height:60px;">
              </div>
            <?php endif; ?>
            <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Phone</label>
            <input type="tel" name="phone" class="form-control"
                   value="<?= htmlspecialchars($settings['phone'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">WhatsApp Number <span class="text-muted fw-normal fs-12">(digits only)</span></label>
            <input type="text" name="whatsapp" class="form-control"
                   value="<?= htmlspecialchars($settings['whatsapp'], ENT_QUOTES,'UTF-8') ?>" placeholder="923001234567">
            <div class="form-text">Leave empty to auto-derive from Phone. Per-branch numbers are managed separately below.</div>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Website URL</label>
            <input type="url" name="website" class="form-control"
                   value="<?= htmlspecialchars($settings['website'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($settings['email'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Address</label>
            <input type="text" name="address" id="mainOfficeAddressInput" class="form-control"
                   value="<?= htmlspecialchars($settings['address'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Latitude <span class="text-danger">*</span></label>
            <input type="number" step="any" min="-90" max="90"
                   name="address_lat" id="mainOfficeLat" class="form-control font-monospace"
                   placeholder="33.7273760"
                   value="<?= htmlspecialchars((string)($settings['address_lat'] ?? ''), ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Longitude <span class="text-danger">*</span></label>
            <input type="number" step="any" min="-180" max="180"
                   name="address_lng" id="mainOfficeLng" class="form-control font-monospace"
                   placeholder="73.0911790"
                   value="<?= htmlspecialchars((string)($settings['address_lng'] ?? ''), ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12">
            <div class="form-text d-flex flex-wrap align-items-center gap-2" style="margin-top:-0.25rem;">
              <i class="fa-solid fa-location-dot text-muted"></i>
              <span>Precise coordinates power the "Get Directions" button — without them, Google Maps geocodes the address text and may pin the wrong spot.</span>
              <a href="#" id="findMainOfficeOnMap" class="ms-auto"
                 style="font-size:.85rem; font-weight:600;">
                <i class="fa-solid fa-map-location-dot me-1"></i>Find on Google Maps
              </a>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label fw-600 mb-2">Business Hours</label>
            <?php renderScheduleEditor('hours', getBusinessHoursSchedule()); ?>
            <div class="form-text">Toggle "Open" to enable the time fields. Consecutive days with matching hours are grouped on the public site (e.g. "Mon–Fri: 9:00 AM – 7:00 PM").</div>
          </div>
          <div class="col-12"><hr><h6 class="fw-700">Social Media Links</h6></div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600"><i class="fa-brands fa-facebook text-primary me-1"></i>Facebook</label>
            <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/..."
                   value="<?= htmlspecialchars($settings['facebook_url'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600"><i class="fa-brands fa-instagram text-danger me-1"></i>Instagram</label>
            <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/..."
                   value="<?= htmlspecialchars($settings['instagram_url'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600"><i class="fa-brands fa-youtube text-danger me-1"></i>YouTube</label>
            <input type="url" name="youtube_url" class="form-control" placeholder="https://youtube.com/..."
                   value="<?= htmlspecialchars($settings['youtube_url'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12"><hr><h6 class="fw-700 d-flex align-items-center justify-content-between">
            <span>Branch Offices</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="addBranchBtn">
              <i class="fa-solid fa-plus me-1"></i> Add Branch
            </button>
          </h6>
          <div class="form-text mb-2">Additional office locations shown on the contact page under "Our Office Locations". Mark one location (main office or a branch) as HQ — it gets the HQ badge on the contact page.</div>
          </div>
          <?php
          $branches = [];
          try {
              // Try new schema (lat/lng) first; fall back to legacy shape.
              try {
                  $stmtBr = $db->query('SELECT name, address, lat, lng, phone, hours, hours_schedule, is_hq FROM branches ORDER BY sort_order ASC, id ASC');
              } catch (Throwable $columnMissing) {
                  $stmtBr = $db->query('SELECT name, address, NULL AS lat, NULL AS lng, phone, hours, hours_schedule, is_hq FROM branches ORDER BY sort_order ASC, id ASC');
              }
              $branches = $stmtBr->fetchAll() ?: [];
          } catch (Throwable $e) { /* branches table may not exist yet */ }
          $anyBranchHq = false;
          foreach ($branches as $br) { if (!empty($br['is_hq'])) { $anyBranchHq = true; break; } }
          $mainIsHq = !$anyBranchHq;
          ?>
          <div class="col-12">
            <div class="p-3 border rounded-3 bg-white d-flex align-items-center gap-2 flex-wrap">
              <i class="fa-solid fa-building" style="color:var(--gold)"></i>
              <strong>Main Office</strong>
              <span class="text-muted fs-13">— drawn from the Address / Phone / Business Hours fields above</span>
              <div class="form-check ms-auto mb-0">
                <input class="form-check-input" type="radio" name="hq_pick" value="main" id="hqPickMain" <?= $mainIsHq ? 'checked' : '' ?>>
                <label class="form-check-label fw-600 fs-13" for="hqPickMain">
                  <i class="fa-solid fa-star" style="color:var(--gold)"></i> Set as HQ
                </label>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div id="branchesWrapper" class="d-flex flex-column gap-3">
              <?php foreach ($branches as $i => $br):
                $bn = htmlspecialchars($br['name']    ?? '', ENT_QUOTES, 'UTF-8');
                $ba = htmlspecialchars($br['address'] ?? '', ENT_QUOTES, 'UTF-8');
                $bp = htmlspecialchars($br['phone']   ?? '', ENT_QUOTES, 'UTF-8');
                $blat = htmlspecialchars((string)($br['lat'] ?? ''), ENT_QUOTES, 'UTF-8');
                $blng = htmlspecialchars((string)($br['lng'] ?? ''), ENT_QUOTES, 'UTF-8');
                $bhSummary = htmlspecialchars(trim((string)($br['hours'] ?? '')) ?: 'Edit hours', ENT_QUOTES, 'UTF-8');
                $bhq = !empty($br['is_hq']);
                $brSched = normalizeSchedule($br['hours_schedule'] ?? null);
              ?>
              <div class="branch-row p-3 border rounded-3 bg-white">
                <div class="row g-2 align-items-end">
                  <div class="col-12 col-md-3">
                    <label class="form-label fw-600 fs-12">Name</label>
                    <input type="text" name="branches[<?= $i ?>][name]" class="form-control form-control-sm" value="<?= $bn ?>" placeholder="e.g. Branch — Lahore">
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="form-label fw-600 fs-12">Address</label>
                    <input type="text" name="branches[<?= $i ?>][address]" class="form-control form-control-sm branch-address-input" value="<?= $ba ?>">
                  </div>
                  <div class="col-12 col-md-2">
                    <label class="form-label fw-600 fs-12">Phone</label>
                    <input type="text" name="branches[<?= $i ?>][phone]" class="form-control form-control-sm" value="<?= $bp ?>">
                  </div>
                  <div class="col-12 col-md-2">
                    <label class="form-label fw-600 fs-12">Hours</label>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start text-truncate"
                            data-bs-toggle="collapse" data-bs-target="#branchHours<?= $i ?>"
                            aria-expanded="false" aria-controls="branchHours<?= $i ?>" title="Edit weekly schedule">
                      <i class="fa-solid fa-clock me-1"></i><span class="fs-12"><?= $bhSummary ?></span>
                    </button>
                  </div>
                  <div class="col-6 col-md-1 d-flex align-items-center">
                    <div class="form-check mb-0">
                      <input class="form-check-input hq-pick-radio" type="radio" name="hq_pick" value="branch-<?= $i ?>" id="hqPickBranch<?= $i ?>" <?= $bhq ? 'checked' : '' ?>>
                      <label class="form-check-label fw-600 fs-12" for="hqPickBranch<?= $i ?>" title="Set this branch as HQ">
                        <i class="fa-solid fa-star" style="color:var(--gold)"></i> HQ
                      </label>
                    </div>
                  </div>
                  <div class="col-6 col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 removeBranchBtn" title="Remove branch">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </div>
                <div class="row g-2 align-items-end mt-1">
                  <div class="col-12 col-md-3">
                    <label class="form-label fw-600 fs-12">Latitude <span class="text-danger">*</span></label>
                    <input type="number" step="any" min="-90" max="90"
                           name="branches[<?= $i ?>][lat]"
                           class="form-control form-control-sm font-monospace branch-lat-input"
                           placeholder="33.7273760" value="<?= $blat ?>">
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="form-label fw-600 fs-12">Longitude <span class="text-danger">*</span></label>
                    <input type="number" step="any" min="-180" max="180"
                           name="branches[<?= $i ?>][lng]"
                           class="form-control form-control-sm font-monospace branch-lng-input"
                           placeholder="73.0911790" value="<?= $blng ?>">
                  </div>
                  <div class="col-12 col-md-6 d-flex align-items-center">
                    <a href="#" class="branch-find-on-map fs-12" style="font-weight:600;">
                      <i class="fa-solid fa-map-location-dot me-1"></i>Find this office on Google Maps
                    </a>
                  </div>
                </div>
                <div class="collapse mt-3 pt-3 border-top" id="branchHours<?= $i ?>">
                  <?php renderScheduleEditor('branches[' . $i . '][hours_schedule]', $brSched); ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-gold px-4">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Agency Profile
            </button>
          </div>
        </div>
      </form>

      <template id="branchRowTpl">
        <div class="branch-row p-3 border rounded-3 bg-white">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label fw-600 fs-12">Name</label>
              <input type="text" name="branches[__IDX__][name]" class="form-control form-control-sm" placeholder="e.g. Branch — Lahore">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label fw-600 fs-12">Address</label>
              <input type="text" name="branches[__IDX__][address]" class="form-control form-control-sm branch-address-input">
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label fw-600 fs-12">Phone</label>
              <input type="text" name="branches[__IDX__][phone]" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label fw-600 fs-12">Hours</label>
              <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start"
                      data-bs-toggle="collapse" data-bs-target="#branchHours__IDX__"
                      aria-expanded="false" aria-controls="branchHours__IDX__" title="Edit weekly schedule">
                <i class="fa-solid fa-clock me-1"></i><span class="fs-12">Edit hours</span>
              </button>
            </div>
            <div class="col-6 col-md-1 d-flex align-items-center">
              <div class="form-check mb-0">
                <input class="form-check-input hq-pick-radio" type="radio" name="hq_pick" value="branch-__IDX__" id="hqPickBranch__IDX__">
                <label class="form-check-label fw-600 fs-12" for="hqPickBranch__IDX__" title="Set this branch as HQ">
                  <i class="fa-solid fa-star" style="color:var(--gold)"></i> HQ
                </label>
              </div>
            </div>
            <div class="col-6 col-md-1 text-end">
              <button type="button" class="btn btn-sm btn-outline-danger w-100 removeBranchBtn" title="Remove branch">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
          <div class="row g-2 align-items-end mt-1">
            <div class="col-12 col-md-3">
              <label class="form-label fw-600 fs-12">Latitude <span class="text-danger">*</span></label>
              <input type="number" step="any" min="-90" max="90"
                     name="branches[__IDX__][lat]"
                     class="form-control form-control-sm font-monospace branch-lat-input"
                     placeholder="33.7273760">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label fw-600 fs-12">Longitude <span class="text-danger">*</span></label>
              <input type="number" step="any" min="-180" max="180"
                     name="branches[__IDX__][lng]"
                     class="form-control form-control-sm font-monospace branch-lng-input"
                     placeholder="73.0911790">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-center">
              <a href="#" class="branch-find-on-map fs-12" style="font-weight:600;">
                <i class="fa-solid fa-map-location-dot me-1"></i>Find this office on Google Maps
              </a>
            </div>
          </div>
          <div class="collapse mt-3 pt-3 border-top" id="branchHours__IDX__">
            <?php
              // Render the default schedule for a new branch row.
              ob_start();
              renderScheduleEditor('branches[__IDX__][hours_schedule]', defaultBusinessHoursSchedule());
              echo ob_get_clean();
            ?>
          </div>
        </div>
      </template>
      <script>
      (function(){
        var wrapper = document.getElementById('branchesWrapper');
        var addBtn  = document.getElementById('addBranchBtn');
        var tpl     = document.getElementById('branchRowTpl');
        if (!wrapper || !addBtn || !tpl) return;
        var nextIdx = wrapper.querySelectorAll('.branch-row').length;

        addBtn.addEventListener('click', function(){
          var html = tpl.innerHTML.replaceAll('__IDX__', String(nextIdx++));
          var tmp = document.createElement('div');
          tmp.innerHTML = html.trim();
          wrapper.appendChild(tmp.firstChild);
        });

        wrapper.addEventListener('click', function(e){
          var btn = e.target.closest('.removeBranchBtn');
          if (!btn) return;
          var row = btn.closest('.branch-row');
          if (row) row.remove();
        });

        // "Find on Google Maps" helper — opens a new tab with the address
        // (or current lat/lng if already filled) pre-searched. Admin can
        // right-click the pin in Google Maps → "What's here?" to copy the
        // exact coordinates, then paste them back into the Lat/Lng fields.
        function openMapForRow(row, fallbackAddr) {
            var addr = (row && row.querySelector('.branch-address-input')?.value) || fallbackAddr || '';
            var lat  = (row && row.querySelector('.branch-lat-input')?.value) || '';
            var lng  = (row && row.querySelector('.branch-lng-input')?.value) || '';
            var url;
            if (lat && lng) {
                url = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat + ',' + lng);
            } else if (addr.trim()) {
                url = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(addr.trim());
            } else {
                alert('Enter an address (or lat/lng) first.');
                return;
            }
            window.open(url, '_blank', 'noopener,noreferrer');
        }

        // Delegated so newly-added branch rows work too.
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.branch-find-on-map');
            if (link) {
                e.preventDefault();
                openMapForRow(link.closest('.branch-row'));
                return;
            }
            if (e.target.closest('#findMainOfficeOnMap')) {
                e.preventDefault();
                var fake = {
                    querySelector: function (sel) {
                        if (sel === '.branch-address-input') return document.getElementById('mainOfficeAddressInput');
                        if (sel === '.branch-lat-input')     return document.getElementById('mainOfficeLat');
                        if (sel === '.branch-lng-input')     return document.getElementById('mainOfficeLng');
                        return null;
                    }
                };
                openMapForRow(fake);
            }
        });
      })();

      // Enable/disable schedule time inputs when Open/Closed toggles change.
      // Delegated so dynamically-added branch rows are handled too.
      document.addEventListener('change', function(e){
        var tgl = e.target;
        if (!tgl.classList || !tgl.classList.contains('hours-open-toggle')) return;
        var row = tgl.closest('.hours-row');
        if (!row) return;
        var open = tgl.checked;
        var lbl  = row.querySelector('.form-check-label');
        if (lbl) lbl.textContent = open ? 'Open' : 'Closed';
        row.querySelectorAll('input[type="time"]').forEach(function(i){ i.disabled = !open; });
      });
      </script>

      <?php elseif ($activeTab === 'about'): ?>
      <!-- About Page Tab — controls the "Our Story" section on /about.php -->
      <?php include __DIR__ . '/includes/_icon_picker.php'; ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="tab" value="about">

        <!-- ─────────────── Page Header Banner ─────────────── -->
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-heading me-2" style="color:var(--gold);"></i>Page Header Banner
        </h5>
        <p class="text-muted small mb-3">The navy banner at the very top of <code>/about.php</code> (above the breadcrumb area).</p>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Banner Title</label>
            <input type="text" name="about_header_title" class="form-control" maxlength="160"
                   placeholder="About Al-Riaz Associates"
                   value="<?= htmlspecialchars($settings['about_header_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Banner Subtitle</label>
            <input type="text" name="about_header_sub" class="form-control" maxlength="240"
                   placeholder="Pakistan's trusted real estate partner since 2021"
                   value="<?= htmlspecialchars($settings['about_header_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Leave empty to auto-generate "Pakistan's trusted real estate partner since &lt;year&gt;".</div>
          </div>
        </div>

        <hr class="my-4">

        <div class="alert alert-info d-flex align-items-start gap-2 mb-4" role="alert">
          <i class="fa-solid fa-circle-info mt-1"></i>
          <div>
            <strong>"Our Story" section on /about.php</strong> &mdash; the label, heading, body, image, and badge below all render in that block on the public About page.
            <div class="text-muted small mt-1">Tip: separate paragraphs with a blank line. Wrap text in <code>**double asterisks**</code> to make it bold.</div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Section Label</label>
            <input type="text" name="about_story_label" class="form-control" maxlength="80"
                   placeholder="Our Story"
                   value="<?= htmlspecialchars($settings['about_story_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Small uppercase eyebrow shown above the heading.</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="about_story_heading" class="form-control" maxlength="160"
                   placeholder="Built on Trust &amp; Transparency"
                   value="<?= htmlspecialchars($settings['about_story_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12">
            <label class="form-label fw-600">Body</label>
            <textarea name="about_story_body" class="form-control" rows="9"
                      placeholder="Tell your agency's story..."><?= htmlspecialchars($settings['about_story_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-text">
              Plain text. Blank line = new paragraph. Wrap a phrase in <code>**stars**</code> to bold it.
            </div>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label fw-600">Story Image</label>
            <?php if (!empty($settings['about_story_image'])): ?>
              <div class="mb-2">
                <img src="<?= htmlspecialchars(BASE_PATH . $settings['about_story_image'], ENT_QUOTES, 'UTF-8') ?>?v=<?= @filemtime(__DIR__ . '/..' . $settings['about_story_image']) ?: '' ?>"
                     alt="Current story image"
                     style="max-height:140px;border-radius:8px;border:1px solid #dee2e6;">
              </div>
            <?php endif; ?>
            <input type="file" name="about_story_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="form-text">Max 5MB. JPEG / PNG / WebP / GIF. Landscape (e.g. 1200&times;800) works best.</div>
          </div>

          <div class="col-12 col-md-4">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-header"><i class="fa-solid fa-award" style="color:var(--gold)"></i> Floating Badge</div>
              <div class="card-body">
                <label class="form-label fw-600 small">Value</label>
                <input type="text" name="about_story_badge_value" class="form-control form-control-sm mb-2" maxlength="40"
                       placeholder="5+ Years"
                       value="<?= htmlspecialchars($settings['about_story_badge_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Label</label>
                <input type="text" name="about_story_badge_label" class="form-control form-control-sm" maxlength="60"
                       placeholder="In Real Estate"
                       value="<?= htmlspecialchars($settings['about_story_badge_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text mt-2">The small white card that floats over the image. Leave value blank to hide it.</div>
              </div>
            </div>
          </div>

        </div>

        <!-- ─────────────── Mission & Vision ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-bullseye me-2" style="color:var(--gold);"></i>Mission &amp; Vision
        </h5>
        <p class="text-muted small mb-3">The two large cards in the Purpose section.</p>

        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="about_mv_label" class="form-control" maxlength="80"
                   value="<?= htmlspecialchars($settings['about_mv_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label fw-600">Section Title</label>
            <input type="text" name="about_mv_title" class="form-control" maxlength="160"
                   value="<?= htmlspecialchars($settings['about_mv_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Subtitle</label>
            <input type="text" name="about_mv_subtitle" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($settings['about_mv_subtitle'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <?php
          $mvBlocks = [
            'about_mission' => 'Mission Card',
            'about_vision'  => 'Vision Card',
          ];
          foreach ($mvBlocks as $mvKey => $mvHeading):
            $mv = $settings[$mvKey] ?? ['icon'=>'','tag'=>'','title'=>'','body'=>'','bullets'=>['','','']];
            $mvBullets = $mv['bullets'] ?? [];
            $mvBullets = array_pad($mvBullets, 3, '');
        ?>
        <div class="form-section-card mt-3">
          <div class="card-header"><i class="fa-solid <?= htmlspecialchars($mv['icon'] ?: 'fa-circle', ENT_QUOTES, 'UTF-8') ?>" style="color:var(--gold)"></i> <?= $mvHeading ?></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-md-3">
                <label class="form-label fw-600 small">Icon</label>
                <?php $mvIconId = $mvKey . 'IconPick'; $mvIconCls = $mv['icon'] ?: 'fa-circle'; ?>
                <input type="hidden" name="<?= $mvKey ?>[icon]" id="<?= $mvIconId ?>" value="<?= htmlspecialchars($mvIconCls, ENT_QUOTES, 'UTF-8') ?>">
                <button type="button" class="btn btn-outline-secondary btn-sm icon-picker-trigger w-100"
                        data-icon-target="<?= $mvIconId ?>" title="Click to choose an icon">
                  <i class="fa-solid <?= htmlspecialchars($mvIconCls, ENT_QUOTES, 'UTF-8') ?>"></i>
                  <span class="icon-picker-label"><?= htmlspecialchars($mvIconCls, ENT_QUOTES, 'UTF-8') ?></span>
                  <i class="fa-solid fa-chevron-down ms-auto text-muted small"></i>
                </button>
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label fw-600 small">Tag</label>
                <input type="text" name="<?= $mvKey ?>[tag]" class="form-control form-control-sm" maxlength="60"
                       value="<?= htmlspecialchars($mv['tag'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-600 small">Title</label>
                <input type="text" name="<?= $mvKey ?>[title]" class="form-control form-control-sm" maxlength="200"
                       value="<?= htmlspecialchars($mv['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12">
                <label class="form-label fw-600 small">Body</label>
                <textarea name="<?= $mvKey ?>[body]" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($mv['body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
              <?php for ($b = 0; $b < 3; $b++): ?>
              <div class="col-12 col-md-4">
                <label class="form-label fw-600 small">Bullet <?= $b + 1 ?></label>
                <input type="text" name="<?= $mvKey ?>[bullets][]" class="form-control form-control-sm" maxlength="200"
                       value="<?= htmlspecialchars($mvBullets[$b] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- ─────────────── CEO Message ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-user-tie me-2" style="color:var(--gold);"></i>CEO Message
        </h5>
        <p class="text-muted small mb-3">A personal message from leadership shown on the About page. Toggle off to hide the section entirely.</p>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="aboutCeoShow" name="about_ceo_show" value="1"
                 <?= ($settings['about_ceo_show'] ?? '1') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="aboutCeoShow">
            <i class="fa-solid fa-eye text-success me-1"></i> Show CEO message on the About page
          </label>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">CEO Photo</label>
            <?php if (!empty($settings['about_ceo_image'])): ?>
              <div class="mb-2">
                <img src="<?= htmlspecialchars(BASE_PATH . $settings['about_ceo_image'], ENT_QUOTES, 'UTF-8') ?>?v=<?= @filemtime(__DIR__ . '/..' . $settings['about_ceo_image']) ?: '' ?>"
                     alt="Current CEO photo"
                     style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);">
              </div>
            <?php endif; ?>
            <input type="file" name="about_ceo_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="form-text">Square photo recommended (e.g. 600&times;600).</div>
          </div>

          <div class="col-12 col-md-8">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label fw-600">Section Eyebrow</label>
                <input type="text" name="about_ceo_label" class="form-control" maxlength="100"
                       placeholder="A Message from Our CEO"
                       value="<?= htmlspecialchars($settings['about_ceo_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-600">Section Heading</label>
                <input type="text" name="about_ceo_heading" class="form-control" maxlength="160"
                       placeholder="Welcome to Al-Riaz Associates"
                       value="<?= htmlspecialchars($settings['about_ceo_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-7">
                <label class="form-label fw-600">CEO Name</label>
                <input type="text" name="about_ceo_name" class="form-control" maxlength="120"
                       placeholder="e.g. Riaz Ahmed"
                       value="<?= htmlspecialchars($settings['about_ceo_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label fw-600">CEO Title</label>
                <input type="text" name="about_ceo_title" class="form-control" maxlength="120"
                       placeholder="Founder &amp; CEO"
                       value="<?= htmlspecialchars($settings['about_ceo_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12">
                <label class="form-label fw-600">Message</label>
                <textarea name="about_ceo_message" class="form-control" rows="6"
                          placeholder="Write a short message from the CEO..."><?= htmlspecialchars($settings['about_ceo_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">Plain text. Blank line = new paragraph. Wrap text in <code>**stars**</code> to bold it.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ─────────────── Core Values ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-heart me-2" style="color:var(--gold);"></i>Core Values
        </h5>
        <p class="text-muted small mb-3">The 4 numbered value cards beneath Mission &amp; Vision.</p>
        <div class="row g-3">
          <?php for ($i = 0; $i < 4; $i++):
            $v = $settings['about_values'][$i] ?? ['icon'=>'','title'=>'','desc'=>''];
          ?>
          <div class="col-12 col-md-6">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-header">
                <i class="fa-solid <?= htmlspecialchars($v['icon'] ?: 'fa-circle', ENT_QUOTES, 'UTF-8') ?>" style="color:var(--gold)"></i>
                Value <?= $i + 1 ?>
              </div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-12 col-md-5">
                    <label class="form-label fw-600 small">Icon</label>
                    <?php $valIconId = 'aboutValueIcon' . $i; $valIconCls = $v['icon'] ?: 'fa-circle'; ?>
                    <input type="hidden" name="about_values[<?= $i ?>][icon]" id="<?= $valIconId ?>"
                           value="<?= htmlspecialchars($valIconCls, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="btn btn-outline-secondary btn-sm icon-picker-trigger w-100"
                            data-icon-target="<?= $valIconId ?>" title="Click to choose an icon">
                      <i class="fa-solid <?= htmlspecialchars($valIconCls, ENT_QUOTES, 'UTF-8') ?>"></i>
                      <span class="icon-picker-label"><?= htmlspecialchars($valIconCls, ENT_QUOTES, 'UTF-8') ?></span>
                      <i class="fa-solid fa-chevron-down ms-auto text-muted small"></i>
                    </button>
                  </div>
                  <div class="col-12 col-md-7">
                    <label class="form-label fw-600 small">Title</label>
                    <input type="text" name="about_values[<?= $i ?>][title]" class="form-control form-control-sm" maxlength="80"
                           value="<?= htmlspecialchars($v['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-600 small">Description</label>
                    <textarea name="about_values[<?= $i ?>][desc]" class="form-control form-control-sm" rows="2" maxlength="240"><?= htmlspecialchars($v['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ─────────────── Awards & Recognition ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-trophy me-2" style="color:var(--gold);"></i>Awards &amp; Recognition
        </h5>
        <p class="text-muted small mb-2">The premium gold-banded strip with 4 award cards.</p>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" role="switch" id="aboutAwardsEnabled"
                 name="about_awards_enabled" value="1"
                 <?= !empty($settings['about_awards_enabled']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="aboutAwardsEnabled">
            <i class="fa-solid fa-globe text-primary me-1"></i>
            Show this section on the public About page
          </label>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-5">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="about_awards_heading" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars($settings['about_awards_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-7">
            <label class="form-label fw-600">Subtitle</label>
            <input type="text" name="about_awards_sub" class="form-control" maxlength="240"
                   value="<?= htmlspecialchars($settings['about_awards_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="row g-3">
          <?php for ($i = 0; $i < 4; $i++):
            $aw = $settings['about_awards'][$i] ?? ['icon'=>'','title'=>'','desc'=>''];
          ?>
          <div class="col-12 col-md-6">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-header">
                <i class="fa-solid <?= htmlspecialchars($aw['icon'] ?: 'fa-trophy', ENT_QUOTES, 'UTF-8') ?>" style="color:var(--gold)"></i>
                Award <?= $i + 1 ?>
              </div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-12 col-md-5">
                    <label class="form-label fw-600 small">Icon</label>
                    <?php $awIconId = 'aboutAwardIcon' . $i; $awIconCls = $aw['icon'] ?: 'fa-trophy'; ?>
                    <input type="hidden" name="about_awards[<?= $i ?>][icon]" id="<?= $awIconId ?>"
                           value="<?= htmlspecialchars($awIconCls, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="btn btn-outline-secondary btn-sm icon-picker-trigger w-100"
                            data-icon-target="<?= $awIconId ?>" title="Click to choose an icon">
                      <i class="fa-solid <?= htmlspecialchars($awIconCls, ENT_QUOTES, 'UTF-8') ?>"></i>
                      <span class="icon-picker-label"><?= htmlspecialchars($awIconCls, ENT_QUOTES, 'UTF-8') ?></span>
                      <i class="fa-solid fa-chevron-down ms-auto text-muted small"></i>
                    </button>
                  </div>
                  <div class="col-12 col-md-7">
                    <label class="form-label fw-600 small">Title</label>
                    <input type="text" name="about_awards[<?= $i ?>][title]" class="form-control form-control-sm" maxlength="120"
                           value="<?= htmlspecialchars($aw['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-600 small">Description</label>
                    <textarea name="about_awards[<?= $i ?>][desc]" class="form-control form-control-sm" rows="2" maxlength="240"><?= htmlspecialchars($aw['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ─────────────── Bottom CTA Card ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-bullhorn me-2" style="color:var(--gold);"></i>Bottom CTA Card
        </h5>
        <p class="text-muted small mb-3">The navy "Let's Talk" panel that sits above the footer on <code>/about.php</code>.</p>
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="about_cta_label" class="form-control" maxlength="60"
                   placeholder="Let's Talk"
                   value="<?= htmlspecialchars($settings['about_cta_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="about_cta_heading" class="form-control" maxlength="160"
                   placeholder="Start your property journey today."
                   value="<?= htmlspecialchars($settings['about_cta_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-600">Sub-heading</label>
            <textarea name="about_cta_sub" class="form-control" rows="2" maxlength="400"
                      placeholder="Speak to an expert consultant..."><?= htmlspecialchars($settings['about_cta_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary Button Label</label>
            <input type="text" name="about_cta_primary_label" class="form-control" maxlength="80"
                   placeholder="Chat on WhatsApp"
                   value="<?= htmlspecialchars($settings['about_cta_primary_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary Button URL</label>
            <input type="text" name="about_cta_primary_url" class="form-control"
                   placeholder="https://wa.me/..."
                   value="<?= htmlspecialchars($settings['about_cta_primary_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Internal path or full URL. Leave empty for the default WhatsApp link.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary Button Label</label>
            <input type="text" name="about_cta_secondary_label" class="form-control" maxlength="80"
                   placeholder="Send a Message"
                   value="<?= htmlspecialchars($settings['about_cta_secondary_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary Button URL</label>
            <input type="text" name="about_cta_secondary_url" class="form-control"
                   placeholder="/contact.php"
                   value="<?= htmlspecialchars($settings['about_cta_secondary_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Internal path or full URL. Leave empty for the default contact page.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Hours line</label>
            <input type="text" name="about_cta_hours" class="form-control" maxlength="160"
                   placeholder="Mon–Sat 9am–7pm · Sun 11am–4pm"
                   value="<?= htmlspecialchars($settings['about_cta_hours'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Short blurb shown under the buttons. Leave empty to hide.</div>
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label fw-600">Badge Value</label>
            <input type="text" name="about_cta_badge_value" class="form-control" maxlength="20"
                   placeholder="auto"
                   value="<?= htmlspecialchars($settings['about_cta_badge_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Big number in the gold circle. Leave empty to auto-fill from "Years Active".</div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label fw-600">Badge Label</label>
            <input type="text" name="about_cta_badge_label" class="form-control" maxlength="40"
                   placeholder="Years Trusted"
                   value="<?= htmlspecialchars($settings['about_cta_badge_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <div class="d-flex justify-content-end mt-4 pb-2">
          <button type="submit" class="btn btn-gold">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save About Page
          </button>
        </div>
      </form>

      <?php elseif ($activeTab === 'projects'): ?>
      <!-- Projects Page Tab -->
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="projects">

        <h6 class="fw-700 mb-1"><i class="fa-solid fa-heading me-2" style="color:var(--gold);"></i>Page Header Banner</h6>
        <p class="text-muted fs-13 mb-3">The dark hero strip at the top of <code>/projects.php</code>.</p>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="projects_header_title" class="form-control" maxlength="160"
                   placeholder="Real Estate Projects"
                   value="<?= htmlspecialchars($settings['projects_header_title'] ?? '', ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Subtitle</label>
            <input type="text" name="projects_header_sub" class="form-control" maxlength="300"
                   placeholder="Authorised developments across Pakistan"
                   value="<?= htmlspecialchars($settings['projects_header_sub'] ?? '', ENT_QUOTES,'UTF-8') ?>">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="fw-700 mb-1"><i class="fa-solid fa-bullhorn me-2" style="color:var(--gold);"></i>Bottom CTA Banner</h6>
        <p class="text-muted fs-13 mb-3">Shown above the footer on <code>/projects.php</code>.</p>
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="projects_cta_heading" class="form-control" maxlength="160"
                   placeholder="Looking for a Specific Project?"
                   value="<?= htmlspecialchars($settings['projects_cta_heading'] ?? '', ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Subtitle</label>
            <input type="text" name="projects_cta_sub" class="form-control" maxlength="300"
                   placeholder="Contact our team — we work with 20+ authorised developers..."
                   value="<?= htmlspecialchars($settings['projects_cta_sub'] ?? '', ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Button Label</label>
            <input type="text" name="projects_cta_primary_label" class="form-control" maxlength="60"
                   placeholder="Get in Touch"
                   value="<?= htmlspecialchars($settings['projects_cta_primary_label'] ?? '', ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Button URL</label>
            <input type="text" name="projects_cta_primary_url" class="form-control"
                   placeholder="/contact.php or https://..."
                   value="<?= htmlspecialchars($settings['projects_cta_primary_url'] ?? '', ENT_QUOTES,'UTF-8') ?>">
            <div class="form-text">Site-relative path (e.g. <code>/contact.php</code>) or a full URL (<code>https://</code>, <code>mailto:</code>, <code>tel:</code>, <code>wa.me/...</code>).</div>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
          <button type="submit" class="btn btn-gold">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Projects Page
          </button>
        </div>
      </form>

      <?php elseif ($activeTab === 'listings'): ?>
      <!-- Listings Tab — Built-in toggles + Custom listing types & possession statuses -->
      <?php
        $disabledCoreListing    = (array)($settings['listing_types_disabled_core'] ?? []);
        $disabledCorePossession = (array)($settings['possession_statuses_disabled_core'] ?? []);
        $coreListing            = getCoreListingTypes();
        $corePossession         = getCorePossessionStatuses();
      ?>
      <style>
        .taxonomy-card {
          background:#fff; border:1px solid #e6ebf2; border-radius:10px;
          padding:1rem 1.1rem;
        }
        .taxonomy-card .core-grid {
          display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
          gap:.5rem .75rem;
        }
        .custom-row {
          background:#f9fafc; border:1px solid #e6ebf2; border-radius:10px;
          padding:.85rem .9rem;
        }
        .custom-row .chk-group { display:flex; gap:1rem; flex-wrap:wrap; }
        .custom-row .chk-group .form-check { min-width:110px; margin-bottom:0; }
        .custom-row .chk-label { font-size:.72rem; letter-spacing:.05em; text-transform:uppercase; color:var(--text-secondary, #6b7280); font-weight:700; margin-bottom:.25rem; }
      </style>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="listings">

        <!-- ─── Built-in Listing Types (enable/disable) ─── -->
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-tags me-2" style="color:var(--gold);"></i>Built-in Listing Types
        </h5>
        <p class="text-muted small mb-2">
          Untick a built-in type to hide it from the picker on <code>/admin/listing-form.php</code>.
          Existing listings already using that type continue to display the right label.
        </p>
        <div class="taxonomy-card mb-4">
          <div class="core-grid">
            <?php foreach ($coreListing as $slug => $row):
              $isEnabled = !in_array($slug, $disabledCoreListing, true);
            ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox"
                     name="listing_types_core_enabled[]"
                     value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                     id="ltc_<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                     <?= $isEnabled ? 'checked' : '' ?>>
              <label class="form-check-label" for="ltc_<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?>
                <small class="text-muted d-block fs-12"><?= implode(' / ', array_map('ucfirst', $row['categories'])) ?></small>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ─── Custom Listing Types ─── -->
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-plus me-2" style="color:var(--gold);"></i>Custom Listing Types
        </h5>
        <p class="text-muted small mb-2">
          Add your own types. Tick the categories (Residential / Commercial) and purposes (Sale / Rent) where this type should appear in the picker.
        </p>
        <?php $customTypes = (array)($settings['listing_types_custom'] ?? []); ?>
        <div id="listingTypesWrapper" class="d-flex flex-column gap-2">
          <?php for ($i = 0; $i < max(count($customTypes), 1); $i++):
            $row    = $customTypes[$i] ?? ['slug'=>'','label'=>'','categories'=>['residential'],'purposes'=>['sale','rent']];
            // Tolerate legacy single `category` rows from earlier saves.
            if (!isset($row['categories']) || !is_array($row['categories'])) {
                $row['categories'] = !empty($row['category']) ? [$row['category']] : ['residential'];
            }
            if (!isset($row['purposes']) || !is_array($row['purposes'])) {
                $row['purposes'] = ['sale','rent'];
            }
          ?>
          <div class="custom-row listing-type-row">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-4">
                <label class="chk-label">Label</label>
                <input type="text" name="listing_types_custom[<?= $i ?>][label]" class="form-control form-control-sm"
                       placeholder="e.g. Studio Apartment" maxlength="60"
                       value="<?= htmlspecialchars((string)($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-3">
                <label class="chk-label">Slug</label>
                <input type="text" name="listing_types_custom[<?= $i ?>][slug]" class="form-control form-control-sm font-monospace"
                       placeholder="auto" maxlength="40" pattern="[a-z0-9_]+"
                       value="<?= htmlspecialchars((string)($row['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-4">
                <div class="row g-2">
                  <div class="col-6">
                    <label class="chk-label">Categories</label>
                    <div class="chk-group">
                      <?php foreach (['residential'=>'Residential','commercial'=>'Commercial'] as $val => $lbl):
                        $id = "lt_{$i}_cat_{$val}";
                        $isOn = in_array($val, (array)$row['categories'], true);
                      ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="listing_types_custom[<?= $i ?>][categories][]"
                               value="<?= $val ?>" id="<?= $id ?>" <?= $isOn ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= $id ?>"><?= $lbl ?></label>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <div class="col-6">
                    <label class="chk-label">Purposes</label>
                    <div class="chk-group">
                      <?php foreach (['sale'=>'Sale','rent'=>'Rent'] as $val => $lbl):
                        $id = "lt_{$i}_pur_{$val}";
                        $isOn = in_array($val, (array)$row['purposes'], true);
                      ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="listing_types_custom[<?= $i ?>][purposes][]"
                               value="<?= $val ?>" id="<?= $id ?>" <?= $isOn ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= $id ?>"><?= $lbl ?></label>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addListingTypeBtn">
          <i class="fa-solid fa-plus me-1"></i> Add Listing Type
        </button>

        <hr class="my-4">

        <!-- ─── Built-in Possession Statuses (enable/disable) ─── -->
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-key me-2" style="color:var(--gold);"></i>Built-in Possession Statuses
        </h5>
        <p class="text-muted small mb-2">
          Untick a built-in status to hide it from the picker. Existing listings still render correctly.
        </p>
        <div class="taxonomy-card mb-4">
          <div class="core-grid">
            <?php foreach ($corePossession as $slug => $label):
              $isEnabled = !in_array($slug, $disabledCorePossession, true);
            ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox"
                     name="possession_statuses_core_enabled[]"
                     value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                     id="pos_<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                     <?= $isEnabled ? 'checked' : '' ?>>
              <label class="form-check-label" for="pos_<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ─── Custom Possession Statuses ─── -->
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-plus me-2" style="color:var(--gold);"></i>Custom Possession Statuses
        </h5>
        <p class="text-muted small mb-2">
          Add any extra statuses (e.g. <em>Newly Handed Over</em>, <em>Coming Soon</em>).
        </p>
        <?php $customPos = (array)($settings['possession_statuses_custom'] ?? []); ?>
        <div id="possessionStatusesWrapper" class="d-flex flex-column gap-2">
          <?php for ($i = 0; $i < max(count($customPos), 1); $i++):
            $row = $customPos[$i] ?? ['slug'=>'','label'=>''];
          ?>
          <div class="custom-row possession-row">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-5">
                <label class="chk-label">Label</label>
                <input type="text" name="possession_statuses_custom[<?= $i ?>][label]" class="form-control form-control-sm"
                       placeholder="e.g. Coming Soon" maxlength="60"
                       value="<?= htmlspecialchars((string)($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="chk-label">Slug</label>
                <input type="text" name="possession_statuses_custom[<?= $i ?>][slug]" class="form-control form-control-sm font-monospace"
                       placeholder="auto" maxlength="40" pattern="[a-z0-9_]+"
                       value="<?= htmlspecialchars((string)($row['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addPossessionBtn">
          <i class="fa-solid fa-plus me-1"></i> Add Possession Status
        </button>

        <hr class="my-4">

        <!-- ─── Built-in Project Statuses (enable/disable) ─── -->
        <?php $coreProject = getCoreProjectStatuses(); $disabledCoreProject = (array)($settings['project_statuses_disabled_core'] ?? []); ?>
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-folder-tree me-2" style="color:var(--gold);"></i>Built-in Project Statuses
        </h5>
        <p class="text-muted small mb-2">
          Untick a built-in status to hide it from the picker on <code>/admin/project-form.php</code> and from the public Projects page filter pills.
        </p>
        <div class="taxonomy-card mb-4">
          <div class="core-grid">
            <?php foreach ($coreProject as $slug => $label):
              $isEnabled = !in_array($slug, $disabledCoreProject, true);
            ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox"
                     name="project_statuses_core_enabled[]"
                     value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                     id="proj_<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                     <?= $isEnabled ? 'checked' : '' ?>>
              <label class="form-check-label" for="proj_<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ─── Custom Project Statuses ─── -->
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-plus me-2" style="color:var(--gold);"></i>Custom Project Statuses
        </h5>
        <p class="text-muted small mb-2">
          Add custom lifecycle stages (e.g. <em>Pre-launch</em>, <em>Sold Out</em>) — appears in the Project Status dropdown when creating or editing a project.
        </p>
        <?php $customProj = (array)($settings['project_statuses_custom'] ?? []); ?>
        <div id="projectStatusesWrapper" class="d-flex flex-column gap-2">
          <?php for ($i = 0; $i < max(count($customProj), 1); $i++):
            $row = $customProj[$i] ?? ['slug'=>'','label'=>''];
          ?>
          <div class="custom-row project-status-row">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-5">
                <label class="chk-label">Label</label>
                <input type="text" name="project_statuses_custom[<?= $i ?>][label]" class="form-control form-control-sm"
                       placeholder="e.g. Pre-launch" maxlength="60"
                       value="<?= htmlspecialchars((string)($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="chk-label">Slug</label>
                <input type="text" name="project_statuses_custom[<?= $i ?>][slug]" class="form-control form-control-sm font-monospace"
                       placeholder="auto" maxlength="40" pattern="[a-z0-9_]+"
                       value="<?= htmlspecialchars((string)($row['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addProjectStatusBtn">
          <i class="fa-solid fa-plus me-1"></i> Add Project Status
        </button>

        <div class="d-flex justify-content-end mt-4">
          <button type="submit" class="btn btn-gold">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Listing Taxonomy
          </button>
        </div>
      </form>

      <script>
      (function () {
        function slugify(s) {
          return String(s || '').toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        }

        // Auto-derive slug from label when slug is empty (initial + delegated for added rows).
        document.addEventListener('blur', function (e) {
          var t = e.target;
          if (!t || !t.name || !t.name.endsWith('[label]')) return;
          var row = t.closest('.custom-row, .row');
          var slugInp = row && row.querySelector('input[name$="[slug]"]');
          if (slugInp && !slugInp.value.trim()) slugInp.value = slugify(t.value);
        }, true);

        function addRow(wrapperId, templateBuilder) {
          var wrap = document.getElementById(wrapperId);
          if (!wrap) return;
          var idx = wrap.children.length;
          var holder = document.createElement('div');
          holder.innerHTML = templateBuilder(idx).trim();
          wrap.appendChild(holder.firstChild);
        }

        document.getElementById('addListingTypeBtn')?.addEventListener('click', function () {
          addRow('listingTypesWrapper', function (i) {
            return '' +
              '<div class="custom-row listing-type-row">' +
                '<div class="row g-2 align-items-end">' +
                  '<div class="col-12 col-md-4">' +
                    '<label class="chk-label">Label</label>' +
                    '<input type="text" name="listing_types_custom[' + i + '][label]" class="form-control form-control-sm" placeholder="e.g. Studio Apartment" maxlength="60">' +
                  '</div>' +
                  '<div class="col-12 col-md-3">' +
                    '<label class="chk-label">Slug</label>' +
                    '<input type="text" name="listing_types_custom[' + i + '][slug]" class="form-control form-control-sm font-monospace" placeholder="auto" maxlength="40" pattern="[a-z0-9_]+">' +
                  '</div>' +
                  '<div class="col-12 col-md-4">' +
                    '<div class="row g-2">' +
                      '<div class="col-6">' +
                        '<label class="chk-label">Categories</label>' +
                        '<div class="chk-group">' +
                          '<div class="form-check"><input class="form-check-input" type="checkbox" name="listing_types_custom[' + i + '][categories][]" value="residential" id="lt_' + i + '_cat_residential" checked><label class="form-check-label" for="lt_' + i + '_cat_residential">Residential</label></div>' +
                          '<div class="form-check"><input class="form-check-input" type="checkbox" name="listing_types_custom[' + i + '][categories][]" value="commercial"  id="lt_' + i + '_cat_commercial"><label class="form-check-label" for="lt_' + i + '_cat_commercial">Commercial</label></div>' +
                        '</div>' +
                      '</div>' +
                      '<div class="col-6">' +
                        '<label class="chk-label">Purposes</label>' +
                        '<div class="chk-group">' +
                          '<div class="form-check"><input class="form-check-input" type="checkbox" name="listing_types_custom[' + i + '][purposes][]" value="sale" id="lt_' + i + '_pur_sale" checked><label class="form-check-label" for="lt_' + i + '_pur_sale">Sale</label></div>' +
                          '<div class="form-check"><input class="form-check-input" type="checkbox" name="listing_types_custom[' + i + '][purposes][]" value="rent" id="lt_' + i + '_pur_rent" checked><label class="form-check-label" for="lt_' + i + '_pur_rent">Rent</label></div>' +
                        '</div>' +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="col-12 col-md-1 text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-trash"></i></button>' +
                  '</div>' +
                '</div>' +
              '</div>';
          });
        });

        document.getElementById('addPossessionBtn')?.addEventListener('click', function () {
          addRow('possessionStatusesWrapper', function (i) {
            return '' +
              '<div class="custom-row possession-row">' +
                '<div class="row g-2 align-items-end">' +
                  '<div class="col-12 col-md-5">' +
                    '<label class="chk-label">Label</label>' +
                    '<input type="text" name="possession_statuses_custom[' + i + '][label]" class="form-control form-control-sm" placeholder="e.g. Coming Soon" maxlength="60">' +
                  '</div>' +
                  '<div class="col-12 col-md-6">' +
                    '<label class="chk-label">Slug</label>' +
                    '<input type="text" name="possession_statuses_custom[' + i + '][slug]" class="form-control form-control-sm font-monospace" placeholder="auto" maxlength="40" pattern="[a-z0-9_]+">' +
                  '</div>' +
                  '<div class="col-12 col-md-1 text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-trash"></i></button>' +
                  '</div>' +
                '</div>' +
              '</div>';
          });
        });

        document.getElementById('addProjectStatusBtn')?.addEventListener('click', function () {
          addRow('projectStatusesWrapper', function (i) {
            return '' +
              '<div class="custom-row project-status-row">' +
                '<div class="row g-2 align-items-end">' +
                  '<div class="col-12 col-md-5">' +
                    '<label class="chk-label">Label</label>' +
                    '<input type="text" name="project_statuses_custom[' + i + '][label]" class="form-control form-control-sm" placeholder="e.g. Pre-launch" maxlength="60">' +
                  '</div>' +
                  '<div class="col-12 col-md-6">' +
                    '<label class="chk-label">Slug</label>' +
                    '<input type="text" name="project_statuses_custom[' + i + '][slug]" class="form-control form-control-sm font-monospace" placeholder="auto" maxlength="40" pattern="[a-z0-9_]+">' +
                  '</div>' +
                  '<div class="col-12 col-md-1 text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-trash"></i></button>' +
                  '</div>' +
                '</div>' +
              '</div>';
          });
        });

        // Delegated remove handler.
        document.addEventListener('click', function (e) {
          var btn = e.target.closest('.remove-row-btn');
          if (!btn) return;
          var row = btn.closest('.custom-row');
          if (row) row.remove();
        });
      })();
      </script>

      <?php elseif ($activeTab === 'navigation'): ?>
      <!-- Navigation Tab -->
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="navigation">

        <?php
        $navSections = [
            'header'                => ['title' => 'Header Navigation',      'hint' => 'Main menu shown in the site navbar and mobile drawer.'],
            'footer_quick'          => ['title' => 'Footer Quick Links',     'hint' => 'Second column of the footer.'],
            'footer_property_types' => ['title' => 'Footer Property Types',  'hint' => 'Third column of the footer.'],
        ];
        $navChoices = getNavUrlChoices();
        foreach ($navSections as $loc => $meta):
            $items = getNavItems($loc);
        ?>
        <div class="mb-4 pb-4 border-bottom">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <h6 class="fw-700 mb-0"><?= htmlspecialchars($meta['title']) ?></h6>
              <div class="form-text mt-0"><?= htmlspecialchars($meta['hint']) ?></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary nav-add-btn"
                    data-target="#navRows_<?= $loc ?>" data-loc="<?= $loc ?>">
              <i class="fa-solid fa-plus me-1"></i> Add Item
            </button>
          </div>

          <div id="navRows_<?= $loc ?>" class="nav-rows d-flex flex-column gap-2" data-loc="<?= $loc ?>">
            <?php foreach ($items as $i => $it):
              $isCustom = !isPresetNavUrl($it['url'], $navChoices);
            ?>
            <div class="nav-row row g-2 align-items-center">
              <div class="col-12 col-md-3">
                <input type="text" name="nav_<?= $loc ?>[<?= $i ?>][label]" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Label (e.g. Contact)">
              </div>
              <div class="col-12 col-md-4">
                <?php renderNavUrlSelect("nav_{$loc}[$i][url_preset]", $it['url'], $navChoices); ?>
              </div>
              <div class="col-12 col-md-4 nav-url-custom-wrap<?= $isCustom ? '' : ' d-none' ?>">
                <input type="text" name="nav_<?= $loc ?>[<?= $i ?>][url_custom]" class="form-control form-control-sm nav-url-custom"
                       value="<?= $isCustom ? htmlspecialchars($it['url'], ENT_QUOTES, 'UTF-8') : '' ?>"
                       placeholder="/custom-page.php or https://...">
              </div>
              <div class="col-12 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger w-100 nav-remove-btn" title="Remove item">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <template id="navRowTpl">
          <div class="nav-row row g-2 align-items-center">
            <div class="col-12 col-md-3">
              <input type="text" name="nav___LOC__[__IDX__][label]" class="form-control form-control-sm" placeholder="Label (e.g. Contact)">
            </div>
            <div class="col-12 col-md-4">
              <?php
                // Default new rows to Home, so the dropdown has a sensible selection.
                ob_start();
                renderNavUrlSelect('nav___LOC__[__IDX__][url_preset]', '/', $navChoices);
                echo ob_get_clean();
              ?>
            </div>
            <div class="col-12 col-md-4 nav-url-custom-wrap d-none">
              <input type="text" name="nav___LOC__[__IDX__][url_custom]" class="form-control form-control-sm nav-url-custom"
                     placeholder="/custom-page.php or https://...">
            </div>
            <div class="col-12 col-md-1 text-end">
              <button type="button" class="btn btn-sm btn-outline-danger w-100 nav-remove-btn" title="Remove item">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
        </template>

        <div class="d-flex justify-content-between align-items-center">
          <div class="form-text">
            URLs starting with <code>/</code> are site-relative and auto-prefixed in sub-directory installs. Absolute URLs (starting with <code>http</code>) are used as-is.
          </div>
          <button type="submit" class="btn btn-gold px-4">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Navigation
          </button>
        </div>
      </form>

      <script>
      (function(){
        var tpl = document.getElementById('navRowTpl');
        if (!tpl) return;

        document.querySelectorAll('.nav-add-btn').forEach(function(btn){
          btn.addEventListener('click', function(){
            var loc    = btn.dataset.loc;
            var target = document.querySelector(btn.dataset.target);
            if (!target) return;
            var nextIdx = target.querySelectorAll('.nav-row').length;
            var html = tpl.innerHTML
              .replaceAll('__LOC__', loc)
              .replaceAll('__IDX__', String(nextIdx));
            var tmp = document.createElement('div');
            tmp.innerHTML = html.trim();
            target.appendChild(tmp.firstChild);
          });
        });

        // Delegated remove-handler for all nav sections
        document.addEventListener('click', function(e){
          var btn = e.target.closest('.nav-remove-btn');
          if (!btn) return;
          var row = btn.closest('.nav-row');
          if (row) row.remove();
        });

        // Show/hide the custom-URL input when the preset select changes.
        document.addEventListener('change', function(e){
          var sel = e.target;
          if (!sel.classList || !sel.classList.contains('nav-url-preset')) return;
          var row = sel.closest('.nav-row');
          if (!row) return;
          var wrap = row.querySelector('.nav-url-custom-wrap');
          if (!wrap) return;
          wrap.classList.toggle('d-none', sel.value !== '__custom__');
        });
      })();
      </script>

      <?php elseif ($activeTab === 'smtp'): ?>
      <!-- SMTP Config Tab (stored in `settings` DB table) -->
      <?php $smtp = getSmtpConfig(true); ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="smtp">
        <div class="row g-3">
          <div class="col-12">
            <div class="alert alert-info d-flex gap-2">
              <i class="fa-solid fa-circle-info mt-1"></i>
              <span>Configure SMTP settings for outgoing emails (inquiry notifications, invitations, etc.).</span>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label fw-600">SMTP Host</label>
            <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com"
                   value="<?= htmlspecialchars($smtp['host'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Port</label>
            <input type="number" name="smtp_port" class="form-control" placeholder="587"
                   value="<?= htmlspecialchars((string)$smtp['port'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">SMTP Username</label>
            <input type="text" name="smtp_user" class="form-control" placeholder="your@email.com"
                   value="<?= htmlspecialchars($smtp['user'], ENT_QUOTES,'UTF-8') ?>" autocomplete="off">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">SMTP Password</label>
            <div class="input-group">
              <input type="password" name="smtp_pass" id="smtpPass" class="form-control"
                     placeholder="<?= $smtp['pass'] !== '' ? 'Leave blank to keep current' : 'Enter password' ?>"
                     autocomplete="new-password">
              <button class="btn btn-outline-secondary" type="button" id="toggleSmtpPass">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">From Name</label>
            <input type="text" name="smtp_from_name" class="form-control"
                   value="<?= htmlspecialchars($smtp['from_name'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">From Email</label>
            <input type="email" name="smtp_from_email" class="form-control"
                   value="<?= htmlspecialchars($smtp['from_email'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Reply-To Email</label>
            <input type="email" name="smtp_reply_to" class="form-control"
                   placeholder="optional"
                   value="<?= htmlspecialchars($smtp['reply_to'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 d-flex justify-content-between flex-wrap gap-2">
            <button type="submit" name="smtp_action" value="test" class="btn btn-outline-info"
                    data-confirm="Send a test email to <?= htmlspecialchars($_SESSION['admin_email']??'your email',ENT_QUOTES,'UTF-8') ?>?"
                    data-confirm-title="Send test email"
                    data-confirm-ok="Send test"
                    data-confirm-variant="primary">
              <i class="fa-solid fa-envelope me-1"></i>
              Test Email (to <?= htmlspecialchars($_SESSION['admin_email']??'', ENT_QUOTES,'UTF-8') ?>)
            </button>
            <button type="submit" name="smtp_action" value="save" class="btn btn-gold px-4">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save SMTP Settings
            </button>
          </div>
        </div>
      </form>
      <script>
      document.getElementById('toggleSmtpPass')?.addEventListener('click', function() {
        var inp = document.getElementById('smtpPass');
        var icon = this.querySelector('i');
        if (inp.type==='password') { inp.type='text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
        else { inp.type='password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
      });
      </script>

      <?php elseif ($activeTab === 'notifications'): ?>
      <!-- Notifications Tab -->
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="notifications">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-600">Email Recipients for Alerts</label>
            <textarea name="notify_email_recipients" class="form-control" rows="4"
                      placeholder="One email per line, or comma-separated"><?= htmlspecialchars($settings['notify_email_recipients'], ENT_QUOTES,'UTF-8') ?></textarea>
            <div class="form-text">These addresses will receive notification emails when enabled below.</div>
          </div>
          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="notifyInquiry" name="notify_on_inquiry"
                     value="1" <?= $settings['notify_on_inquiry']==='1'?'checked':'' ?>>
              <label class="form-check-label" for="notifyInquiry">
                <i class="fa-solid fa-inbox me-1" style="color:var(--gold)"></i>
                Notify on new inquiry
              </label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="notifyListing" name="notify_on_listing"
                     value="1" <?= $settings['notify_on_listing']==='1'?'checked':'' ?>>
              <label class="form-check-label" for="notifyListing">
                <i class="fa-solid fa-house me-1" style="color:var(--gold)"></i>
                Notify on new listing published
              </label>
            </div>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-gold px-4">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Notification Settings
            </button>
          </div>
        </div>
      </form>

      <?php elseif ($activeTab === 'banners'): ?>
      <!-- Banners Tab -->
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="banners">

        <h5 class="fw-700 mb-3"><i class="fa-solid fa-house me-2" style="color:var(--gold)"></i>Homepage Hero</h5>
        <div class="row g-3 mb-4">
          <div class="col-12">
            <label class="form-label fw-600">Badge text</label>
            <input type="text" name="hero_badge" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_badge'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Small pill above the headline.</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Heading (line 1)</label>
            <input type="text" name="hero_heading" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_heading'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Heading (accent line)</label>
            <input type="text" name="hero_heading_accent" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_heading_accent'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Styled in the primary theme colour.</div>
          </div>
          <div class="col-12">
            <label class="form-label fw-600">Sub-heading</label>
            <textarea name="hero_sub" class="form-control" rows="3"><?= htmlspecialchars($settings['hero_sub'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary CTA label</label>
            <input type="text" name="hero_cta_primary_label" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_cta_primary_label'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary CTA URL</label>
            <input type="text" name="hero_cta_primary_url" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_cta_primary_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="/listings.php">
            <div class="form-text">Internal path (e.g. <code>/contact.php</code>) or full URL. Leave empty for the default listings page.</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary CTA label</label>
            <input type="text" name="hero_cta_secondary_label" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_cta_secondary_label'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary CTA URL</label>
            <input type="text" name="hero_cta_secondary_url" class="form-control"
                   value="<?= htmlspecialchars($settings['hero_cta_secondary_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="https://wa.me/...">
            <div class="form-text">Internal path or full URL. Leave empty for the default WhatsApp link.</div>
          </div>
        </div>

        <!-- ─────────────── Hero stats strip (3 cells under the CTA) ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-chart-simple me-2" style="color:var(--gold)"></i>Hero Stats Strip</h5>
        <p class="text-muted small mb-3">The three small stats directly below the hero CTA buttons. Leave value blank to auto-fill from the database / default.</p>
        <div class="row g-3 mb-4">
          <?php for ($i = 0; $i < 3; $i++):
            $s = $settings['home_stats'][$i] ?? ['value'=>'', 'label'=>''];
          ?>
          <div class="col-12 col-md-4">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-body">
                <label class="form-label fw-600 small">Stat <?= $i+1 ?> Value</label>
                <input type="text" name="home_stats[<?= $i ?>][value]" class="form-control form-control-sm mb-2" maxlength="20"
                       placeholder="auto"
                       value="<?= htmlspecialchars($s['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Stat <?= $i+1 ?> Label</label>
                <input type="text" name="home_stats[<?= $i ?>][label]" class="form-control form-control-sm" maxlength="60"
                       value="<?= htmlspecialchars($s['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ─────────────── Hero Mosaic (right-side images) ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-images me-2" style="color:var(--gold)"></i>Hero Mosaic Images</h5>
        <p class="text-muted small mb-3">Three photos on the right of the hero. Leave empty to use the stock fallback. Max 5MB each, JPEG/PNG/WebP/GIF.</p>
        <div class="row g-3 mb-4">
          <?php foreach ([
            ['hero_mosaic_main',  'Main Image',  'Largest tile.'],
            ['hero_mosaic_tile1', 'Tile 1',      'Upper-left floating tile.'],
            ['hero_mosaic_tile2', 'Tile 2',      'Lower-right floating tile.'],
          ] as [$slot, $lbl, $help]): ?>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600"><?= $lbl ?></label>
            <?php if (!empty($settings[$slot])): ?>
              <div class="mb-2">
                <img src="<?= htmlspecialchars(BASE_PATH . $settings[$slot], ENT_QUOTES, 'UTF-8') ?>?v=<?= @filemtime(__DIR__ . '/..' . $settings[$slot]) ?: '' ?>"
                     alt="" style="max-height:120px;border-radius:8px;border:1px solid #dee2e6;">
              </div>
            <?php endif; ?>
            <input type="file" name="<?= $slot ?>" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="form-text"><?= $help ?></div>
            <?php if (!empty($settings[$slot])): ?>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox"
                       name="<?= $slot ?>_clear" id="<?= $slot ?>_clear" value="1">
                <label class="form-check-label small text-muted" for="<?= $slot ?>_clear">
                  Remove this image (revert to stock default)
                </label>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- ─────────────── Hero Floating Cards ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-id-card me-2" style="color:var(--gold)"></i>Hero Floating Cards</h5>
        <p class="text-muted small mb-3">The two small white cards that overlap the mosaic (rating + developers).</p>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Rating Score</label>
            <input type="text" name="hero_rating_score" class="form-control" maxlength="10"
                   placeholder="auto"
                   value="<?= htmlspecialchars($settings['hero_rating_score'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Empty → averaged from approved reviews. Example: <code>4.9/5</code>.</div>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label fw-600">Rating Caption</label>
            <input type="text" name="hero_rating_caption" class="form-control" maxlength="120"
                   placeholder="From {count}+ happy clients"
                   value="<?= htmlspecialchars($settings['hero_rating_caption'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Use <code>{count}</code> for live review count or <code>{clients}</code> for the configured Happy Clients value.</div>
          </div>

          <?php $devTags = $settings['hero_dev_tags'] ?? ['','','']; ?>
          <div class="col-12 col-md-2">
            <label class="form-label fw-600 small">Dev Tag 1</label>
            <input type="text" name="hero_dev_tags[0]" class="form-control" maxlength="6" placeholder="BT"
                   value="<?= htmlspecialchars($devTags[0] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-2">
            <label class="form-label fw-600 small">Dev Tag 2</label>
            <input type="text" name="hero_dev_tags[1]" class="form-control" maxlength="6" placeholder="DHA"
                   value="<?= htmlspecialchars($devTags[1] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-2">
            <label class="form-label fw-600 small">Dev Tag 3</label>
            <input type="text" name="hero_dev_tags[2]" class="form-control" maxlength="6" placeholder="CSC"
                   value="<?= htmlspecialchars($devTags[2] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label fw-600 small">"+N" Override</label>
            <input type="text" name="hero_dev_count_override" class="form-control" maxlength="6" placeholder="auto"
                   value="<?= htmlspecialchars($settings['hero_dev_count_override'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600 small">Devs Label</label>
            <input type="text" name="hero_dev_label" class="form-control" maxlength="60"
                   placeholder="Authorised Developers"
                   value="<?= htmlspecialchars($settings['hero_dev_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <!-- ─────────────── Wide stats band ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-chart-line me-2" style="color:var(--gold)"></i>Stats Band</h5>
        <p class="text-muted small mb-3">The wide navy strip with four animated counters lower on the homepage. Leave value blank to auto-fill from the database / default.</p>
        <div class="row g-3 mb-4">
          <?php for ($i = 0; $i < 4; $i++):
            $s = $settings['home_strip_stats'][$i] ?? ['value'=>'', 'label'=>''];
          ?>
          <div class="col-6 col-md-3">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-body">
                <label class="form-label fw-600 small">Stat <?= $i+1 ?> Value</label>
                <input type="text" name="home_strip_stats[<?= $i ?>][value]" class="form-control form-control-sm mb-2" maxlength="20"
                       placeholder="auto"
                       value="<?= htmlspecialchars($s['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Stat <?= $i+1 ?> Label</label>
                <input type="text" name="home_strip_stats[<?= $i ?>][label]" class="form-control form-control-sm" maxlength="60"
                       value="<?= htmlspecialchars($s['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ─────────────── Intent cards ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-compass me-2" style="color:var(--gold)"></i>Intent Cards Section</h5>
        <p class="text-muted small mb-3">The "What are you looking for?" block with three pick-your-path cards.</p>
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="intent_label" class="form-control" maxlength="60"
                   value="<?= htmlspecialchars($settings['intent_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="intent_heading" class="form-control" maxlength="160"
                   value="<?= htmlspecialchars($settings['intent_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Sub-heading</label>
            <input type="text" name="intent_sub" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($settings['intent_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="row g-3 mb-4">
          <?php for ($i = 0; $i < 3; $i++):
            $c = $settings['intent_cards'][$i] ?? ['icon'=>'','title'=>'','desc'=>'','cta_label'=>'','cta_url'=>'','highlight'=>'0'];
          ?>
          <div class="col-12 col-md-4">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-header"><i class="fa-solid fa-square-poll-vertical" style="color:var(--gold)"></i> Card <?= $i+1 ?></div>
              <div class="card-body">
                <label class="form-label fw-600 small">Icon (FontAwesome)</label>
                <input type="text" name="intent_cards[<?= $i ?>][icon]" class="form-control form-control-sm mb-2" maxlength="60"
                       placeholder="fa-house-chimney"
                       value="<?= htmlspecialchars($c['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Title</label>
                <input type="text" name="intent_cards[<?= $i ?>][title]" class="form-control form-control-sm mb-2" maxlength="80"
                       value="<?= htmlspecialchars($c['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Description</label>
                <textarea name="intent_cards[<?= $i ?>][desc]" class="form-control form-control-sm mb-2" rows="3" maxlength="300"><?= htmlspecialchars($c['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <label class="form-label fw-600 small">CTA Label</label>
                <input type="text" name="intent_cards[<?= $i ?>][cta_label]" class="form-control form-control-sm mb-2" maxlength="60"
                       value="<?= htmlspecialchars($c['cta_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">CTA URL</label>
                <input type="text" name="intent_cards[<?= $i ?>][cta_url]" class="form-control form-control-sm mb-2"
                       placeholder="/listings.php"
                       value="<?= htmlspecialchars($c['cta_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox"
                         name="intent_cards[<?= $i ?>][highlight]"
                         id="intentHighlight<?= $i ?>" value="1"
                         <?= (string)($c['highlight'] ?? '0') === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="intentHighlight<?= $i ?>">Highlight (gold tint)</label>
                </div>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ─────────────── Featured Projects section header ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-building me-2" style="color:var(--gold)"></i>"Featured Projects" Section</h5>
        <p class="text-muted small mb-3">Header copy for the Featured Projects strip on <code>/index.php</code> (the cards underneath are pulled live from the Projects admin).</p>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="home_featured_label" class="form-control" maxlength="60"
                   placeholder="New Developments"
                   value="<?= htmlspecialchars($settings['home_featured_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="home_featured_heading" class="form-control" maxlength="160"
                   placeholder="Featured Projects"
                   value="<?= htmlspecialchars($settings['home_featured_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">"View All" Button Label</label>
            <input type="text" name="home_featured_cta_label" class="form-control" maxlength="60"
                   placeholder="View All"
                   value="<?= htmlspecialchars($settings['home_featured_cta_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-600">Subtitle</label>
            <input type="text" name="home_featured_sub" class="form-control" maxlength="300"
                   placeholder="Authorised dealer for Pakistan's top real estate developments"
                   value="<?= htmlspecialchars($settings['home_featured_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <!-- ─────────────── Featured Properties section header ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-fire me-2" style="color:var(--gold)"></i>"Featured Properties" Section</h5>
        <p class="text-muted small mb-3">Header copy for the Featured Properties strip on <code>/index.php</code> (the cards underneath are pulled live from the Listings admin).</p>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="home_props_label" class="form-control" maxlength="60"
                   placeholder="Hot Listings"
                   value="<?= htmlspecialchars($settings['home_props_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="home_props_heading" class="form-control" maxlength="160"
                   placeholder="Featured Properties"
                   value="<?= htmlspecialchars($settings['home_props_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Top-Right Button Label</label>
            <input type="text" name="home_props_cta_label" class="form-control" maxlength="60"
                   placeholder="Browse All"
                   value="<?= htmlspecialchars($settings['home_props_cta_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label fw-600">Subtitle</label>
            <input type="text" name="home_props_sub" class="form-control" maxlength="300"
                   placeholder="Handpicked listings across Islamabad, Rawalpindi & beyond"
                   value="<?= htmlspecialchars($settings['home_props_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Bottom Button Label</label>
            <input type="text" name="home_props_bottom_cta_label" class="form-control" maxlength="80"
                   placeholder="Browse All Properties"
                   value="<?= htmlspecialchars($settings['home_props_bottom_cta_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Larger CTA below the property cards.</div>
          </div>
        </div>

        <!-- ─────────────── Why-choose section ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-star me-2" style="color:var(--gold)"></i>"Why Choose Us" Section</h5>
        <p class="text-muted small mb-3">Six numbered feature cards. Leave the sub-heading empty to auto-generate "Your trusted real estate partner in Pakistan since &lt;year&gt;".</p>
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-3">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="why_label" class="form-control" maxlength="60"
                   value="<?= htmlspecialchars($settings['why_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="why_heading" class="form-control" maxlength="160"
                   value="<?= htmlspecialchars($settings['why_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Sub-heading</label>
            <input type="text" name="why_sub" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($settings['why_sub'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="row g-3 mb-4">
          <?php for ($i = 0; $i < 6; $i++):
            $w = $settings['why_cards'][$i] ?? ['icon'=>'','title'=>'','desc'=>''];
          ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-header"><i class="fa-solid fa-hashtag" style="color:var(--gold)"></i> Reason <?= $i+1 ?></div>
              <div class="card-body">
                <label class="form-label fw-600 small">Icon</label>
                <input type="text" name="why_cards[<?= $i ?>][icon]" class="form-control form-control-sm mb-2" maxlength="80"
                       placeholder="fa-certificate &nbsp;or&nbsp; fa-brands fa-whatsapp"
                       value="<?= htmlspecialchars($w['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Title</label>
                <input type="text" name="why_cards[<?= $i ?>][title]" class="form-control form-control-sm mb-2" maxlength="80"
                       value="<?= htmlspecialchars($w['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Description</label>
                <textarea name="why_cards[<?= $i ?>][desc]" class="form-control form-control-sm" rows="3" maxlength="320"><?= htmlspecialchars($w['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ─────────────── Bottom CTA ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1"><i class="fa-solid fa-bullhorn me-2" style="color:var(--gold)"></i>Bottom Call-to-Action</h5>
        <p class="text-muted small mb-3">The navy panel above the footer with the gold "Years Trusted" badge.</p>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Eyebrow Label</label>
            <input type="text" name="cta_label" class="form-control" maxlength="60"
                   placeholder="Let's Talk"
                   value="<?= htmlspecialchars($settings['cta_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label fw-600">Heading</label>
            <input type="text" name="cta_heading" class="form-control"
                   value="<?= htmlspecialchars($settings['cta_heading'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-600">Sub-heading</label>
            <textarea name="cta_sub" class="form-control" rows="3"><?= htmlspecialchars($settings['cta_sub'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary Button Label</label>
            <input type="text" name="cta_primary_label" class="form-control" maxlength="80"
                   placeholder="Chat on WhatsApp"
                   value="<?= htmlspecialchars($settings['cta_primary_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary Button URL</label>
            <input type="text" name="cta_primary_url" class="form-control"
                   placeholder="https://wa.me/..."
                   value="<?= htmlspecialchars($settings['cta_primary_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Internal path or full URL. Leave empty for the default WhatsApp link.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary Button Label</label>
            <input type="text" name="cta_secondary_label" class="form-control" maxlength="80"
                   placeholder="Contact Form"
                   value="<?= htmlspecialchars($settings['cta_secondary_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary Button URL</label>
            <input type="text" name="cta_secondary_url" class="form-control"
                   placeholder="/contact.php"
                   value="<?= htmlspecialchars($settings['cta_secondary_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Internal path or full URL. Leave empty for the default contact page.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Hours line</label>
            <input type="text" name="cta_hours" class="form-control" maxlength="160"
                   placeholder="Mon–Sat 9am–7pm · Sun 11am–4pm"
                   value="<?= htmlspecialchars($settings['cta_hours'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Short blurb shown under the buttons. Leave empty to hide.</div>
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label fw-600">Badge Value</label>
            <input type="text" name="cta_badge_value" class="form-control" maxlength="20"
                   placeholder="auto"
                   value="<?= htmlspecialchars($settings['cta_badge_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Big number in the gold circle. Leave empty to auto-fill from "Years Active".</div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label fw-600">Badge Label</label>
            <input type="text" name="cta_badge_label" class="form-control" maxlength="40"
                   placeholder="Years Trusted"
                   value="<?= htmlspecialchars($settings['cta_badge_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-gold px-4">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Homepage
          </button>
        </div>
      </form>

      <?php elseif ($activeTab === 'theme'): ?>
      <!-- Theme Tab -->
      <form method="POST" id="themeForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="theme">

        <p class="text-muted mb-4" style="font-size:0.92rem;">
          Pick the two brand colours. The preview below updates instantly, and the change applies site-wide as soon as you click <strong>Save Theme</strong>.
        </p>

        <div class="row g-4 mb-4">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Primary (accent) colour</label>
            <div class="d-flex align-items-center gap-2">
              <input type="color" id="themePrimaryPicker" name="theme_primary"
                     value="<?= htmlspecialchars($settings['theme_primary'], ENT_QUOTES, 'UTF-8') ?>"
                     style="width:60px; height:46px; padding:0; border:1px solid #dee2e6; border-radius:8px; cursor:pointer;">
              <input type="text" id="themePrimaryHex" class="form-control font-monospace"
                     value="<?= htmlspecialchars($settings['theme_primary'], ENT_QUOTES, 'UTF-8') ?>"
                     pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="max-width:140px;">
            </div>
            <div class="form-text">Replaces <code>--gold</code> across the site (buttons, accents, links).</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Secondary (brand) colour</label>
            <div class="d-flex align-items-center gap-2">
              <input type="color" id="themeSecondaryPicker" name="theme_secondary"
                     value="<?= htmlspecialchars($settings['theme_secondary'], ENT_QUOTES, 'UTF-8') ?>"
                     style="width:60px; height:46px; padding:0; border:1px solid #dee2e6; border-radius:8px; cursor:pointer;">
              <input type="text" id="themeSecondaryHex" class="form-control font-monospace"
                     value="<?= htmlspecialchars($settings['theme_secondary'], ENT_QUOTES, 'UTF-8') ?>"
                     pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="max-width:140px;">
            </div>
            <div class="form-text">Replaces <code>--navy-700</code> (dark backgrounds, headings).</div>
          </div>
        </div>

        <!-- Live preview -->
        <div id="themePreviewWrap" class="mb-4" style="border:1px solid #e6ebf2; border-radius:12px; overflow:hidden;">
          <div style="background: var(--preview-secondary); color:#fff; padding:1.5rem 2rem;">
            <div style="font-size:0.78rem; opacity:.7; letter-spacing:.08em; text-transform:uppercase;">Preview</div>
            <h3 style="margin:0; color:#fff;">
              <span style="color:#fff;">Smart real estate </span>
              <span style="color: var(--preview-primary);">starts here.</span>
            </h3>
            <p style="opacity:.75; margin:.5rem 0 1rem 0; font-size:.92rem;">
              This is how the homepage hero will look with your chosen colours.
            </p>
            <div style="display:inline-flex; gap:.5rem;">
              <button type="button"
                      style="background: var(--preview-primary); color: var(--preview-secondary); border:none; padding:.5rem 1rem; border-radius:8px; font-weight:700; cursor:default;">
                Primary CTA
              </button>
              <button type="button"
                      style="background:transparent; color:#fff; border:1px solid #fff; padding:.5rem 1rem; border-radius:8px; cursor:default;">
                Secondary CTA
              </button>
            </div>
          </div>
          <div style="background:#fff; padding:1.5rem 2rem;">
            <div style="font-weight:700; color: var(--preview-secondary);">Featured Listing</div>
            <div style="color: var(--preview-primary); font-weight:700; font-size:1.4rem;">PKR 1.5 Crore</div>
            <a href="#" onclick="return false;" style="color: var(--preview-primary); text-decoration:none; font-weight:600;">
              View details &rarr;
            </a>
          </div>
        </div>

        <div class="d-flex justify-content-between gap-2">
          <button type="submit" name="reset_defaults" value="1" class="btn btn-outline-secondary"
                  data-confirm="Reset theme colours to the brand defaults?"
                  data-confirm-title="Reset theme"
                  data-confirm-ok="Reset"
                  data-confirm-variant="warning">
            <i class="fa-solid fa-rotate-left me-1"></i> Reset to defaults
          </button>
          <button type="submit" class="btn btn-gold px-4">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Theme
          </button>
        </div>
      </form>

      <script>
      (function () {
        var wrap = document.getElementById('themePreviewWrap');
        if (!wrap) return;
        var primaryPicker = document.getElementById('themePrimaryPicker');
        var primaryHex    = document.getElementById('themePrimaryHex');
        var secondaryPicker = document.getElementById('themeSecondaryPicker');
        var secondaryHex    = document.getElementById('themeSecondaryHex');

        function apply() {
          wrap.style.setProperty('--preview-primary',   primaryPicker.value);
          wrap.style.setProperty('--preview-secondary', secondaryPicker.value);
        }
        function syncFromPicker(picker, hex) {
          hex.value = picker.value.toLowerCase();
          apply();
        }
        function syncFromHex(picker, hex) {
          var v = hex.value.trim();
          if (/^#[0-9a-fA-F]{6}$/.test(v)) {
            picker.value = v;
            apply();
          }
        }

        primaryPicker.addEventListener('input',   function () { syncFromPicker(primaryPicker, primaryHex); });
        primaryHex.addEventListener('input',      function () { syncFromHex(primaryPicker, primaryHex); });
        secondaryPicker.addEventListener('input', function () { syncFromPicker(secondaryPicker, secondaryHex); });
        secondaryHex.addEventListener('input',    function () { syncFromHex(secondaryPicker, secondaryHex); });

        apply();
      })();
      </script>

      <?php elseif ($activeTab === 'preferences'): ?>
      <!-- Preferences Tab -->
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
        <input type="hidden" name="tab" value="preferences">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Default City</label>
            <select name="default_city" class="form-select">
              <?php foreach (['islamabad'=>'Islamabad','rawalpindi'=>'Rawalpindi','lahore'=>'Lahore','karachi'=>'Karachi','peshawar'=>'Peshawar'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $settings['default_city']===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Used as default selection in search forms and filters.</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">Currency Format</label>
            <select name="currency_format" class="form-select">
              <option value="pakistan" <?= $settings['currency_format']==='pakistan'?'selected':'' ?>>Pakistan Format (1 Crore, 50 Lakh)</option>
              <option value="raw"      <?= $settings['currency_format']==='raw'?'selected':'' ?>>Raw Number (PKR 10,000,000)</option>
            </select>
          </div>
          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="watermark" name="watermark"
                     value="1" <?= $settings['watermark']==='1'?'checked':'' ?>>
              <label class="form-check-label" for="watermark">
                <i class="fa-solid fa-droplet me-1" style="color:var(--gold)"></i>
                Add watermark to uploaded property images
              </label>
            </div>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-gold px-4">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Preferences
            </button>
          </div>
        </div>
      </form>
      <?php endif; ?>

    </div><!-- /.card-plain -->
  </div><!-- /.col -->
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
