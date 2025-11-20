<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = getCurrentUserId();

switch ($method) {
    case 'GET':
        // Get events
        $conn = getDBConnection();
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');
        
        $query = "SELECT id, title, description, event_date, event_time, category, color, created_at 
                  FROM calendar_events 
                  WHERE user_id = ? AND event_date BETWEEN ? AND ?
                  ORDER BY event_date ASC, event_time ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'date' => $row['event_date'],
                'time' => $row['event_time'],
                'category' => $row['category'],
                'color' => $row['color'],
                'created_at' => $row['created_at']
            ];
        }
        
        $stmt->close();
        $conn->close();
        
        sendSuccessResponse($events);
        break;
        
    case 'POST':
        // Create event
        $data = json_decode(file_get_contents('php://input'), true);
        
        $title = sanitizeInput($data['title'] ?? '');
        $description = sanitizeInput($data['description'] ?? '');
        $eventDate = sanitizeInput($data['date'] ?? '');
        $eventTime = sanitizeInput($data['time'] ?? null);
        $category = sanitizeInput($data['category'] ?? 'personal');
        $color = sanitizeInput($data['color'] ?? '#3498db');
        
        if (empty($title) || empty($eventDate)) {
            sendErrorResponse('Title and date are required');
        }
        
        if (!validateDate($eventDate)) {
            sendErrorResponse('Invalid date format');
        }
        
        $categories = getCalendarCategories();
        if (!isset($categories[$category])) {
            $category = 'personal';
        }
        
        $conn = getDBConnection();
        $query = "INSERT INTO calendar_events (user_id, title, description, event_date, event_time, category, color) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('issssss', $userId, $title, $description, $eventDate, $eventTime, $category, $color);
        
        if ($stmt->execute()) {
            logSecurityEvent('calendar_event_created', "Event created: $title", 'low', $userId);
            sendSuccessResponse(['id' => $stmt->insert_id], 'Event created successfully');
        } else {
            sendErrorResponse('Failed to create event');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    case 'PUT':
        // Update event
        $data = json_decode(file_get_contents('php://input'), true);
        $eventId = intval($data['id'] ?? 0);
        
        if ($eventId <= 0) {
            sendErrorResponse('Invalid event ID');
        }
        
        // Verify ownership
        $conn = getDBConnection();
        $query = "SELECT id FROM calendar_events WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $eventId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            sendErrorResponse('Event not found', 404);
        }
        
        $stmt->close();
        
        $title = sanitizeInput($data['title'] ?? '');
        $description = sanitizeInput($data['description'] ?? '');
        $eventDate = sanitizeInput($data['date'] ?? '');
        $eventTime = sanitizeInput($data['time'] ?? null);
        $category = sanitizeInput($data['category'] ?? 'personal');
        $color = sanitizeInput($data['color'] ?? '#3498db');
        
        $query = "UPDATE calendar_events SET title = ?, description = ?, event_date = ?, event_time = ?, category = ?, color = ? 
                  WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssssii', $title, $description, $eventDate, $eventTime, $category, $color, $eventId, $userId);
        
        if ($stmt->execute()) {
            logSecurityEvent('calendar_event_updated', "Event updated: $title", 'low', $userId);
            $stmt->close();
            $conn->close();
            sendSuccessResponse([], 'Event updated successfully');
        } else {
            $stmt->close();
            $conn->close();
            sendErrorResponse('Failed to update event');
        }
        break;
        
    case 'DELETE':
        // Delete event
        $eventId = intval($_GET['id'] ?? 0);
        
        if ($eventId <= 0) {
            sendErrorResponse('Invalid event ID');
        }
        
        // Verify ownership and delete
        $conn = getDBConnection();
        $query = "DELETE FROM calendar_events WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $eventId, $userId);
        
        if ($stmt->execute()) {
            logSecurityEvent('calendar_event_deleted', "Event deleted: ID $eventId", 'low', $userId);
            sendSuccessResponse([], 'Event deleted successfully');
        } else {
            sendErrorResponse('Failed to delete event');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    default:
        sendErrorResponse('Method not allowed', 405);
        break;
}
?>

