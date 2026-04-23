<?php
/**
 * Al-Riaz Associates — Admin Logout
 */
require_once __DIR__ . '/../config/config.php';

// Log logout before clearing session
if (!empty($_SESSION['admin_id'])) {
    try {
        require_once __DIR__ . '/../includes/db.php';
        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO audit_log (admin_id, admin_name, action, entity, entity_id, detail, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $_SESSION['admin_id'],
            $_SESSION['admin_name'] ?? '',
            'logout',
            'users',
            $_SESSION['admin_id'],
            'Logged out',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log('[Logout audit] ' . $e->getMessage());
    }
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: ' . BASE_PATH . '/admin/login.php');
exit;
