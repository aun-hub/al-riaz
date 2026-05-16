<?php
/**
 * Al-Riaz Associates — Migration 011: Create `project_notices` table
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/011_project_notices.php
 *
 * Idempotent. Stores public project notices (info / warning / critical)
 * surfaced on /notices.php and as the homepage auto-popup.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (PHP_SAPI !== 'cli' && !isLocalRequest($ip)) {
    http_response_code(403);
    die('Access denied. Detected REMOTE_ADDR: ' . htmlspecialchars($ip)
        . '. Run this migration from localhost or a private LAN address only.');
}

/**
 * Allow loopback and standard private/link-local IPv4/IPv6 ranges.
 * Public IPs are rejected — the migration must never be reachable from the internet.
 */
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

    $exists = (bool)$db->query("SHOW TABLES LIKE 'project_notices'")->fetch();
    if ($exists) {
        $log[] = ['ok', 'Table `project_notices` already exists.'];
    } else {
        $db->exec("CREATE TABLE `project_notices` (
            `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id`     INT UNSIGNED DEFAULT NULL,
            `title`          VARCHAR(200) NOT NULL,
            `body`           TEXT,
            `source_url`     VARCHAR(500) DEFAULT NULL,
            `attachment_url` VARCHAR(500) DEFAULT NULL,
            `severity`       ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
            `starts_at`      DATETIME DEFAULT NULL,
            `ends_at`        DATETIME DEFAULT NULL,
            `is_published`   TINYINT(1) NOT NULL DEFAULT 0,
            `created_by`     INT UNSIGNED DEFAULT NULL,
            `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`     DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_published_window` (`is_published`, `starts_at`, `ends_at`),
            INDEX `idx_project`          (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $log[] = ['ok', 'Created `project_notices` table.'];
    }

    $log[] = ['done', 'Migration 011 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 011 — project_notices</title>
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
    <h3 class="mb-3">Migration 011 — <code>project_notices</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
    <p class="mt-3 mb-0 fs-13 text-muted">
      Next: open <code>/admin/notices.php</code> to publish a notice — it will appear on <code>/notices.php</code> and auto-popup on the homepage.
    </p>
  </div>
</body>
</html>
