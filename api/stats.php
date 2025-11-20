<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$conn = getDBConnection();

// Get events count for current month
$startDate = date('Y-m-01');
$endDate = date('Y-m-t');
$query = "SELECT COUNT(*) as count FROM calendar_events WHERE user_id = ? AND event_date BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('iss', $userId, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$eventsCount = $result->fetch_assoc()['count'];
$stmt->close();

// Get sticky notes count
$query = "SELECT COUNT(*) as count FROM sticky_notes WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$notesCount = $result->fetch_assoc()['count'];
$stmt->close();

// Get diary entries count
$query = "SELECT COUNT(*) as count FROM diary_entries WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$diaryCount = $result->fetch_assoc()['count'];
$stmt->close();

// Get upcoming events (next 5)
$query = "SELECT id, title, event_date, event_time, color 
          FROM calendar_events 
          WHERE user_id = ? AND event_date >= CURDATE()
          ORDER BY event_date ASC, event_time ASC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$upcomingEvents = [];
while ($row = $result->fetch_assoc()) {
    $upcomingEvents[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'date' => $row['event_date'],
        'time' => $row['event_time'],
        'color' => $row['color']
    ];
}
$stmt->close();
$conn->close();

sendSuccessResponse([
    'events_count' => $eventsCount,
    'notes_count' => $notesCount,
    'diary_count' => $diaryCount,
    'upcoming_events' => $upcomingEvents
]);
?>

