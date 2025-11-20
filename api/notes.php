<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = getCurrentUserId();

switch ($method) {
    case 'GET':
        // Get sticky notes
        $conn = getDBConnection();
        $query = "SELECT id, content, position_x, position_y, color, created_at, updated_at 
                  FROM sticky_notes 
                  WHERE user_id = ?
                  ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notes = [];
        while ($row = $result->fetch_assoc()) {
            $notes[] = [
                'id' => $row['id'],
                'content' => $row['content'],
                'position_x' => $row['position_x'],
                'position_y' => $row['position_y'],
                'color' => $row['color'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        
        $stmt->close();
        $conn->close();
        
        sendSuccessResponse($notes);
        break;
        
    case 'POST':
        // Create note
        $data = json_decode(file_get_contents('php://input'), true);
        
        $content = sanitizeInput($data['content'] ?? '');
        $positionX = intval($data['position_x'] ?? 100);
        $positionY = intval($data['position_y'] ?? 100);
        $color = sanitizeInput($data['color'] ?? getRandomColor());
        
        if (empty($content)) {
            sendErrorResponse('Content is required');
        }
        
        $conn = getDBConnection();
        $query = "INSERT INTO sticky_notes (user_id, content, position_x, position_y, color) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isiss', $userId, $content, $positionX, $positionY, $color);
        
        if ($stmt->execute()) {
            logSecurityEvent('note_created', 'Sticky note created', 'low', $userId);
            sendSuccessResponse(['id' => $stmt->insert_id], 'Note created successfully');
        } else {
            sendErrorResponse('Failed to create note');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    case 'PUT':
        // Update note
        $data = json_decode(file_get_contents('php://input'), true);
        $noteId = intval($data['id'] ?? 0);
        
        if ($noteId <= 0) {
            sendErrorResponse('Invalid note ID');
        }
        
        // Verify ownership
        $conn = getDBConnection();
        $query = "SELECT id FROM sticky_notes WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $noteId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            sendErrorResponse('Note not found', 404);
        }
        
        $content = sanitizeInput($data['content'] ?? '');
        $positionX = intval($data['position_x'] ?? 100);
        $positionY = intval($data['position_y'] ?? 100);
        $color = sanitizeInput($data['color'] ?? getRandomColor());
        
        $query = "UPDATE sticky_notes SET content = ?, position_x = ?, position_y = ?, color = ? 
                  WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('siissi', $content, $positionX, $positionY, $color, $noteId, $userId);
        
        if ($stmt->execute()) {
            logSecurityEvent('note_updated', 'Sticky note updated', 'low', $userId);
            sendSuccessResponse([], 'Note updated successfully');
        } else {
            sendErrorResponse('Failed to update note');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    case 'DELETE':
        // Delete note
        $noteId = intval($_GET['id'] ?? 0);
        
        if ($noteId <= 0) {
            sendErrorResponse('Invalid note ID');
        }
        
        // Verify ownership and delete
        $conn = getDBConnection();
        $query = "DELETE FROM sticky_notes WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $noteId, $userId);
        
        if ($stmt->execute()) {
            logSecurityEvent('note_deleted', 'Sticky note deleted', 'low', $userId);
            sendSuccessResponse([], 'Note deleted successfully');
        } else {
            sendErrorResponse('Failed to delete note');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    default:
        sendErrorResponse('Method not allowed', 405);
        break;
}
?>

