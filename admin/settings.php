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
    'phone'          => '+92 300 123 4567',
    'whatsapp'       => '923001234567',
    'email'          => 'info@alriazassociates.pk',
    'address'        => 'Islamabad, Pakistan',
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
    'about_story_label'       => 'Our Story',
    'about_story_heading'     => 'Built on Trust & Transparency',
    'about_story_body'        => "Al-Riaz Associates was founded with a clear mission: to bring transparency, integrity, and professionalism to Pakistan's real estate market. Based in the heart of Islamabad, we began as a small consultancy helping families find their dream homes in Rawalpindi and Islamabad.\n\nOver the years, we have grown into a full-service real estate agency, becoming an **authorised dealer** for Pakistan's most prestigious developments including Bahria Town, DHA, Capital Smart City, Gulberg Greens, and Blue World City.\n\nToday, our team of experienced property consultants serves clients across Islamabad, Rawalpindi, Lahore, and Karachi — offering verified listings, transparent pricing, and end-to-end support from property search to final possession.",
    'about_story_image'       => '',
    'about_story_badge_value' => '5+ Years',
    'about_story_badge_label' => 'In Real Estate',

    // About-page key-stats strip (4 cards). Empty value falls back to the
    // DB-derived count or the legacy seed value.
    'about_stats' => [
        ['value' => '', 'label' => 'Properties Listed'],
        ['value' => '', 'label' => 'Active Projects'],
        ['value' => '200', 'label' => 'Happy Clients'],
        ['value' => '5',   'label' => 'Years Active'],
    ],

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

    // Core Values strip (4 cards).
    'about_values' => [
        ['icon' => 'fa-handshake',    'title' => 'Integrity',    'desc' => 'We never compromise on honesty in our dealings.'],
        ['icon' => 'fa-check-circle', 'title' => 'Transparency', 'desc' => 'Clear pricing, no hidden costs or surprises.'],
        ['icon' => 'fa-user-tie',     'title' => 'Expertise',    'desc' => 'Seasoned consultants with deep market knowledge.'],
        ['icon' => 'fa-headset',      'title' => 'Client First', 'desc' => 'Your satisfaction is our top priority, always.'],
    ],
];

$settings = array_merge($defaults, $settings);

$activeTab = $_GET['tab'] ?? 'agency';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $tab = $_POST['tab'] ?? 'agency';

    if ($tab === 'agency') {
        $fields = ['agency_name','agency_tagline','phone','whatsapp','email','address','website','facebook_url','instagram_url','youtube_url'];
        foreach ($fields as $f) {
            $settings[$f] = trim($_POST[$f] ?? $settings[$f]);
        }

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
                'INSERT INTO branches (name, address, phone, hours, hours_schedule, is_hq, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
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
                    $isHq = ($hqPick === 'branch-' . $branchIdx) ? 1 : 0;
                    $ins->execute([
                        $name,
                        $address,
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

    if ($tab === 'about') {
        // ── Story sub-section ───────────────────────────────────────
        foreach (['about_story_label','about_story_heading','about_story_body','about_story_badge_value','about_story_badge_label'] as $f) {
            if (array_key_exists($f, $_POST)) $settings[$f] = trim((string)$_POST[$f]);
        }

        // ── Stats strip (4 items) ──────────────────────────────────
        $statsIn = $_POST['about_stats'] ?? [];
        $stats = [];
        for ($i = 0; $i < 4; $i++) {
            $row = is_array($statsIn[$i] ?? null) ? $statsIn[$i] : [];
            $stats[] = [
                'value' => trim((string)($row['value'] ?? '')),
                'label' => trim((string)($row['label'] ?? '')),
            ];
        }
        $settings['about_stats'] = $stats;

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
      $tabs = [
        'agency'        => ['icon'=>'fa-building','label'=>'Agency Profile'],
        'about'         => ['icon'=>'fa-circle-info','label'=>'About Page'],
        'navigation'    => ['icon'=>'fa-bars','label'=>'Navigation'],
        'smtp'          => ['icon'=>'fa-envelope','label'=>'SMTP Config'],
        'notifications' => ['icon'=>'fa-bell','label'=>'Notifications'],
        'preferences'   => ['icon'=>'fa-sliders','label'=>'Preferences'],
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
            <input type="text" name="address" class="form-control"
                   value="<?= htmlspecialchars($settings['address'], ENT_QUOTES,'UTF-8') ?>">
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
              $stmtBr = $db->query('SELECT name, address, phone, hours, hours_schedule, is_hq FROM branches ORDER BY sort_order ASC, id ASC');
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
                    <input type="text" name="branches[<?= $i ?>][address]" class="form-control form-control-sm" value="<?= $ba ?>">
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
              <input type="text" name="branches[__IDX__][address]" class="form-control form-control-sm">
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

        <!-- ─────────────── Key Stats Strip ─────────────── -->
        <hr class="my-4">
        <h5 class="fw-700 mb-1" style="color:var(--sidebar-bg);">
          <i class="fa-solid fa-chart-line me-2" style="color:var(--gold);"></i>Key Stats
        </h5>
        <p class="text-muted small mb-3">The 4 dark-blue stat cards under the story. Leave a value blank to auto-pull from the database (where supported) or fall back to the previous default.</p>
        <div class="row g-3">
          <?php for ($i = 0; $i < 4; $i++):
            $stat = $settings['about_stats'][$i] ?? ['value'=>'', 'label'=>''];
          ?>
          <div class="col-12 col-md-3">
            <div class="form-section-card mb-0" style="height:100%;">
              <div class="card-body">
                <label class="form-label fw-600 small">Stat <?= $i + 1 ?> Value</label>
                <input type="text" name="about_stats[<?= $i ?>][value]" class="form-control form-control-sm mb-2" maxlength="20"
                       placeholder="<?= $i === 0 ? 'auto' : ($i === 1 ? 'auto' : ($i === 2 ? '200' : '5')) ?>"
                       value="<?= htmlspecialchars($stat['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label fw-600 small">Stat <?= $i + 1 ?> Label</label>
                <input type="text" name="about_stats[<?= $i ?>][label]" class="form-control form-control-sm" maxlength="60"
                       value="<?= htmlspecialchars($stat['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
          </div>
          <?php endfor; ?>
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

        <div class="d-flex justify-content-end mt-4 pb-2">
          <button type="submit" class="btn btn-gold">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save About Page
          </button>
        </div>
      </form>

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
