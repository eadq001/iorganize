<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

// Set error handler to catch fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Calendar API Error: [$errno] $errstr in $errfile:$errline");
    sendErrorResponse("Server error: " . $errstr, 500);
});

set_exception_handler(function($exception) {
    error_log("Calendar API Exception: " . $exception->getMessage());
    sendErrorResponse("Server error: " . $exception->getMessage(), 500);
});

$method = $_SERVER['REQUEST_METHOD'];
$userId = getCurrentUserId();

// Log detailed debugging information
$userIdDebug = "Type: " . gettype($userId) . ", Value: " . var_export($userId, true);
$sessionUser = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET';
error_log("Calendar API - Method: $method, userId=$userIdDebug, SESSION['user_id']=$sessionUser");

try {
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
        
        if (!is_array($data)) {
            sendErrorResponse('Invalid JSON received');
            exit;
        }
        
        $title = sanitizeInput($data['title'] ?? '');
        $description = sanitizeInput($data['description'] ?? '');
        $eventDate = sanitizeInput($data['date'] ?? '');
        $eventTimeRaw = $data['time'] ?? null;
        $category = sanitizeInput($data['category'] ?? 'personal');
        $color = sanitizeInput($data['color'] ?? '#3498db');
        
        // Sanitize event time separately
        if (!empty($eventTimeRaw)) {
            $eventTime = sanitizeInput($eventTimeRaw);
        } else {
            $eventTime = null;
        }
        
        if (empty($title) || empty($eventDate)) {
            sendErrorResponse('Title and date are required');
            exit;
        }
        
        if (!validateDate($eventDate)) {
            sendErrorResponse('Invalid date format');
            exit;
        }
        
        $categories = getCalendarCategories();
        if (!isset($categories[$category])) {
            $category = 'personal';
        }
        
        $conn = getDBConnection();
        
        // Build query based on whether we have a time
        if ($eventTime === null) {
            $query = "INSERT INTO calendar_events (user_id, title, description, event_date, category, color) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendErrorResponse('Prepare failed: ' . $conn->error);
                exit;
            }
            $stmt->bind_param('issss', $userId, $title, $description, $eventDate, $category, $color);
        } else {
            $query = "INSERT INTO calendar_events (user_id, title, description, event_date, event_time, category, color) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendErrorResponse('Prepare failed: ' . $conn->error);
                exit;
            }
            $stmt->bind_param('issssss', $userId, $title, $description, $eventDate, $eventTime, $category, $color);
        }
        
        if ($stmt->execute()) {
            logSecurityEvent('calendar_event_created', "Event created: $title", 'low', $userId);
            sendSuccessResponse(['id' => $stmt->insert_id], 'Event created successfully');
        } else {
            sendErrorResponse('Failed to create event: ' . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    case 'PUT':
        // Update event
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!is_array($data)) {
            sendErrorResponse('Invalid JSON received');
            exit;
        }
        
        $eventId = intval($data['id'] ?? 0);
        
        if ($eventId <= 0) {
            sendErrorResponse('Invalid event ID');
            exit;
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
            exit;
        }
        
        $stmt->close();
        
        $title = sanitizeInput($data['title'] ?? '');
        $description = sanitizeInput($data['description'] ?? '');
        $eventDate = sanitizeInput($data['date'] ?? '');
        $eventTimeRaw = $data['time'] ?? null;
        $category = sanitizeInput($data['category'] ?? 'personal');
        $color = sanitizeInput($data['color'] ?? '#3498db');
        
        // Sanitize event time separately
        if (!empty($eventTimeRaw)) {
            $eventTime = sanitizeInput($eventTimeRaw);
        } else {
            $eventTime = null;
        }
        
        // Build query based on whether we have a time
        if ($eventTime === null) {
            $query = "UPDATE calendar_events SET title = ?, description = ?, event_date = ?, event_time = NULL, category = ?, color = ? 
                      WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendErrorResponse('Prepare failed: ' . $conn->error);
                exit;
            }
            $stmt->bind_param('sssssii', $title, $description, $eventDate, $category, $color, $eventId, $userId);
        } else {
            $query = "UPDATE calendar_events SET title = ?, description = ?, event_date = ?, event_time = ?, category = ?, color = ? 
                      WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendErrorResponse('Prepare failed: ' . $conn->error);
                exit;
            }
            $stmt->bind_param('ssssssii', $title, $description, $eventDate, $eventTime, $category, $color, $eventId, $userId);
        }
        
        if ($stmt->execute()) {
            logSecurityEvent('calendar_event_updated', "Event updated: $title", 'low', $userId);
            $stmt->close();
            $conn->close();
            sendSuccessResponse([], 'Event updated successfully');
        } else {
            $stmt->close();
            $conn->close();
            sendErrorResponse('Failed to update event: ' . $stmt->error);
        }
        break;
        
    case 'DELETE':
        // Delete event
        $eventId = intval($_GET['id'] ?? 0);
        
        if ($eventId <= 0) {
            sendErrorResponse('Invalid event ID');
            exit;
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
} catch (Exception $e) {
    error_log("Calendar API Caught Exception: " . $e->getMessage());
    sendErrorResponse("Server error: " . $e->getMessage(), 500);
}

