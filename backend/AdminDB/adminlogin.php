<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true
]);
session_start();

require 'db.php';

// Fallback hardcoded admin for initial access
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    // 1. Check database admin_users table first
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM admin_users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_user'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: adminpanel.php");
            exit();
        }
    }

    // 2. Fallback hardcoded admin
    if ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_name'] = 'System Administrator';
        $_SESSION['admin_role'] = 'superadmin';
        header("Location: adminpanel.php");
        exit();
    }

    // 3. Student Verification (username = email)
    // Check if password column exists
    $hasPassword = false;
    $colCheck = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'password'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $hasPassword = true;
    }

    if ($hasPassword) {
        $stmt = $conn->prepare("SELECT student_id, firstname, lastname, password FROM enrolled WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            if (password_verify($password, $student['password'])) {
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['firstname'] . ' ' . $student['lastname'];
                // Update last login
                $upd = $conn->prepare("UPDATE enrolled SET last_login = NOW(), status = 'online', ip_address = ? WHERE student_id = ?");
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $upd->bind_param("si", $ip, $student['student_id']);
                $upd->execute();
                header("Location: ../student/dashboard.php");
                exit();
            }
        }
    } else {
        // Legacy mode: no password column yet
        $stmt = $conn->prepare("SELECT student_id, firstname, lastname FROM enrolled WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['student_name'] = $student['firstname'] . ' ' . $student['lastname'];
            header("Location: ../student/dashboard.php");
            exit();
        }
    }

    echo "<script>alert('Invalid credentials. Please try again.'); window.location.href='login.html';</script>";
    exit();
}
?>