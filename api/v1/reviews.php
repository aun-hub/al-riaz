<?php
/**
 * Al-Riaz Associates — Reviews Endpoint
 * POST /api/v1/reviews.php
 *
 * Accepts client review submissions from the public site. Includes
 * honeypot, rate limiting, and validation. Submissions land with
 * status='pending' and become visible only after admin approval.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

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

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
} else {
    $input = $_POST;
}

// Honeypot: silently discard if filled (bots)
if (!empty($input['website'])) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your review. It will appear after our team reviews it.']);
    exit;
}

$name   = trim((string)($input['name']   ?? ''));
$email  = trim((string)($input['email']  ?? ''));
$rating = (int)($input['rating'] ?? 0);
$title  = trim((string)($input['title']  ?? ''));
$body   = trim((string)($input['body']   ?? $input['message'] ?? ''));

$ip        = $_SERVER['REMOTE_ADDR']     ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$errors = [];

if ($name === '') {
    $errors['name'] = 'Name is required.';
} elseif (mb_strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters.';
} elseif (mb_strlen($name) > 120) {
    $errors['name'] = 'Name must not exceed 120 characters.';
}

// Email is optional. Validate only if supplied.
if ($email !== '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 160) {
        $errors['email'] = 'Email address is too long.';
    }
}

if ($rating < 1 || $rating > 5) {
    $errors['rating'] = 'Please select a rating between 1 and 5 stars.';
}

if ($title !== '' && mb_strlen($title) > 160) {
    $errors['title'] = 'Title must not exceed 160 characters.';
}

if ($body === '') {
    $errors['body'] = 'Please share a few words about your experience.';
} elseif (mb_strlen($body) < 10) {
    $errors['body'] = 'Review must be at least 10 characters.';
} elseif (mb_strlen($body) > 2000) {
    $errors['body'] = 'Review must not exceed 2,000 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Rate limit: max 3 submissions from one IP within 30 minutes
try {
    $pdo = db();
    $rateStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM reviews
        WHERE ip_address = :ip
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $rateStmt->execute([':ip' => $ip]);
    if ((int)$rateStmt->fetchColumn() >= 3) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many submissions. Please wait a while before submitting another review.',
        ]);
        exit;
    }
} catch (RuntimeException $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable. Please try again.']);
    exit;
}

try {
    $insertStmt = $pdo->prepare("
        INSERT INTO reviews
            (name, email, rating, title, body, status, ip_address, user_agent)
        VALUES
            (:name, :email, :rating, :title, :body, 'pending', :ip, :ua)
    ");
    $insertStmt->execute([
        ':name'   => $name,
        ':email'  => $email !== '' ? $email : null,
        ':rating' => $rating,
        ':title'  => $title !== '' ? $title : null,
        ':body'   => $body,
        ':ip'     => $ip,
        ':ua'     => mb_substr($userAgent, 0, 512),
    ]);

    $reviewId = (int)$pdo->lastInsertId();
    error_log(sprintf('[Review #%d] %s — %d stars (pending)', $reviewId, $name, $rating));

    echo json_encode([
        'success'   => true,
        'message'   => 'Thank you for your review! It will appear publicly once our team has reviewed it.',
        'review_id' => $reviewId,
    ]);
} catch (PDOException $e) {
    error_log('[Review] Insert failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit review. Please try again later.',
    ]);
}
