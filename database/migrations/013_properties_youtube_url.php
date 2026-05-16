<?php
/**
 * Al-Riaz Associates — Migration 013: Add `youtube_url` to `properties`
 *
 * Older DBs predate the YouTube video field on listings and break the
 * property create/edit form ("Unknown column 'youtube_url' in SET").
 *
 * Run once:
 *   - Browser (localhost): http://localhost/al-riaz/database/migrations/013_properties_youtube_url.php
 *   - SSH (server):        php /path/to/site/database/migrations/013_properties_youtube_url.php
 *
 * Idempotent.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (PHP_SAPI !== 'cli' && !isLocalRequest($ip)) {
    http_response_code(403);
    die('Access denied. Detected REMOTE_ADDR: ' . htmlspecialchars($ip)
        . '. Run this migration from localhost or a private LAN address only.');
}

function isLocalRequest(string $ip): bool
{
    if ($ip === '') return false;
    if (in_array($ip, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) return true;

    $isPublic = (bool)filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    );
    return !$isPublic;
}

$log = [];

try {
    $db = Database::getInstance();

    $exists = (bool)$db->query("SHOW COLUMNS FROM `properties` LIKE 'youtube_url'")->fetch();
    if ($exists) {
        $log[] = ['ok', 'Column `youtube_url` already present on `properties`.'];
    } else {
        $db->exec("ALTER TABLE `properties`
                   ADD COLUMN `youtube_url` VARCHAR(500) DEFAULT NULL
                   AFTER `description`");
        $log[] = ['ok', 'Added `youtube_url` column to `properties`.'];
    }

    $log[] = ['done', 'Migration 013 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}

if (PHP_SAPI === 'cli') {
    foreach ($log as [$status, $msg]) {
        $tag = $status === 'ok' ? '[OK] ' : ($status === 'error' ? '[ERR] ' : '[DONE] ');
        echo $tag . $msg . PHP_EOL;
    }
    exit($log[count($log)-1][0] === 'error' ? 1 : 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 013 — properties.youtube_url</title>
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
    <h3 class="mb-3">Migration 013 — <code>properties.youtube_url</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
