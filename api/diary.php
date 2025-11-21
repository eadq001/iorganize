<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = getCurrentUserId();

switch ($method) {
    case 'GET':
        // Get diary entries
        $conn = getDBConnection();
        $entryId = $_GET['id'] ?? null;
        
        if ($entryId) {
            // Get single entry
            $query = "SELECT id, title, content, encrypted_content, mood, entry_date, created_at, updated_at 
                      FROM diary_entries 
                      WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $entryId, $userId);
        } else {
            // Get all entries
            $startDate = $_GET['start'] ?? null;
            $endDate = $_GET['end'] ?? null;
            
            if ($startDate && $endDate) {
                $query = "SELECT id, title, mood, entry_date, created_at, updated_at 
                          FROM diary_entries 
                          WHERE user_id = ? AND entry_date BETWEEN ? AND ?
                          ORDER BY entry_date DESC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iss', $userId, $startDate, $endDate);
            } else {
                $query = "SELECT id, title, mood, entry_date, created_at, updated_at 
                          FROM diary_entries 
                          WHERE user_id = ?
                          ORDER BY entry_date DESC
                          LIMIT 50";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $userId);
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $entry = [
                'id' => $row['id'],
                'title' => $row['title'],
                'mood' => $row['mood'],
                'entry_date' => $row['entry_date'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
            
            // Decrypt content if single entry requested
            if ($entryId && !empty($row['encrypted_content'])) {
                $entry['content'] = decryptData($row['encrypted_content']);
            } elseif ($entryId) {
                $entry['content'] = $row['content'];
            }
            
            $entries[] = $entry;
        }
        
        $stmt->close();
        $conn->close();
        
        if ($entryId && count($entries) > 0) {
            sendSuccessResponse($entries[0]);
        } else {
            sendSuccessResponse($entries);
        }
        break;
        
    case 'POST':
        // Create diary entry
        $data = json_decode(file_get_contents('php://input'), true);
        
        $title = sanitizeInput($data['title'] ?? '');
        $content = $data['content'] ?? '';
        $mood = sanitizeInput($data['mood'] ?? '');
        $entryDate = sanitizeInput($data['entry_date'] ?? date('Y-m-d'));
        
        if (empty($title) || empty($content)) {
            sendErrorResponse('Title and content are required');
        }
        
        if (!validateDate($entryDate)) {
            sendErrorResponse('Invalid date format');
        }
        
        // Encrypt content
        $encryptedContent = encryptData($content);
        
        $conn = getDBConnection();
        $query = "INSERT INTO diary_entries (user_id, title, content, encrypted_content, mood, entry_date) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isssss', $userId, $title, $content, $encryptedContent, $mood, $entryDate);
        
        if ($stmt->execute()) {
            logSecurityEvent('diary_entry_created', "Diary entry created: $title", 'low', $userId);
            sendSuccessResponse(['id' => $stmt->insert_id], 'Diary entry created successfully');
        } else {
            sendErrorResponse('Failed to create diary entry');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    case 'PUT':
        // Update diary entry
        $data = json_decode(file_get_contents('php://input'), true);
        $entryId = intval($data['id'] ?? 0);
        
        if ($entryId <= 0) {
            sendErrorResponse('Invalid entry ID');
        }
        
        // Verify ownership
        $conn = getDBConnection();
        $query = "SELECT id FROM diary_entries WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $entryId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            sendErrorResponse('Entry not found', 404);
        }
        
        $title = sanitizeInput($data['title'] ?? '');
        $content = $data['content'] ?? '';
        $mood = sanitizeInput($data['mood'] ?? '');
        $entryDate = sanitizeInput($data['entry_date'] ?? date('Y-m-d'));
        
        // Encrypt content
        $encryptedContent = encryptData($content);
        
        $query = "UPDATE diary_entries SET title = ?, content = ?, encrypted_content = ?, mood = ?, entry_date = ? 
                  WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssssii', $title, $content, $encryptedContent, $mood, $entryDate, $entryId, $userId);
        
        if ($stmt->execute()) {
            logSecurityEvent('diary_entry_updated', "Diary entry updated: $title", 'low', $userId);
            sendSuccessResponse([], 'Diary entry updated successfully');
        } else {
            sendErrorResponse('Failed to update diary entry');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    case 'DELETE':
        // Delete diary entry
        $entryId = intval($_GET['id'] ?? 0);
        
        if ($entryId <= 0) {
            sendErrorResponse('Invalid entry ID');
        }
        
        // Verify ownership and delete
        $conn = getDBConnection();
        $query = "DELETE FROM diary_entries WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $entryId, $userId);
        
        if ($stmt->execute()) {
            logSecurityEvent('diary_entry_deleted', "Diary entry deleted: ID $entryId", 'low', $userId);
            sendSuccessResponse([], 'Diary entry deleted successfully');
        } else {
            sendErrorResponse('Failed to delete diary entry');
        }
        
        $stmt->close();
        $conn->close();
        break;
        
    default:
        sendErrorResponse('Method not allowed', 405);
        break;
}
?>

