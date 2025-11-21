<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Registration successful! Please login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    
    // Verify reCAPTCHA
    if (empty($recaptcha_token)) {
        $error = 'Please complete the reCAPTCHA verification.';
        logSecurityEvent('recaptcha_missing', 'reCAPTCHA token missing on login attempt', 'medium');
    } else {
        $recaptcha_secret = '6LcH0hAsAAAAAHQMpu0W5r0mZZI-Ylw3nr43yp4q';
        $recaptcha_verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        
        // Use cURL for better reliability if available, fallback to file_get_contents
        $recaptcha_response = null;
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $recaptcha_verify_url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => http_build_query(['secret' => $recaptcha_secret, 'response' => $recaptcha_token]),
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true
            ));
            $recaptcha_response = curl_exec($curl);
            curl_close($curl);
        } else {
            $recaptcha_response = @file_get_contents($recaptcha_verify_url . '?secret=' . urlencode($recaptcha_secret) . '&response=' . urlencode($recaptcha_token));
        }
        
        $recaptcha_result = json_decode($recaptcha_response, true);
        
        // Log reCAPTCHA result for debugging
        logSecurityEvent('recaptcha_attempt', 'reCAPTCHA response: success=' . ($recaptcha_result['success'] ? 'true' : 'false') . ', score=' . ($recaptcha_result['score'] ?? 'N/A'), 'low');
        
        if (!isset($recaptcha_result['success']) || !$recaptcha_result['success']) {
            $error = 'reCAPTCHA verification failed. Please try again.';
            logSecurityEvent('recaptcha_failed', 'reCAPTCHA verification failed: ' . json_encode($recaptcha_result), 'medium');
        } elseif (!verifyCSRFToken($csrf_token)) {
            $error = 'Invalid security token. Please try again.';
            logSecurityEvent('csrf_failed', 'CSRF token validation failed during login', 'medium');
        } elseif (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            // Check user
            $conn = getDBConnection();
        $query = "SELECT id, username, email, password_hash, failed_login_attempts, account_locked_until, is_active FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Invalid username or password.';
            logSecurityEvent('login_failed', "Failed login attempt for: $username", 'medium');
            // Don't reveal if user exists
        } else {
            $user = $result->fetch_assoc();
            
            // Check if account is locked
            if (isAccountLocked($user['id'])) {
                $lockUntil = strtotime($user['account_locked_until']);
                $remaining = ceil(($lockUntil - time()) / 60);
                $error = "Account locked. Please try again in $remaining minutes.";
                logSecurityEvent('login_blocked', "Login attempt on locked account: $username", 'high', $user['id']);
            } elseif (!$user['is_active']) {
                $error = 'Account is inactive. Please contact administrator.';
                logSecurityEvent('login_blocked', "Login attempt on inactive account: $username", 'high', $user['id']);
            } elseif (!verifyPassword($password, $user['password_hash'])) {
                // Invalid password
                incrementFailedAttempts($user['id']);
                $error = 'Invalid username or password.';
                logSecurityEvent('login_failed', "Failed login attempt for: $username", 'medium', $user['id']);
            } else {
                // Successful login
                resetFailedAttempts($user['id']);
                
                // Update last login
                $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($query);
                $updateStmt->bind_param('i', $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                // Store session in database
                $sessionId = session_id();
                $ip = getClientIP();
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $query = "INSERT INTO sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE last_activity = NOW()";
                $sessionStmt = $conn->prepare($query);
                $sessionStmt->bind_param('siss', $sessionId, $user['id'], $ip, $userAgent);
                $sessionStmt->execute();
                $sessionStmt->close();
                
                logSecurityEvent('login_success', "Successful login: $username", 'low', $user['id']);
                
                $stmt->close();
                $conn->close();
                
                header('Location: dashboard.php');
                exit();
            }
        }
        
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1><?php echo SITE_NAME; ?></h1>
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escapeOutput($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo escapeOutput($_POST['username'] ?? ''); ?>"
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group recaptcha-container">
                    <div class="g-recaptcha" data-sitekey="6LcH0hAsAAAAAF5K3G_IlRndhaA3DB4BIm11oehR" data-theme="light"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <p class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>

