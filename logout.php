<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $sessionId = session_id();
    
    // Remove session from database
    $conn = getDBConnection();
    $query = "DELETE FROM sessions WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $sessionId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    logSecurityEvent('logout', 'User logged out', 'low', $userId);
}

// Destroy session
session_unset();
session_destroy();

header('Location: login.php');
exit();
?>

