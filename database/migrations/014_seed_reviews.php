<?php
/**
 * Al-Riaz Associates — Seeder 014: Sample approved reviews
 *
 * Inserts a handful of realistic-looking approved reviews so the homepage
 * marquee + about-page grid have content to display before real submissions
 * come in. Idempotent — skips reviews whose email is already in the table.
 *
 * Run once:
 *   - Browser (localhost): http://localhost/al-riaz/database/migrations/014_seed_reviews.php
 *   - SSH (server):        php /path/to/site/database/migrations/014_seed_reviews.php
 *
 * To remove the seeded rows later:
 *   DELETE FROM reviews WHERE email LIKE '%@seed.local';
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (PHP_SAPI !== 'cli' && !isLocalRequest($ip)) {
    http_response_code(403);
    die('Access denied. Detected REMOTE_ADDR: ' . htmlspecialchars($ip)
        . '. Run this seeder from localhost or a private LAN address only.');
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

$reviews = [
    [
        'name' => 'Hassan Ahmed', 'email' => 'hassan.ahmed@seed.local', 'rating' => 5,
        'title' => 'Smooth purchase from start to finish',
        'body'  => "Booked a 10-marla plot in Bahria Phase 8 through Al-Riaz. The NOC check, transfer paperwork and possession handover all happened without a single hiccup. Their agent answered every question — even the small ones — over WhatsApp on weekends. Easily the most professional real-estate experience I have had in Pakistan.",
        'featured' => 1,
    ],
    [
        'name' => 'Ayesha Khan', 'email' => 'ayesha.khan@seed.local', 'rating' => 5,
        'title' => 'Trustworthy team',
        'body'  => "We were nervous about buying a house remotely from the UK. Al-Riaz did the site visit on a video call, shared every original document and walked us through the price negotiation. The keys were handed to my brother on the exact date promised.",
        'featured' => 1,
    ],
    [
        'name' => 'Omar Malik', 'email' => 'omar.malik@seed.local', 'rating' => 5,
        'title' => 'Sharp market reads',
        'body'  => "I have invested in three of their authorised projects over the last two years. Their market reads are sharp, the WhatsApp updates keep me in the loop without the noise, and they have never tried to push a property that didn't match my budget.",
        'featured' => 0,
    ],
    [
        'name' => 'Sana Rafiq', 'email' => 'sana.rafiq@seed.local', 'rating' => 5,
        'title' => 'Found my apartment in a week',
        'body'  => "Found a great 2-bed apartment in F-11 in under a week. The agent actually listened — instead of dragging me through random places, he sent three options that fit my brief, and the second one was perfect. Lease paperwork was clean too.",
        'featured' => 0,
    ],
    [
        'name' => 'Bilal Saeed', 'email' => 'bilal.saeed@seed.local', 'rating' => 4,
        'title' => 'Helpful and patient',
        'body'  => "Spent almost three months deciding between Capital Smart City and DHA Phase 2. The Al-Riaz consultant patiently walked me through both — payment plans, possession timelines, expected returns. No pressure tactics. Ended up choosing CSC and I have not regretted it.",
        'featured' => 0,
    ],
    [
        'name' => 'Maryam Sheikh', 'email' => 'maryam.sheikh@seed.local', 'rating' => 5,
        'title' => 'Honest pricing',
        'body'  => "What stood out was the transparent pricing. The brokerage was disclosed up front and matched what we signed at closing — no surprise fees, no last-minute revisions. Refreshing in this market. Will recommend to my family.",
        'featured' => 1,
    ],
    [
        'name' => 'Adeel Raza', 'email' => 'adeel.raza@seed.local', 'rating' => 5,
        'title' => 'Great service for an investor',
        'body'  => "Bought two shops in Blue Area through Al-Riaz this year. The legal due diligence on the building was thorough — they flagged a tenancy issue on one shop that I would have missed completely. Saved me a lot of hassle. Strongly recommended.",
        'featured' => 0,
    ],
    [
        'name' => 'Fatima Siddiqui', 'email' => 'fatima.siddiqui@seed.local', 'rating' => 5,
        'title' => 'Professional and quick',
        'body'  => "Listed our family home with Al-Riaz after a poor experience elsewhere. They had three serious buyers visit within the first 10 days and closed at our asking price within six weeks. Professional photographs, honest negotiation. Couldn't ask for more.",
        'featured' => 0,
    ],
];

$log = [];
try {
    $db = Database::getInstance();

    $existsCheck = $db->prepare("SELECT COUNT(*) FROM reviews WHERE email = ?");
    $insert = $db->prepare(
        "INSERT INTO reviews (name, email, rating, title, body, status, is_featured, reviewed_at, created_at)
         VALUES (?, ?, ?, ?, ?, 'approved', ?, NOW(), ?)"
    );

    $inserted = 0; $skipped = 0;
    foreach ($reviews as $i => $r) {
        $existsCheck->execute([$r['email']]);
        if ((int)$existsCheck->fetchColumn() > 0) {
            $skipped++;
            continue;
        }
        // Spread created_at across the last ~6 months so the ORDER BY shuffles naturally.
        $daysAgo = ($i + 1) * 18;
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
        $insert->execute([
            $r['name'], $r['email'], $r['rating'], $r['title'], $r['body'], $r['featured'], $createdAt,
        ]);
        $inserted++;
    }

    $log[] = ['ok', "Seeded reviews inserted: {$inserted}, already-present: {$skipped}."];
    $log[] = ['done', 'Seeder 014 complete.'];
} catch (Throwable $e) {
    $log[] = ['error', 'Seeder failed: ' . $e->getMessage()];
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
<title>Seeder 014 — sample reviews</title>
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
    <h3 class="mb-3">Seeder 014 — sample reviews</h3>
    <?php foreach ($log as [$status, $msg]): ?>
      <div class="log-row <?= htmlspecialchars($status) ?>">
        <span><?= $status === 'ok' ? '&#10003;' : ($status === 'error' ? '&#10007;' : '&#10004;') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endforeach; ?>
    <p class="mt-3 mb-0 fs-13 text-muted">
      To remove these later: <code>DELETE FROM reviews WHERE email LIKE '%@seed.local';</code>
    </p>
  </div>
</body>
</html>
