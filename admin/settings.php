<?php
/**
 * Al-Riaz Associates — Site Settings
 */
$pageTitle = 'Settings';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

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
    'address'        => 'Islamabad, Pakistan',
    'website'        => 'https://alriazassociates.pk',
    'facebook_url'   => '',
    'instagram_url'  => '',
    'youtube_url'    => '',
    'smtp_host'      => '',
    'smtp_port'      => '587',
    'smtp_user'      => '',
    'smtp_pass'      => '',
    'smtp_from_name' => 'Al-Riaz Associates',
    'smtp_from_email'=> 'noreply@alriazassociates.pk',
    'smtp_reply_to'  => '',
    'notify_email_recipients' => '',
    'notify_on_inquiry' => '1',
    'notify_on_listing' => '0',
    'default_city'   => 'islamabad',
    'watermark'      => '0',
    'currency_format'=> 'pakistan', // pakistan = Lakh/Crore, raw = number
    'logo_path'      => '',
];

$settings = array_merge($defaults, $settings);

$activeTab = $_GET['tab'] ?? 'agency';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $tab = $_POST['tab'] ?? 'agency';

    if ($tab === 'agency') {
        $fields = ['agency_name','agency_tagline','phone','whatsapp','address','website','facebook_url','instagram_url','youtube_url'];
        foreach ($fields as $f) {
            $settings[$f] = trim($_POST[$f] ?? $settings[$f]);
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
        header('Location: /admin/settings.php?tab=agency'); exit;
    }

    if ($tab === 'smtp') {
        $fields = ['smtp_host','smtp_port','smtp_user','smtp_from_name','smtp_from_email','smtp_reply_to'];
        foreach ($fields as $f) { $settings[$f] = trim($_POST[$f] ?? $settings[$f]); }
        // Only update password if provided
        if (!empty($_POST['smtp_pass'])) {
            $settings['smtp_pass'] = $_POST['smtp_pass'];
        }
        saveSettings($settingsFile, $settings);
        auditLog('update','settings',0,'Updated SMTP configuration');
        setFlash('success', 'SMTP settings saved.');
        header('Location: /admin/settings.php?tab=smtp'); exit;
    }

    if ($tab === 'smtp_test') {
        // Simple test email using mail()
        $to      = $_SESSION['admin_email'] ?? '';
        $subject = 'Test Email — Al-Riaz Associates Admin';
        $message = "This is a test email from Al-Riaz Associates Admin Panel.\n\nSent at: " . date('Y-m-d H:i:s');
        $headers = "From: " . ($settings['smtp_from_name'] ?? 'Al-Riaz Associates') . " <" . ($settings['smtp_from_email'] ?? 'noreply@alriazassociates.pk') . ">";
        if ($to && mail($to, $subject, $message, $headers)) {
            setFlash('success', "Test email sent to $to.");
        } else {
            setFlash('danger', 'Failed to send test email. Check SMTP settings or server mail configuration.');
        }
        header('Location: /admin/settings.php?tab=smtp'); exit;
    }

    if ($tab === 'notifications') {
        $settings['notify_email_recipients'] = trim($_POST['notify_email_recipients'] ?? '');
        $settings['notify_on_inquiry']        = isset($_POST['notify_on_inquiry']) ? '1' : '0';
        $settings['notify_on_listing']        = isset($_POST['notify_on_listing'])  ? '1' : '0';
        saveSettings($settingsFile, $settings);
        setFlash('success', 'Notification settings saved.');
        header('Location: /admin/settings.php?tab=notifications'); exit;
    }

    if ($tab === 'preferences') {
        $settings['default_city']   = trim($_POST['default_city']   ?? $settings['default_city']);
        $settings['watermark']      = isset($_POST['watermark'])  ? '1' : '0';
        $settings['currency_format']= $_POST['currency_format'] === 'raw' ? 'raw' : 'pakistan';
        saveSettings($settingsFile, $settings);
        setFlash('success', 'Preferences saved.');
        header('Location: /admin/settings.php?tab=preferences'); exit;
    }
}

$csrf = csrfToken();

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
        'smtp'          => ['icon'=>'fa-envelope','label'=>'SMTP Config'],
        'notifications' => ['icon'=>'fa-bell','label'=>'Notifications'],
        'preferences'   => ['icon'=>'fa-sliders','label'=>'Preferences'],
      ];
      foreach ($tabs as $tk => $td):
      ?>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab===$tk?'active':'' ?>"
           href="/admin/settings.php?tab=<?= $tk ?>">
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
                <img src="<?= htmlspecialchars($settings['logo_path'], ENT_QUOTES,'UTF-8') ?>" alt="Logo" style="max-height:60px;">
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
          <div class="col-12">
            <label class="form-label fw-600">Address</label>
            <input type="text" name="address" class="form-control"
                   value="<?= htmlspecialchars($settings['address'], ENT_QUOTES,'UTF-8') ?>">
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
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-gold px-4">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Agency Profile
            </button>
          </div>
        </div>
      </form>

      <?php elseif ($activeTab === 'smtp'): ?>
      <!-- SMTP Config Tab -->
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
                   value="<?= htmlspecialchars($settings['smtp_host'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Port</label>
            <input type="number" name="smtp_port" class="form-control" placeholder="587"
                   value="<?= htmlspecialchars($settings['smtp_port'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">SMTP Username</label>
            <input type="text" name="smtp_user" class="form-control" placeholder="your@email.com"
                   value="<?= htmlspecialchars($settings['smtp_user'], ENT_QUOTES,'UTF-8') ?>" autocomplete="off">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-600">SMTP Password</label>
            <div class="input-group">
              <input type="password" name="smtp_pass" id="smtpPass" class="form-control"
                     placeholder="Leave blank to keep current" autocomplete="new-password">
              <button class="btn btn-outline-secondary" type="button" id="toggleSmtpPass">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">From Name</label>
            <input type="text" name="smtp_from_name" class="form-control"
                   value="<?= htmlspecialchars($settings['smtp_from_name'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">From Email</label>
            <input type="email" name="smtp_from_email" class="form-control"
                   value="<?= htmlspecialchars($settings['smtp_from_email'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-600">Reply-To Email</label>
            <input type="email" name="smtp_reply_to" class="form-control"
                   placeholder="optional"
                   value="<?= htmlspecialchars($settings['smtp_reply_to'], ENT_QUOTES,'UTF-8') ?>">
          </div>
          <div class="col-12 d-flex justify-content-between flex-wrap gap-2">
            <form method="POST" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES,'UTF-8') ?>">
              <input type="hidden" name="tab" value="smtp_test">
              <button type="submit" class="btn btn-outline-info"
                      onclick="return confirm('Send a test email to <?= htmlspecialchars($_SESSION['admin_email']??'your email',ENT_QUOTES,'UTF-8') ?>?')">
                <i class="fa-solid fa-envelope me-1"></i>
                Test Email (to <?= htmlspecialchars($_SESSION['admin_email']??'', ENT_QUOTES,'UTF-8') ?>)
              </button>
            </form>
            <button type="submit" class="btn btn-gold px-4">
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
