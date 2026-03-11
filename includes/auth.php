<?php
/**
 * Avaritia — Authentication & Session Management
 */

session_start();

require_once __DIR__ . '/db.php';

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Attempt to log in a user
 */
function loginUser(string $username, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id, username, password_hash, role, is_active FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        if (!$user['is_active']) {
            return false;
        }
        
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;
        
        session_regenerate_id(true);
        return true;
    }
    
    return false;
}

/**
 * Log out the current user
 */
function logoutUser(): void {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if the current user is an admin
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get the current user's ID
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's username
 */
function getCurrentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

/**
 * Require the user to be logged in (redirect to login if not)
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * Require the user to be an admin (redirect if not)
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        header("Location: /index.php");
        exit;
    }
}

/**
 * Get current user's full profile
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}
