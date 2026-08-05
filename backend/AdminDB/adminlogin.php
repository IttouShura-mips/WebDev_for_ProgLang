<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true
]);
session_start();

require 'db.php';

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    // 1. Admin Verification
    if ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['admin_user'] = $username;
        header("Location: adminpanel.php");
        exit();
    }

    // 2. Student Verification (username = email)
    $stmt = $conn->prepare("SELECT student_id, firstname, lastname FROM enrolled WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $_SESSION['student_id']   = $student['student_id'];
        $_SESSION['student_name'] = $student['firstname'] . ' ' . $student['lastname'];
        
        header("Location: student/dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid Admin or Student credentials.'); window.history.back();</script>";
        exit();
    }
}
?>