<?php
session_start();
require 'db.php';

// Auto-create received_mail table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS received_mail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date_received DATE NOT NULL,
    status ENUM('unread', 'read', 'urgent') DEFAULT 'unread'
)";
$conn->query($createTableSQL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $subject  = trim($_POST['subject'] ?? '');
    $concern  = trim($_POST['concern'] ?? '');

    // Validation
    if (empty($fullname) || empty($email) || empty($subject) || empty($concern)) {
        header('Location: contact.html?error=empty');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: contact.html?error=email');
        exit();
    }

    $date = date('Y-m-d');
    $status = 'unread';

    $stmt = $conn->prepare("INSERT INTO received_mail (email, sender_name, subject, message, date_received, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $email, $fullname, $subject, $concern, $date, $status);

    if ($stmt->execute()) {
        header('Location: contact.html?success=1');
        exit();
    } else {
        header('Location: contact.html?error=db');
        exit();
    }
} else {
    header('Location: contact.html');
    exit();
}