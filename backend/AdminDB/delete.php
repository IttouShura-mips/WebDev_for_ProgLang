<?php
session_start();
if (!isset($_SESSION['admin_user'])) { header("Location: login.html"); exit(); }
require 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM enrolled WHERE student_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: adminpanel.php");
exit();
?>