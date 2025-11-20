<?php
/**
 * General Functions
 * Helper functions for the application
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) {
        return '';
    }
    return date($format, strtotime($datetime));
}

/**
 * Get calendar categories
 */
function getCalendarCategories() {
    return [
        'academic' => ['name' => 'Academic', 'color' => '#3498db'],
        'personal' => ['name' => 'Personal', 'color' => '#2ecc71'],
        'work' => ['name' => 'Work', 'color' => '#e74c3c'],
        'health' => ['name' => 'Health', 'color' => '#f39c12'],
        'social' => ['name' => 'Social', 'color' => '#9b59b6'],
        'other' => ['name' => 'Other', 'color' => '#95a5a6']
    ];
}

/**
 * Get mood options
 */
function getMoodOptions() {
    return [
        'happy' => '😊 Happy',
        'sad' => '😢 Sad',
        'excited' => '🤩 Excited',
        'anxious' => '😰 Anxious',
        'calm' => '😌 Calm',
        'angry' => '😠 Angry',
        'grateful' => '🙏 Grateful',
        'tired' => '😴 Tired',
        'motivated' => '💪 Motivated',
        'neutral' => '😐 Neutral'
    ];
}

/**
 * Generate random color for sticky notes
 */
function getRandomColor() {
    $colors = ['#f1c40f', '#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#1abc9c', '#f39c12'];
    return $colors[array_rand($colors)];
}

/**
 * Send JSON response
 */
function sendJSONResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Send error response
 */
function sendErrorResponse($message, $statusCode = 400) {
    sendJSONResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Send success response
 */
function sendSuccessResponse($data = [], $message = 'Success') {
    sendJSONResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * Validate date
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Create backup
 */
function createBackup($userId, $backupType = 'manual') {
    require_once __DIR__ . '/../config/database.php';
    
    $backupDir = __DIR__ . '/../backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = 'backup_' . $userId . '_' . date('Y-m-d_His') . '.sql';
    $filepath = $backupDir . '/' . $filename;
    
    // Export user data
    $conn = getDBConnection();
    
    // Get user data
    $tables = ['calendar_events', 'sticky_notes', 'diary_entries'];
    $sql = "-- Backup for user ID: $userId\n";
    $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $query = "SELECT * FROM $table WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $columns = implode('`, `', array_keys($row));
            $values = array_map(function($val) use ($conn) {
                return "'" . $conn->real_escape_string($val) . "'";
            }, array_values($row));
            $values = implode(', ', $values);
            
            $sql .= "INSERT INTO $table (`$columns`) VALUES ($values);\n";
        }
        $stmt->close();
    }
    
    $conn->close();
    
    file_put_contents($filepath, $sql);
    $fileSize = filesize($filepath);
    
    // Save backup record
    $conn = getDBConnection();
    $query = "INSERT INTO backups (user_id, backup_type, file_path, file_size) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $filepathRelative = 'backups/' . $filename;
    $stmt->bind_param('issi', $userId, $backupType, $filepathRelative, $fileSize);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    logSecurityEvent('backup_created', "Backup created: $filename", 'low', $userId);
    
    return $filepath;
}

?>

