<?php
require_once 'config/config.php';
requireLogin();

$userId = getCurrentUserId();
$success = '';
$error = '';

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
        
        <div class="settings-container">
            <!-- Backup & Restore -->
            <div class="settings-card">
                <h3>Backup & Restore</h3>
                <p>Create a backup of your data for safekeeping.</p>
                <button class="btn btn-primary" onclick="window.location.href='settings.php?action=backup'">Create Backup</button>
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
            
            <!-- Account Information -->
            <div class="settings-card">
                <h3>Account Information</h3>
                <p><strong>Username:</strong> <?php echo escapeOutput(getCurrentUsername()); ?></p>
                <p><strong>Email:</strong> <?php echo escapeOutput($_SESSION['email'] ?? 'N/A'); ?></p>
            </div>
            
            <!-- Security Information -->
            <div class="settings-card">
                <h3>Security Features</h3>
                <ul>
                    <li>✓ Password encryption with bcrypt</li>
                    <li>✓ Account lockout after failed attempts</li>
                    <li>✓ Encrypted diary entries</li>
                    <li>✓ Input validation and sanitization</li>
                    <li>✓ CSRF protection</li>
                    <li>✓ Security logging</li>
                    <li>✓ Session management</li>
                </ul>
            </div>
        </div>
    </main>
    
    <script src="assets/js/settings.js"></script>
</body>
</html>

