<?php
session_start();

define('ADMIN_USER', 'admin'); //[cite: 3]
define('ADMIN_PASS', 'admin123'); //[cite: 3]

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_submit'])) { //[cite: 3]
    $admin_name = trim($_POST['admin_name']); //[cite: 3]
    $password   = trim($_POST['password']); //[cite: 3]

    if (empty($admin_name) || empty($password)) { //[cite: 3]
        die("Please fill in all fields."); //[cite: 3]
    }

     if ($admin_name === ADMIN_USER && $password === ADMIN_PASS) { //[cite: 3]
        $_SESSION['admin_user'] = $admin_name; //[cite: 3]
        header("Location: adminpanel.php"); //[cite: 3]
        exit(); //[cite: 3]

         } else { //[cite: 3]
        echo "<script>alert('Invalid Admin credentials.'); window.location.href='login.php';</script>"; //[cite: 3]
        exit(); //[cite: 3]
    }
}
?>