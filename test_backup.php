<?php
// Test backup API directly
require_once 'config/config.php';

// Simulate a user
$_SESSION['user_id'] = 1; // Replace with actual user ID
$_SESSION['username'] = 'test';
$_SESSION['email'] = 'test@example.com';

$userId = 1;

echo "Testing backup API for user $userId\n";

try {
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
    $query = "SELECT * FROM calendar_events WHERE user_id = ? ORDER BY date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "Calendar events: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        $backupData['calendar_events'][] = $row;
    }
    $stmt->close();
    
    // Get diary entries
    $query = "SELECT * FROM diary_entries WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "Diary entries: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        $backupData['diary_entries'][] = $row;
    }
    $stmt->close();
    
    // Get sticky notes
    $query = "SELECT * FROM sticky_notes WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "Sticky notes: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        $backupData['sticky_notes'][] = $row;
    }
    $stmt->close();
    
    $conn->close();
    
    // Generate JSON content
    $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "JSON size: " . strlen($jsonContent) . " bytes\n";
    echo "Success! Backup data structure:\n";
    echo "- Calendar events: " . count($backupData['calendar_events']) . "\n";
    echo "- Diary entries: " . count($backupData['diary_entries']) . "\n";
    echo "- Sticky notes: " . count($backupData['sticky_notes']) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
