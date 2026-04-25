<?php
/**
 * Al-Riaz Associates — Migration 007: Create `property_features` table
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/007_property_features.php
 *
 * Idempotent. Seeds the table with the 15 baseline features that were
 * previously hardcoded in admin/listing-form.php.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    http_response_code(403);
    die('Access denied. Run this migration from localhost only.');
}

$log = [];

try {
    $db = Database::getInstance();

    $exists = (bool)$db->query("SHOW TABLES LIKE 'property_features'")->fetch();
    if ($exists) {
        $log[] = ['ok', 'Table `property_features` already exists.'];
    } else {
        $db->exec("CREATE TABLE `property_features` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug`       VARCHAR(80)  NOT NULL UNIQUE,
            `label`      VARCHAR(120) NOT NULL,
            `icon`       VARCHAR(80)  NOT NULL DEFAULT 'fa-check-circle',
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_active` (`is_active`),
            INDEX `idx_sort`   (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $log[] = ['ok', 'Created `property_features` table.'];
    }

    $seeds = [
        ['parking',         'Parking',          'fa-square-parking'],
        ['gas',             'Gas',              'fa-fire-flame-curved'],
        ['electricity',     'Electricity',      'fa-bolt-lightning'],
        ['water',           'Water Supply',     'fa-droplet'],
        ['security',        'Security',         'fa-shield-halved'],
        ['boundary_wall',   'Boundary Wall',    'fa-border-all'],
        ['furnished',       'Furnished',        'fa-couch'],
        ['corner',          'Corner Plot',      'fa-arrows-turn-to-dots'],
        ['garden',          'Garden',           'fa-tree'],
        ['servant_quarter', 'Servant Quarter',  'fa-user-tie'],
        ['store_room',      'Store Room',       'fa-box-archive'],
        ['drawing_room',    'Drawing Room',     'fa-couch'],
        ['double_unit',     'Double Unit',      'fa-layer-group'],
        ['basement',        'Basement',         'fa-layer-group'],
        ['lift',            'Lift / Elevator',  'fa-elevator'],
    ];

    $ins = $db->prepare(
        "INSERT IGNORE INTO `property_features` (`slug`, `label`, `icon`, `sort_order`, `is_active`)
         VALUES (?, ?, ?, ?, 1)"
    );
    $seeded = 0;
    foreach ($seeds as $i => [$slug, $label, $icon]) {
        $ins->execute([$slug, $label, $icon, $i * 10]);
        if ($ins->rowCount() > 0) $seeded++;
    }
    $log[] = ['ok', $seeded > 0
        ? "Seeded $seeded baseline feature(s)."
        : 'Baseline features already present — nothing to seed.'];

    $log[] = ['done', 'Migration 007 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 007 — property_features</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  body { background:#f4f6f9; font-family:'Segoe UI',sans-serif; padding:3rem 1rem; }
  .card { border:none; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); max-width:720px; margin:0 auto; }
  .log-row { padding:.55rem .9rem; border-radius:6px; margin-bottom:.4rem; font-size:.92rem; display:flex; gap:.6rem; }
  .ok { background:#d1e7dd; color:#0a3622; }
  .error { background:#f8d7da; color:#58151c; }
  .done { background:#0A1628; color:#F5B301; font-weight:600; }
  h3 { color:#0A1628; }
</style>
</head>
<body>
  <div class="card p-4">
    <h3 class="mb-3">Migration 007 — <code>property_features</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
