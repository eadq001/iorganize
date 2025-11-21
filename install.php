<?php
/**
 * Installation Script for iOrganize
 * Run this script to set up the database
 */

// Check if already installed
if (file_exists('config/installed.flag')) {
    die('iOrganize is already installed. Delete config/installed.flag to reinstall.');
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'iorganize';

echo "iOrganize Installation\n";
echo "======================\n\n";

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected to MySQL server.\n";

// Read SQL file
$sql_file = __DIR__ . '/config/install.sql';
if (!file_exists($sql_file)) {
    die("SQL file not found: $sql_file\n");
}

$sql = file_get_contents($sql_file);

// Execute SQL
if ($conn->multi_query($sql)) {
    do {
        // Store result
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "Database created successfully.\n";
    echo "Tables created successfully.\n";
} else {
    die("Error executing SQL: " . $conn->error . "\n");
}

// Create directories
$directories = [
    'logs',
    'backups'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Create installed flag
file_put_contents('config/installed.flag', date('Y-m-d H:i:s'));
echo "Installation flag created.\n";

$conn->close();

echo "\nInstallation complete!\n";
echo "You can now access iOrganize at: http://localhost/L\n";
echo "Please change the ENCRYPTION_KEY in config/config.php for production use.\n";
?>

