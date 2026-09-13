<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

/* =====================================================
   PHPMailer auto-detect
   ===================================================== */
$phpmailerLoaded = false;
$possiblePaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
foreach ($possiblePaths as $path) {
    if (file_exists($path)) { require_once $path; $phpmailerLoaded = true; break; }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =====================================================
   HELPERS
   ===================================================== */
function generateStudentCode($conn) {
    $year = date('Y'); $prefix = $year . '-';
    $stmt = $conn->prepare("SELECT username FROM users WHERE username LIKE ? ORDER BY username DESC LIMIT 1");
    $like = $prefix . '%';
    $stmt->bind_param("s", $like); $stmt->execute();
    $result = $stmt->get_result(); $nextNum = 1;
    if ($result->num_rows > 0) {
        $last = $result->fetch_assoc()['username'];
        $parts = explode('-', $last);
        if (isset($parts[1]) && is_numeric($parts[1])) $nextNum = intval($parts[1]) + 1;
    }
    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Send an email via PHPMailer (with native mail() fallback).
 */
function sendMailer($toEmail, $toName, $subject, $htmlBody, $altBody = '') {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'Shanrayeguzman0@gmail.com';
            $mail->Password   = 'beto pzbx zgqk kjcv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('admin@icfpaniqui.edu.ph', 'ICF Enrollment System');
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
        }
    }
    // Fallback
    $h  = "MIME-Version: 1.0\r\n";
    $h .= "Content-type: text/html; charset=UTF-8\r\n";
    $h .= "From: ICF Enrollment System <admin@icfpaniqui.edu.ph>\r\n";
    return mail($toEmail, $subject, $htmlBody, $h);
}

/**
 * Enrollment approval email (uses sendMailer).
 */
function sendEnrollmentApprovalEmail($toEmail, $studentName, $studentId, $password) {
    $subject = 'ICF Enrollment Approved - Student Portal Credentials';
    $htmlBody = "
        <div style='font-family:Arial,sans-serif;color:#0a192f;max-width:600px;margin:0 auto;border:1px solid #172a45;border-radius:12px;overflow:hidden;'>
            <div style='background:#020c1b;padding:20px;text-align:center;'><h2 style='color:#0df5e3;margin:0;'>Welcome to ICF!</h2></div>
            <div style='padding:25px;background:#fff;'>
                <p style='font-size:16px;'>Dear <strong>{$studentName}</strong>,</p>
                <p>Your enrollment application has been <strong style='color:#10b981;'>APPROVED</strong>.</p>
                <p>Student ID: <strong>{$studentId}</strong><br>Password: <strong>{$password}</strong></p>
                <p style='font-size:13px;color:#666;margin-top:20px;border-top:1px solid #e2e8f0;padding-top:15px;'>Please change your password after first login.</p>
            </div>
        </div>";
    return sendMailer($toEmail, $studentName, $subject, $htmlBody);
}

/* =====================================================
   POST: SEND CUSTOM MESSAGE VIA PHPMailer (AJAX)
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $toEmail = trim($_POST['to_email'] ?? '');
    $toName  = trim($_POST['to_name'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Welcome to Interworld Colleges Foundation Inc. - Enrollment Confirmation');
    $body    = trim($_POST['body'] ?? '');

    if ($toEmail === '' || $body === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or empty message.']);
        exit();
    }

    // Convert plain-text newlines to HTML paragraphs for nicer rendering
    $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

    $sent = sendMailer($toEmail, $toName ?: $toEmail, $subject, $htmlBody, $body);

    echo json_encode([
        'success' => $sent,
        'message' => $sent ? 'Message sent successfully.' : 'Failed to send message. Check SMTP settings or logs.'
    ]);
    exit();
}

/* =====================================================
   POST: ACCEPT ENROLLMENT
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_enrollment'])) {
    $id       = intval($_POST['student_id'] ?? 0);
    $username = trim($_POST['portal_username'] ?? '');
    $password = trim($_POST['portal_password'] ?? '');

    if ($id <= 0 || $username === '' || $password === '') {
        header("Location: enrollments.php?approval_error=1&reason=missing"); exit();
    }

    $stmt = $conn->prepare("SELECT * FROM enrolled WHERE student_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows !== 1) { header("Location: enrollments.php?approval_error=1&reason=notfound"); exit(); }
    $enr = $r->fetch_assoc();

    // Subjects
    $subjects = [];
    $subjCodes = $_POST['subj_code'] ?? [];
    for ($i = 0; $i < count($subjCodes); $i++) {
        $code = trim($subjCodes[$i] ?? '');
        if ($code === '') continue;
        $subjects[] = [
            'code'        => $code,
            'description' => trim($_POST['subj_desc'][$i] ?? ''),
            'units'       => (float)($_POST['subj_units'][$i] ?? 0),
            'day_time'    => trim($_POST['subj_day_time'][$i] ?? ''),
            'room'        => trim($_POST['subj_room'][$i] ?? ''),
            'block'       => trim($_POST['subj_block'][$i] ?? '')
        ];
    }
    $subjectsJson = json_encode($subjects);

    // Assessment
    $assessment = [
        'tuition_fee' => trim($_POST['tuition_fee'] ?? '0.00'),
        'academic'    => trim($_POST['academic_fee'] ?? '0.00'),
        'computer'    => trim($_POST['computer_fee'] ?? '0.00'),
        'misc_fee'    => trim($_POST['misc_fee'] ?? '0.00'),
        'nstp'        => trim($_POST['nstp_fee'] ?? '0.00'),
        'others'      => trim($_POST['others_fee'] ?? '0.00'),
        'total'       => trim($_POST['total_assessment'] ?? '0.00')
    ];
    $assessmentJson = json_encode($assessment);

    // Ensure columns
    $conn->query("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS student_code VARCHAR(20) DEFAULT NULL AFTER student_id");
    $conn->query("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS subjects_json TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS assessment_json TEXT DEFAULT NULL");

    $studentCode = generateStudentCode($conn);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt2 = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("sssss", $enr['firstname'], $enr['middlename'], $enr['lastname'], $username, $hashedPassword);

    if ($stmt2->execute()) {
        $stmt3 = $conn->prepare("UPDATE enrolled SET enrollment_status='approved', student_code=?, subjects_json=?, assessment_json=? WHERE student_id=?");
        $stmt3->bind_param("sssi", $studentCode, $subjectsJson, $assessmentJson, $id);
        $stmt3->execute();

        $fullName = $enr['firstname'] . ' ' . $enr['lastname'];
        $emailSent = sendEnrollmentApprovalEmail($enr['email'], $fullName, $studentCode, $password);

        // Store info for the follow-up Send Message modal
        $_SESSION['msg_student_id'] = $studentCode;
        $_SESSION['msg_full_name']  = $fullName;
        $_SESSION['msg_course']     = $enr['course'];
        $_SESSION['msg_email']      = $enr['email'];
        $_SESSION['msg_username']   = $username;
        $_SESSION['msg_password']   = $password;
        $_SESSION['msg_display_id'] = $id;
        $_SESSION['show_send_message'] = true;

        header("Location: enrollments.php?enrollment_approved=1" . ($emailSent ? "" : "&email_error=1"));
        exit();
    }

    header("Location: enrollments.php?approval_error=1&reason=db"); exit();
}

/* =====================================================
   GET: DECLINE ENROLLMENT
   ===================================================== */
