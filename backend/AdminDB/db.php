<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "enrollmentdb";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");
?>