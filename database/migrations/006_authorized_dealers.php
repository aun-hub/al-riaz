<?php
/**
 * Al-Riaz Associates — Migration 006: Create `authorized_dealers` table
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/006_authorized_dealers.php
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

    $tableExists = (bool)$db->query("SHOW TABLES LIKE 'authorized_dealers'")->fetch();
    if ($tableExists) {
        $log[] = ['ok', 'Table `authorized_dealers` already exists — nothing to do.'];
    } else {
        $db->exec("CREATE TABLE `authorized_dealers` (
            `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`          VARCHAR(200) NOT NULL,
            `logo_url`      VARCHAR(500) NOT NULL DEFAULT '',
            `website_url`   VARCHAR(500) NOT NULL DEFAULT '',
            `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_published`  TINYINT(1) NOT NULL DEFAULT 1,
            `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_published` (`is_published`),
            INDEX `idx_sort`      (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $log[] = ['ok', 'Created `authorized_dealers` table.'];
    }

    $uploadDir = __DIR__ . '/../../assets/uploads/dealers/';
    if (!is_dir($uploadDir)) {
        if (@mkdir($uploadDir, 0755, true)) {
            $log[] = ['ok', 'Created upload directory: /assets/uploads/dealers/'];
        } else {
            $log[] = ['error', 'Could not create /assets/uploads/dealers/ — please create it manually.'];
        }
    } else {
        $log[] = ['ok', 'Upload directory already exists: /assets/uploads/dealers/'];
    }

    $log[] = ['done', 'Migration 006 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 006 — authorized_dealers</title>
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
    <h3 class="mb-3">Migration 006 — <code>authorized_dealers</code></h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
