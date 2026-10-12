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
    $h  = "MIME-Version: 1.0\r\n";
    $h .= "Content-type: text/html; charset=UTF-8\r\n";
    $h .= "From: ICF Enrollment System <admin@icfpaniqui.edu.ph>\r\n";
    return mail($toEmail, $subject, $htmlBody, $h);
}

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

    $subjects = [];
    $subjCodes = $_POST['subj_code'] ?? [];
    for ($i = 0; $i < count($subjCodes); $i++) {
        $code = trim($subjCodes[$i] ?? '');
        if ($code === '') continue;

        // Read separate Date + Time text inputs (free-form) and combine
        // into a `day_time` string for backwards compatibility with
        // profile.php / enrolled.php viewers.
        $subjDate = trim($_POST['subj_date'][$i] ?? '');
        $subjTime = trim($_POST['subj_time'][$i] ?? '');
        $dayTime  = trim($subjDate . ($subjDate && $subjTime ? ' ' : '') . $subjTime);

        $subjects[] = [
            'code'        => $code,
            'description' => trim($_POST['subj_desc'][$i] ?? ''),
            'units'       => (float)($_POST['subj_units'][$i] ?? 0),
            'day_time'    => $dayTime,
            'date'        => $subjDate,
            'time'        => $subjTime,
            'room'        => trim($_POST['subj_room'][$i] ?? ''),
            'block'       => trim($_POST['subj_block'][$i] ?? '')
        ];
    }
    $subjectsJson = json_encode($subjects);

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
  <title>Enrollment Management | ICF Interworld Colleges Foundation Inc.</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    /* ============================================
       ICF Admin Panel — Collapsible Sidebar Theme
       ============================================ */

    :root {
      --bg-deep-abyss: #020c1b;
      --bg-card: #0a192f;
      --bg-card-hover: #112240;
      --primary-neon: #0df5e3;
      --primary-neon-hover: #00cbb9;
      --text-high-contrast: #e2e8f0;
      --text-muted-teal: #8892b0;
      --border-teal: #172a45;
      --success-green: #10b981;
      --warning-amber: #f59e0b;
      --danger-red: #ef4444;
      --purple-accent: #8b5cf6;
      --blue-accent: #3b82f6;
      --pink-accent: #ec4899;
      --orange-accent: #fb923c;
      --border-radius: 12px;
      --transition-smooth: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      --neon-glow: 0 0 15px rgba(13, 245, 227, 0.25);
      --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);

      --sidebar-collapsed: 76px;
      --sidebar-expanded: 280px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    body { display: flex; background-color: #0b1120; color: var(--text-high-contrast); min-height: 100vh; }

    /* ============================================
       SIDEBAR
       ============================================ */
    .sidebar {
      background: linear-gradient(180deg, #071f30 0%, #020c1b 100%);
      width: var(--sidebar-collapsed);
      display: flex; flex-direction: column;
      position: fixed; height: 100vh; left: 0; top: 0;
      z-index: 100; border-right: 1px solid var(--border-teal);
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5);
      overflow-x: visible; overflow-y: auto;
      transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar.expanded { width: var(--sidebar-expanded); }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }

    .sidebar-toggle {
      position: fixed; top: 50%;
      left: calc(var(--sidebar-collapsed) + 8px);
      width: 20px; height: 40px;
      background: transparent; border: none;
      color: var(--primary-neon); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem; z-index: 150;
      transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s ease;
      flex-shrink: 0;
    }
    body.sidebar-open .sidebar-toggle { left: calc(var(--sidebar-expanded) + 8px); }
    .sidebar-toggle:hover { transform: scale(1.2); text-shadow: 0 0 10px rgba(13, 245, 227, 0.5); }
    .sidebar-toggle i { transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
    body.sidebar-open .sidebar-toggle i { transform: rotate(180deg); }

    .school-brand {
      display: flex; align-items: center;
      padding: 20px 0 18px; border-bottom: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.6); flex-shrink: 0; min-height: 80px;
      overflow: hidden; cursor: pointer; transition: var(--transition-smooth);
    }
    .school-brand:hover { background: rgba(13, 245, 227, 0.03); }

    .school-logo {
      width: 44px; height: 44px;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden; flex-shrink: 0; margin-left: 16px; border-radius: 10px;
    }
    .school-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }

    .school-name {
      display: flex; flex-direction: column; margin-left: 12px;
      white-space: nowrap; overflow: hidden;
      opacity: 0; width: 0;
      transition: opacity 0.25s ease 0.1s, width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar.expanded .school-name { opacity: 1; width: auto; }
    .school-name .title { font-size: 0.95rem; font-weight: 700; color: var(--text-high-contrast); line-height: 1.25; }
    .school-name .sub { font-size: 0.6rem; color: var(--text-muted-teal); margin-top: 3px; letter-spacing: 1.2px; text-transform: uppercase; }

    .nav-links { list-style: none; padding: 8px 0; flex-shrink: 0; }
    .nav-links li { margin-bottom: 3px; }
    .nav-links li a {
      display: flex; align-items: center; padding: 11px 0;
      color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 500;
      transition: var(--transition-smooth); position: relative;
      white-space: nowrap; overflow: hidden;
    }
    .nav-links li a .nav-icon {
      width: var(--sidebar-collapsed); flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #64748b; transition: var(--transition-smooth);
    }
    .nav-links li a .nav-label {
      opacity: 0; transform: translateX(-10px);
      transition: opacity 0.25s ease 0.05s, transform 0.3s ease 0.05s;
    }
    .sidebar.expanded .nav-links li a .nav-label { opacity: 1; transform: translateX(0); }
    .nav-links li a:hover { background-color: #1e293b; color: var(--primary-neon); }
    .nav-links li a:hover .nav-icon { color: var(--primary-neon); text-shadow: 0 0 8px rgba(13, 245, 227, 0.5); }
    .nav-links li.active a { background: linear-gradient(90deg, #1e3a5f 0%, #0f2a45 100%); color: var(--primary-neon); font-weight: 700; }
    .nav-links li.active a::before {
      content: ''; position: absolute; left: 0; top: 0; bottom: 0;
      width: 4px; background: var(--primary-neon);
      box-shadow: 0 0 10px rgba(13, 245, 227, 0.7);
    }
    .nav-links li.active a .nav-icon { color: var(--primary-neon); }

    .sidebar-footer {
      padding: 12px 0 16px; border-top: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.85); flex-shrink: 0; margin-top: auto;
    }
    .admin-profile-card {
      display: flex; align-items: center; gap: 12px; padding: 8px 0;
      cursor: pointer; transition: var(--transition-smooth);
      overflow: hidden;
    }
    .admin-profile-card:hover { background: rgba(13, 245, 227, 0.04); }
    .admin-avatar {
      width: 40px; height: 40px;
      background: linear-gradient(135deg, var(--primary-neon), #009f9a);
      color: #020c1b; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 1.15rem; flex-shrink: 0;
      box-shadow: 0 0 12px rgba(13, 245, 227, 0.4); margin-left: 18px;
    }
    .admin-info {
      display: flex; flex-direction: column; overflow: hidden;
      white-space: nowrap; opacity: 0; width: 0;
      transition: opacity 0.25s ease 0.05s, width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar.expanded .admin-info { opacity: 1; width: auto; flex: 1; }
    .admin-name { font-weight: 600; font-size: 0.85rem; color: var(--text-high-contrast); overflow: hidden; text-overflow: ellipsis; }
    .admin-role { font-size: 0.68rem; color: var(--text-muted-teal); display: flex; align-items: center; gap: 5px; }
    .admin-role i { font-size: 0.58rem; color: var(--primary-neon); }
    .admin-toggle-icon { color: var(--text-muted-teal); font-size: 0.75rem; transition: var(--transition-smooth); flex-shrink: 0; margin-right: 16px; opacity: 0; width: 0; }
    .sidebar.expanded .admin-toggle-icon { opacity: 1; width: auto; }
    .admin-profile-card.active .admin-toggle-icon { transform: rotate(180deg); color: var(--primary-neon); }

    .sidebar-actions {
      display: flex; flex-direction: column; gap: 6px;
      max-height: 0; overflow: hidden; opacity: 0;
      margin-top: 0; padding: 0;
      transition: max-height 0.4s ease, opacity 0.3s ease, margin-top 0.3s ease;
    }
    .sidebar.expanded .sidebar-actions.open { max-height: 220px; opacity: 1; margin-top: 10px; padding: 0 12px; }

    .btn-sidebar {
      display: flex; align-items: center; gap: 10px; width: 100%;
      padding: 10px 14px; background-color: transparent;
      color: var(--text-muted-teal); border: 1px solid var(--border-teal);
      border-radius: 8px; text-decoration: none;
      font-weight: 500; font-size: 0.82rem;
      transition: var(--transition-smooth);
      cursor: pointer; white-space: nowrap; overflow: hidden;
    }
    .btn-sidebar i { width: 18px; font-size: 0.9rem; text-align: center; flex-shrink: 0; }
    .btn-sidebar:hover { background-color: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }
    .btn-sidebar.profile-settings:hover { color: var(--primary-neon); border-color: var(--primary-neon); box-shadow: var(--neon-glow); }
    .btn-sidebar.homepage { color: var(--primary-neon); border-color: rgba(13, 245, 227, 0.5); }
    .btn-sidebar.homepage:hover { background-color: var(--primary-neon); color: var(--bg-deep-abyss); box-shadow: var(--neon-glow); }
    .btn-sidebar.logout { color: #f87171; border-color: rgba(239, 68, 68, 0.5); }
    .btn-sidebar.logout:hover { background-color: #ef4444; color: white; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); border-color: #ef4444; }

    /* ============================================
       MAIN CONTENT
       ============================================ */
    .main-content {
      flex: 1; padding: 24px 32px 40px; overflow-y: auto;
      margin-left: var(--sidebar-collapsed);
      background:
        radial-gradient(circle at 15% 10%, rgba(13, 245, 227, 0.06) 0%, transparent 40%),
        radial-gradient(circle at 85% 80%, rgba(139, 92, 246, 0.06) 0%, transparent 45%),
        radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.03) 0%, transparent 60%),
        linear-gradient(135deg, #0b1120 0%, #0d1528 50%, #0a1120 100%);
      background-attachment: fixed;
      transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      min-height: 100vh;
    }
    body.sidebar-open .main-content { margin-left: var(--sidebar-expanded); }

    .top-header {
      display: grid; grid-template-columns: 1fr auto 1fr;
      align-items: center; gap: 24px; margin-bottom: 24px;
    }
    .top-header-left h1 { font-size: 1.5rem; font-weight: 600; color: var(--text-high-contrast); letter-spacing: -0.3px; }
    .top-header-left h1 i { color: var(--primary-neon); margin-right: 10px; }
    .top-header-left p { font-size: 0.82rem; color: var(--text-muted-teal); margin-top: 4px; }

    .live-clock {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 6px 20px;
      background: linear-gradient(135deg, rgba(13, 245, 227, 0.06), rgba(13, 245, 227, 0.01));
      border: 1px solid var(--border-teal); border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
      position: relative; overflow: hidden; min-width: 200px;
      transition: var(--transition-smooth);
    }
    .live-clock:hover { border-color: var(--primary-neon); box-shadow: 0 6px 25px rgba(13, 245, 227, 0.15); }
    .live-clock::before {
      content: ''; position: absolute; top: 0; left: 0;
      width: 100%; height: 2px;
      background: linear-gradient(90deg, transparent, var(--primary-neon), transparent);
      animation: clockPulse 2s ease-in-out infinite;
    }
    @keyframes clockPulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }
    .clock-time { font-size: 1.4rem; font-weight: 700; color: var(--primary-neon); letter-spacing: 1.2px; font-variant-numeric: tabular-nums; text-shadow: 0 0 12px rgba(13, 245, 227, 0.5); line-height: 1; font-family: 'Segoe UI', monospace; }
    .clock-ampm { font-size: 0.65rem; font-weight: 600; color: var(--primary-neon); margin-left: 3px; opacity: 0.85; vertical-align: super; }
    .clock-date { font-size: 0.62rem; color: var(--text-muted-teal); margin-top: 4px; letter-spacing: 0.4px; text-transform: uppercase; font-weight: 600; }

    /* ============================================
       METRIC CARDS
       ============================================ */
    .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
    .metric-card {
      background: var(--bg-card); border: 1px solid var(--border-teal);
      border-radius: var(--border-radius); padding: 20px;
      display: flex; flex-direction: column;
      transition: var(--transition-smooth);
      box-shadow: var(--card-shadow);
      position: relative; overflow: hidden;
    }
    .metric-card:hover { transform: translateY(-4px); border-color: var(--primary-neon); box-shadow: 0 12px 28px rgba(13, 245, 227, 0.15); }
    .metric-card::before {
      content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px;
      background: linear-gradient(90deg, var(--warning-amber), #fbbf24); opacity: 0.8;
    }
    .metric-card:nth-child(2)::before { background: linear-gradient(90deg, var(--success-green), #34d399); }
    .metric-card:nth-child(3)::before { background: linear-gradient(90deg, #ef4444, #f87171); }
    .metric-card:nth-child(4)::before { background: linear-gradient(90deg, var(--blue-accent), #60a5fa); }

    .metric-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .metric-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted-teal); font-weight: 600; }
    .metric-icon {
      width: 38px; height: 38px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: var(--warning-amber); background: rgba(245, 158, 11, 0.1);
    }
    .metric-card:nth-child(2) .metric-icon { color: var(--success-green); background: rgba(16, 185, 129, 0.1); }
    .metric-card:nth-child(3) .metric-icon { color: #f87171; background: rgba(239, 68, 68, 0.1); }
    .metric-card:nth-child(4) .metric-icon { color: var(--blue-accent); background: rgba(59, 130, 246, 0.1); }
    .metric-value { font-size: 2rem; font-weight: 700; color: var(--text-high-contrast); line-height: 1.15; letter-spacing: -0.5px; }

    /* ============================================
       TABLES
       ============================================ */
    .table-card {
      background: var(--bg-card); border: 1px solid var(--border-teal);
      border-radius: var(--border-radius); padding: 22px 24px;
      box-shadow: var(--card-shadow);
      transition: var(--transition-smooth); margin-bottom: 24px;
    }
    .table-card:hover { border-color: #1f3a5f; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; gap: 15px; flex-wrap: wrap; }
    .table-header h2 { font-size: 1.05rem; font-weight: 600; color: var(--text-high-contrast); display: flex; align-items: center; gap: 10px; }
    .table-header h2 i { color: var(--primary-neon); font-size: 1rem; }
    .table-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .table-wrapper { overflow-x: auto; max-height: 640px; overflow-y: auto; }
    .table-wrapper::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-wrapper::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }

    table { width: 100%; border-collapse: collapse; text-align: left; }
    th {
      padding: 12px 16px; font-size: 0.73rem; text-transform: uppercase;
      letter-spacing: 0.6px; color: var(--text-muted-teal); font-weight: 600;
      border-bottom: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.6); white-space: nowrap;
      position: sticky; top: 0; z-index: 2;
    }
    td {
      padding: 14px 16px; color: var(--text-high-contrast);
      border-bottom: 1px solid #1a2d45; font-size: 0.87rem;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background-color: rgba(13, 245, 227, 0.03); }

    /* ============================================
       SEARCH INPUT
       ============================================ */
    .section-search {
      padding: 10px 16px 10px 40px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      color: var(--text-high-contrast);
      font-size: 0.85rem;
      outline: none;
      width: 240px;
      transition: var(--transition-smooth);
    }
    .section-search:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1); }
    .search-wrap { position: relative; }
    .search-wrap i {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: var(--text-muted-teal); font-size: 0.85rem; pointer-events: none;
    }

    /* ============================================
       BUTTONS
       ============================================ */
    .btn {
      padding: 8px 18px; border: 1px solid var(--border-teal);
      background: transparent; border-radius: 8px; cursor: pointer;
      font-size: 0.82rem; font-weight: 500;
      transition: var(--transition-smooth);
      text-decoration: none; display: inline-flex; align-items: center;
      gap: 6px; color: var(--text-muted-teal);
    }
    .btn:hover { background: #1e293b; color: var(--text-high-contrast); border-color: #2d4a6b; }
    .btn.primary { background: var(--primary-neon); color: #020c1b; border: none; font-weight: 600; }
    .btn.primary:hover { background: var(--primary-neon-hover); box-shadow: var(--neon-glow); }

    .btn-action {
      padding: 5px 12px; background: transparent; border: 1px solid var(--border-teal);
      color: var(--text-muted-teal); border-radius: 6px; cursor: pointer;
      font-size: 0.75rem; font-weight: 500; transition: var(--transition-smooth);
      display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-action:hover { background: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }
    .btn-action.view { border-color: rgba(13, 245, 227, 0.5); color: var(--primary-neon); }
    .btn-action.view:hover { background: var(--primary-neon); color: #020c1b; box-shadow: var(--neon-glow); }
    .btn-action.btn-send { border-color: rgba(16, 185, 129, 0.5); color: #34d399; }
    .btn-action.btn-send:hover { background: var(--success-green); color: white; box-shadow: 0 0 12px rgba(16, 185, 129, 0.4); }
    .btn-action.decline { border-color: rgba(239, 68, 68, 0.5); color: #f87171; }
    .btn-action.decline:hover { background: rgba(239, 68, 68, 0.15); border-color: #ef4444; color: #fca5a5; }
    .action-group { display: flex; gap: 6px; flex-wrap: wrap; }

    /* ============================================
       MODALS
       ============================================ */
    .modal-overlay {
      display: none; position: fixed; top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(2, 12, 27, 0.9);
      backdrop-filter: blur(6px);
      justify-content: center; align-items: center; padding: 20px;
      z-index: 2000;
    }
    .modal-overlay.active { display: flex; }

    .modal-container {
      background: var(--bg-card); border: 1px solid var(--border-teal);
      border-radius: 16px; width: 100%; max-width: 1100px;
      max-height: 90vh; overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      padding: 30px; color: var(--text-high-contrast);
      position: relative;
      transform: translateY(20px); transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-container { transform: translateY(0); }
    .modal-container::-webkit-scrollbar { width: 8px; height: 8px; }
    .modal-container::-webkit-scrollbar-track { background: var(--bg-deep-abyss); border-radius: 10px; }
    .modal-container::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 10px; }
    .modal-container::-webkit-scrollbar-thumb:hover { background: var(--primary-neon); }

    .modal-close {
      position: absolute; top: 18px; right: 22px;
      background: transparent; border: none; color: var(--text-muted-teal);
      font-size: 1.6rem; cursor: pointer; z-index: 10;
      transition: color 0.2s ease; line-height: 1;
    }
    .modal-close:hover { color: #ef4444; }

    /* ============================================
       COR HEADER
       ============================================ */
    .cor-header {
      text-align: center; margin-bottom: 25px; padding-bottom: 20px;
      border-bottom: 1px solid var(--border-teal);
    }
    .cor-header-top {
      display: flex; align-items: center; justify-content: center;
      gap: 15px; margin-bottom: 10px;
    }
    .cor-logo {
      width: 70px; height: 70px; object-fit: contain;
      filter: drop-shadow(0 0 10px rgba(13, 245, 227, 0.25));
    }
    .cor-school-name {
      font-size: 1.3rem; font-weight: 800; color: var(--text-high-contrast);
      text-transform: uppercase; letter-spacing: 1px;
    }
    .cor-address {
      font-size: 0.78rem; color: var(--text-muted-teal); margin-top: 4px;
      display: flex; align-items: center; gap: 6px; justify-content: center;
    }
    .cor-address i { color: var(--primary-neon); }
    .cor-title {
      font-size: 1.15rem; font-weight: 700; color: var(--primary-neon);
      text-transform: uppercase; letter-spacing: 2px; margin-top: 15px;
    }
    .cor-session {
      font-size: 0.82rem; color: var(--text-muted-teal);
      margin-top: 5px; font-style: italic;
    }

    /* ============================================
       Student Info Grid
       ============================================ */
    .student-info-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 20px; margin-bottom: 25px;
      background: var(--bg-deep-abyss); border: 1px solid var(--border-teal);
      border-radius: 12px; padding: 22px;
    }
    .student-info-left, .student-info-right { display: flex; flex-direction: column; gap: 12px; }
    .student-info-right { align-items: flex-end; text-align: right; }
    .info-line { font-size: 0.85rem; color: var(--text-muted-teal); }
    .info-line strong { color: var(--text-high-contrast); font-weight: 600; }
    .info-line strong i { color: var(--primary-neon); margin-right: 8px; }
    .info-line .highlight { color: var(--primary-neon); font-weight: 700; }

    /* ============================================
       Modal Sections
       ============================================ */
    .modal-sections { display: flex; flex-direction: column; gap: 30px; margin-bottom: 20px; }
    .section-title {
      font-size: 0.9rem; font-weight: 700; color: var(--text-high-contrast);
      text-transform: uppercase; border-bottom: 2px solid var(--primary-neon);
      padding-bottom: 8px; margin-bottom: 15px;
      display: flex; align-items: center; gap: 8px;
      letter-spacing: 0.5px;
    }
    .section-title i { color: var(--primary-neon); }

    /* ============================================
       Student Portal Account
       ============================================ */
    .student-portal-section {
      background: var(--bg-deep-abyss); border: 1px solid var(--border-teal);
      border-radius: 12px; padding: 22px;
    }
    .student-portal-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    .portal-form-group label {
      display: block; font-size: 0.72rem; color: var(--text-muted-teal);
      font-weight: 600; margin-bottom: 6px; text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .portal-form-group input {
      width: 100%; padding: 11px 14px;
      background: var(--bg-card); border: 1px solid var(--border-teal);
      border-radius: 8px; font-size: 0.88rem; color: var(--text-high-contrast);
      outline: none; transition: var(--transition-smooth); font-family: inherit;
    }
    .portal-form-group input:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1); }

    /* ============================================
       Subject Table
       ============================================ */
    .subject-table {
      width: 100%; border-collapse: collapse; font-size: 0.82rem;
      border-radius: 10px; overflow: hidden;
      border: 1px solid var(--border-teal);
    }
    .subject-table th, .subject-table td {
      padding: 10px 8px; border-bottom: 1px solid #1a2d45; text-align: left;
    }
    .subject-table th {
      background: rgba(2, 12, 27, 0.7); color: var(--primary-neon);
      font-weight: 600; font-size: 0.7rem; text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .subject-table input, .subject-table select {
      width: 100%; padding: 6px 8px;
      background: var(--bg-deep-abyss); border: 1px solid var(--border-teal);
      border-radius: 6px; font-size: 0.82rem; color: var(--text-high-contrast);
      outline: none; font-family: inherit; transition: var(--transition-smooth);
    }
    .subject-table input:focus, .subject-table select:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 2px rgba(13, 245, 227, 0.1); }
    .subject-table tbody tr:hover { background: rgba(13, 245, 227, 0.03); }

    .btn-add-subject {
      color: var(--primary-neon); background-color: transparent;
      border: 1px solid rgba(13, 245, 227, 0.4);
      border-radius: 6px; width: 32px; height: 32px;
      font-size: 0.85rem; cursor: pointer; margin-left: auto;
      display: flex; align-items: center; justify-content: center;
      transition: var(--transition-smooth);
    }
    .btn-add-subject:hover { background: var(--primary-neon); color: #020c1b; box-shadow: var(--neon-glow); }

    /* ============================================
       Assessment Table
       ============================================ */
    .assessment-table {
      width: 100%; max-width: 600px; border-collapse: collapse;
      font-size: 0.85rem; background: var(--bg-deep-abyss);
      border-radius: 10px; overflow: hidden;
      border: 1px solid var(--border-teal);
    }
    .assessment-table td {
      padding: 11px 14px; border-bottom: 1px solid #1a2d45;
      color: var(--text-high-contrast);
    }
    .assessment-table td:last-child { width: 180px; }
    .assessment-table input {
      width: 100%; padding: 8px 10px;
      background: var(--bg-card); border: 1px solid var(--border-teal);
      border-radius: 6px; font-size: 0.85rem; color: var(--text-high-contrast);
      text-align: right; outline: none; font-family: inherit;
      transition: var(--transition-smooth);
    }
    .assessment-table input:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 2px rgba(13, 245, 227, 0.1); }
    .assessment-table tbody tr:hover { background: rgba(13, 245, 227, 0.03); }
    .assessment-table .total-row td {
      font-weight: 800; font-size: 1rem;
      border-top: 2px solid var(--primary-neon);
      padding-top: 14px; background: rgba(13, 245, 227, 0.06);
    }
    .assessment-table .total-row td:last-child { color: var(--primary-neon); text-align: right; }

    /* ============================================
       Modal Footer
       ============================================ */
    .modal-footer {
      display: flex; justify-content: flex-end; gap: 10px;
      border-top: 1px solid var(--border-teal);
      padding-top: 20px; margin-top: 12px;
    }
    .btn-cancel {
      background: transparent; color: var(--text-muted-teal);
      border: 1px solid var(--border-teal); padding: 11px 22px;
      border-radius: 8px; cursor: pointer; font-weight: 500;
      font-size: 0.85rem; transition: var(--transition-smooth);
      font-family: inherit;
    }
    .btn-cancel:hover { background: var(--bg-card-hover); color: var(--text-high-contrast); }
    .btn-confirm-accept {
      background: var(--primary-neon); color: #020c1b;
      border: none; padding: 11px 26px; border-radius: 8px;
      cursor: pointer; font-weight: 700; font-size: 0.85rem;
      transition: var(--transition-smooth);
      display: inline-flex; align-items: center; gap: 8px;
      font-family: inherit;
    }
    .btn-confirm-accept:hover { background: var(--primary-neon-hover); box-shadow: var(--neon-glow); }

    /* ============================================
       Send Message Modal (Small)
       ============================================ */
    .modal-container-sm {
      background: var(--bg-card); border: 1px solid var(--border-teal);
      border-radius: 16px; width: 100%; max-width: 600px;
      max-height: 90vh; overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      padding: 30px; color: var(--text-high-contrast);
      position: relative; transform: translateY(20px);
      transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-container-sm { transform: translateY(0); }

    .modal-header-sm {
      border-bottom: 2px solid var(--primary-neon);
      padding-bottom: 12px; margin-bottom: 20px;
    }
    .modal-header-sm h3 {
      font-size: 1.05rem; color: var(--primary-neon);
      display: flex; align-items: center; gap: 10px;
      font-weight: 600;
    }

    .message-info-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 12px; margin-bottom: 20px;
    }
    .message-info-item label {
      font-size: 0.7rem; color: var(--text-muted-teal);
      font-weight: 600; text-transform: uppercase;
      margin-bottom: 5px; display: block; letter-spacing: 0.5px;
    }
    .message-info-item input {
      padding: 9px 12px; border: 1px solid var(--border-teal);
      border-radius: 8px; font-size: 0.85rem;
      background: var(--bg-deep-abyss); color: var(--text-high-contrast);
      outline: none; width: 100%; font-family: inherit;
    }
    .message-form-group { margin-bottom: 15px; }
    .message-form-group label {
      display: block; font-size: 0.72rem; color: var(--text-muted-teal);
      font-weight: 600; margin-bottom: 6px; text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .message-form-group input,
    .message-form-group textarea {
      width: 100%; padding: 11px 14px;
      border: 1px solid var(--border-teal); border-radius: 8px;
      font-size: 0.88rem; font-family: inherit; resize: vertical;
      background: var(--bg-deep-abyss); color: var(--text-high-contrast);
      outline: none; transition: var(--transition-smooth);
    }
    .message-form-group textarea { min-height: 240px; line-height: 1.6; }
    .message-form-group input:focus,
    .message-form-group textarea:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1); }

    .modal-footer-sm {
      display: flex; justify-content: flex-end; gap: 10px;
      border-top: 1px solid var(--border-teal); padding-top: 16px;
    }
    .btn-send-email {
      background: var(--primary-neon); color: #020c1b;
      border: none; padding: 11px 26px; border-radius: 8px;
      cursor: pointer; font-weight: 700;
      display: inline-flex; align-items: center; gap: 8px;
      transition: var(--transition-smooth);
      font-size: 0.85rem; font-family: inherit;
    }
    .btn-send-email:hover { background: var(--primary-neon-hover); box-shadow: var(--neon-glow); }
    .btn-send-email:disabled { opacity: 0.6; cursor: not-allowed; }

    /* ============================================
       Success Modal
       ============================================ */
    .success-modal-body { text-align: center; padding: 30px 20px; }
    .success-icon { font-size: 3.5rem; color: var(--success-green); margin-bottom: 15px; filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.5)); }
    .success-modal-body h4 { font-size: 1.15rem; color: var(--text-high-contrast); margin-bottom: 8px; font-weight: 700; }
    .success-modal-body p { font-size: 0.88rem; color: var(--text-muted-teal); }

    /* ============================================
       View Modal Details
       ============================================ */
    .view-modal-section { margin-bottom: 22px; }
    .view-modal-section h4 {
      font-size: 0.82rem; text-transform: uppercase;
      letter-spacing: 0.8px; color: var(--primary-neon);
      margin-bottom: 12px; padding-bottom: 6px;
      border-bottom: 1px solid var(--border-teal);
    }
    .view-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
    .view-modal-row { display: flex; font-size: 0.85rem; padding: 4px 0; }
    .view-modal-label { width: 140px; font-weight: 500; color: var(--text-muted-teal); flex-shrink: 0; }
    .view-modal-value { flex: 1; color: var(--text-high-contrast); word-break: break-word; }

    /* ============================================
       REQUIREMENTS GALLERY (Uploaded Files)
       ============================================ */
    .requirements-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 12px;
      margin-top: 8px;
    }

    .requirement-item {
      display: flex;
      flex-direction: column;
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      overflow: hidden;
      text-decoration: none;
      color: var(--text-high-contrast);
      transition: var(--transition-smooth);
      background: var(--bg-deep-abyss);
      cursor: pointer;
      position: relative;
    }

    .requirement-item:hover {
      border-color: var(--primary-neon);
      box-shadow: var(--neon-glow);
      transform: translateY(-4px);
    }

    .requirement-item img {
      width: 100%;
      height: 130px;
      object-fit: cover;
      display: block;
      background: #0a192f;
      transition: transform 0.3s ease;
    }

    .requirement-item:hover img {
      transform: scale(1.05);
    }

    .requirement-item .file-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 130px;
      color: var(--primary-neon);
      font-size: 2.4rem;
      background: rgba(13, 245, 227, 0.04);
      transition: var(--transition-smooth);
    }

    .requirement-item:hover .file-icon {
      background: rgba(13, 245, 227, 0.1);
      transform: scale(1.05);
    }

    .requirement-item .file-icon.pdf { color: #f87171; }
    .requirement-item .file-icon.doc { color: #60a5fa; }

    .requirement-item .file-name {
      display: block;
      padding: 8px 10px;
      font-size: 0.7rem;
      color: var(--text-muted-teal);
      text-align: center;
      word-break: break-all;
      background: rgba(2, 12, 27, 0.6);
      border-top: 1px solid var(--border-teal);
      line-height: 1.4;
    }

    .requirement-item:hover .file-name {
      color: var(--primary-neon);
    }

    .no-requirements {
      color: var(--text-muted-teal);
      font-size: 0.85rem;
      padding: 14px 18px;
      font-style: italic;
      background: rgba(2, 12, 27, 0.5);
      border-radius: 10px;
      border: 1px dashed var(--border-teal);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .no-requirements i { color: var(--text-muted-teal); }

    /* ============================================
       WARNING BANNER
       ============================================ */
    .warning-banner {
      background: rgba(245, 158, 11, 0.1); color: #fbbf24;
      padding: 14px 18px; border-radius: 10px; margin-top: 20px;
      font-size: 0.85rem; border: 1px solid rgba(245, 158, 11, 0.3);
      display: flex; align-items: center; gap: 10px;
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 1200px) {
      .cards-grid { grid-template-columns: repeat(2, 1fr); }
      .top-header { grid-template-columns: 1fr; gap: 18px; }
    }
    @media (max-width: 768px) {
      .main-content { padding: 20px; }
      .cards-grid { grid-template-columns: 1fr; }
      .student-info-grid { grid-template-columns: 1fr; }
      .student-info-right { align-items: flex-start; text-align: left; }
      .assessment-table { max-width: 100%; }
      .student-portal-grid { grid-template-columns: 1fr; }
      .message-info-grid { grid-template-columns: 1fr; }
      .view-modal-grid { grid-template-columns: 1fr; }
      .requirements-gallery { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }
      .live-clock { min-width: 100%; }
      .clock-time { font-size: 1.2rem; }
      .sidebar-toggle { top: 20px; }
    }
  </style>
</head>
<body>
  <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar(event)" title="Toggle Sidebar">
    <i class="fa-solid fa-chevron-right"></i>
  </button>

  <aside class="sidebar" id="sidebar">
    <div class="school-brand" onclick="pinSidebar(event)">
      <div class="school-logo">
        <img src="/WebDev_for_ProgLang/BackGroundimage/ICFLogo.png" alt="ICF Logo" />
      </div>
      <div class="school-name">
        <span class="title">ICF Interworld Colleges <br> Foundation INC.</span>
        <span class="sub">Admin Dashboard</span>
      </div>
    </div>

    <ul class="nav-links">
      <li><a href="adminpanel.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span><span class="nav-label">Overview</span></a></li>
      <li><a href="users.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-users"></i></span><span class="nav-label">Users</span></a></li>
      <li class="active"><a href="enrollments.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-file-signature"></i></span><span class="nav-label">Enrollments</span></a></li>
      <li><a href="enrolled.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-user-graduate"></i></span><span class="nav-label">Enrolled</span></a></li>
      <li><a href="instructor.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-chalkboard-user"></i></span><span class="nav-label">Instructors</span></a></li>
      <li><a href="curriculum.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-book"></i></span><span class="nav-label">Curriculum</span></a></li>
      <li><a href="received-mail.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-envelope"></i></span><span class="nav-label">Received Mail</span></a></li>
    </ul>

    <div class="sidebar-footer">
      <div class="admin-profile-card" id="adminProfileToggle" onclick="onAdminProfileClick(event)">
        <div class="admin-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        <div class="admin-info">
          <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
          <span class="admin-role"><i class="fa-solid fa-circle-check"></i> System Administrator</span>
        </div>
        <i class="fa-solid fa-chevron-up admin-toggle-icon" id="adminToggleIcon"></i>
      </div>

      <div class="sidebar-actions" id="adminActions">
        <a href="profile-settings.php" class="btn-sidebar profile-settings"><i class="fa-solid fa-gear"></i> Profile Settings</a>
        <a href="../../index.html" class="btn-sidebar homepage"><i class="fa-solid fa-house"></i> Homepage</a>
        <a href="logout.php" class="btn-sidebar logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </div>
  </aside>

  <main class="main-content" id="mainContent">
    <header class="top-header">
      <div class="top-header-left">
        <h1><i class="fa-solid fa-file-signature"></i> Enrollment Management</h1>
        <p>Review, approve, or decline incoming student enrollment applications.</p>
      </div>

      <div class="live-clock">
        <div>
          <span class="clock-time" id="liveClockTime">--:--:--</span>
          <span class="clock-ampm" id="liveClockAmPm">--</span>
        </div>
        <div class="clock-date" id="liveClockDate">Loading date...</div>
      </div>

      <div></div>
    </header>

    <!-- METRIC CARDS -->
    <div class="cards-grid">
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Pending Requests</span>
          <div class="metric-icon"><i class="fa-solid fa-clock"></i></div>
        </div>
        <div class="metric-value"><?php echo $pendingCount; ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Approved Enrollments</span>
          <div class="metric-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="metric-value"><?php echo $approvedCount; ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Declined Requests</span>
          <div class="metric-icon"><i class="fa-solid fa-circle-xmark"></i></div>
        </div>
        <div class="metric-value"><?php echo $declinedCount; ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Total Records</span>
          <div class="metric-icon"><i class="fa-solid fa-database"></i></div>
        </div>
        <div class="metric-value"><?php echo $totalEnrolled; ?></div>
      </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-user-clock"></i> Pending Enrollment Requests</h2>
        <div class="table-actions">
          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="userSearchInput" class="section-search" placeholder="Search applicants..." autocomplete="off" />
          </div>
          <a href="create.php"><button class="btn primary"><i class="fa-solid fa-plus"></i> New Enrollment</button></a>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="pendingEnrollmentTable">
          <thead>
            <tr>
              <th>Student Name</th>
              <th>Course</th>
              <th>Email</th>
              <th>Date Submitted</th>
              <th>Action</th>
            </tr>
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
                    <button class="btn-action view" onclick="openEnrollmentViewModal(this)">
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
              <tr><td colspan="5" style="text-align:center; color:#8892b0; padding:40px;">No pending enrollment requests</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!$hasStatusCol): ?>
    <div class="warning-banner">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span><strong>Note:</strong> The <code>enrollment_status</code> column was not found in <code>enrolled</code>.</span>
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
      <button type="button" class="modal-close" onclick="closeAcceptModal()">&times;</button>

      <div class="cor-header">
        <div class="cor-header-top">
          <img src="/WebDev_for_ProgLang/BackGroundimage/ICFLogo.png" alt="School Logo" class="cor-logo">
          <div>
            <div class="cor-school-name">Interworld Colleges Foundation Inc.</div>
            <div class="cor-address"><i class="fas fa-map-marker-alt"></i> Burgos St., Paniqui, Tarlac · Tel No. (045) 470-8645</div>
          </div>
        </div>
        <div class="cor-title">Student Subject and Tuition Allocation</div>
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
              <tr>
                <th>Code</th>
                <th>Description</th>
                <th>Units</th>
                <th>Date</th>
                <th>Time</th>
                <th>Room</th>
                <th>Block</th>
              </tr>
            </thead>
            <tbody id="subjectBody">
              <tr>
                <td><input type="text" name="subj_code[]" required /></td>
                <td><input type="text" name="subj_desc[]" required /></td>
                <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
                <td><input type="text" name="subj_date[]" placeholder="e.g. MON / 2026-10-12" /></td>
                <td><input type="text" name="subj_time[]" placeholder="e.g. 07:30 AM - 09:00 AM" /></td>
                <td><input type="text" name="subj_room[]" /></td>
                <td><input type="text" name="subj_block[]" required /></td>
              </tr>
              <tr style="background:var(--bg-deep-abyss)">
                <td colspan="2" style="text-align:right; font-weight:700; color:var(--text-high-contrast);">TOTAL UNITS</td>
                <td id="totalUnitsDisplay" style="font-weight:800; color:var(--primary-neon);">0.0</td>
                <td colspan="4"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="assessment-section">
          <div class="section-title"><i class="fas fa-calculator"></i> Assessment Details</div>
          <table class="assessment-table">
            <tbody>
              <tr><td><strong>Academic:</strong></td><td><input type="text" name="academic_fee" id="academicFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Computer:</strong></td><td><input type="text" name="computer_fee" id="computerFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Misc. Fee:</strong></td><td><input type="text" name="misc_fee" id="miscFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>NSTP:</strong></td><td><input type="text" name="nstp_fee" id="nstpFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr><td><strong>Others:</strong></td><td><input type="text" name="others_fee" id="othersFee" placeholder="0.00" inputmode="decimal" oninput="formatMoneyInput(this)" /></td></tr>
              <tr class="total-row">
                <td>TOTAL TUITION FEE:</td>
                <td><span id="totalAssessment">₱ 0.00</span></td>
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
  <div class="modal-overlay" id="sendMessageModal">
    <div class="modal-container-sm">
      <button type="button" class="modal-close" onclick="closeSendMessageModal()">&times;</button>
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
        <input type="text" id="msgSubject" value="Welcome to Interworld Colleges Foundation Inc. - Enrollment Confirmation" />
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
    <div class="modal-container" style="max-width:750px;">
      <button type="button" class="modal-close" onclick="closeEnrollmentViewModal()">&times;</button>
      <div class="modal-header-sm" style="border-bottom:2px solid var(--primary-neon); padding-bottom:12px; margin-bottom:20px;">
        <h3 style="color:var(--primary-neon); font-size:1.05rem; display:flex; align-items:center; gap:10px; font-weight:600;"><i class="fa-solid fa-id-card"></i> Enrollment Application Details</h3>
      </div>
      <div id="enrollmentViewModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn primary" onclick="closeEnrollmentViewModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- ============================================================= -->
  <!-- ================ SUCCESS MODAL ============================== -->
  <!-- ============================================================= -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-container-sm" style="max-width:420px;">
      <button type="button" class="modal-close" onclick="toggleSuccessModal(false)">&times;</button>
      <div class="success-modal-body">
        <i class="fa-solid fa-circle-check success-icon"></i>
        <h4 id="successModalTitle">Action Completed</h4>
        <p id="successModalBody">The action was completed successfully.</p>
      </div>
      <div class="modal-footer" style="justify-content:center; border-top:none; padding-top:0;">
        <button type="button" class="btn primary" onclick="toggleSuccessModal(false)">OK</button>
      </div>
    </div>
  </div>

  <script>
    /* ============================================
       SIDEBAR
       ============================================ */
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    let isPinned = false;
    let collapseTimer = null;

    function expandSidebar() { sidebar.classList.add('expanded'); body.classList.add('sidebar-open'); }
    function collapseSidebar() { if (isPinned) return; sidebar.classList.remove('expanded'); body.classList.remove('sidebar-open'); }
    function pinSidebar(event) { if (event) event.stopPropagation(); isPinned = true; expandSidebar(); }
    function unpinSidebar() {
      isPinned = false;
      document.getElementById('adminActions').classList.remove('open');
      document.getElementById('adminProfileToggle').classList.remove('active');
      collapseSidebar();
    }
    function toggleSidebar(event) {
      if (event) event.stopPropagation();
      if (sidebar.classList.contains('expanded')) { unpinSidebar(); } else { pinSidebar(); }
    }
    sidebar.addEventListener('mouseenter', () => {
      if (collapseTimer) { clearTimeout(collapseTimer); collapseTimer = null; }
      expandSidebar();
    });
    sidebar.addEventListener('mouseleave', () => {
      if (isPinned) return;
      if (document.getElementById('adminActions').classList.contains('open')) return;
      collapseTimer = setTimeout(() => { collapseSidebar(); }, 250);
    });
    document.addEventListener('click', function(e) {
      if (sidebar.contains(e.target)) return;
      if (isPinned) unpinSidebar();
    });
    function onAdminProfileClick(event) {
      event.stopPropagation();
      pinSidebar();
      setTimeout(() => {
        document.getElementById('adminActions').classList.toggle('open');
        document.getElementById('adminProfileToggle').classList.toggle('active');
      }, 150);
    }

    /* ============================================
       LIVE CLOCK
       ============================================ */
    function updateLiveClock() {
      const now = new Date();
      let hours = now.getHours();
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12; hours = hours ? hours : 12;
      const hoursStr = String(hours).padStart(2, '0');
      document.getElementById('liveClockTime').textContent = `${hoursStr}:${minutes}:${seconds}`;
      document.getElementById('liveClockAmPm').textContent = ampm;
      const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      document.getElementById('liveClockDate').textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    /* ============================================
       MONEY FORMATTER
       ============================================ */
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

    /* ============================================
       UNITS CALCULATOR
       ============================================ */
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
        <td><input type="text" name="subj_date[]" placeholder="e.g. MON / 2026-10-12" /></td>
        <td><input type="text" name="subj_time[]" placeholder="e.g. 07:30 AM - 09:00 AM" /></td>
        <td><input type="text" name="subj_room[]" /></td>
        <td><input type="text" name="subj_block[]" required /></td>`;
      tbody.insertBefore(newRow, totalRow);
      attachUnitListeners();
      calculateTotalUnits();
    }

    /* ============================================
       ACCEPT MODAL
       ============================================ */
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
          <td><input type="text" name="subj_date[]" placeholder="e.g. MON / 2026-10-12" /></td>
          <td><input type="text" name="subj_time[]" placeholder="e.g. 07:30 AM - 09:00 AM" /></td>
          <td><input type="text" name="subj_room[]" /></td>
          <td><input type="text" name="subj_block[]" required /></td>
        </tr>
        <tr>
          <td><input type="text" name="subj_code[]" required /></td>
          <td><input type="text" name="subj_desc[]" required /></td>
          <td><input type="number" class="units-input" name="subj_units[]" step="0.5" min="0" value="0" required /></td>
          <td><input type="text" name="subj_date[]" placeholder="e.g. MON / 2026-10-12" /></td>
          <td><input type="text" name="subj_time[]" placeholder="e.g. 07:30 AM - 09:00 AM" /></td>
          <td><input type="text" name="subj_room[]" /></td>
          <td><input type="text" name="subj_block[]" required /></td>
        </tr>
        <tr style="background:var(--bg-deep-abyss)">
          <td colspan="2" style="text-align:right; font-weight:700; color:var(--text-high-contrast);">TOTAL UNITS</td>
          <td id="totalUnitsDisplay" style="font-weight:800; color:var(--primary-neon);">0.0</td>
          <td colspan="4"></td>
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

    /* ============================================
       VIEW APPLICATION MODAL — WITH REQUIREMENTS GALLERY
       ============================================ */
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

      /* ===== Uploaded Requirements Section ===== */
      html += `<div class="view-modal-section"><h4><i class="fa-solid fa-paperclip"></i> Uploaded Requirements</h4>`;

      let reqFiles = [];
      if (data.requirements_files) {
        try {
          const parsed = JSON.parse(data.requirements_files);
          if (Array.isArray(parsed)) reqFiles = parsed;
        } catch (e) {
          reqFiles = String(data.requirements_files).split(',').map(x => x.trim()).filter(Boolean);
        }
      }

      if (reqFiles.length > 0) {
        html += `<div class="requirements-gallery">`;
        reqFiles.forEach(f => {
          const cleanPath = String(f).replace(/^\.\.\//, '').replace(/^\/+/, '');
          const relPath = '../../' + cleanPath;
          const fileName = cleanPath.split('/').pop();
          const ext = fileName.split('.').pop().toLowerCase();
          const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext);

          if (isImage) {
            html += `<a href="${relPath}" target="_blank" rel="noopener" class="requirement-item" title="Click to open ${fileName}">
              <img src="${relPath}" alt="${fileName}" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div class="file-icon" style="display:none;"><i class="fa-solid fa-image"></i></div>
              <span class="file-name">${fileName}</span>
            </a>`;
          } else {
            const iconClass = ext === 'pdf' ? 'pdf' : (['doc', 'docx'].includes(ext) ? 'doc' : '');
            const faIcon = ext === 'pdf' ? 'fa-file-pdf' : (['doc', 'docx'].includes(ext) ? 'fa-file-word' : 'fa-file-lines');
            html += `<a href="${relPath}" target="_blank" rel="noopener" class="requirement-item" title="Click to open ${fileName}">
              <div class="file-icon ${iconClass}"><i class="fa-solid ${faIcon}"></i></div>
              <span class="file-name">${fileName}</span>
            </a>`;
          }
        });
        html += `</div>`;
      } else {
        html += `<p class="no-requirements"><i class="fa-solid fa-circle-info"></i> No requirements uploaded by this applicant.</p>`;
      }
      html += `</div>`;

      document.getElementById('enrollmentViewModalBody').innerHTML = html;
      document.getElementById('viewEnrollmentModal').classList.add('active');
    }

    function closeEnrollmentViewModal() { document.getElementById('viewEnrollmentModal').classList.remove('active'); }

    /* ============================================
       SEND MESSAGE MODAL
       ============================================ */
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

    /* ============================================
       SUCCESS MODAL
       ============================================ */
    function toggleSuccessModal(show, title, body) {
      if (title) document.getElementById('successModalTitle').textContent = title;
      if (body) document.getElementById('successModalBody').textContent = body;
      document.getElementById('successModal').classList.toggle('active', show);
    }

    /* ============================================
       CLICK OUTSIDE MODALS
       ============================================ */
    ['viewEnrollmentModal', 'successModal', 'acceptModal', 'sendMessageModal'].forEach(id => {
      document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
      });
    });

    /* ============================================
       SEARCH
       ============================================ */
    document.addEventListener('DOMContentLoaded', () => {
      const searchInput = document.getElementById('userSearchInput');
      const table = document.getElementById('pendingEnrollmentTable');
      if (searchInput && table) {
        searchInput.addEventListener('input', (e) => {
          const term = e.target.value.toLowerCase();
          table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
          });
        });
      }
      attachUnitListeners();
      calculateTotalUnits();
    });

    /* ============================================
       INIT — Show modals based on URL params
       ============================================ */
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