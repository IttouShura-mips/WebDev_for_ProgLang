<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['student_name']) || !isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Database Connection
$host     = '127.0.0.1';
$db_name  = 'enrollmentdb';
$dbuser   = 'root';
$dbpass   = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch full student record from database
$username = $_SESSION['username'];
try {
    $stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, username FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: login.html");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Build display values
$full_name     = htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
$first_name    = htmlspecialchars($student['first_name']);
$middle_name   = htmlspecialchars($student['middle_name']);
$last_name     = htmlspecialchars($student['last_name']);
$username_disp = htmlspecialchars($student['username']);
$user_id       = htmlspecialchars($student['user_id']);
$initial       = strtoupper(substr($student['first_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | ICF Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bg-deep-abyss: #020c1b;
            --bg-card: #0a192f;
            --bg-card-hover: #112240;
            --primary-neon: #0df5e3;
            --primary-neon-hover: #00cbb9;
            --text-high-contrast: #e2e8f0;
            --text-muted-teal: #8892b0;
            --border-teal: #172a45;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --neon-glow: 0 0 15px rgba(13, 245, 227, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #071f30, var(--bg-deep-abyss));
            color: var(--text-high-contrast);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-teal);
            padding: 16px 100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            flex-wrap: wrap;
            gap: 10px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            color: var(--text-high-contrast);
            text-decoration: none;
        }

        .navbar-brand img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .navbar-brand .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.5;
        }

        .navbar-brand .brand-text .school-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-high-contrast);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .navbar-brand .brand-text .school-subtitle {
            font-size: 13px;
            color: var(--primary-neon);
            font-weight: 400;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .user-pill {
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            color: var(--text-muted-teal);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-pill i {
            color: var(--primary-neon);
        }

        .btn-logout {
            background: transparent;
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: rgba(255, 107, 107, 0.1);
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.3);
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
        }

        .profile-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 20px;
            padding: 40px 35px;
            width: 100%;
            max-width: 1100px;
            box-shadow: 0 0px 30px rgba(13, 245, 227, 0.15);
            transition: var(--transition-smooth);
            margin-bottom: 40px;
        }

        .profile-card:hover {
            box-shadow: 0 0px 40px rgba(13, 245, 227, 0.25);
            border-color: rgba(13, 245, 227, 0.3);
        }

        /* ===== HEADER SECTION ===== */
        .cor-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-teal);
        }

        .cor-header-top {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .cor-header-text {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cor-logo {
            background-color: transparent;
            border: none;
            width: 80px;
            height: 80px;
            object-fit: contain;
            padding: 5px;
        }

        .cor-school-name {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-high-contrast);
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }

        .cor-address {
            font-size: 13px;
            color: var(--text-muted-teal);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-align: center;
        }

        .cor-address i {
            color: var(--primary-neon);
        }

        .cor-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 10px;
            border-bottom: 1px solid var(--border-teal);
            padding-bottom: 5px;
        }

        .cor-session {
            font-size: 14px;
            color: var(--text-muted-teal);
            margin-top: 5px;
            font-style: italic;
        }

        /* ===== Student Info Section ===== */
        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            padding: 20px;
        }

        .student-info-left,
        .student-info-right {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .student-info-left {
            align-items: flex-start;
        }

        .student-info-right {
            align-items: flex-end;
            text-align: right;
        }

        .info-line {
            font-size: 14px;
            color: var(--text-muted-teal);
        }

        .info-line strong {
            color: var(--text-high-contrast);
            font-weight: 600;
        }

        .info-line strong i {
            color: var(--primary-neon);
            margin-right: 5px;
        }

        .info-line .highlight {
            color: var(--primary-neon);
            font-weight: 700;
        }

        /* ===== COURSE TABLE ===== */
        .course-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background: var(--bg-deep-abyss);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--border-teal);
        }

        .course-table th {
            background: #0e1f36;
            color: var(--primary-neon);
            font-weight: 600;
            padding: 10px 8px;
            text-align: left;
            border-bottom: 2px solid var(--border-teal);
        }

        .course-table td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--border-teal);
            color: var(--text-high-contrast);
        }

        .course-table tr:last-child td {
            border-bottom: none;
        }

        .course-table .total-row td {
            background: #0e1f36;
            font-weight: 700;
            color: var(--primary-neon);
            border-top: 2px solid var(--border-teal);
        }

        .course-table .summary-row td {
            font-size: 12px;
            color: var(--text-muted-teal);
            border-bottom: none;
            padding-top: 10px;
        }

        /* ===== PAYMENT & ASSESSMENT SECTION ===== */
        .payment-assessment-wrap {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .payment-assess-card {
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            background: #0e1f36;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--primary-neon);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header i {
            font-size: 16px;
        }

        .table-container {
            padding: 0;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th,
        .data-table td {
            padding: 8px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-teal);
        }

        .data-table th {
            color: var(--text-muted-teal);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-table td {
            color: var(--text-high-contrast);
            font-size: 13px;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table .assessment-item td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .data-table .assessment-total td {
            background: #0e1f36;
            font-weight: 800;
            color: var(--primary-neon);
            font-size: 15px;
            border-top: 2px solid var(--primary-neon);
        }

        .data-table .assessment-total td:last-child {
            text-align: right;
            font-size: 18px;
        }

        .data-table .assessment-remaining td {
            background: var(--bg-card-hover);
            color: var(--text-high-contrast);
            font-weight: 800;
            font-size: 15px;
            border-top: 2px solid var(--primary-neon);
        }

        .data-table .assessment-remaining td:last-child {
            text-align: right;
            font-size: 18px;
        }

        /* ===== PAY TUITION BUTTON ===== */
        .btn-pay-tuition {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-align: center;
            box-shadow: 0 4px 15px rgba(13, 245, 227, 0.3);
        }

        .btn-pay-tuition:hover {
            transform: translateY(-3px);
            box-shadow: var(--neon-glow);
        }

        /* ===== SIGNATURE SECTION ===== */
        .signature-box {
            margin-top: 20px;
            padding: 20px;
            background: var(--bg-deep-abyss);
            border: 1px dashed var(--border-teal);
            border-radius: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: end;
            text-align: center;
        }

        .sig-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sig-line {
            width: 150px;
            border-bottom: 1px solid var(--text-muted-teal);
            margin: 0 auto 5px auto;
        }

        .sig-item strong {
            color: var(--text-high-contrast);
            font-size: 14px;
        }

        .sig-item span {
            color: var(--text-muted-teal);
            font-size: 12px;
        }

        .student-copy {
            font-size: 12px;
            color: var(--text-muted-teal);
            margin-top: 15px;
            text-align: center;
            font-style: italic;
        }

        /* ===== CONTACT US SECTION ===== */
        .contact-section {
            margin-top: 40px;
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(13, 245, 227, 0.05);
        }

        .contact-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .contact-header i {
            font-size: 24px;
            color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.1);
            padding: 12px;
            border-radius: 50%;
        }

        .contact-header h2 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-high-contrast);
        }

        .contact-header p {
            font-size: 13px;
            color: var(--text-muted-teal);
            margin-top: 2px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .contact-card {
            background: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
            transition: var(--transition-smooth);
        }

        .contact-card:hover {
            border-color: rgba(13, 245, 227, 0.3);
            transform: translateY(-2px);
        }

        .contact-card .icon-box {
            background: rgba(13, 245, 227, 0.1);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 10px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-card .icon-box i {
            font-size: 18px;
            color: var(--primary-neon);
        }

        .contact-card .contact-info h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-high-contrast);
            margin-bottom: 6px;
        }

        .contact-card .contact-info p {
            font-size: 13px;
            color: var(--text-muted-teal);
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .btn-contact {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            border: none;
            padding: 8px 18px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-decoration: none;
        }

        .btn-contact:hover {
            transform: scale(1.05);
            box-shadow: var(--neon-glow);
        }

        /* ===== CUSTOMER SERVICE HANDLER ===== */
        .support-handler {
            margin-top: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 0 20px rgba(13, 245, 227, 0.05);
        }

        .handler-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .handler-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--bg-deep-abyss);
            font-weight: 800;
            flex-shrink: 0;
        }

        .handler-details h5 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-high-contrast);
            margin: 0;
        }

        .handler-details p {
            font-size: 12px;
            color: var(--text-muted-teal);
            margin: 2px 0 0 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .handler-details p i {
            color: #10b981;
            font-size: 10px;
        }

        .handler-actions {
            display: flex;
            gap: 10px;
        }

        .btn-call,
        .btn-chat {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--border-teal);
            background: transparent;
            color: var(--text-high-contrast);
        }

        .btn-call:hover,
        .btn-chat:hover {
            background: var(--bg-card-hover);
            border-color: var(--primary-neon);
        }

        .btn-chat {
            background: var(--primary-neon);
            color: var(--bg-deep-abyss);
            border: none;
        }

        .btn-chat:hover {
            background: var(--primary-neon-hover);
        }

        /* ===== PAYMENT MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(2, 12, 27, 0.9);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--primary-neon);
            border-radius: 20px;
            padding: 30px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 0 40px rgba(13, 245, 227, 0.3);
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            color: var(--text-muted-teal);
            font-size: 24px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .modal-close:hover {
            color: #ff6b6b;
            transform: rotate(90deg);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary-neon);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-title i {
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: var(--text-muted-teal);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 10px;
            color: var(--text-high-contrast);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: var(--transition-smooth);
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--primary-neon);
            box-shadow: 0 0 10px rgba(13, 245, 227, 0.2);
        }

        /* ===== GCash Input Specific Styles ===== */
        .form-group input[type="tel"] {
            letter-spacing: 1px;
        }

        .form-group input::placeholder {
            color: var(--text-muted-teal);
            opacity: 0.7;
        }

        .btn-pay-confirm {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition-smooth);
            margin-top: 10px;
        }

        .btn-pay-confirm:hover {
            transform: translateY(-2px);
            box-shadow: var(--neon-glow);
        }

        /* ===== CUSTOM ALERT MODAL ===== */
        .alert-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(2, 12, 27, 0.95);
            backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .alert-overlay.active {
            display: flex;
        }

        .alert-box {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 0 40px rgba(13, 245, 227, 0.3);
            animation: modalFadeIn 0.3s ease;
            border: 1px solid var(--border-teal);
        }

        .alert-box.success {
            border-color: #10b981;
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.3);
        }

        .alert-box.warning {
            border-color: #fbbf24;
            box-shadow: 0 0 40px rgba(251, 191, 36, 0.3);
        }

        .alert-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .alert-box.success .alert-icon {
            color: #10b981;
        }

        .alert-box.warning .alert-icon {
            color: #fbbf24;
        }

        .alert-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-high-contrast);
            margin-bottom: 10px;
        }

        .alert-message {
            font-size: 14px;
            color: var(--text-muted-teal);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .alert-close-btn {
            padding: 10px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .alert-box.success .alert-close-btn {
            background: #10b981;
            color: var(--bg-deep-abyss);
        }

        .alert-box.warning .alert-close-btn {
            background: #fbbf24;
            color: var(--bg-deep-abyss);
        }

        .alert-close-btn:hover {
            transform: scale(1.05);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 820px) {
            .payment-assessment-wrap {
                grid-template-columns: 1fr;
            }

            .signature-box {
                grid-template-columns: 1fr;
            }

            .student-info-grid {
                grid-template-columns: 1fr;
            }

            .student-info-right {
                align-items: flex-start;
                text-align: left;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .navbar {
                padding: 14px 20px;
            }

            .profile-card {
                padding: 25px 18px;
            }

            .cor-school-name {
                font-size: 22px;
            }

            .course-table {
                font-size: 12px;
            }

            .course-table th,
            .course-table td {
                padding: 6px 4px;
            }

            .support-handler {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .cor-header-top {
                flex-direction: column;
            }

            .cor-logo {
                width: 60px;
                height: 60px;
            }

            .handler-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn-call,
            .btn-chat {
                justify-content: center;
                width: 100%;
            }

            .navbar-brand .brand-text .school-title {
                font-size: 14px;
            }

            .navbar-brand img {
                width: 35px;
                height: 35px;
            }
        }
    </style>

    <!-- <style>
        :root {
            --bg-deep-abyss: #020c1b;
            --bg-card: #0a192f;
            --bg-card-hover: #112240;
            --primary-neon: #0df5e3;
            --primary-neon-hover: #00cbb9;
            --text-high-contrast: #e2e8f0;
            --text-muted-teal: #8892b0;
            --border-teal: #172a45;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --neon-glow: 0 0 15px rgba(13, 245, 227, 0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #071f30, var(--bg-deep-abyss));
            color: var(--text-high-contrast);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-teal);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
            color: var(--primary-neon);
        }

        .navbar-brand i { font-size: 24px; }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-pill {
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            color: var(--text-muted-teal);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-pill i { color: var(--primary-neon); }

        .btn-logout {
            background: transparent;
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: rgba(255, 107, 107, 0.1);
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.3);
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .profile-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 20px;
            padding: 50px 40px;
            width: 100%;
            max-width: 560px;
            text-align: center;
            box-shadow: 0 0px 30px rgba(13, 245, 227, 0.15);
            transition: var(--transition-smooth);
        }

        .profile-card:hover {
            box-shadow: 0 0px 40px rgba(13, 245, 227, 0.25);
            border-color: rgba(13, 245, 227, 0.3);
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 42px;
            color: var(--bg-deep-abyss);
            font-weight: 800;
            box-shadow: var(--neon-glow);
        }

        .welcome-text {
            font-size: 13px;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .student-name {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-high-contrast);
            margin-bottom: 6px;
        }

        .student-id {
            font-size: 14px;
            color: var(--primary-neon);
            font-family: monospace;
            background: rgba(13, 245, 227, 0.1);
            padding: 4px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .divider {
            height: 1px;
            background: var(--border-teal);
            margin: 24px 0;
        }

        .section-title {
            font-size: 12px;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 16px;
            text-align: left;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .info-item {
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            padding: 16px;
            text-align: left;
            transition: var(--transition-smooth);
        }

        .info-item:hover {
            border-color: rgba(13, 245, 227, 0.3);
            transform: translateY(-2px);
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item i {
            color: var(--primary-neon);
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
        }

        .info-label {
            font-size: 11px;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-high-contrast);
            word-break: break-word;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 24px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
        }

        @media (max-width: 600px) {
            .navbar { padding: 14px 20px; }
            .navbar-brand span { display: none; }
            .profile-card { padding: 35px 22px; }
            .info-grid { grid-template-columns: 1fr; }
            .student-name { font-size: 22px; }
        }
    </style> -->
</head>
<body>

    <!-- <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>ICF Portal</span>
        </div>
        <div class="navbar-actions">
            <div class="user-pill">
                <i class="fas fa-user"></i>
                <span><?php echo $username_disp; ?></span>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="profile-card">
            <div class="avatar-circle"><?php echo $initial; ?></div>

            <div class="welcome-text">Registered Student</div>
            <div class="student-name"><?php echo $full_name; ?></div>
            <div class="student-id">
                <i class="fas fa-id-card" style="margin-right:6px;"></i><?php echo $username_disp; ?>
            </div>

            <div class="divider"></div>

            <div class="section-title">Personal Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-hashtag"></i>
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?php echo $user_id; ?></div>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-label">First Name</div>
                    <div class="info-value"><?php echo $first_name; ?></div>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-label">Middle Name</div>
                    <div class="info-value"><?php echo $middle_name; ?></div>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-label">Last Name</div>
                    <div class="info-value"><?php echo $last_name; ?></div>
                </div>
                <div class="info-item full-width">
                    <i class="fas fa-fingerprint"></i>
                    <div class="info-label">Username / Student ID</div>
                    <div class="info-value"><?php echo $username_disp; ?></div>
                </div>
            </div>

            <div class="status-badge">Account Active</div>
        </div>
    </main> -->

    
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="../../BackGroundimage/ICFLogo.png" alt="School Logo">
            <div class="brand-text">
                <span class="school-title">ICF</span>
                <span class="school-subtitle">Interworld Colleges Foundation Inc.</span>
            </div>
        </div>
        <div class="navbar-actions">
            <div class="user-pill">
                <i class="fas fa-user"></i>
                <span>2024-0061</span>
            </div>
            <a href="logout.php" class="btn-logout" onclick="showAlert('warning', 'Logout Confirmation', 'Are you sure you want to logout?'); return false;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="profile-card">

            <!-- ===== HEADER ===== -->
            <div class="cor-header">
                <div class="cor-header-top">
                    <img src="../../BackGroundimage/ICFLogo.png" alt="School Logo" class="cor-logo">
                    <div class="cor-header-text">
                        <div class="cor-school-name">Interworld Colleges Foundation Inc.</div>
                        <div class="cor-address"><i class="fas fa-map-marker-alt"></i> Burgos St., Paniqui, Tarlac · Tel No. (045) 470-8645</div>
                    </div>
                </div>
                <div class="cor-title">Certificate of Registration (COR)</div>
                <div class="cor-session">2026-2027 — 1st Semester</div>
            </div>

            <!-- ===== STUDENT INFO ===== -->
            <div class="student-info-grid">
                <div class="student-info-left">
                    <div class="info-line"><strong><i class="fas fa-id-card"></i> Student ID:</strong> <span class="highlight">2024-0061</span></div>
                    <div class="info-line"><strong><i class="fas fa-user"></i> Name:</strong> MIPANGA, ALMADIN NOR</div>
                    <div class="info-line"><strong><i class="fas fa-book-open"></i> Course:</strong> BACHELOR OF SCIENCE IN COMPUTER SCIENCE</div>
                </div>
                <div class="student-info-right">
                    <div class="info-line"><strong><i class="fas fa-graduation-cap"></i> Year Level:</strong> 3rd Year</div>
                    <div class="info-line"><strong><i class="fas fa-print"></i> Date Printed:</strong> June 9, 2026</div>
                </div>
            </div>

            <!-- ===== ENROLLED COURSES TABLE ===== -->
            <table class="course-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Units</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Block</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>PC7</td><td>Automata Theory & Formal Languages</td><td>3.0</td><td>MON / WED</td><td>07:30 AM - 09:00 AM</td><td>208 / CL1</td><td>Block A</td></tr>
                    <tr><td>PC8</td><td>Architecture and Organization</td><td>3.0</td><td>MON / WED</td><td>09:00 AM - 10:30 AM</td><td>208 / CL1</td><td>Block A</td></tr>
                    <tr><td>PC11</td><td>Programming Languages</td><td>3.0</td><td>MON / WED</td><td>01:00 PM - 02:30 PM</td><td>208 / CL1</td><td>Block A</td></tr>
                    <tr><td>PC10</td><td>Information Assurance and Security</td><td>3.0</td><td>MON / WED</td><td>02:30 PM - 04:00 PM</td><td>208 / CL1</td><td>Block A</td></tr>
                    <tr><td>PElective3</td><td>Intelligent Systems</td><td>3.0</td><td>MON / WED</td><td>04:00 PM - 05:30 PM</td><td>208 / CL1</td><td>Block A</td></tr>
                    <tr><td>PElective2</td><td>Graphics and Visual Computing</td><td>3.0</td><td>S</td><td>09:00 AM - 12:00 PM</td><td>209 / CL2</td><td>Block A</td></tr>
                    <tr><td>PC12</td><td>Software Engineering 2</td><td>3.0</td><td>S</td><td>01:00 PM - 04:00 PM</td><td>ILAB</td><td>Block A</td></tr>
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right; padding-right: 20px;">TOTAL UNITS</td>
                        <td>21.0</td>
                        <td colspan="4" style="text-align: left;">No. of Classcards: 7 | Bridging Subjects: 0 | NSTP: 0</td>
                    </tr>
                </tbody>
            </table>

            <!-- ===== PAYMENT DETAILS & ASSESSMENT ===== -->
            <div class="payment-assessment-wrap">

                <!-- LEFT: Payment Details -->
                <div class="payment-assess-card">
                    <div class="card-header"><i class="fas fa-receipt"></i> Payment Details</div>
                    <div class="table-container">
                        <table class="data-table" id="paymentTable">
                            <thead>
                                <tr>
                                    <th style="width: 30px;">#</th>
                                    <th>Date</th>
                                    <th>O.R. No.</th>
                                    <th style="text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="paymentBody">
                                <tr><td>1</td><td>Jun 02, 2026</td><td>OR-001</td><td style="text-align: right;">₱ 5,000.00</td></tr>
                                <tr><td>2</td><td>Jun 15, 2026</td><td>OR-002</td><td style="text-align: right;">₱ 3,000.00</td></tr>
                                <tr><td>3</td><td>Jul 05, 2026</td><td>OR-003</td><td style="text-align: right;">₱ 2,000.00</td></tr>
                                <tr><td>4</td><td>Aug 10, 2026</td><td>OR-004</td><td style="text-align: right;">₱ 2,000.00</td></tr>
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: 700; color: var(--primary-neon); background: #0e1f36; border-top: 2px solid var(--primary-neon);">TOTAL PAID:</td>
                                    <td id="totalPaid" style="text-align: right; font-weight: 800; color: var(--primary-neon); background: #0e1f36; border-top: 2px solid var(--primary-neon);">₱ 12,000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: Assessment -->
                <div class="payment-assess-card">
                    <div class="card-header"><i class="fas fa-calculator"></i> Assessment</div>
                    <div class="table-container">
                        <table class="data-table">
                            <tbody>
                                <tr class="assessment-item"><td>Tuition Fee:</td><td>₱ 14,070.00</td></tr>
                                <tr class="assessment-item"><td>Academic:</td><td>₱ 1,200.00</td></tr>
                                <tr class="assessment-item"><td>Computer:</td><td>₱ 1,850.00</td></tr>
                                <tr class="assessment-item"><td>Misc. Fee:</td><td>₱ 2,100.00</td></tr>
                                <tr class="assessment-item"><td>NSTP:</td><td>₱ 0.00</td></tr>
                                <tr class="assessment-item"><td>Others:</td><td>₱ 420.00</td></tr>
                                <tr class="assessment-total"><td>TOTAL:</td><td>₱ 19,640.00</td></tr>
                                <tr class="assessment-remaining"><td>Balance:</td><td id="remainingBalance">₱ 7,640.00</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 15px;">
                        <button class="btn-pay-tuition" onclick="openPaymentModal()">
                            <i class="fas fa-credit-card"></i> Pay Tuition
                        </button>
                    </div>
                </div>

            </div>

            <!-- ===== SIGNATURES ===== -->
            <div class="signature-box">
                <div class="sig-item">
                    <div class="sig-line"></div>
                    <strong>ENGR. CESAR C. GASPAR</strong>
                    <span>Registrar / Authorized Official</span>
                </div>
                <div class="sig-item">
                    <div class="sig-line" style="border-bottom: none;"></div>
                    <strong>Accounting Officer</strong>
                </div>
                <div class="sig-item">
                    <div class="sig-line"></div>
                    <strong>Almadin Nor Mipanga</strong>
                    <span>Student's Signature</span>
                </div>
            </div>

            <div class="student-copy">( Student's Copy )</div>

            <!-- ===== CONTACT US ===== -->
            <div class="contact-section">
                <div class="contact-header">
                    <i class="fas fa-headset"></i>
                    <div>
                        <h2>Contact Us / Inquire Us</h2>
                        <p>For additional concerns, our customer service team is here to help you.</p>
                    </div>
                </div>

                <div class="contact-grid">
                    <div class="contact-card">
                        <div class="icon-box"><i class="fas fa-envelope-open-text"></i></div>
                        <div class="contact-info">
                            <h4>Email & Landline</h4>
                            <p>Reach us anytime for inquiries regarding enrollment, payments, and student records.</p>
                            <a href="mailto:info@icf.edu.ph" class="btn-contact"><i class="fas fa-envelope"></i> Send Email</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <div class="icon-box"><i class="fas fa-map-marked-alt"></i></div>
                        <div class="contact-info">
                            <h4>Office Address</h4>
                            <p>Visit our registrar or accounting office for in-person assistance.</p>
                            <p style="font-size: 12px; color: var(--primary-neon);"><i class="fas fa-map-pin"></i> Burgos St., Paniqui, Tarlac</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== CUSTOMER SERVICE HANDLER ===== -->
            <div class="support-handler">
                <div class="handler-profile">
                    <div class="handler-avatar"><i class="fas fa-user-tie"></i></div>
                    <div class="handler-details">
                        <h5>Customer Service Handler</h5>
                        <p><i class="fas fa-circle"></i> Available now · Ms. Maria Santos</p>
                        <p style="margin-top: 4px; font-size: 11px; color: var(--text-muted-teal);">Mon-Fri 8:00 AM - 5:00 PM · Sat 8:00 AM - 12:00 PM</p>
                    </div>
                </div>
                <div class="handler-actions">
                    <button class="btn-call" onclick="showAlert('warning', 'Calling Customer Service', 'You are about to call: (045) 470-8645');"><i class="fas fa-phone-alt"></i> Call</button>
                    <button class="btn-chat" onclick="showAlert('success', 'Live Chat', 'Opening Live Chat with Ms. Maria Santos...');"><i class="fas fa-comment-dots"></i> Chat with Us</button>
                </div>
            </div>

        </div>
    </main>

    <!-- ===== PAYMENT MODAL ===== -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            <div class="modal-title"><i class="fas fa-mobile-alt"></i> Pay via GCash</div>

            <div class="form-group">
                <label>Payment Purpose</label>
                <select id="paymentPurpose">
                    <option value="Partial Payment">Partial Payment</option>
                    <option value="Full Payment">Full Payment</option>
                </select>
            </div>

            <div class="form-group">
                <label>Amount (₱)</label>
                <input type="number" id="paymentAmount" placeholder="Enter amount" min="500" step="100">
            </div>

            <div class="form-group">
                <label>GCash Mobile Number</label>
                <input type="tel" id="gcashNumber" placeholder="0917-123-4567" maxlength="13" pattern="[0-9]{4}-[0-9]{3}-[0-9]{4}" oninput="formatGCashNumber(this)">
            </div>

            <button class="btn-pay-confirm" onclick="processPayment()">
                <i class="fas fa-check-circle"></i> Confirm Payment
            </button>
        </div>
    </div>

    <!-- ===== CUSTOM ALERT MODAL ===== -->
    <div class="alert-overlay" id="alertModal">
        <div class="alert-box" id="alertBox">
            <div class="alert-icon" id="alertIcon"></div>
            <div class="alert-title" id="alertTitle"></div>
            <div class="alert-message" id="alertMessage"></div>
            <button class="alert-close-btn" onclick="closeAlert()">OK</button>
        </div>
    </div>

    <script>
        // State Variables
        let totalPaid = 12000;
        let remainingBalance = 7640;
        let paymentCounter = 5;
        let currentDate = new Date().toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric'
        });

        // ===== CUSTOM ALERT FUNCTIONS =====
        function showAlert(type, title, message) {
            const alertModal = document.getElementById('alertModal');
            const alertBox = document.getElementById('alertBox');
            const alertIcon = document.getElementById('alertIcon');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');

            alertBox.classList.remove('success', 'warning');

            if (type === 'success') {
                alertBox.classList.add('success');
                alertIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            } else if (type === 'warning') {
                alertBox.classList.add('warning');
                alertIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            }

            alertTitle.textContent = title;
            alertMessage.textContent = message;

            alertModal.classList.add('active');
        }

        function closeAlert() {
            document.getElementById('alertModal').classList.remove('active');
        }

        // ===== GCASH NUMBER FORMATTING =====
        function formatGCashNumber(input) {
            // Remove all non-digit characters
            let value = input.value.replace(/\D/g, '');
            
            // Limit to 11 digits
            if (value.length > 11) {
                value = value.substring(0, 11);
            }

            // Format as 4-3-4
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue += value.substring(0, 4);
            }
            if (value.length >= 5) {
                formattedValue += '-' + value.substring(4, 7);
            }
            if (value.length >= 8) {
                formattedValue += '-' + value.substring(7, 11);
            }

            input.value = formattedValue;
        }

        // ===== PAYMENT MODAL FUNCTIONS =====
        function openPaymentModal() {
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentAmount').placeholder = 'Max: ₱ ' + remainingBalance.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
            document.getElementById('gcashNumber').value = '';
            document.getElementById('paymentModal').classList.add('active');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }

        // ===== PROCESS PAYMENT =====
        function processPayment() {
            const purpose = document.getElementById('paymentPurpose').value;
            const amountInput = document.getElementById('paymentAmount').value;
            const gcashNum = document.getElementById('gcashNumber').value;

            if (!amountInput || amountInput < 500) {
                showAlert('warning', 'Invalid Amount', 'Please enter a valid amount (minimum ₱500).');
                return;
            }

            // Validate GCash number format (4-3-4, 11 digits)
            const gcashRegex = /^[0-9]{4}-[0-9]{3}-[0-9]{4}$/;
            if (!gcashRegex.test(gcashNum)) {
                showAlert('warning', 'Invalid Mobile Number', 'Please enter a valid GCash number in the format: 0917-123-4567');
                return;
            }

            let amount = parseFloat(amountInput);

            if (purpose === 'Full Payment') {
                amount = remainingBalance;
            }

            if (amount > remainingBalance) {
                showAlert('warning', 'Insufficient Balance', 'Amount exceeds the remaining balance. Please enter a valid amount.');
                return;
            }

            // Update Totals
            totalPaid += amount;
            remainingBalance -= amount;

            // Update UI Totals
            document.getElementById('totalPaid').textContent = '₱ ' + totalPaid.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
            document.getElementById('remainingBalance').textContent = '₱ ' + remainingBalance.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });

            // Add New Payment Row
            const paymentBody = document.getElementById('paymentBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
            <td>${paymentCounter}</td>
            <td>${currentDate}</td>
            <td>OR-00${paymentCounter}</td>
            <td style="text-align: right;">₱ ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
        `;

            const totalRow = paymentBody.lastElementChild;
            paymentBody.insertBefore(newRow, totalRow);

            paymentCounter++;

            // Show Success Alert
            showAlert('success', 'Payment Successful!', 'Payment via GCash (' + gcashNum + ') completed.\nPurpose: ' + purpose + '\nAmount: ₱ ' + amount.toLocaleString('en-US', {
                minimumFractionDigits: 2
            }));

            // Close Payment Modal
            closePaymentModal();
        }
    </script>
</body>
</html>