<?php
/**
 * Database setup script
 * This tries multiple connection methods to establish database connection
 */

$host = "127.0.0.1";
$user = "root";
$passwords = ["", "root", "password", "laragon", "123456"];

$mysqli = null;

foreach ($passwords as $pwd) {
    try {
        $mysqli = new mysqli($host, $user, $pwd);
        if (!$mysqli->connect_error) {
            echo "✓ Connected successfully with password: [" . ($pwd ? $pwd : "empty") . "]\n";
            break;
        }
    } catch (Exception $e) {
        echo "✗ Failed with password [" . ($pwd ? $pwd : "empty") . "]: " . $e->getMessage() . "\n";
    }
}

if (!$mysqli || $mysqli->connect_error) {
    die("\n❌ Could not connect to MySQL with any password\n");
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS franchisecare DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($sql)) {
    echo "✓ Database 'franchisecare' created/verified\n";
} else {
    echo "✗ Error creating database: " . $mysqli->error . "\n";
}

// Show databases
echo "\nAvailable databases:\n";
$result = $mysqli->query("SHOW DATABASES");
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    echo "  - " . $row[0] . "\n";
}

$mysqli->close();
echo "\n✓ Setup complete\n";
?>
