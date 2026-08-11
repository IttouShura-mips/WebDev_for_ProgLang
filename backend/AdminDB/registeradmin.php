<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit();
    }

    if (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters.'); window.history.back();</script>";
        exit();
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Username or email already exists.'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admin_users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
    $stmt->bind_param("ssss", $full_name, $username, $email, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Admin account created successfully! Please log in.'); window.location.href='login.html';</script>";
        exit();
    } else {
        echo "<script>alert('Error creating account: " . addslashes($stmt->error) . "'); window.history.back();</script>";
        exit();
    }
}
?>