<?php
/**
 * Al-Riaz Associates — Migration 010: Create `reviews` table
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/010_reviews.php
 *
 * Idempotent. Stores client experience reviews submitted from the public
 * site. New rows default to status='pending' and require admin approval
 * before they appear publicly.
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

    // FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE returns false for
    // loopback / private / link-local / reserved ranges — i.e. exactly the
    // ones we want to allow.
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

    $exists = (bool)$db->query("SHOW TABLES LIKE 'reviews'")->fetch();
    if ($exists) {
        $log[] = ['ok', 'Table `reviews` already exists.'];
    } else {
        $db->exec("CREATE TABLE `reviews` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`         VARCHAR(120) NOT NULL,
            `email`        VARCHAR(160) DEFAULT NULL,
            `rating`       TINYINT UNSIGNED NOT NULL,
            `title`        VARCHAR(160) DEFAULT NULL,
            `body`         TEXT NOT NULL,
            `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `is_featured`  TINYINT(1) NOT NULL DEFAULT 0,
            `ip_address`   VARCHAR(45) DEFAULT NULL,
            `user_agent`   VARCHAR(512) DEFAULT NULL,
            `reviewed_by`  INT UNSIGNED DEFAULT NULL,
            `reviewed_at`  DATETIME DEFAULT NULL,
            `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`   DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_status`     (`status`),
            INDEX `idx_featured`   (`is_featured`),
            INDEX `idx_created`    (`created_at`),
            CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $log[] = ['ok', 'Created `reviews` table.'];
    }

    $log[] = ['done', 'Migration 010 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 010 — reviews</title>
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
    <h3 class="mb-3">Migration 010 — <code>reviews</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
    <p class="mt-3 mb-0 fs-13 text-muted">
      Next: open <code>/admin/reviews.php</code> to moderate submissions.
    </p>
  </div>
</body>
</html>