if (isset($_GET['decline_enrollment'])) {
    $id = intval($_GET['decline_enrollment']);
    $stmt = $conn->prepare("UPDATE enrolled SET enrollment_status = 'declined' WHERE student_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    header("Location: enrollments.php?enrollment_declined=1"); exit();
}

/* =====================================================
   FETCH PENDING ENROLLMENTS
   ===================================================== */
$pendingEnrollments = [];
$hasStatusCol = false;
$colCheck = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'enrollment_status'");
if ($colCheck && $colCheck->num_rows > 0) $hasStatusCol = true;

if ($hasStatusCol) {
    $penRes = $conn->query("SELECT * FROM enrolled WHERE enrollment_status = 'pending' ORDER BY created_at DESC");
    if ($penRes) while ($row = $penRes->fetch_assoc()) $pendingEnrollments[] = $row;
}

/* =====================================================
   SUMMARY COUNTS
   ===================================================== */
$pendingCount = count($pendingEnrollments);
$approvedCount = 0; $declinedCount = 0;
if ($hasStatusCol) {
    $r = $conn->query("SELECT COUNT(*) as t FROM enrolled WHERE enrollment_status='approved'");
    if ($r) $approvedCount = $r->fetch_assoc()['t'];
    $r = $conn->query("SELECT COUNT(*) as t FROM enrolled WHERE enrollment_status='declined'");
    if ($r) $declinedCount = $r->fetch_assoc()['t'];
}
$totalEnrolled = 0;
$r = $conn->query("SELECT COUNT(*) as t FROM enrolled");
if ($r) $totalEnrolled = $r->fetch_assoc()['t'];

/* Send message session data */
$showSendMessage = !empty($_SESSION['show_send_message']);
$msgStudentID = $_SESSION['msg_student_id'] ?? '';
$msgFullName  = $_SESSION['msg_full_name']  ?? '';
$msgCourse    = $_SESSION['msg_course']     ?? '';
$msgEmail     = $_SESSION['msg_email']      ?? '';
$msgUsername  = $_SESSION['msg_username']   ?? '';
$msgPassword  = $_SESSION['msg_password']   ?? '';
$msgDisplayID = $_SESSION['msg_display_id'] ?? 0;
if ($showSendMessage) { unset($_SESSION['show_send_message']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel - Enrollments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    /* ===== SUMMARY CARDS ===== */
    .cards-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin-bottom:30px; }
    .card { background:var(--text-high-contrast); padding:20px; border-radius:8px; box-shadow:0 0px 5px var(--primary-neon); }
    .card h3 { font-size:0.85rem; color:var(--bg-deep-abyss); text-transform:uppercase; margin-bottom:8px; }
    .card-value { font-size:1.8rem; font-weight:bold; color:var(--bg-deep-abyss); }

    /* ===== MODAL OVERRIDES ===== */
    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); justify-content:center; align-items:center; padding:20px; z-index:2000; }
    .modal-overlay.active { display:flex; }
    .modal-container { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:1100px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:30px; color:var(--text-high-contrast); position:relative; animation:modalFadeIn 0.3s ease; }
    .modal-container::-webkit-scrollbar { width:10px; height:10px; }
    .modal-container::-webkit-scrollbar-track { background:var(--bg-deep-abyss); border-radius:10px; }
    .modal-container::-webkit-scrollbar-thumb { background:var(--border-teal); border-radius:10px; border:2px solid var(--bg-deep-abyss); }
    .modal-container::-webkit-scrollbar-thumb:hover { background:var(--primary-neon); }
    @keyframes modalFadeIn { from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }

    .view-modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .view-modal-section { margin-bottom:18px; }
    .view-modal-section h4 { color:var(--primary-neon); font-size:0.95rem; margin-bottom:10px; border-bottom:1px solid var(--border-teal); padding-bottom:6px; }
    .view-modal-row { display:flex; margin-bottom:8px; font-size:0.88rem; }
    .view-modal-label { width:140px; color:var(--text-muted-teal); font-weight:600; flex-shrink:0; }
    .view-modal-value { color:var(--text-high-contrast); flex:1; word-break:break-word; }
    @media (max-width:600px) { .view-modal-grid { grid-template-columns:1fr; } }

    /* ===== COR HEADER ===== */
    .cor-header { text-align:center; margin-bottom:25px; padding-bottom:20px; border-bottom:2px solid var(--border-teal); }
    .cor-header-top { display:flex; align-items:center; justify-content:center; gap:15px; margin-bottom:10px; }
    .cor-logo { width:70px; height:70px; object-fit:contain; }
    .cor-school-name { font-size:24px; font-weight:800; color:var(--text-high-contrast); text-transform:uppercase; letter-spacing:1px; }
    .cor-address { font-size:13px; color:var(--text-muted-teal); margin-top:4px; display:flex; align-items:center; gap:6px; justify-content:center; }
    .cor-address i { color:var(--primary-neon); }
    .cor-title { font-size:22px; font-weight:700; color:var(--primary-neon); text-transform:uppercase; letter-spacing:2px; margin-top:15px; }
    .cor-session { font-size:14px; color:var(--text-muted-teal); margin-top:5px; font-style:italic; }

    /* ===== Student Info Grid ===== */
    .student-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:25px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:8px; padding:20px; }
    .student-info-left, .student-info-right { display:flex; flex-direction:column; gap:12px; }
    .student-info-right { align-items:flex-end; text-align:right; }
    .info-line { font-size:14px; color:var(--text-muted-teal); }
    .info-line strong { color:var(--text-high-contrast); font-weight:600; }
    .info-line strong i { color:var(--primary-neon); margin-right:8px; }
    .info-line .highlight { color:var(--primary-neon); font-weight:700; }

    /* ===== Modal Sections ===== */
    .modal-sections { display:flex; flex-direction:column; gap:30px; margin-bottom:20px; }
    .section-title { font-size:16px; font-weight:700; color:var(--text-high-contrast); text-transform:uppercase; border-bottom:2px solid var(--primary-neon); padding-bottom:8px; margin-bottom:15px; display:flex; align-items:center; gap:8px; }
    .section-title i { color:var(--primary-neon); }

    /* ===== Student Portal Account ===== */
    .student-portal-section { background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:8px; padding:20px; margin-bottom:30px; }
    .student-portal-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; }
    .portal-form-group { margin-bottom:10px; }
    .portal-form-group label { display:block; font-size:12px; color:var(--text-muted-teal); font-weight:600; margin-bottom:6px; text-transform:uppercase; }
    .portal-form-group input { width:100%; padding:10px; background:var(--bg-card); border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; color:var(--text-high-contrast); outline:none; transition:border-color 0.2s ease; }
    .portal-form-group input:focus { border-color:var(--primary-neon); }

    /* ===== Subject Table ===== */
    .subject-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
    .subject-table th, .subject-table td { padding:10px 8px; border:1px solid var(--border-teal); text-align:left; }
    .subject-table th { background-color:var(--bg-deep-abyss); color:var(--primary-neon); font-weight:600; }
    .subject-table input, .subject-table select { width:100%; padding:6px; background:var(--bg-card); border:1px solid var(--border-teal); border-radius:4px; font-size:0.85rem; color:var(--text-high-contrast); outline:none; }
    .subject-table input:focus, .subject-table select:focus { border-color:var(--primary-neon); }
    .subject-table tbody tr:hover { background-color:var(--bg-card-hover); }
    .btn-add-subject { color:var(--primary-neon); background-color:transparent; border:none; width:40px; height:40px; font-size:18px; font-weight:bold; cursor:pointer; margin-left:auto; display:flex; align-items:center; justify-content:center; transition:all 0.2s ease; }
    .btn-add-subject:hover { transform:scale(1.4); }

    /* ===== Assessment Table ===== */
    .assessment-table { width:100%; max-width:600px; border-collapse:collapse; font-size:0.85rem; background-color:var(--bg-deep-abyss); }
    .assessment-table td { padding:10px 8px; border-bottom:1px solid var(--border-teal); color:var(--text-high-contrast); }
    .assessment-table td:last-child { width:120px; }
    .assessment-table input { width:100%; padding:6px; background:var(--bg-card); border:1px solid var(--border-teal); border-radius:4px; font-size:0.85rem; color:var(--text-high-contrast); text-align:left; outline:none; }
    .assessment-table input:focus { border-color:var(--primary-neon); }
    .assessment-table tbody tr:hover { background-color:var(--bg-card-hover); }
    .assessment-table .total-row td { font-weight:800; font-size:16px; border-top:2px solid var(--primary-neon); padding-top:12px; }
    .assessment-table .total-row td:last-child { color:var(--primary-neon); }

    /* ===== Modal Footer ===== */
    .modal-footer { display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--border-teal); padding-top:20px; }
    .btn-cancel { background:var(--bg-card); color:var(--text-high-contrast); border:1px solid var(--border-teal); padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; }
    .btn-cancel:hover { background:var(--bg-card-hover); }
    .btn-confirm-accept { background:var(--success-green); color:white; border:none; padding:10px 25px; border-radius:6px; cursor:pointer; font-weight:700; }
    .btn-confirm-accept:hover { background:#059669; }

    /* ===== Send Message Modal ===== */
    .modal-overlay-sm { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:3000; justify-content:center; align-items:center; padding:20px; }
    .modal-overlay-sm.active { display:flex; }
    .modal-container-sm { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:30px; color:var(--text-high-contrast); position:relative; animation:modalFadeIn 0.3s ease; }
    .modal-header-sm { border-bottom:2px solid var(--primary-neon); padding-bottom:10px; margin-bottom:20px; }
    .modal-header-sm h3 { font-size:18px; color:var(--primary-neon); display:flex; align-items:center; gap:10px; }
    .message-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
    .message-info-item { display:flex; flex-direction:column; }
    .message-info-item label { font-size:11px; color:var(--text-muted-teal); font-weight:600; text-transform:uppercase; margin-bottom:4px; }
    .message-info-item input { padding:8px; border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; background:var(--bg-deep-abyss); color:var(--text-high-contrast); outline:none; }
    .message-form-group { margin-bottom:15px; }
    .message-form-group label { display:block; font-size:12px; color:var(--text-muted-teal); font-weight:600; margin-bottom:6px; text-transform:uppercase; }
    .message-form-group textarea { width:100%; padding:12px; border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; font-family:inherit; resize:vertical; min-height:250px; outline:none; line-height:1.6; background:var(--bg-deep-abyss); color:var(--text-high-contrast); }
    .message-form-group textarea:focus { border-color:var(--primary-neon); }
    .modal-footer-sm { display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--border-teal); padding-top:15px; }
    .btn-send-email { background:var(--primary-neon); color:var(--bg-deep-abyss); border:none; padding:10px 25px; border-radius:6px; cursor:pointer; font-weight:700; display:inline-flex; align-items:center; gap:8px; transition:background 0.2s ease; }
    .btn-send-email:hover { background:var(--primary-neon-hover); }
    .btn-send-email:disabled { opacity:0.6; cursor:not-allowed; }

    /* ===== Success modal ===== */
    .success-modal-body { text-align:center; padding:30px 20px; }
    .success-icon { font-size:3rem; color:var(--success-green); margin-bottom:15px; }
    .success-modal-body h4 { font-size:1.2rem; color:var(--text-high-contrast); margin-bottom:8px; }
    .success-modal-body p { font-size:0.9rem; color:var(--text-muted-teal); }

    @media (max-width:768px) {
      .student-info-grid { grid-template-columns:1fr; }
      .student-info-right { align-items:flex-start; text-align:left; }
      .assessment-table { max-width:100%; }
      .student-portal-grid { grid-template-columns:1fr; }
      .message-info-grid { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div>
      <div class="brand"><h2>Admin Panel</h2></div>
      <ul class="nav-links">
        <li><a href="adminpanel.php">Dashboard</a></li>
        <li><a href="users.php">Users</a></li>
        <li class="active"><a href="enrollments.php">Enrollments</a></li>
        <li><a href="enrolled.php">Enrolled</a></li>
        <li><a href="instructor.php">Instructors</a></li>
        <li><a href="curriculum.php">Curriculum</a></li>
        <li><a href="received-mail.php">Received Mail</a></li>
      </ul>
    </div>
    <div class="sidebar-footer">
      <a href="../../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
      <a href="logout.php" class="btn-homepage" style="margin-top:10px; border-color:#ef4444; color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Enrollment Management</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="userSearchInput" placeholder="Search applicants..." autocomplete="off"/>
          <div id="searchResults" class="search-results-dropdown"></div>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <div class="cards-grid">
      <div class="card"><h3>Pending Requests</h3><div class="card-value"><?php echo $pendingCount; ?></div></div>
      <div class="card"><h3>Approved Enrollments</h3><div class="card-value"><?php echo $approvedCount; ?></div></div>
      <div class="card"><h3>Declined Requests</h3><div class="card-value"><?php echo $declinedCount; ?></div></div>
      <div class="card"><h3>Total Records</h3><div class="card-value"><?php echo $totalEnrolled; ?></div></div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-user-graduate"></i> Pending Enrollment Requests</h2>
        <div class="table-actions">
          <form method="GET" style="display:inline;">
            <input type="text" name="search" class="section-search" placeholder="Search applicants..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width:220px;">
          </form>
          <a href="create.php"><button class="btn primary">+ New Enrollment</button></a>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="pendingEnrollmentTable">
          <thead>
            <tr><th>Student Name</th><th>Course</th><th>Email</th><th>Date Submitted</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (count($pendingEnrollments) > 0): ?>
              <?php foreach ($pendingEnrollments as $pen): ?>
              <tr data-record='<?php echo htmlspecialchars(json_encode($pen), ENT_QUOTES, "UTF-8"); ?>'>
                <td><?php echo htmlspecialchars($pen['firstname'] . ' ' . $pen['lastname']); ?></td>
                <td><?php echo htmlspecialchars($pen['course']); ?></td>
                <td><?php echo htmlspecialchars($pen['email']); ?></td>
                <td><?php echo htmlspecialchars($pen['created_at'] ?? 'N/A'); ?></td>
                <td>
                  <div class="action-group">
                    <button class="btn-action" onclick="openEnrollmentViewModal(this)" style="border-color:#10b981; color:#10b981;">
                      <i class="fa-solid fa-eye"></i> View
                    </button>
                    <button class="btn-action btn-send" onclick="openAcceptModal(this)">
                      <i class="fa-solid fa-check"></i> Approve
                    </button>
                    <a href="enrollments.php?decline_enrollment=<?php echo $pen['student_id']; ?>"
                       onclick="return confirm('Decline this enrollment request?')">
                      <button class="btn-action decline"><i class="fa-solid fa-xmark"></i> Decline</button>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" style="text-align:center; color:#666;">No pending enrollment requests</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!$hasStatusCol): ?>
    <div style="background:#fef3c7; color:#92400e; padding:12px 16px; border-radius:8px; margin-top:20px; font-size:0.9rem;">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <strong>Note:</strong> The <code>enrollment_status</code> column was not found in <code>enrolled</code>.
    </div>
    <?php endif; ?>
  </main>

  <!-- ============================================================= -->
  <!-- ===== ACCEPT ENROLLMENT MODAL (STUDENT SUBJECT + TUITION) === -->
  <!-- ============================================================= -->
  <div class="modal-overlay" id="acceptModal">
    <form class="modal-container" method="POST" action="enrollments.php" id="acceptForm">
      <input type="hidden" name="accept_enrollment" value="1">
      <input type="hidden" name="student_id" id="acceptStudentId" value="">
      <button type="button" class="modal-close" onclick="closeAcceptModal()" style="position:absolute;top:15px;right:20px;background:transparent;border:none;color:var(--text-muted-teal);font-size:28px;cursor:pointer;">&times;</button>

      <div class="cor-header">
        <div class="cor-header-top">
          <img src="../BackGroundimage/ICFLogo.png" alt="School Logo" class="cor-logo">
          <div>
            <div class="cor-school-name">Interworld Colleges Foundation Inc.</div>
            <div class="cor-address"><i class="fas fa-map-marker-alt"></i> Burgos St., Paniqui, Tarlac · Tel No. (045) 470-8645</div>
          </div>
        </div>
        <div class="cor-title">STUDENT SUBJECT AND TUITION ALLOCATION</div>
        <div class="cor-session">2026-2027 — 1st Semester</div>
      </div>

      <div class="student-info-grid">
        <div class="student-info-left">
          <div class="info-line"><strong><i class="fas fa-id-card"></i> Student ID:</strong> <span class="highlight" id="acceptStudentID">-</span></div>
          <div class="info-line"><strong><i class="fas fa-user"></i> Name:</strong> <span id="acceptStudentName">-</span></div>
          <div class="info-line"><strong><i class="fas fa-book-open"></i> Course:</strong> <span id="acceptStudentCourse">-</span></div>
        </div>
        <div class="student-info-right">
          <div class="info-line"><strong><i class="fas fa-graduation-cap"></i> Year Level:</strong> <span id="acceptStudentYear">3rd Year</span></div>
          <div class="info-line"><strong><i class="fas fa-print"></i> Date Printed:</strong> <span id="acceptStudentDate">-</span></div>
        </div>
      </div>

      <div class="modal-sections">
        <div class="student-portal-section">
          <div class="section-title"><i class="fas fa-user-shield"></i> Student Portal Account</div>
          <div class="student-portal-grid">
            <div class="portal-form-group"><label>Student ID</label><input type="text" id="portalStudentID" readonly /></div>
            <div class="portal-form-group"><label>Username</label><input type="text" name="portal_username" id="portalUsername" placeholder="Enter username" required /></div>
            <div class="portal-form-group"><label>Password</label><input type="text" name="portal_password" id="portalPassword" placeholder="Enter password" required /></div>
            <div class="portal-form-group"><label>Full Name</label><input type="text" id="portalFullName" readonly /></div>
          </div>
        </div>

        <div class="subject-assignment-section">
          <div class="section-title">
            <i class="fas fa-book-open"></i> Assign Subjects
            <button type="button" class="btn-add-subject" onclick="addSubjectRow()" title="Add Subject"><i class="fas fa-plus"></i></button>
          </div>
          <table class="subject-table" id="subjectTable">
            <thead>
              <tr><th>Code</th><th>Description</th><th>Units</th><th>Day/Time</th><th>Room</th><th>Block</th></tr>
            </thead>
            <tbody id="subjectBody">
              <tr>
                <td><input type="text" name="subj_code[]" required /></td>
                <td><input type="text" name="subj_desc[]" required /></td>
                <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
                <td><input type="text" name="subj_day_time[]" required /></td>
                <td><input type="text" name="subj_room[]" /></td>
                <td><input type="text" name="subj_block[]" required /></td>
              </tr>
              <tr>
                <td><input type="text" name="subj_code[]" required /></td>
                <td><input type="text" name="subj_desc[]" required /></td>
                <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
                <td><input type="text" name="subj_day_time[]" required /></td>
                <td><input type="text" name="subj_room[]" /></td>
                <td><input type="text" name="subj_block[]" required /></td>
              </tr>
              <tr style="background:var(--bg-deep-abyss)">
                <td colspan="2" style="text-align:right; font-weight:700; color:var(--text-high-contrast);">TOTAL UNITS</td>
                <td id="totalUnitsDisplay" style="font-weight:800; color:var(--primary-neon);">0.0</td>
                <td colspan="3"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="assessment-section">
          <div class="section-title"><i class="fas fa-calculator"></i> Assessment Details</div>
          <table class="assessment-table">
            <tbody>
              <tr><td><strong>Tuition Fee:</strong></td><td><input type="text" name="tuition_fee" id="tuitionFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Academic:</strong></td><td><input type="text" name="academic_fee" id="academicFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Computer:</strong></td><td><input type="text" name="computer_fee" id="computerFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Misc. Fee:</strong></td><td><input type="text" name="misc_fee" id="miscFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>NSTP:</strong></td><td><input type="text" name="nstp_fee" id="nstpFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Others:</strong></td><td><input type="text" name="others_fee" id="othersFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr class="total-row">
                <td>TOTAL:</td>
                <td><span id="totalAssessment" style="color:var(--primary-neon); font-size:18px;">₱ 0.00</span></td>
              </tr>
            </tbody>
          </table>
          <input type="hidden" name="total_assessment" id="totalAssessmentHidden" value="₱ 0.00">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeAcceptModal()">Cancel</button>
        <button type="submit" class="btn-confirm-accept"><i class="fas fa-check-circle"></i> Confirm Acceptance</button>
      </div>
    </form>
  </div>

  <!-- ============================================================= -->
  <!-- ================ SEND MESSAGE MODAL (PHPMailer) ============= -->
  <!-- ============================================================= -->
  <div class="modal-overlay-sm" id="sendMessageModal">
    <div class="modal-container-sm">
      <button type="button" class="modal-close" onclick="closeSendMessageModal()" style="position:absolute;top:15px;right:20px;background:transparent;border:none;color:var(--text-muted-teal);font-size:28px;cursor:pointer;">&times;</button>
      <div class="modal-header-sm">
        <h3><i class="fas fa-envelope-open-text"></i> Send Message to a Student</h3>
      </div>
      <div class="message-info-grid">
        <div class="message-info-item"><label>Student ID</label><input type="text" id="msgStudentID" readonly /></div>
        <div class="message-info-item"><label>Full Name</label><input type="text" id="msgFullName" readonly /></div>
        <div class="message-info-item"><label>Course/Year</label><input type="text" id="msgCourseYear" readonly /></div>
        <div class="message-info-item"><label>Email</label><input type="email" id="msgEmail" readonly /></div>
      </div>
      <div class="message-form-group">
        <label>Subject</label>
        <input type="text" id="msgSubject" value="Welcome to Interworld Colleges Foundation Inc. - Enrollment Confirmation" style="width:100%;padding:10px;background:var(--bg-deep-abyss);border:1px solid var(--border-teal);border-radius:4px;color:var(--text-high-contrast);font-size:0.9rem;outline:none;margin-bottom:12px;" />
      </div>
      <div class="message-form-group">
        <label>Message</label>
        <textarea id="msgBody"></textarea>
      </div>
      <div class="modal-footer-sm">
        <button type="button" class="btn-cancel" onclick="closeSendMessageModal()">Close</button>
        <button type="button" class="btn-send-email" id="sendEmailBtn" onclick="sendEmailToStudent()">
          <i class="fas fa-paper-plane"></i> <span>Send Message</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ============================================================= -->
  <!-- ============= VIEW APPLICATION MODAL ======================== -->
  <!-- ============================================================= -->
  <div class="modal-overlay" id="viewEnrollmentModal">
    <div class="modal-container" style="max-width:700px; max-height:85vh; overflow-y:auto;">
      <div class="modal-header" style="background:var(--bg-deep-abyss);padding:16px 20px;border-bottom:1px solid var(--border-teal);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="color:var(--primary-neon);font-size:1.1rem;"><i class="fa-solid fa-id-card"></i> Enrollment Application Details</h3>
        <button class="modal-close-btn" onclick="closeEnrollmentViewModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="enrollmentViewModalBody" style="padding:20px;"></div>
      <div class="modal-footer"><button type="button" class="btn primary" onclick="closeEnrollmentViewModal()">Close</button></div>
    </div>
  </div>

  <!-- ============================================================= -->
  <!-- ================ SUCCESS MODAL ============================== -->
  <!-- ============================================================= -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-container" style="max-width:400px;">
      <div class="modal-header" style="background:var(--bg-deep-abyss);padding:16px 20px;border-bottom:1px solid var(--border-teal);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="color:var(--primary-neon);font-size:1.1rem;">Success</h3>
        <button class="modal-close-btn" onclick="toggleSuccessModal(false)"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body success-modal-body">
        <i class="fa-solid fa-circle-check success-icon"></i>
        <h4 id="successModalTitle">Action Completed</h4>
        <p id="successModalBody">The action was completed successfully.</p>
      </div>
      <div class="modal-footer"><button type="button" class="btn primary" onclick="toggleSuccessModal(false)">OK</button></div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    /* ============================================================
       MONEY FORMATTER
       ============================================================ */
    function formatMoneyInput(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length > 7) value = value.slice(0, 7);
      if (value === '') { input.value = ''; calculateTotal(); return; }
      if (value.length <= 2) { input.value = value; calculateTotal(); return; }
      let whole = value.slice(0, -2);
      let decimal = value.slice(-2);
      whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      input.value = whole + '.' + decimal;
      calculateTotal();
    }

    function calculateTotal() {
      const feeInputs = ['tuitionFee', 'academicFee', 'computerFee', 'miscFee', 'nstpFee', 'othersFee'];
      let total = 0;
      feeInputs.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        let val = el.value.replace(/,/g, '');
        if (val.includes('.')) total += parseFloat(val) || 0;
        else if (val.length > 0) total += parseFloat(val) / 100 || 0;
      });
      const formatted = '₱ ' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
      document.getElementById('totalAssessment').textContent = formatted;
      document.getElementById('totalAssessmentHidden').value = formatted;
    }

    /* ============================================================
       UNITS CALCULATOR
       ============================================================ */
    function calculateTotalUnits() {
      let total = 0;
      document.querySelectorAll('.units-input').forEach(i => total += parseFloat(i.value) || 0);
      const el = document.getElementById('totalUnitsDisplay');
      if (el) el.textContent = total.toFixed(1);
    }
    function attachUnitListeners() {
      document.querySelectorAll('.units-input').forEach(i => {
        i.removeEventListener('input', calculateTotalUnits);
        i.addEventListener('input', calculateTotalUnits);
      });
    }

    function addSubjectRow() {
      const tbody = document.getElementById('subjectBody');
      const totalRow = tbody.lastElementChild;
      const newRow = document.createElement('tr');
      newRow.innerHTML = `
        <td><input type="text" name="subj_code[]" required /></td>
        <td><input type="text" name="subj_desc[]" required /></td>
        <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
        <td><input type="text" name="subj_day_time[]" required /></td>
        <td><input type="text" name="subj_room[]" /></td>
        <td><input type="text" name="subj_block[]" required /></td>`;
      tbody.insertBefore(newRow, totalRow);
      attachUnitListeners();
      calculateTotalUnits();
    }

    /* ============================================================
       ACCEPT MODAL
       ============================================================ */
    function openAcceptModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;

      document.getElementById('acceptStudentId').value = data.student_id;
      document.getElementById('acceptStudentID').textContent = '#' + data.student_id;
      document.getElementById('acceptStudentName').textContent = (data.firstname || '') + ' ' + (data.lastname || '');
      document.getElementById('acceptStudentCourse').textContent = data.course || 'N/A';
      document.getElementById('acceptStudentYear').textContent = '3rd Year';
      document.getElementById('acceptStudentDate').textContent = new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'});

      document.getElementById('portalStudentID').value = data.student_id;
      document.getElementById('portalFullName').value = (data.firstname || '') + ' ' + (data.lastname || '');
      document.getElementById('portalUsername').value = '';
      document.getElementById('portalPassword').value = '';

      const tbody = document.getElementById('subjectBody');
      tbody.innerHTML = `
        <tr>
          <td><input type="text" name="subj_code[]" required /></td>
          <td><input type="text" name="subj_desc[]" required /></td>
          <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
          <td><input type="text" name="subj_day_time[]" required /></td>
          <td><input type="text" name="subj_room[]" /></td>
          <td><input type="text" name="subj_block[]" required /></td>
        </tr>
        <tr>
          <td><input type="text" name="subj_code[]" required /></td>
          <td><input type="text" name="subj_desc[]" required /></td>
          <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
          <td><input type="text" name="subj_day_time[]" required /></td>
          <td><input type="text" name="subj_room[]" /></td>
          <td><input type="text" name="subj_block[]" required /></td>
        </tr>
        <tr style="background:var(--bg-deep-abyss)">
          <td colspan="2" style="text-align:right; font-weight:700; color:var(--text-high-contrast);">TOTAL UNITS</td>
          <td id="totalUnitsDisplay" style="font-weight:800; color:var(--primary-neon);">0.0</td>
          <td colspan="3"></td>
        </tr>`;
      attachUnitListeners();
      calculateTotalUnits();

      ['tuitionFee','academicFee','computerFee','miscFee','nstpFee','othersFee'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      calculateTotal();

      document.getElementById('acceptModal').classList.add('active');
    }

    function closeAcceptModal() { document.getElementById('acceptModal').classList.remove('active'); }

    /* ============================================================
       VIEW APPLICATION MODAL
       ============================================================ */
    function openEnrollmentViewModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;
      const sections = [
        { title: 'Personal Information', fields: [
            ['First Name', data.firstname], ['Middle Name', data.middlename], ['Last Name', data.lastname],
            ['Suffix', data.suffix || 'N/A'], ['Gender', data.gender], ['Birthday', data.birthday],
            ['Birthplace', data.birthplace], ['Citizenship', data.citizenship], ['Civil Status', data.civilstatus], ['Employment', data.employment]
        ]},
        { title: 'Family / Guardian', fields: [
            ["Mother's Name", data.mother], ["Mother's Phone", data.mphone_number],
            ["Father's Name", data.father], ["Father's Phone", data.fphone_number],
            ["Guardian's Name", data.guardian], ["Guardian's Phone", data.gphone_number]
        ]},
        { title: 'Academic Information', fields: [
            ['Course', data.course], ['Major', data.major], ['School Address', data.school_address],
            ['Academic Year', data.academic_year], ['Scholarship', data.scholarship]
        ]},
        { title: 'Contact & Address', fields: [
            ['Full Address', data.full_address], ['Mobile Number', data.mobile_number], ['Email', data.email]
        ]}
      ];
      let html = '';
      sections.forEach(sec => {
        html += `<div class="view-modal-section"><h4>${sec.title}</h4><div class="view-modal-grid">`;
        sec.fields.forEach(([label, value]) => {
          html += `<div class="view-modal-row"><span class="view-modal-label">${label}:</span><span class="view-modal-value">${(value !== null && value !== undefined && String(value).trim() !== '') ? value : 'N/A'}</span></div>`;
        });
        html += '</div></div>';
      });
      document.getElementById('enrollmentViewModalBody').innerHTML = html;
      document.getElementById('viewEnrollmentModal').classList.add('active');
    }
    function closeEnrollmentViewModal() { document.getElementById('viewEnrollmentModal').classList.remove('active'); }
    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) { if (e.target === this) closeEnrollmentViewModal(); });

    /* ============================================================
       SEND MESSAGE MODAL — sends via PHPMailer (AJAX)
       ============================================================ */
    function openSendMessageModal() {
      const sid = <?php echo json_encode($msgStudentID); ?>;
      const fullName = <?php echo json_encode($msgFullName); ?>;
      const course = <?php echo json_encode($msgCourse); ?>;
      const email = <?php echo json_encode($msgEmail); ?>;
      const username = <?php echo json_encode($msgUsername); ?>;
      const password = <?php echo json_encode($msgPassword); ?>;
      if (!sid) return;

      document.getElementById('msgStudentID').value = sid;
      document.getElementById('msgFullName').value = fullName;
      let courseAcronym = course.split(' ').map(w => w[0]).join('').toUpperCase().replace('101','');
      document.getElementById('msgCourseYear').value = courseAcronym + ' - 3rd Year';
      document.getElementById('msgEmail').value = email;

      const message = `Dear ${fullName},

We are pleased to inform you that you have been officially enrolled at Interworld Colleges Foundation Inc. for the 1st Semester of Academic Year 2026-2027.

Course: ${course}
Student ID: ${sid}

Your Student Portal Account has been created with the following credentials:
Username: ${username}
Password: ${password}
Our system uses a Default Password so for security privacy measures we suggest you to replace the current password.

Please make sure to keep this information secure. You will need this to access the Student Portal.

We look forward to your first day of school and wish you a successful academic year ahead!

Best regards,
Admissions Office
ICF Interworld Colleges Foundation Inc.`;

      document.getElementById('msgBody').value = message;
      document.getElementById('sendMessageModal').classList.add('active');
    }

    function closeSendMessageModal() { document.getElementById('sendMessageModal').classList.remove('active'); }

    /**
     * Sends the message via PHPMailer using AJAX POST.
     */
    async function sendEmailToStudent() {
      const btn = document.getElementById('sendEmailBtn');
      const btnSpan = btn.querySelector('span');
      const originalText = btnSpan.textContent;

      const toEmail = document.getElementById('msgEmail').value.trim();
      const toName  = document.getElementById('msgFullName').value.trim();
      const subject = document.getElementById('msgSubject').value.trim();
      const body    = document.getElementById('msgBody').value;

      if (!toEmail || !body) { alert('⚠️ Email or message is empty.'); return; }

      btn.disabled = true;
      btnSpan.textContent = 'Sending…';

      try {
        const fd = new FormData();
        fd.append('send_message_ajax', '1');
        fd.append('to_email', toEmail);
        fd.append('to_name', toName);
        fd.append('subject', subject);
        fd.append('body', body);

        const res = await fetch('enrollments.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          closeSendMessageModal();
          toggleSuccessModal(true, 'Message Sent!', 'The message was sent successfully via PHPMailer.');
        } else {
          alert('❌ ' + (data.message || 'Failed to send email.'));
        }
      } catch (err) {
        console.error(err);
        alert('❌ Something went wrong while sending the email.');
      } finally {
        btn.disabled = false;
        btnSpan.textContent = originalText;
      }
    }

    /* ============================================================
       SUCCESS MODAL
       ============================================================ */
    function toggleSuccessModal(show, title, body) {
      if (title) document.getElementById('successModalTitle').textContent = title;
      if (body) document.getElementById('successModalBody').textContent = body;
      document.getElementById('successModal').classList.toggle('active', show);
    }
    document.getElementById('successModal').addEventListener('click', function(e) { if (e.target === this) toggleSuccessModal(false); });

    /* ============================================================
       INIT
       ============================================================ */
    document.addEventListener('DOMContentLoaded', () => {
      attachUnitListeners();
      calculateTotalUnits();
    });

    <?php if (isset($_GET['enrollment_approved'])): ?>
      toggleSuccessModal(true, 'Enrollment Approved!', 'The student account has been created and credentials have been emailed.<?php echo isset($_GET["email_error"]) ? " (Warning: Email failed to send.)" : ""; ?>');
      <?php if ($showSendMessage): ?>
        setTimeout(openSendMessageModal, 400);
      <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['enrollment_declined'])): ?>
      toggleSuccessModal(true, 'Enrollment Declined', 'The enrollment request has been declined.');
    <?php endif; ?>
    <?php if (isset($_GET['approval_error'])): ?>
      toggleSuccessModal(true, 'Approval Failed', 'Something went wrong while processing the enrollment. (Reason: <?php echo htmlspecialchars($_GET["reason"] ?? "unknown"); ?>)');
    <?php endif; ?>
  </script>
</body>
</html>