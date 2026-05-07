<?php
$mysqli = new mysqli("127.0.0.1", "root", "password", "franchisecare");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Database Tables:\n";
$result = $mysqli->query("SHOW TABLES");
$count = 0;
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    echo "  - " . $row[0] . "\n";
    $count++;
}
echo "\nTotal tables: $count\n";

$mysqli->close();
?>
