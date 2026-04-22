<?php
/**
 * Al-Riaz Associates — Inquiries Endpoint
 * POST /api/v1/inquiries.php
 *
 * Accepts lead submissions from property/project detail pages and the
 * general contact form. Includes honeypot, rate limiting, and validation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

// ── CORS ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ── Parse body (support both form-data and JSON body) ─────────────────────────
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
} else {
    $input = $_POST;
}

// ── Honeypot: if 'website' field is filled, silently discard ──────────────────
if (!empty($input['website'])) {
    // Return fake success so bots don't know they were caught
    echo json_encode(['success' => true, 'message' => 'Inquiry submitted successfully.']);
    exit;
}

// ── Collect & sanitize raw fields ─────────────────────────────────────────────
$name                = trim($input['name']                  ?? '');
$phone               = trim($input['phone']                 ?? '');
$email               = trim($input['email']                 ?? '');
$message             = trim($input['message']               ?? '');
$preferredContactTime= trim($input['preferred_contact_time']?? '');
$source              = trim($input['source']                ?? 'website');
$propertyId          = !empty($input['property_id']) ? (int)$input['property_id'] : null;
$projectId           = !empty($input['project_id'])  ? (int)$input['project_id']  : null;

$ip        = $_SERVER['REMOTE_ADDR']     ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// ── Validation ────────────────────────────────────────────────────────────────
$errors = [];

// Name: 2–80 characters
if ($name === '') {
    $errors['name'] = 'Name is required.';
} elseif (mb_strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters.';
} elseif (mb_strlen($name) > 80) {
    $errors['name'] = 'Name must not exceed 80 characters.';
}

// Phone: Pakistan format (+92 or 0 followed by 3xx xxxxxxx)
if ($phone === '') {
    $errors['phone'] = 'Phone number is required.';
} elseif (!preg_match('/^(\+92|0)3[0-9]{9}$/', $phone)) {
    $errors['phone'] = 'Please enter a valid Pakistani mobile number (e.g. 0300 1234567 or +923001234567).';
}

// Email: required, valid format
if ($email === '') {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
} elseif (mb_strlen($email) > 160) {
    $errors['email'] = 'Email address is too long.';
}

// Message: optional but max 1000 chars if provided
if ($message !== '' && mb_strlen($message) > 1000) {
    $errors['message'] = 'Message must not exceed 1,000 characters.';
}

// Sanitize source
$allowedSources = ['website', 'whatsapp', 'facebook', 'instagram', 'referral', 'other'];
if (!in_array($source, $allowedSources, true)) {
    $source = 'website';
}

// Sanitize preferred_contact_time
$allowedTimes = ['Morning (9am - 12pm)', 'Afternoon (12pm - 4pm)', 'Evening (5pm - 8pm)', 'Anytime', 'Business Hours', ''];
if (!in_array($preferredContactTime, $allowedTimes, true)) {
    $preferredContactTime = '';
}

// Return early if validation failed
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Rate Limiting ─────────────────────────────────────────────────────────────
// Max 5 submissions from the same IP within the last 10 minutes
try {
    $pdo = db();

    $rateStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM inquiries
        WHERE ip_address = :ip
          AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $rateStmt->execute([':ip' => $ip]);
    $recentCount = (int)$rateStmt->fetchColumn();

    if ($recentCount >= 5) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many submissions. Please wait a few minutes and try again.',
        ]);
        exit;
    }
} catch (RuntimeException $e) {
    // DB unavailable — fail gracefully
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable. Please try again.']);
    exit;
}

// ── Validate that referenced property/project exist ───────────────────────────
if ($propertyId !== null) {
    $chk = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND is_published = 1 LIMIT 1");
    $chk->execute([$propertyId]);
    if (!$chk->fetch()) {
        $propertyId = null; // Orphan reference — store anyway without FK
    }
}

if ($projectId !== null) {
    $chk = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND is_published = 1 LIMIT 1");
    $chk->execute([$projectId]);
    if (!$chk->fetch()) {
        $projectId = null;
    }
}

// ── Insert Inquiry ────────────────────────────────────────────────────────────
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO inquiries
            (property_id, project_id, name, phone, email,
             preferred_contact_time, message, source, status,
             ip_address, user_agent)
        VALUES
            (:property_id, :project_id, :name, :phone, :email,
             :preferred_contact_time, :message, :source, 'new',
             :ip_address, :user_agent)
    ");

    $insertStmt->execute([
        ':property_id'           => $propertyId,
        ':project_id'            => $projectId,
        ':name'                  => $name,
        ':phone'                 => $phone,
        ':email'                 => $email,
        ':preferred_contact_time'=> $preferredContactTime ?: null,
        ':message'               => $message ?: null,
        ':source'                => $source,
        ':ip_address'            => $ip,
        ':user_agent'            => mb_substr($userAgent, 0, 512),
    ]);

    $inquiryId = (int)$pdo->lastInsertId();

    // ── Optional: log to error_log for immediate visibility ───────────────────
    error_log(sprintf(
        '[Inquiry #%d] %s (%s) — %s',
        $inquiryId,
        $name,
        $phone,
        $propertyId ? "Property #$propertyId" : ($projectId ? "Project #$projectId" : 'General')
    ));

    echo json_encode([
        'success'    => true,
        'message'    => 'Thank you! Your inquiry has been submitted. Our team will contact you shortly.',
        'inquiry_id' => $inquiryId,
    ]);

} catch (PDOException $e) {
    error_log('[Inquiry] Insert failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit inquiry. Please try again or contact us directly.',
    ]);
}
