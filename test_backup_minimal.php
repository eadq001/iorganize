<?php
// Minimal backup test
session_start();
$_SESSION['user_id'] = 1;

require_once 'config/database.php';

$userId = 1;
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
$query = "SELECT id, user_id, title, description, date, time, category, color FROM calendar_events WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $backupData['calendar_events'][] = $row;
}
$stmt->close();

// Get diary entries
$query = "SELECT id, user_id, title, content, entry_date, mood, is_encrypted FROM diary_entries WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $backupData['diary_entries'][] = $row;
}
$stmt->close();

// Get sticky notes
$query = "SELECT id, user_id, title, content, color FROM sticky_notes WHERE user_id = ?";
$stmt = $conn->prepare($query);
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

echo "Backup data:\n";
echo "- Calendar events: " . count($backupData['calendar_events']) . "\n";
echo "- Diary entries: " . count($backupData['diary_entries']) . "\n";
echo "- Sticky notes: " . count($backupData['sticky_notes']) . "\n";
echo "- JSON size: " . strlen($jsonContent) . " bytes\n";
echo "\nFirst 500 chars of JSON:\n";
echo substr($jsonContent, 0, 500) . "\n";
?>
