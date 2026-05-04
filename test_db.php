<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$db_selected = mysqli_select_db($conn, 'franchisecare');
if (!$db_selected) {
    die ('Can\'t use franchisecare : ' . mysqli_error($conn));
}
echo "Connected successfully and database exists";
?>
