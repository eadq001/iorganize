<?php
require_once 'config/config.php';

$conn = getDBConnection();

echo "=== Users Table ===\n";
$result = $conn->query("SELECT id, username, email FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}\n";
    }
} else {
    echo "Error querying users: " . $conn->error . "\n";
}

echo "\n=== Email Verifications Table ===\n";
$result = $conn->query("SELECT id, email, username, verified_at, expires_at FROM email_verifications");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Email: {$row['email']}, Username: {$row['username']}, Verified: {$row['verified_at']}, Expires: {$row['expires_at']}\n";
    }
} else {
    echo "Error querying email_verifications: " . $conn->error . "\n";
}

$conn->close();
?>
