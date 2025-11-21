<?php
// Test if session is working
require_once 'config/config.php';

echo "Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "\n";
echo "User ID in SESSION: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Username in SESSION: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Full SESSION array:\n";
var_dump($_SESSION);

// Check sessions table
$conn = getDBConnection();
$result = $conn->query('SELECT user_id FROM sessions WHERE id = "' . session_id() . '"');
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Session in DB has user_id: " . $row['user_id'] . "\n";
} else {
    echo "Session NOT found in DB\n";
}
$conn->close();
?>
