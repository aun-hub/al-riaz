<?php
/**
 * Al-Riaz Associates — Migration 002: Add `is_hq` flag to `branches`
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/002_branches_is_hq.php
 *
 * Idempotent — checks for the column before adding it.
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

    $exists = (bool)$db->query("SHOW COLUMNS FROM `branches` LIKE 'is_hq'")->fetch();
    if ($exists) {
        $log[] = ['ok', 'Column `is_hq` already present — nothing to do.'];
    } else {
        $db->exec("ALTER TABLE `branches`
                   ADD COLUMN `is_hq` TINYINT(1) NOT NULL DEFAULT 0 AFTER `hours`,
                   ADD INDEX `idx_hq` (`is_hq`)");
        $log[] = ['ok', 'Added `is_hq` column and `idx_hq` index to `branches`.'];
    }

    $log[] = ['done', 'Migration 002 complete.'];

} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 002 — branches.is_hq</title>
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
    <h3 class="mb-3">Migration 002 — <code>branches.is_hq</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '✔' : ($status === 'error' ? '✘' : '✓') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
