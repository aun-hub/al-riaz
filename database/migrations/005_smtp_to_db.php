<?php
/**
 * Al-Riaz Associates — Migration 005: Move SMTP config from
 * config/settings.json into the `settings` DB table.
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/005_smtp_to_db.php
 *
 * Idempotent. Safe to re-run.
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

    $smtpKeys = [
        'smtp_host','smtp_port','smtp_user','smtp_pass',
        'smtp_from_name','smtp_from_email','smtp_reply_to',
    ];

    $file = __DIR__ . '/../../config/settings.json';
    if (!is_readable($file)) {
        $log[] = ['ok', 'No config/settings.json found — nothing to migrate.'];
    } else {
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) {
            $log[] = ['error', 'settings.json is not valid JSON.'];
        } else {
            $up = $db->prepare(
                'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            $moved = 0;
            foreach ($smtpKeys as $k) {
                if (array_key_exists($k, $data)) {
                    $up->execute([$k, (string)$data[$k]]);
                    unset($data[$k]);
                    $moved++;
                }
            }
            if ($moved > 0) {
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $log[] = ['ok', "Moved $moved SMTP key(s) from settings.json into the `settings` table."];
            } else {
                $log[] = ['ok', 'No SMTP keys were present in settings.json.'];
            }
        }
    }

    $log[] = ['done', 'Migration 005 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 005 — SMTP to DB</title>
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
    <h3 class="mb-3">Migration 005 — SMTP → DB</h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '✔' : ($status === 'error' ? '✘' : '✓') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
