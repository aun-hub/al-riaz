<?php
/**
 * Al-Riaz Associates — Migration 008: Add password-reset columns to `users`
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/008_users_password_reset.php
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

    foreach (['reset_token' => "VARCHAR(100) DEFAULT NULL",
              'reset_token_expires_at' => "DATETIME DEFAULT NULL"] as $col => $def) {
        $exists = (bool)$db->query("SHOW COLUMNS FROM `users` LIKE '$col'")->fetch();
        if ($exists) {
            $log[] = ['ok', "Column `$col` already present."];
        } else {
            $db->exec("ALTER TABLE `users` ADD COLUMN `$col` $def AFTER `invite_token`");
            $log[] = ['ok', "Added `$col` column to `users`."];
        }
    }

    $idxExists = (bool)$db->query("SHOW INDEX FROM `users` WHERE Key_name = 'idx_reset_token'")->fetch();
    if (!$idxExists) {
        $db->exec("ALTER TABLE `users` ADD INDEX `idx_reset_token` (`reset_token`)");
        $log[] = ['ok', 'Added `idx_reset_token` index.'];
    } else {
        $log[] = ['ok', 'Index `idx_reset_token` already present.'];
    }

    $log[] = ['done', 'Migration 008 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 008 — users password reset</title>
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
    <h3 class="mb-3">Migration 008 — <code>users</code> password-reset columns</h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
