<?php
require_once 'config/config.php';
requireLogin();

$userId = getCurrentUserId();
$success = '';
$error = '';

// Handle account update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
        logSecurityEvent('csrf_failed', 'CSRF token validation failed in settings', 'medium', $userId);
    } elseif ($action === 'update_username') {
        $new_username = sanitizeInput($_POST['new_username'] ?? '');
        
        if (empty($new_username)) {
            $error = 'Username cannot be empty.';
        } elseif (strlen($new_username) < 3 || strlen($new_username) > 50) {
            $error = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $error = 'Username can only contain letters, numbers, and underscores.';
        } else {
            $conn = getDBConnection();
            
            // Check if username already exists
            $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param('si', $new_username, $userId);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'Username already exists. Please choose a different one.';
                $check_stmt->close();
            } else {
                $check_stmt->close();
                
                // Update username
                $update_query = "UPDATE users SET username = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('si', $new_username, $userId);
                
                if ($update_stmt->execute()) {
                    $_SESSION['username'] = $new_username;
                    $success = 'Username updated successfully!';
                    logSecurityEvent('username_changed', 'User updated their username', 'low', $userId);
                } else {
                    $error = 'Failed to update username. Please try again.';
                }
                $update_stmt->close();
            }
            $conn->close();
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (!validatePassword($new_password)) {
            $error = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
        } else {
            $conn = getDBConnection();
            
            // Get current password hash
            $query = "SELECT password_hash FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Verify current password
            if (!verifyPassword($current_password, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
                logSecurityEvent('password_change_failed', 'Failed password verification attempt', 'medium', $userId);
            } else {
                // Update password
                $new_password_hash = hashPassword($new_password);
                $update_query = "UPDATE users SET password_hash = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('si', $new_password_hash, $userId);
                
                if ($update_stmt->execute()) {
                    $success = 'Password updated successfully!';
                    logSecurityEvent('password_changed', 'User updated their password', 'low', $userId);
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
                $update_stmt->close();
            }
            $conn->close();
        }
    }
}

// Handle backup request
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    try {
        createBackup($userId, 'manual');
        $success = 'Backup created successfully!';
        logSecurityEvent('backup_requested', 'User requested manual backup', 'low', $userId);
    } catch (Exception $e) {
        $error = 'Failed to create backup: ' . $e->getMessage();
    }
}

// Get security logs
$conn = getDBConnection();
$query = "SELECT event_type, description, severity, created_at, ip_address 
          FROM security_logs 
          WHERE user_id = ?
          ORDER BY created_at DESC
          LIMIT 50";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$securityLogs = [];
while ($row = $result->fetch_assoc()) {
    $securityLogs[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo"><?php echo SITE_NAME; ?></h1>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="calendar.php" class="nav-link">Calendar</a>
                <a href="diary.php" class="nav-link">Diary</a>
                <a href="settings.php" class="nav-link active">Settings</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo escapeOutput(getCurrentUsername()); ?></span>
            </div>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="settings-header">
            <h2>Settings</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <!-- Account Information -->
        <div class="settings-card">
            <h3>Account Information</h3>
            
            <!-- Update Username -->
            <div class="settings-section">
                <h4>Update Username</h4>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_username">
                    
                    <div class="form-group">
                        <label for="current_username">Current Username</label>
                        <input type="text" id="current_username" value="<?php echo escapeOutput(getCurrentUsername()); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_username">New Username</label>
                        <input type="text" id="new_username" name="new_username" required 
                               pattern="[a-zA-Z0-9_]{3,50}" 
                               title="3-50 characters, letters, numbers, and underscores only"
                               placeholder="Enter new username">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Username</button>
                </form>
            </div>
            
            <!-- Update Password -->
            <div class="settings-section">
                <h4>Update Password</h4>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$"
                               title="At least 8 characters with uppercase, lowercase, and number">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
            
            <!-- Display Email -->
            <div class="settings-section">
                <h4>Account Email</h4>
                <p><strong>Email:</strong> <?php echo escapeOutput($_SESSION['email'] ?? 'N/A'); ?></p>
            </div>
        </div>
        
        <!-- Security Logs -->
        <div class="settings-card">
            <h3>Security Logs</h3>
            <p>View your account security activity and login attempts.</p>
            <div class="security-logs">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>Severity</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($securityLogs)): ?>
                            <tr>
                                <td colspan="5">No security logs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($securityLogs as $log): ?>
                                <tr class="severity-<?php echo $log['severity']; ?>">
                                    <td><?php echo formatDateTime($log['created_at']); ?></td>
                                    <td><?php echo escapeOutput($log['event_type']); ?></td>
                                    <td><?php echo escapeOutput($log['description']); ?></td>
                                    <td><span class="badge badge-<?php echo $log['severity']; ?>"><?php echo ucfirst($log['severity']); ?></span></td>
                                    <td><?php echo escapeOutput($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
            
            <!-- Security Information -->
            <div class="settings-card">
                <h3>Security Features</h3>
                <ul>
                    <li>✓ Password encryption with bcrypt</li>
                    <li>✓ Account lockout after failed attempts</li>
                    <li>✓ Encrypted diary entries</li>
                    <li>✓ Input validation and sanitization</li>
                    <li>✓ Security logging</li>
                    <li>✓ Session management</li>
                </ul>
            </div>
        </div>
    </main>
    
    <script src="assets/js/settings.js"></script>
</body>
</html>

