<?php
/**
 * Al-Riaz Associates — Migration 001: Create `branches` table
 *
 * Run once from localhost:
 *   http://localhost/al-riaz/database/migrations/001_branches.php
 *
 * Idempotent:
 *   - Creates `branches` table if missing.
 *   - Imports any branches from config/settings.json on first run only
 *     (subsequent runs skip the import if the table already has rows).
 *   - Removes the `branches` key from settings.json once imported, so the
 *     table becomes the single source of truth.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

// Only allow localhost
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    http_response_code(403);
    die('Access denied. Run this migration from localhost only.');
}

$log = [];

try {
    $db = Database::getInstance();

    // ── 1. Create table ──────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS `branches` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`        VARCHAR(180) NOT NULL DEFAULT '',
            `address`     VARCHAR(300) NOT NULL DEFAULT '',
            `phone`       VARCHAR(40)  NOT NULL DEFAULT '',
            `hours`       VARCHAR(120) NOT NULL DEFAULT '',
            `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = ['ok', 'Table `branches` is ready.'];

    // ── 2. Import from settings.json (only if table is empty) ────
    $existing = (int)$db->query('SELECT COUNT(*) FROM `branches`')->fetchColumn();

    $settingsFile = __DIR__ . '/../../config/settings.json';
    $importedCount = 0;

    if ($existing === 0 && is_readable($settingsFile)) {
        $json = file_get_contents($settingsFile);
        $data = json_decode($json ?: '', true);

        if (is_array($data) && !empty($data['branches']) && is_array($data['branches'])) {
            $stmt = $db->prepare(
                'INSERT INTO `branches` (`name`, `address`, `phone`, `hours`, `sort_order`)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $order = 0;
            foreach ($data['branches'] as $row) {
                if (!is_array($row)) continue;
                $name    = trim((string)($row['name']    ?? ''));
                $address = trim((string)($row['address'] ?? ''));
                $phone   = trim((string)($row['phone']   ?? ''));
                $hours   = trim((string)($row['hours']   ?? ''));
                if ($name === '' && $address === '' && $phone === '' && $hours === '') continue;
                $stmt->execute([$name, $address, $phone, $hours, $order++]);
                $importedCount++;
            }
        }

        // Drop the stale `branches` key from JSON regardless — the table owns this now.
        if (is_array($data) && array_key_exists('branches', $data)) {
            unset($data['branches']);
            file_put_contents($settingsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $log[] = ['ok', 'Removed `branches` key from config/settings.json (table is now the source of truth).'];
        }

        $log[] = ['ok', $importedCount > 0
            ? "Imported $importedCount branch(es) from settings.json."
            : 'No branches in settings.json to import.'];
    } elseif ($existing > 0) {
        $log[] = ['ok', "Skipped import: table already has $existing row(s)."];
    } else {
        $log[] = ['ok', 'Skipped import: no settings.json file found.'];
    }

    $log[] = ['done', 'Migration 001 complete.'];

} catch (Throwable $e) {
    $log[] = ['error', 'Migration failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration 001 — branches</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  body { background:#f4f6f9; font-family:'Segoe UI',sans-serif; padding:3rem 1rem; }
  .card { border:none; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); max-width:720px; margin:0 auto; }
  .log-row { padding:.55rem .9rem; border-radius:6px; margin-bottom:.4rem; font-size:.92rem; display:flex; gap:.6rem; }
  .ok    { background:#d1e7dd; color:#0a3622; }
  .error { background:#f8d7da; color:#58151c; }
  .done  { background:#0A1628; color:#F5B301; font-weight:600; }
  h3 { color:#0A1628; }
</style>
</head>
<body>
  <div class="card p-4">
    <h3 class="mb-3">Migration 001 — <code>branches</code> table</h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span>
          <?= $status === 'ok' ? '✔' : ($status === 'error' ? '✘' : '✓') ?>
        </span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
    <hr>
    <p class="text-muted mb-0" style="font-size:.85rem;">
      This migration is idempotent — running it again will re-check the table and skip re-importing.
      You can safely leave the file in place, but best practice is to remove it from production after success.
    </p>
  </div>
</body>
</html>
