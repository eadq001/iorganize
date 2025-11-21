<?php
require_once 'config/config.php';
require_once 'config/email.php';

$error = '';
$success = '';
$show_verification = false;
$verification_email = '';

// Handle verification code submission
if (isset($_POST['verify_code'])) {
    $verification_email = sanitizeInput($_POST['verification_email'] ?? '');
    $verification_code = sanitizeInput($_POST['verification_code'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
        $show_verification = true;
        logSecurityEvent('csrf_failed', 'CSRF token validation failed during verification', 'medium');
    } elseif (empty($verification_code)) {
        $error = 'Please enter the verification code.';
        $show_verification = true;
    } else {
        $conn = getDBConnection();
        
        // Get verification record
        $query = "SELECT * FROM email_verifications WHERE email = ? AND verified_at IS NULL AND expires_at > NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $verification_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Verification code expired or invalid. Please register again.';
            $show_verification = true;
            logSecurityEvent('verification_expired', "Expired verification attempt for: $verification_email", 'low');
        } else {
            $verification = $result->fetch_assoc();
            
            // Check code attempts
            if ($verification['code_attempts'] >= 5) {
                // Delete expired verification
                $delete_query = "DELETE FROM email_verifications WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param('i', $verification['id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $error = 'Too many failed attempts. Please register again.';
                $show_verification = true;
                logSecurityEvent('verification_failed', "Too many failed attempts for: $verification_email", 'medium');
            } elseif ($verification_code !== $verification['verification_code']) {
                // Increment attempts
                $update_query = "UPDATE email_verifications SET code_attempts = code_attempts + 1 WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('i', $verification['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $remaining = 5 - ($verification['code_attempts'] + 1);
                $error = "Invalid code. You have $remaining attempts remaining.";
                $show_verification = true;
                logSecurityEvent('verification_failed', "Wrong code entered for: $verification_email", 'low');
            } else {
                // Code is correct! Create user account
                $update_query = "UPDATE email_verifications SET verified_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('i', $verification['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Create user
                $user_query = "INSERT INTO users (username, email, password_hash, email_verified) VALUES (?, ?, ?, TRUE)";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param('sss', $verification['username'], $verification['email'], $verification['password_hash']);
                
                if ($user_stmt->execute()) {
                    $userId = $user_stmt->insert_id;
                    $user_stmt->close();
                    
                    // Delete verification record
                    $delete_query = "DELETE FROM email_verifications WHERE id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bind_param('i', $verification['id']);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    
                    // Send welcome email
                    sendWelcomeEmail($verification['email'], $verification['username']);
                    
                    logSecurityEvent('registration_success', "User registered and verified: " . $verification['username'], 'low', $userId);
                    
                    $conn->close();
                    
                    // Redirect to login
                    header('Location: login.php?registered=1');
                    exit();
                } else {
                    $error = 'An error occurred during account creation. Please try again.';
                    $show_verification = true;
                }
            }
        }
        
        $stmt->close();
        $conn->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initial registration form submission
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
        logSecurityEvent('csrf_failed', 'CSRF token validation failed during registration', 'medium');
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email address.';
    } elseif (!validatePassword($password)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists in users table
        $conn = getDBConnection();
        $query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists.';
            $stmt->close();
            $conn->close();
            logSecurityEvent('registration_failed', "Registration attempt with existing credentials: $username", 'low');
        } else {
            $stmt->close();
            
            // Also check email_verifications table for unverified registrations
            $check_query = "SELECT id FROM email_verifications WHERE email = ? AND verified_at IS NULL";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param('s', $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // If unverified registration exists, delete expired ones first
            $verify_query = "DELETE FROM email_verifications WHERE email = ? AND verified_at IS NULL AND expires_at < NOW()";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param('s', $email);
            $verify_stmt->execute();
            $verify_stmt->close();
            
            $check_stmt->close();
            
            // Generate verification code and hash password
            $verification_code = generateVerificationCode();
            $password_hash = hashPassword($password);
            
            // Insert into email_verifications table
            $insert_query = "INSERT INTO email_verifications (email, username, password_hash, verification_code) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('ssss', $email, $username, $password_hash, $verification_code);
            
            try {
                if ($insert_stmt->execute()) {
                    // Send verification code
                    if (sendVerificationCode($email, $verification_code)) {
                        $show_verification = true;
                        $verification_email = $email;
                        $success = 'Verification code sent to your email. Please check your inbox.';
                        logSecurityEvent('registration_started', "Verification code sent to: $email", 'low');
                    } else {
                        $error = 'Failed to send verification code. Please try again.';
                        // Delete the unverified record
                        $delete_query = "DELETE FROM email_verifications WHERE email = ?";
                        $delete_stmt = $conn->prepare($delete_query);
                        $delete_stmt->bind_param('s', $email);
                        $delete_stmt->execute();
                        $delete_stmt->close();
                        logSecurityEvent('email_send_failed', "Failed to send verification code to: $email", 'medium');
                    }
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            } catch (Exception $e) {
                // Check if it's a duplicate entry error
                if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email') !== false) {
                    $error = 'This email is already registered. Please use a different email or try to login.';
                    logSecurityEvent('registration_duplicate', "Registration attempt with existing email: $email", 'low');
                } else {
                    $error = 'Registration failed: ' . $e->getMessage();
                    logSecurityEvent('registration_error', "Registration error for: $email - " . $e->getMessage(), 'medium');
                }
            }
            
            if ($insert_stmt) {
                $insert_stmt->close();
            }
        }
        
        if ($conn) {
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1><?php echo SITE_NAME; ?></h1>
            <h2><?php echo $show_verification ? 'Verify Email' : 'Create Account'; ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escapeOutput($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
            <?php endif; ?>
            
            <?php if ($show_verification): ?>
                <!-- Email Verification Form -->
                <form method="POST" action="" id="verificationForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="verify_code" value="1">
                    <input type="hidden" name="verification_email" value="<?php echo escapeOutput($verification_email); ?>">
                    
                    <div class="form-group">
                        <p style="color: #666; margin-bottom: 1rem; text-align: center;">
                            A verification code has been sent to<br>
                            <strong><?php echo escapeOutput($verification_email); ?></strong>
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label for="verification_code">Verification Code</label>
                        <input type="text" id="verification_code" name="verification_code" required 
                               placeholder="Enter 6-digit code"
                               pattern="[0-9]{6}"
                               maxlength="6"
                               inputmode="numeric"
                               autofocus>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Verify & Create Account</button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Registration Form -->
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo escapeOutput($_POST['username'] ?? ''); ?>"
                               pattern="[a-zA-Z0-9_]{3,50}" 
                               title="3-50 characters, letters, numbers, and underscores only">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo escapeOutput($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$"
                               title="At least 8 characters with uppercase, lowercase, and number">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <p class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>
