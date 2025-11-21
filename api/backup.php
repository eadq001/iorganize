<?php
// Backup API - Download user data as JSON

require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Create backup and download
if ($action === 'create') {
    try {
        // Collect all user data
        $conn = getDBConnection();
        
        $backupData = [
            'version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'calendar_events' => [],
            'diary_entries' => [],
            'sticky_notes' => []
        ];
        
        // Get calendar events
        $query = "SELECT * FROM calendar_events WHERE user_id = ? ORDER BY event_date DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $backupData['calendar_events'][] = $row;
        }
        $stmt->close();
        
        // Get diary entries
        $query = "SELECT * FROM diary_entries WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $backupData['diary_entries'][] = $row;
        }
        $stmt->close();
        
        // Get sticky notes
        $query = "SELECT * FROM sticky_notes WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $backupData['sticky_notes'][] = $row;
        }
        $stmt->close();
        
        $conn->close();
        
        // Generate JSON content
        $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // Log the backup
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('backup_created', "Backup created with " . count($backupData['calendar_events']) . " calendar events, " . count($backupData['diary_entries']) . " diary entries, " . count($backupData['sticky_notes']) . " sticky notes", 'low', $userId);
        }
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Generate filename
        $filename = 'backup_user_' . $userId . '_' . date('Y-m-d_His') . '.json';
        
        // Send headers
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($jsonContent));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output the file
        echo $jsonContent;
        exit;
    } catch (Exception $e) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create backup: ' . $e->getMessage()]);
        exit;
    }
}

// Import backup
if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['backup_file'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['backup_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            throw new Exception('File is too large (max 10MB)');
        }
        
        if ($file['type'] !== 'application/json') {
            throw new Exception('Invalid file type. Only JSON files are allowed');
        }
        
        // Read and validate JSON
        $jsonContent = file_get_contents($file['tmp_name']);
        $backupData = json_decode($jsonContent, true);
        
        if (!$backupData || !is_array($backupData)) {
            throw new Exception('Invalid backup file format');
        }
        
        if (!isset($backupData['calendar_events'], $backupData['diary_entries'], $backupData['sticky_notes'])) {
            throw new Exception('Backup file is missing required data');
        }
        
        $conn = getDBConnection();
        $merge = isset($_POST['merge']) && $_POST['merge'] === '1';
        
        // Clear existing data if not merging
        if (!$merge) {
            $conn->query("DELETE FROM calendar_events WHERE user_id = $userId");
            $conn->query("DELETE FROM diary_entries WHERE user_id = $userId");
            $conn->query("DELETE FROM sticky_notes WHERE user_id = $userId");
        }
        
        $importedCount = 0;
        
        // Import calendar events
        foreach ($backupData['calendar_events'] as $event) {
            $title = $conn->real_escape_string($event['title']);
            $description = $conn->real_escape_string($event['description'] ?? '');
            $eventDate = $event['event_date'] ?? $event['date'] ?? date('Y-m-d');
            $eventTime = $event['event_time'] ?? $event['time'] ?? null;
            $category = $event['category'] ?? 'personal';
            $color = $event['color'] ?? '#3498db';
            
            // Check if event already exists (to avoid duplicates when merging)
            if ($merge) {
                $checkQuery = "SELECT id FROM calendar_events WHERE user_id = $userId AND title = '$title' AND event_date = '$eventDate'";
                $checkResult = $conn->query($checkQuery);
                if ($checkResult && $checkResult->num_rows > 0) {
                    continue; // Skip duplicate
                }
            }
            
            $eventTimeValue = $eventTime ? "'$eventTime'" : "NULL";
            $query = "INSERT INTO calendar_events (user_id, title, description, event_date, event_time, category, color) 
                      VALUES ($userId, '$title', '$description', '$eventDate', $eventTimeValue, '$category', '$color')";
            $conn->query($query);
            $importedCount++;
        }
        
        // Import diary entries
        foreach ($backupData['diary_entries'] as $entry) {
            $title = $conn->real_escape_string($entry['title']);
            $content = $conn->real_escape_string($entry['content'] ?? '');
            $entry_date = $entry['entry_date'] ?? date('Y-m-d');
            $mood = $entry['mood'] ?? null;
            
            // Check if entry already exists (to avoid duplicates when merging)
            if ($merge) {
                $checkQuery = "SELECT id FROM diary_entries WHERE user_id = $userId AND title = '$title' AND entry_date = '$entry_date'";
                $checkResult = $conn->query($checkQuery);
                if ($checkResult && $checkResult->num_rows > 0) {
                    continue; // Skip duplicate
                }
            }
            
            // Only use columns that exist in the database
            $query = "INSERT INTO diary_entries (user_id, title, content, entry_date, mood) 
                      VALUES ($userId, '$title', '$content', '$entry_date', " . ($mood ? "'$mood'" : "NULL") . ")";
            $conn->query($query);
            $importedCount++;
        }
        
        // Import sticky notes
        foreach ($backupData['sticky_notes'] as $note) {
            $title = $conn->real_escape_string($note['title']);
            $content = $conn->real_escape_string($note['content']);
            $color = $note['color'] ?? '#ffeb3b';
            
            // Check if note already exists (to avoid duplicates when merging)
            if ($merge) {
                $checkQuery = "SELECT id FROM sticky_notes WHERE user_id = $userId AND title = '$title' AND content = '$content'";
                $checkResult = $conn->query($checkQuery);
                if ($checkResult && $checkResult->num_rows > 0) {
                    continue; // Skip duplicate
                }
            }
            
            $query = "INSERT INTO sticky_notes (user_id, title, content, color) 
                      VALUES ($userId, '$title', '$content', '$color')";
            $conn->query($query);
            $importedCount++;
        }
        
        $conn->close();
        
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('backup_imported', "Imported backup with $importedCount items. Merge: " . ($merge ? 'yes' : 'no'), 'medium', $userId);
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully imported $importedCount items",
            'items_count' => $importedCount
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get backup history
if ($action === 'history' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $backupDir = __DIR__ . '/../backups';
        $backups = [];
        
        if (file_exists($backupDir)) {
            $files = array_reverse(scandir($backupDir));
            foreach ($files as $file) {
                if (strpos($file, "backup_user_$userId") === 0) {
                    $filepath = $backupDir . '/' . $file;
                    $backups[] = [
                        'filename' => $file,
                        'created_at' => filemtime($filepath),
                        'size' => filesize($filepath)
                    ];
                }
            }
        }
        
        echo json_encode(['success' => true, 'backups' => $backups]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
