<?php
/**
 * Al-Riaz Associates — Migration 004: Add `is_sold` flag to `properties`
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/004_properties_is_sold.php
 *
 * Idempotent.
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
    $exists = (bool)$db->query("SHOW COLUMNS FROM `properties` LIKE 'is_sold'")->fetch();
    if ($exists) {
        $log[] = ['ok', 'Column `is_sold` already present — nothing to do.'];
    } else {
        $db->exec("ALTER TABLE `properties`
                   ADD COLUMN `is_sold` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_published`,
                   ADD INDEX `idx_sold` (`is_sold`)");
        $log[] = ['ok', 'Added `is_sold` column and `idx_sold` index to `properties`.'];
    }
    $log[] = ['done', 'Migration 004 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 004 — properties.is_sold</title>
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
    <h3 class="mb-3">Migration 004 — <code>properties.is_sold</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '✔' : ($status === 'error' ? '✘' : '✓') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
