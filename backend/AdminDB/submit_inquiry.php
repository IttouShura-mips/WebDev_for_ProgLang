<?php
session_start();
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

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

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$subject  = trim($_POST['subject'] ?? '');
$concern  = trim($_POST['concern'] ?? '');

if (empty($fullname) || empty($email) || empty($subject) || empty($concern)) {
    $response['message'] = 'Please fill in all required fields.';
    echo json_encode($response);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit();
}

$date = date('Y-m-d');
$status = 'unread';

$stmt = $conn->prepare("INSERT INTO received_mail (email, sender_name, subject, message, date_received, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $email, $fullname, $subject, $concern, $date, $status);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Inquiry submitted successfully.';
} else {
    $response['message'] = 'Database error: ' . $stmt->error;
}

echo json_encode($response);
exit();
?>