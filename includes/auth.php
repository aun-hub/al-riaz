<?php
/**
 * Al-Riaz Associates — Authentication & Authorization Helpers
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Redirect to login if not authenticated.
 */
if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}

/**
 * Check if the current admin has a given role or higher.
 * Role hierarchy: agent < admin < super_admin
 */
if (!function_exists('hasRole')) {
    function hasRole(string $required): bool
    {
        $hierarchy = ['agent' => 1, 'admin' => 2, 'super_admin' => 3];
        $current   = $_SESSION['admin_role'] ?? 'agent';
        return ($hierarchy[$current] ?? 0) >= ($hierarchy[$required] ?? 99);
    }
}

/**
 * Require a minimum role; redirect to dashboard with error if insufficient.
 */
if (!function_exists('requireRole')) {
    function requireRole(string $required): void
    {
        requireLogin();
        if (!hasRole($required)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Access denied. Insufficient permissions.'];
            header('Location: /admin/index.php');
            exit;
        }
    }
}

/**
 * Generate CSRF token.
 */
if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verify CSRF token from POST.
 */
if (!function_exists('verifyCsrf')) {
    function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('CSRF token mismatch. Please go back and try again.');
        }
    }
}

/**
 * Set a flash message.
 */
if (!function_exists('setFlash')) {
    function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}

/**
 * Get and clear flash message.
 */
if (!function_exists('getFlash')) {
    function getFlash(): ?array
    {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

/**
 * Log admin action to audit_log table.
 */
if (!function_exists('auditLog')) {
    function auditLog(string $action, string $entity, int $entityId = 0, string $detail = ''): void
    {
        try {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
            $adminId   = $_SESSION['admin_id'] ?? 0;
            $adminName = $_SESSION['admin_name'] ?? 'System';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt = $db->prepare(
                'INSERT INTO audit_log (admin_id, admin_name, action, entity, entity_id, detail, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$adminId, $adminName, $action, $entity, $entityId, $detail, $ip]);
        } catch (Exception $e) {
            error_log('[AuditLog] ' . $e->getMessage());
        }
    }
}

/**
 * Escape output safely.
 */
if (!function_exists('e')) {
    function e(mixed $val): string
    {
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
    }
}
