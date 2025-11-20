<?php
/**
 * Security Functions
 * Input validation, sanitization, encryption, and security utilities
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // Remove whitespace
    $data = trim($data);
    
    // Remove HTML tags
    $data = strip_tags($data);
    
    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
    return preg_match($pattern, $password);
}

/**
 * Hash password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Encrypt data
 */
function encryptData($data, $key = null) {
    if ($key === null) {
        $key = ENCRYPTION_KEY;
    }
    
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, $key, 0, $iv);
    
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypt data
 */
function decryptData($data, $key = null) {
    if ($key === null) {
        $key = ENCRYPTION_KEY;
    }
    
    $data = base64_decode($data);
    list($encrypted_data, $iv) = explode('::', $data, 2);
    
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Prevent SQL Injection - use prepared statements (already implemented in database.php)
 * This function validates that data doesn't contain dangerous SQL patterns
 */
function validateSQLInput($input) {
    $dangerous = [';', '--', '/*', '*/', 'xp_', 'sp_', 'exec', 'union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter'];
    $input_lower = strtolower($input);
    
    foreach ($dangerous as $pattern) {
        if (strpos($input_lower, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Prevent XSS - sanitize output
 */
function escapeOutput($data) {
    if (is_array($data)) {
        return array_map('escapeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipkeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipkeys as $keyword) {
        if (array_key_exists($keyword, $_SERVER) && !empty($_SERVER[$keyword])) {
            $ip = $_SERVER[$keyword];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

/**
 * Log security event
 */
function logSecurityEvent($eventType, $description, $severity = 'medium', $userId = null) {
    require_once __DIR__ . '/../config/database.php';
    
    $conn = getDBConnection();
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $query = "INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, description, severity) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssss', $userId, $eventType, $ip, $userAgent, $description, $severity);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    // Also log to file
    $logMessage = date('Y-m-d H:i:s') . " - [$severity] $eventType: $description (User: $userId, IP: $ip)\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/security.log');
}

/**
 * Check if account is locked
 */
function isAccountLocked($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $conn = getDBConnection();
    $query = "SELECT account_locked_until FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $locked = false;
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
            $locked = true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $locked;
}

/**
 * Lock account
 */
function lockAccount($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
    $conn = getDBConnection();
    $query = "UPDATE users SET account_locked_until = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $lockUntil, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    logSecurityEvent('account_locked', "Account locked due to failed login attempts", 'high', $userId);
}

/**
 * Increment failed login attempts
 */
function incrementFailedAttempts($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $conn = getDBConnection();
    $query = "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    // Check if should lock
    $conn = getDBConnection();
    $query = "SELECT failed_login_attempts FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($user['failed_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        lockAccount($userId);
    }
}

/**
 * Reset failed login attempts
 */
function resetFailedAttempts($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $conn = getDBConnection();
    $query = "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

?>

