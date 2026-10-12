<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

/* ============================================
   ENSURE TABLES EXIST
   ============================================ */
$conn->query("CREATE TABLE IF NOT EXISTS instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    department VARCHAR(50) NOT NULL,
    status ENUM('active','former') DEFAULT 'active',
    profile_pic VARCHAR(255) DEFAULT '',
    gender VARCHAR(20) DEFAULT '',
    birthday DATE DEFAULT NULL,
    citizenship VARCHAR(50) DEFAULT '',
    civil_status VARCHAR(30) DEFAULT '',
    employment VARCHAR(30) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(30) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS instructor_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    code VARCHAR(50) DEFAULT '',
    title VARCHAR(255) DEFAULT '',
    units DECIMAL(4,1) DEFAULT 0,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS instructor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    day VARCHAR(50) DEFAULT '',
    time VARCHAR(100) DEFAULT '',
    room VARCHAR(50) DEFAULT '',
    subject VARCHAR(50) DEFAULT '',
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ============================================
   AJAX HANDLER
   ============================================ */
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_REQUEST['action'];

    if ($action === 'list') {
        $rows = [];
        $res = $conn->query("SELECT * FROM instructors ORDER BY id ASC");
        while ($r = $res->fetch_assoc()) {
            $tid = intval($r['id']);
            $u = $conn->query("SELECT COALESCE(SUM(units),0) AS total FROM instructor_subjects WHERE instructor_id = $tid")->fetch_assoc();
            $r['total_units'] = floatval($u['total']);
            $rows[] = $r;
        }
        echo json_encode(['success'=>true, 'data'=>$rows]); exit;
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $res = $conn->query("SELECT * FROM instructors WHERE id = $id");
        $inst = $res->fetch_assoc();
        if (!$inst) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        $subs = [];
        $sr = $conn->query("SELECT * FROM instructor_subjects WHERE instructor_id = $id ORDER BY id ASC");
        while ($s = $sr->fetch_assoc()) $subs[] = $s;
        $sch = [];
        $scr = $conn->query("SELECT * FROM instructor_schedules WHERE instructor_id = $id ORDER BY id ASC");
        while ($s = $scr->fetch_assoc()) $sch[] = $s;
        $inst['subjects'] = $subs;
        $inst['schedules'] = $sch;
        echo json_encode(['success'=>true, 'data'=>$inst]); exit;
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO instructors (full_name, department, gender, birthday, citizenship, civil_status, employment, email, phone, address, status) VALUES (?,?,?,?,?,?,?,?,?,?, 'active')");
        $full = trim($_POST['full_name'] ?? '');
        $dept = trim($_POST['department'] ?? 'CICS');
        $gender = trim($_POST['gender'] ?? '');
        $bday = trim($_POST['birthday'] ?? '') ?: null;
        $cit = trim($_POST['citizenship'] ?? '');
        $civil = trim($_POST['civil_status'] ?? '');
        $emp = trim($_POST['employment'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        if ($full === '') { echo json_encode(['success'=>false,'message'=>'Full name required.']); exit; }
        $stmt->bind_param("ssssssssss", $full, $dept, $gender, $bday, $cit, $civil, $emp, $email, $phone, $addr);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        echo json_encode(['success'=>true, 'id'=>$stmt->insert_id]); exit;
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $full = trim($_POST['full_name'] ?? '');
        $dept = trim($_POST['department'] ?? 'CICS');
        $gender = trim($_POST['gender'] ?? '');
        $bday = trim($_POST['birthday'] ?? '') ?: null;
        $cit = trim($_POST['citizenship'] ?? '');
        $civil = trim($_POST['civil_status'] ?? '');
        $emp = trim($_POST['employment'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        $stmt = $conn->prepare("UPDATE instructors SET full_name=?, department=?, gender=?, birthday=?, citizenship=?, civil_status=?, employment=?, email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $full, $dept, $gender, $bday, $cit, $civil, $emp, $email, $phone, $addr, $id);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'archive') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE instructors SET status='former' WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'restore') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE instructors SET status='active' WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("DELETE FROM instructors WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'add_subject') {
        $iid = intval($_POST['instructor_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $units = floatval($_POST['units'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO instructor_subjects (instructor_id, code, title, units) VALUES (?,?,?,?)");
        $stmt->bind_param("issd", $iid, $code, $title, $units);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'edit_subject') {
        $sid = intval($_POST['subject_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $units = floatval($_POST['units'] ?? 0);
        $stmt = $conn->prepare("UPDATE instructor_subjects SET code=?, title=?, units=? WHERE id=?");
        $stmt->bind_param("ssdi", $code, $title, $units, $sid);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete_subject') {
        $sid = intval($_POST['subject_id'] ?? 0);
        $conn->query("DELETE FROM instructor_subjects WHERE id=$sid");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'add_schedule') {
        $iid = intval($_POST['instructor_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $stmt = $conn->prepare("INSERT INTO instructor_schedules (instructor_id, day, time, room, subject) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $iid, $day, $time, $room, $subject);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'edit_schedule') {
        $sid = intval($_POST['schedule_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $stmt = $conn->prepare("UPDATE instructor_schedules SET day=?, time=?, room=?, subject=? WHERE id=?");
        $stmt->bind_param("ssssi", $day, $time, $room, $subject, $sid);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete_schedule') {
        $sid = intval($_POST['schedule_id'] ?? 0);
        $conn->query("DELETE FROM instructor_schedules WHERE id=$sid");
        echo json_encode(['success'=>true]); exit;
    }

    echo json_encode(['success'=>false, 'message'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Instructor Management | ICF Interworld Colleges Foundation Inc.</title>
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

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      background-color: #0b1120;
      color: var(--text-high-contrast);
      min-height: 100vh;
    }

    /* ============================================
       SIDEBAR
       ============================================ */
    .sidebar {
      background: linear-gradient(180deg, #071f30 0%, #020c1b 100%);
      width: var(--sidebar-collapsed);
      display: flex;
      flex-direction: column;
      position: fixed;
      height: 100vh;
      left: 0;
      top: 0;
      z-index: 100;
      border-right: 1px solid var(--border-teal);
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5);
      overflow-x: visible;
      overflow-y: auto;
      transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.expanded { width: var(--sidebar-expanded); }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }

    .sidebar-toggle {
      position: fixed;
      top: 50%;
      left: calc(var(--sidebar-collapsed) + 8px);
      width: 20px;
      height: 40px;
      background: transparent;
      border: none;
      color: var(--primary-neon);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      z-index: 150;
      transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s ease;
      flex-shrink: 0;
    }

    body.sidebar-open .sidebar-toggle { left: calc(var(--sidebar-expanded) + 8px); }
    .sidebar-toggle:hover { transform: scale(1.2); text-shadow: 0 0 10px rgba(13, 245, 227, 0.5); }
    .sidebar-toggle i { transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
    body.sidebar-open .sidebar-toggle i { transform: rotate(180deg); }

    .school-brand {
      display: flex;
      align-items: center;
      padding: 20px 0 18px;
      border-bottom: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.6);
      flex-shrink: 0;
      min-height: 80px;
      overflow: hidden;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .school-brand:hover { background: rgba(13, 245, 227, 0.03); }

    .school-logo {
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      margin-left: 16px;
      border-radius: 10px;
    }

    .school-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }

    .school-name {
      display: flex;
      flex-direction: column;
      margin-left: 12px;
      white-space: nowrap;
      overflow: hidden;
      opacity: 0;
      width: 0;
      transition: opacity 0.25s ease 0.1s, width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.expanded .school-name { opacity: 1; width: auto; }

    .school-name .title { font-size: 0.95rem; font-weight: 700; color: var(--text-high-contrast); line-height: 1.25; }
    .school-name .sub { font-size: 0.6rem; color: var(--text-muted-teal); margin-top: 3px; letter-spacing: 1.2px; text-transform: uppercase; }

    .nav-links { list-style: none; padding: 8px 0; flex-shrink: 0; }
    .nav-links li { margin-bottom: 3px; }

    .nav-links li a {
      display: flex;
      align-items: center;
      padding: 11px 0;
      color: #94a3b8;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: var(--transition-smooth);
      position: relative;
      white-space: nowrap;
      overflow: hidden;
    }

    .nav-links li a .nav-icon {
      width: var(--sidebar-collapsed);
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      color: #64748b;
      transition: var(--transition-smooth);
    }

    .nav-links li a .nav-label {
      opacity: 0;
      transform: translateX(-10px);
      transition: opacity 0.25s ease 0.05s, transform 0.3s ease 0.05s;
    }

    .sidebar.expanded .nav-links li a .nav-label { opacity: 1; transform: translateX(0); }

    .nav-links li a:hover { background-color: #1e293b; color: var(--primary-neon); }
    .nav-links li a:hover .nav-icon { color: var(--primary-neon); text-shadow: 0 0 8px rgba(13, 245, 227, 0.5); }

    .nav-links li.active a {
      background: linear-gradient(90deg, #1e3a5f 0%, #0f2a45 100%);
      color: var(--primary-neon);
      font-weight: 700;
    }

    .nav-links li.active a::before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 4px;
      background: var(--primary-neon);
      box-shadow: 0 0 10px rgba(13, 245, 227, 0.7);
    }

    .nav-links li.active a .nav-icon { color: var(--primary-neon); }

    .sidebar-footer {
      padding: 12px 0 16px;
      border-top: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.85);
      flex-shrink: 0;
      margin-top: auto;
    }

    .admin-profile-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 8px 0;
      cursor: pointer;
      transition: var(--transition-smooth);
      overflow: hidden;
    }

    .admin-profile-card:hover { background: rgba(13, 245, 227, 0.04); }

    .admin-avatar {
      width: 40px; height: 40px;
      background: linear-gradient(135deg, var(--primary-neon), #009f9a);
      color: #020c1b;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.15rem;
      flex-shrink: 0;
      box-shadow: 0 0 12px rgba(13, 245, 227, 0.4);
      margin-left: 18px;
    }

    .admin-info {
      display: flex;
      flex-direction: column;
      overflow: hidden;
      white-space: nowrap;
      opacity: 0;
      width: 0;
      transition: opacity 0.25s ease 0.05s, width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.expanded .admin-info { opacity: 1; width: auto; flex: 1; }

    .admin-name { font-weight: 600; font-size: 0.85rem; color: var(--text-high-contrast); overflow: hidden; text-overflow: ellipsis; }
    .admin-role { font-size: 0.68rem; color: var(--text-muted-teal); display: flex; align-items: center; gap: 5px; }
    .admin-role i { font-size: 0.58rem; color: var(--primary-neon); }

    .admin-toggle-icon {
      color: var(--text-muted-teal);
      font-size: 0.75rem;
      transition: var(--transition-smooth);
      flex-shrink: 0;
      margin-right: 16px;
      opacity: 0;
      width: 0;
    }

    .sidebar.expanded .admin-toggle-icon { opacity: 1; width: auto; }
    .admin-profile-card.active .admin-toggle-icon { transform: rotate(180deg); color: var(--primary-neon); }

    .sidebar-actions {
      display: flex;
      flex-direction: column;
      gap: 6px;
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      margin-top: 0;
      padding: 0;
      transition: max-height 0.4s ease, opacity 0.3s ease, margin-top 0.3s ease;
    }

    .sidebar.expanded .sidebar-actions.open { max-height: 220px; opacity: 1; margin-top: 10px; padding: 0 12px; }

    .btn-sidebar {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 10px 14px;
      background-color: transparent;
      color: var(--text-muted-teal);
      border: 1px solid var(--border-teal);
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.82rem;
      transition: var(--transition-smooth);
      cursor: pointer;
      white-space: nowrap;
      overflow: hidden;
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
      flex: 1;
      padding: 24px 32px 40px;
      overflow-y: auto;
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
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 24px;
      margin-bottom: 24px;
    }

    .top-header-left h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-high-contrast);
      letter-spacing: -0.3px;
    }

    .top-header-left h1 i { color: var(--primary-neon); margin-right: 10px; }
    .top-header-left p { font-size: 0.82rem; color: var(--text-muted-teal); margin-top: 4px; }

    .live-clock {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 6px 20px;
      background: linear-gradient(135deg, rgba(13, 245, 227, 0.06), rgba(13, 245, 227, 0.01));
      border: 1px solid var(--border-teal);
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
      position: relative;
      overflow: hidden;
      min-width: 200px;
      transition: var(--transition-smooth);
    }

    .live-clock:hover { border-color: var(--primary-neon); box-shadow: 0 6px 25px rgba(13, 245, 227, 0.15); }

    .live-clock::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 2px;
      background: linear-gradient(90deg, transparent, var(--primary-neon), transparent);
      animation: clockPulse 2s ease-in-out infinite;
    }

    @keyframes clockPulse {
      0%, 100% { opacity: 0.4; }
      50% { opacity: 1; }
    }

    .clock-time {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--primary-neon);
      letter-spacing: 1.2px;
      font-variant-numeric: tabular-nums;
      text-shadow: 0 0 12px rgba(13, 245, 227, 0.5);
      line-height: 1;
      font-family: 'Segoe UI', monospace;
    }

    .clock-ampm { font-size: 0.65rem; font-weight: 600; color: var(--primary-neon); margin-left: 3px; opacity: 0.85; vertical-align: super; }
    .clock-date { font-size: 0.62rem; color: var(--text-muted-teal); margin-top: 4px; letter-spacing: 0.4px; text-transform: uppercase; font-weight: 600; }

    /* ============================================
       METRIC CARDS
       ============================================ */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 24px;
    }

    .metric-card {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 20px;
      display: flex;
      flex-direction: column;
      transition: var(--transition-smooth);
      box-shadow: var(--card-shadow);
      position: relative;
      overflow: hidden;
    }

    .metric-card:hover {
      transform: translateY(-4px);
      border-color: var(--primary-neon);
      box-shadow: 0 12px 28px rgba(13, 245, 227, 0.15);
    }

    .metric-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 3px;
      background: linear-gradient(90deg, var(--primary-neon), #00b4d8);
      opacity: 0.8;
    }

    .metric-card:nth-child(2)::before { background: linear-gradient(90deg, var(--success-green), #34d399); }
    .metric-card:nth-child(3)::before { background: linear-gradient(90deg, var(--warning-amber), #fbbf24); }
    .metric-card:nth-child(4)::before { background: linear-gradient(90deg, var(--purple-accent), #a78bfa); }

    .metric-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .metric-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted-teal); font-weight: 600; }

    .metric-icon {
      width: 38px; height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      color: var(--primary-neon);
      background: rgba(13, 245, 227, 0.1);
    }

    .metric-card:nth-child(2) .metric-icon { color: var(--success-green); background: rgba(16, 185, 129, 0.1); }
    .metric-card:nth-child(3) .metric-icon { color: var(--warning-amber); background: rgba(245, 158, 11, 0.1); }
    .metric-card:nth-child(4) .metric-icon { color: var(--purple-accent); background: rgba(139, 92, 246, 0.1); }

    .metric-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-high-contrast);
      line-height: 1.15;
      letter-spacing: -0.5px;
    }

    /* ============================================
       DEPARTMENT SECTIONS
       ============================================ */
    .department-section { margin-bottom: 32px; }

    .department-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      background: var(--bg-card);
      padding: 18px 22px;
      border-radius: var(--border-radius);
      border: 1px solid var(--border-teal);
      border-left: 4px solid var(--primary-neon);
      box-shadow: var(--card-shadow);
      gap: 15px;
      flex-wrap: wrap;
    }

    .department-header h2 {
      color: var(--primary-neon);
      font-size: 1.05rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .department-header h2 i { font-size: 1rem; }

    .department-actions { display: flex; gap: 10px; }

    /* ============================================
       TABLES
       ============================================ */
    .table-card {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 22px 24px;
      box-shadow: var(--card-shadow);
      transition: var(--transition-smooth);
      margin-bottom: 24px;
    }

    .table-card:hover { border-color: #1f3a5f; }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      gap: 15px;
      flex-wrap: wrap;
    }

    .table-header h2 {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-high-contrast);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .table-header h2 i { color: var(--primary-neon); font-size: 0.95rem; }

    .table-wrapper {
      overflow-x: auto;
      max-height: 640px;
      overflow-y: auto;
    }

    .table-wrapper::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-wrapper::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }

    table { width: 100%; border-collapse: collapse; text-align: left; }

    th {
      padding: 12px 16px;
      font-size: 0.73rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--text-muted-teal);
      font-weight: 600;
      border-bottom: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.6);
      white-space: nowrap;
      position: sticky;
      top: 0;
      z-index: 2;
    }

    td {
      padding: 14px 16px;
      color: var(--text-high-contrast);
      border-bottom: 1px solid #1a2d45;
      font-size: 0.87rem;
    }

    tr:last-child td { border-bottom: none; }
    tr:hover td { background-color: rgba(13, 245, 227, 0.03); }

    /* ============================================
       SEARCH INPUT
       ============================================ */
    .search-wrap { position: relative; }

    .search-wrap input {
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

    .search-wrap input:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1); }

    .search-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted-teal);
      font-size: 0.85rem;
      pointer-events: none;
    }

    /* ============================================
       BUTTONS
       ============================================ */
    .btn {
      padding: 8px 18px;
      border: 1px solid var(--border-teal);
      background: transparent;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.82rem;
      font-weight: 500;
      transition: var(--transition-smooth);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--text-muted-teal);
    }

    .btn:hover { background: #1e293b; color: var(--text-high-contrast); border-color: #2d4a6b; }
    .btn.primary { background: var(--primary-neon); color: #020c1b; border: none; font-weight: 600; }
    .btn.primary:hover { background: var(--primary-neon-hover); box-shadow: var(--neon-glow); }

    .btn-action {
      padding: 5px 12px;
      background: transparent;
      border: 1px solid var(--border-teal);
      color: var(--text-muted-teal);
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.75rem;
      font-weight: 500;
      transition: var(--transition-smooth);
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn-action:hover { background: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }

    .btn-action.view { border-color: rgba(13, 245, 227, 0.5); color: var(--primary-neon); }
    .btn-action.view:hover { background: var(--primary-neon); color: #020c1b; box-shadow: var(--neon-glow); }

    .btn-action.archive { border-color: rgba(245, 158, 11, 0.5); color: #fbbf24; }
    .btn-action.archive:hover { background: var(--warning-amber); color: white; box-shadow: 0 0 12px rgba(245, 158, 11, 0.4); }

    .btn-action.restore { border-color: rgba(16, 185, 129, 0.5); color: #34d399; }
    .btn-action.restore:hover { background: var(--success-green); color: white; box-shadow: 0 0 12px rgba(16, 185, 129, 0.4); }

    .btn-action.delete { border-color: rgba(239, 68, 68, 0.5); color: #f87171; }
    .btn-action.delete:hover { background: rgba(239, 68, 68, 0.15); border-color: #ef4444; color: #fca5a5; }

    .action-group { display: flex; gap: 6px; flex-wrap: wrap; }

    /* ============================================
       MODALS
       ============================================ */
    .operation-modal-overlay,
    .view-modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(2, 12, 27, 0.9);
      backdrop-filter: blur(6px);
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .operation-modal-overlay { z-index: 5000; }
    .view-modal-overlay { z-index: 3000; }
    .operation-modal-overlay.active, .view-modal-overlay.active { display: flex; }

    .operation-modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      padding: 30px;
      color: var(--text-high-contrast);
      text-align: center;
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .operation-modal-overlay.active .operation-modal-box { transform: translateY(0); }

    .operation-modal-box.success {
      border-color: rgba(16, 185, 129, 0.4);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(16, 185, 129, 0.15);
    }

    .operation-modal-box.warning {
      border-color: rgba(245, 158, 11, 0.4);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(245, 158, 11, 0.15);
    }

    .operation-icon { font-size: 3rem; margin-bottom: 15px; }
    .operation-modal-box.success .operation-icon { color: var(--success-green); filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.5)); }
    .operation-modal-box.warning .operation-icon { color: var(--warning-amber); filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.5)); }
    .operation-modal-box h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 10px; }
    .operation-modal-box p { font-size: 0.85rem; color: var(--text-muted-teal); margin-bottom: 20px; line-height: 1.6; }

    .operation-form-group { margin-bottom: 15px; text-align: left; }

    .operation-form-group label {
      display: block;
      font-size: 0.72rem;
      color: var(--text-muted-teal);
      font-weight: 600;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .operation-form-group input {
      width: 100%;
      padding: 10px 14px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 8px;
      font-size: 0.88rem;
      color: var(--text-high-contrast);
      outline: none;
      font-family: inherit;
      transition: var(--transition-smooth);
    }

    .operation-form-group input:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1); }

    .operation-form-group input[type="radio"] { width: auto; margin-right: 5px; }

    .operation-form-group .radio-label {
      display: inline-block;
      margin-right: 15px;
      text-transform: none;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-high-contrast);
    }

    .operation-modal-actions { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 20px; }

    .op-btn {
      padding: 11px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.85rem;
      font-family: inherit;
      transition: var(--transition-smooth);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .op-btn.cancel { background: transparent; color: var(--text-muted-teal); border: 1px solid var(--border-teal); }
    .op-btn.cancel:hover { background: var(--bg-card-hover); color: var(--text-high-contrast); }
    .op-btn.confirm-success { background: var(--primary-neon); color: #020c1b; }
    .op-btn.confirm-success:hover { background: var(--primary-neon-hover); box-shadow: var(--neon-glow); }
    .op-btn.confirm-danger { background: #ef4444; color: white; }
    .op-btn.confirm-danger:hover { background: #dc2626; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }

    /* ============================================
       VIEW MODAL
       ============================================ */
    .view-modal-container {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 900px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9);
      color: var(--text-high-contrast);
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .view-modal-overlay.active .view-modal-container { transform: translateY(0); }

    .view-modal-header {
      background: #06152b;
      padding: 18px 24px;
      border-bottom: 1px solid var(--border-teal);
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 10;
      border-radius: 16px 16px 0 0;
    }

    .view-modal-header h3 {
      font-size: 1.05rem;
      color: var(--primary-neon);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .view-modal-close {
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      font-size: 1.4rem;
      cursor: pointer;
      transition: color 0.2s ease;
      line-height: 1;
    }

    .view-modal-close:hover { color: #ef4444; }

    .view-modal-body { padding: 24px; }

    .profile-pic-container { text-align: center; margin-bottom: 24px; }

    .profile-pic {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid var(--primary-neon);
      box-shadow: var(--neon-glow);
      background: var(--bg-deep-abyss);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-neon);
      margin: 0 auto;
    }

    .profile-pic-container h2 { margin-top: 12px; color: var(--primary-neon); font-size: 1.2rem; font-weight: 700; }
    .profile-pic-container p { color: var(--text-muted-teal); font-size: 0.85rem; margin-top: 4px; }

    .info-section { margin-bottom: 28px; }

    .info-section-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--primary-neon);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border-bottom: 1px solid var(--border-teal);
      padding-bottom: 10px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .info-item { display: flex; gap: 10px; font-size: 0.85rem; }
    .info-label { width: 130px; color: var(--text-muted-teal); font-weight: 600; flex-shrink: 0; }
    .info-value { color: var(--text-high-contrast); flex: 1; word-break: break-word; }

    .subject-table, .schedule-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.83rem;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border-teal);
    }

    .subject-table th, .subject-table td,
    .schedule-table th, .schedule-table td {
      padding: 11px 12px;
      border-bottom: 1px solid #1a2d45;
      text-align: left;
      color: var(--text-high-contrast);
    }

    .subject-table th, .schedule-table th {
      background: rgba(2, 12, 27, 0.7);
      color: var(--primary-neon);
      font-weight: 600;
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .subject-table tr:last-child td, .schedule-table tr:last-child td { border-bottom: none; }
    .subject-table tr:hover, .schedule-table tr:hover { background: rgba(13, 245, 227, 0.03); }

    .btn-small {
      padding: 5px 12px;
      border: 1px solid var(--border-teal);
      background: transparent;
      color: var(--text-muted-teal);
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.72rem;
      font-weight: 500;
      transition: var(--transition-smooth);
      font-family: inherit;
    }

    .btn-small:hover { background: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }
    .btn-small.add { border-color: rgba(13, 245, 227, 0.4); color: var(--primary-neon); }
    .btn-small.add:hover { background: var(--primary-neon); color: #020c1b; }

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
      .info-grid { grid-template-columns: 1fr; }
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
      <li>
        <a href="adminpanel.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
          <span class="nav-label">Overview</span>
        </a>
      </li>
      <li>
        <a href="users.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
          <span class="nav-label">Users</span>
        </a>
      </li>
      <li>
        <a href="enrollments.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-file-signature"></i></span>
          <span class="nav-label">Enrollments</span>
        </a>
      </li>
      <li>
        <a href="enrolled.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-user-graduate"></i></span>
          <span class="nav-label">Enrolled</span>
        </a>
      </li>
      <li class="active">
        <a href="instructor.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
          <span class="nav-label">Instructors</span>
        </a>
      </li>
      <li>
        <a href="curriculum.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-book"></i></span>
          <span class="nav-label">Curriculum</span>
        </a>
      </li>
      <li>
        <a href="received-mail.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-envelope"></i></span>
          <span class="nav-label">Received Mail</span>
        </a>
      </li>
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
        <a href="profile-settings.php" class="btn-sidebar profile-settings">
          <i class="fa-solid fa-gear"></i> Profile Settings
        </a>
        <a href="../../index.html" class="btn-sidebar homepage">
          <i class="fa-solid fa-house"></i> Homepage
        </a>
        <a href="logout.php" class="btn-sidebar logout">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </div>
    </div>
  </aside>

  <main class="main-content" id="mainContent">
    <header class="top-header">
      <div class="top-header-left">
        <h1><i class="fa-solid fa-chalkboard-user"></i> Instructor Management</h1>
        <p>Manage active and former instructors across all college departments.</p>
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
          <span class="metric-label">Total Instructors</span>
          <div class="metric-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
        </div>
        <div class="metric-value" id="totalInstructorsCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Active Instructors</span>
          <div class="metric-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="metric-value" id="activeInstructorsCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Former Instructors</span>
          <div class="metric-icon"><i class="fa-solid fa-user-clock"></i></div>
        </div>
        <div class="metric-value" id="formerInstructorsCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Total Departments</span>
          <div class="metric-icon"><i class="fa-solid fa-building-columns"></i></div>
        </div>
        <div class="metric-value" id="totalDepartmentsCard">2</div>
      </div>
    </div>

    <!-- ACTIVE INSTRUCTORS -->
    <div class="department-section">
      <div class="department-header">
        <h2><i class="fa-solid fa-user-check"></i> Active Instructors by Department</h2>
        <div class="department-actions">
          <button class="btn primary" onclick="openAddInstructorModal()"><i class="fas fa-plus"></i> Add Instructor</button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h2><i class="fa-solid fa-laptop-code"></i> CICS - College of Information and Computing Sciences</h2>
        </div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="CICS" data-status="active">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Department</th>
                <th>Units Handling</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h2><i class="fa-solid fa-graduation-cap"></i> EDUC - College of Education</h2>
        </div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="EDUC" data-status="active">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Department</th>
                <th>Units Handling</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- FORMER INSTRUCTORS -->
    <div class="department-section">
      <div class="department-header">
        <h2><i class="fa-solid fa-user-clock"></i> Former Instructors by Department</h2>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h2><i class="fa-solid fa-laptop-code"></i> CICS - Former Instructors</h2>
        </div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="CICS" data-status="former">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Department</th>
                <th>Units Handling</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h2><i class="fa-solid fa-graduation-cap"></i> EDUC - Former Instructors</h2>
        </div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="EDUC" data-status="former">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Department</th>
                <th>Units Handling</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- VIEW INSTRUCTOR MODAL -->
  <div class="view-modal-overlay" id="viewInstructorModal">
    <div class="view-modal-container">
      <div class="view-modal-header">
        <h3><i class="fas fa-user-tie"></i> Instructor Full Details</h3>
        <button class="view-modal-close" onclick="closeViewInstructorModal()">&times;</button>
      </div>
      <div class="view-modal-body" id="viewInstructorBody"></div>
    </div>
  </div>

  <!-- OPERATION MODAL -->
  <div class="operation-modal-overlay" id="operationModal">
    <div class="operation-modal-box" id="operationBox">
      <div class="operation-icon" id="operationIcon"></div>
      <h3 id="operationTitle"></h3>
      <p id="operationMessage"></p>
      <div id="operationFormContainer" style="display: none;"></div>
      <div class="operation-modal-actions" id="operationActions"></div>
    </div>
  </div>

  <script>
    /* ============================================
       SIDEBAR — hover expands, click pins
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
      const actions = document.getElementById('adminActions');
      const card = document.getElementById('adminProfileToggle');
      actions.classList.remove('open');
      card.classList.remove('active');
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
        const actions = document.getElementById('adminActions');
        const card = document.getElementById('adminProfileToggle');
        actions.classList.toggle('open');
        card.classList.toggle('active');
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
      hours = hours % 12;
      hours = hours ? hours : 12;
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
       INSTRUCTOR LOGIC
       ============================================ */
    let operationCallback = null;
    let currentInstructorId = null;
    let currentInstructorData = null;

    function escapeHtml(s) {
      return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    async function apiPost(action, data = {}) {
      const fd = new FormData();
      fd.append('action', action);
      for (const k in data) fd.append(k, data[k]);
      const res = await fetch('instructor.php', { method: 'POST', body: fd });
      return res.json();
    }

    function formatPhoneInput(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length > 11) value = value.substring(0, 11);
      let f = '';
      if (value.length > 0) f += value.substring(0, 4);
      if (value.length >= 5) f += '-' + value.substring(4, 7);
      if (value.length >= 8) f += '-' + value.substring(7, 11);
      input.value = f;
    }

    /* ============ Modals ============ */
    function showSuccessModal(title, message) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      box.className = 'operation-modal-box success';
      document.getElementById('operationIcon').innerHTML = '<i class="fas fa-check-circle"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = message;
      document.getElementById('operationFormContainer').style.display = 'none';
      document.getElementById('operationActions').innerHTML = `<button class="op-btn confirm-success" onclick="closeOperationModal()">OK</button>`;
      modal.classList.add('active');
    }

    function showWarningModal(title, message, confirmText, callback) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      box.className = 'operation-modal-box warning';
      document.getElementById('operationIcon').innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = message;
      document.getElementById('operationFormContainer').style.display = 'none';
      operationCallback = callback;
      document.getElementById('operationActions').innerHTML = `
        <button class="op-btn cancel" onclick="closeOperationModal()">Cancel</button>
        <button class="op-btn confirm-danger" onclick="executeOperation()">${confirmText}</button>`;
      modal.classList.add('active');
    }

    function showInputModal(title, fields, callback) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      box.className = 'operation-modal-box';
      document.getElementById('operationIcon').innerHTML = '<i class="fas fa-edit"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = '';
      let formHtml = '';
      fields.forEach((field, i) => {
        if (field.type === 'radio') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label>${field.options.map(opt => `<label class="radio-label"><input type="radio" name="op_radio_${i}" value="${opt}" ${field.value === opt ? 'checked' : ''}> ${opt}</label>`).join('')}</div>`;
        } else if (field.type === 'date') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="date" id="op_field_${i}" value="${field.value || ''}" /></div>`;
        } else if (field.type === 'phone') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" inputmode="numeric" id="op_field_${i}" value="${field.value || ''}" oninput="formatPhoneInput(this)" placeholder="0912-345-6789" /></div>`;
        } else {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" id="op_field_${i}" value="${escapeHtml(field.value || '')}" /></div>`;
        }
      });
      document.getElementById('operationFormContainer').innerHTML = formHtml;
      document.getElementById('operationFormContainer').style.display = 'block';
      operationCallback = callback;
      document.getElementById('operationActions').innerHTML = `
        <button class="op-btn cancel" onclick="closeOperationModal()">Cancel</button>
        <button class="op-btn confirm-success" onclick="executeInputOperation()">Save</button>`;
      modal.classList.add('active');
    }

    function executeOperation() { const cb = operationCallback; closeOperationModal(); if (cb) cb(); }

    function executeInputOperation() {
      const cb = operationCallback;
      const values = [];
      document.querySelectorAll('#operationFormContainer input').forEach(input => {
        if (input.type === 'radio') { if (input.checked) values.push(input.value); }
        else values.push(input.value);
      });
      closeOperationModal();
      if (cb) cb(values);
    }

    function closeOperationModal() {
      document.getElementById('operationModal').classList.remove('active');
      operationCallback = null;
    }

    /* ============ Load instructors ============ */
    async function loadInstructors() {
      const r = await apiPost('list');
      if (!r.success) return;
      const all = r.data;

      document.querySelectorAll('.instructor-table').forEach(table => {
        const dept = table.getAttribute('data-department');
        const status = table.getAttribute('data-status');
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        const filtered = all.filter(i => i.department === dept && i.status === status);
        if (filtered.length === 0) {
          tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#8892b0; padding:24px;">No instructors found in this section.</td></tr>`;
        } else {
          filtered.forEach(i => {
            const actions = status === 'active'
              ? `<button class="btn-action view" onclick="viewInstructor(${i.id})"><i class="fas fa-eye"></i> View</button>
                 <button class="btn-action archive" onclick="archiveInstructor(${i.id})"><i class="fas fa-archive"></i> Archive</button>`
              : `<button class="btn-action view" onclick="viewInstructor(${i.id})"><i class="fas fa-eye"></i> View</button>
                 <button class="btn-action restore" onclick="restoreInstructor(${i.id})"><i class="fas fa-undo"></i> Restore</button>
                 <button class="btn-action delete" onclick="deleteInstructor(${i.id})"><i class="fas fa-trash"></i> Delete</button>`;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(i.full_name)}</td><td>${escapeHtml(i.department)}</td><td>${i.total_units}</td><td><div class="action-group">${actions}</div></td>`;
            tbody.appendChild(tr);
          });
        }
      });

      document.getElementById('totalInstructorsCard').textContent = all.length;
      document.getElementById('activeInstructorsCard').textContent = all.filter(i => i.status === 'active').length;
      document.getElementById('formerInstructorsCard').textContent = all.filter(i => i.status === 'former').length;
    }

    /* ============ View instructor ============ */
    async function viewInstructor(id) {
      currentInstructorId = id;
      const r = await apiPost('get', { id });
      if (!r.success) { alert(r.message); return; }
      currentInstructorData = r.data;
      const inst = r.data;
      const canEdit = inst.status === 'active';
      const initials = (inst.full_name || '').split(' ').filter(w => w[0] && w[0] === w[0].toUpperCase()).map(w => w[0]).slice(0,2).join('');
      const totalUnits = inst.subjects.reduce((s, x) => s + parseFloat(x.units), 0);

      let subjectHtml = '';
      inst.subjects.forEach(s => {
        subjectHtml += `<tr><td>${escapeHtml(s.code)}</td><td>${escapeHtml(s.title)}</td><td>${s.units}</td>${canEdit ? `<td><div class="action-group"><button class="btn-small" onclick="editSubject(${s.id})">Edit</button> <button class="btn-small" onclick="deleteSubject(${s.id})">Delete</button></div></td>` : ''}</tr>`;
      });
      if (inst.subjects.length === 0) {
        subjectHtml = `<tr><td colspan="${canEdit ? 4 : 3}" style="text-align:center; color:#8892b0;">No subjects assigned.</td></tr>`;
      }

      let scheduleHtml = '';
      inst.schedules.forEach(s => {
        scheduleHtml += `<tr><td>${escapeHtml(s.day)}</td><td>${escapeHtml(s.time)}</td><td>${escapeHtml(s.room)}</td><td>${escapeHtml(s.subject)}</td>${canEdit ? `<td><div class="action-group"><button class="btn-small" onclick="editSchedule(${s.id})">Edit</button> <button class="btn-small" onclick="deleteSchedule(${s.id})">Delete</button></div></td>` : ''}</tr>`;
      });
      if (inst.schedules.length === 0) {
        scheduleHtml = `<tr><td colspan="${canEdit ? 5 : 4}" style="text-align:center; color:#8892b0;">No schedule entries.</td></tr>`;
      }

      document.getElementById('viewInstructorBody').innerHTML = `
        <div class="profile-pic-container">
          <div class="profile-pic">${initials}</div>
          <h2>${escapeHtml(inst.full_name)}</h2>
          <p>${escapeHtml(inst.department)} Department</p>
          <p style="color:var(--text-muted-teal); margin-top:6px;">Total Units Handling: <strong style="color:var(--primary-neon); font-weight:700;">${totalUnits}</strong></p>
        </div>
        <div class="info-section">
          <div class="info-section-title"><i class="fas fa-user"></i> Bio Data</div>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Gender:</span><span class="info-value">${escapeHtml(inst.gender || 'N/A')}</span></div>
            <div class="info-item"><span class="info-label">Birthday:</span><span class="info-value">${escapeHtml(inst.birthday || 'N/A')}</span></div>
            <div class="info-item"><span class="info-label">Citizenship:</span><span class="info-value">${escapeHtml(inst.citizenship || 'N/A')}</span></div>
            <div class="info-item"><span class="info-label">Civil Status:</span><span class="info-value">${escapeHtml(inst.civil_status || 'N/A')}</span></div>
            <div class="info-item"><span class="info-label">Employment:</span><span class="info-value">${escapeHtml(inst.employment || 'N/A')}</span></div>
          </div>
        </div>
        <div class="info-section">
          <div class="info-section-title"><i class="fas fa-address-book"></i> Contact Info</div>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Email:</span><span class="info-value">${escapeHtml(inst.email || 'N/A')}</span></div>
            <div class="info-item"><span class="info-label">Phone:</span><span class="info-value">${escapeHtml(inst.phone || 'N/A')}</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">${escapeHtml(inst.address || 'N/A')}</span></div>
          </div>
        </div>
        <div class="info-section">
          <div class="info-section-title"><i class="fas fa-book"></i> Subject Handling</div>
          ${canEdit ? `<div style="margin-bottom:12px;"><button class="btn-small add" onclick="addSubject()"><i class="fas fa-plus"></i> Add Subject</button></div>` : ''}
          <table class="subject-table">
            <thead><tr><th>Code</th><th>Title</th><th>Units</th>${canEdit ? '<th>Actions</th>' : ''}</tr></thead>
            <tbody>${subjectHtml}</tbody>
          </table>
        </div>
        <div class="info-section">
          <div class="info-section-title"><i class="fas fa-calendar-alt"></i> Schedule List</div>
          ${canEdit ? `<div style="margin-bottom:12px;"><button class="btn-small add" onclick="addSchedule()"><i class="fas fa-plus"></i> Add Schedule</button></div>` : ''}
          <table class="schedule-table">
            <thead><tr><th>Day</th><th>Time</th><th>Room</th><th>Subject</th>${canEdit ? '<th>Actions</th>' : ''}</tr></thead>
            <tbody>${scheduleHtml}</tbody>
          </table>
        </div>
        ${canEdit ? `<div style="text-align:center; margin-top:24px;"><button class="btn primary" onclick="openEditInstructorModal()"><i class="fas fa-edit"></i> Edit Instructor</button></div>` : ''}`;

      document.getElementById('viewInstructorModal').classList.add('active');
    }

    function closeViewInstructorModal() { document.getElementById('viewInstructorModal').classList.remove('active'); }

    /* ============ Add / Edit instructor ============ */
    function openAddInstructorModal() {
      showInputModal("Add New Instructor", [
        {label:"Full Name", value:""},
        {label:"Department", value:"CICS"},
        {label:"Gender", type:"radio", options:["Male","Female"], value:"Male"},
        {label:"Birthday", type:"date", value:""},
        {label:"Citizenship", value:"Filipino"},
        {label:"Civil Status", type:"radio", options:["Single","Married"], value:"Single"},
        {label:"Employment", type:"radio", options:["Full-Time","Part-Time"], value:"Full-Time"},
        {label:"Email", value:""},
        {label:"Phone Number", type:"phone", value:""},
        {label:"Address", value:""}
      ], async (v) => {
        if (!v[0]) { showWarningModal("Missing Information", "Please enter the Instructor's Full Name.", "OK", null); return; }
        const r = await apiPost('add', {
          full_name: v[0], department: v[1] || "CICS",
          gender: v[2] || "", birthday: v[3] || "",
          citizenship: v[4] || "", civil_status: v[5] || "",
          employment: v[6] || "", email: v[7] || "",
          phone: v[8] || "", address: v[9] || ""
        });
        if (r.success) { await loadInstructors(); showSuccessModal("Added!", "New instructor added successfully."); }
        else alert(r.message);
      });
    }

    function openEditInstructorModal() {
      const i = currentInstructorData;
      if (!i) return;
      showInputModal("Edit Instructor Information", [
        {label:"Full Name", value:i.full_name},
        {label:"Department", value:i.department},
        {label:"Gender", type:"radio", options:["Male","Female"], value:i.gender},
        {label:"Birthday", type:"date", value:i.birthday || ''},
        {label:"Citizenship", value:i.citizenship},
        {label:"Civil Status", type:"radio", options:["Single","Married"], value:i.civil_status},
        {label:"Employment", type:"radio", options:["Full-Time","Part-Time"], value:i.employment},
        {label:"Email", value:i.email},
        {label:"Phone Number", type:"phone", value:i.phone},
        {label:"Address", value:i.address}
      ], async (v) => {
        const r = await apiPost('edit', {
          id: i.id,
          full_name: v[0] || i.full_name,
          department: v[1] || i.department,
          gender: v[2] || i.gender,
          birthday: v[3] || i.birthday,
          citizenship: v[4] || i.citizenship,
          civil_status: v[5] || i.civil_status,
          employment: v[6] || i.employment,
          email: v[7] || i.email,
          phone: v[8] || i.phone,
          address: v[9] || i.address
        });
        if (r.success) {
          await loadInstructors();
          closeViewInstructorModal();
          showSuccessModal("Updated!", "Instructor information updated successfully.");
        } else alert(r.message);
      });
    }

    /* ============ Subject CRUD ============ */
    function addSubject() {
      showInputModal("Add Subject", [{label:"Code",value:""},{label:"Title",value:""},{label:"Units",value:"3"}], async (v) => {
        const r = await apiPost('add_subject', {
          instructor_id: currentInstructorId,
          code: v[0] || "", title: v[1] || "", units: v[2] || 0
        });
        if (r.success) {
          await viewInstructor(currentInstructorId);
          await loadInstructors();
          showSuccessModal("Added!", "Subject added successfully.");
        } else alert(r.message);
      });
    }

    function editSubject(subjectId) {
      const s = currentInstructorData.subjects.find(x => x.id == subjectId);
      if (!s) return;
      showInputModal("Edit Subject", [{label:"Code",value:s.code},{label:"Title",value:s.title},{label:"Units",value:s.units}], async (v) => {
        const r = await apiPost('edit_subject', {
          subject_id: subjectId,
          code: v[0] || s.code, title: v[1] || s.title, units: v[2] || s.units
        });
        if (r.success) {
          await viewInstructor(currentInstructorId);
          await loadInstructors();
          showSuccessModal("Updated!", "Subject updated successfully.");
        } else alert(r.message);
      });
    }

    function deleteSubject(subjectId) {
      showWarningModal("Delete Subject", "Are you sure you want to delete this subject?", "Delete", async () => {
        const r = await apiPost('delete_subject', { subject_id: subjectId });
        if (r.success) {
          await viewInstructor(currentInstructorId);
          await loadInstructors();
          showSuccessModal("Deleted!", "Subject deleted successfully.");
        } else alert(r.message);
      });
    }

    /* ============ Schedule CRUD ============ */
    function addSchedule() {
      showInputModal("Add Schedule", [
        {label:"Day (e.g. MON/WED)",value:""},
        {label:"Time (e.g. 07:30 AM - 09:00 AM)",value:""},
        {label:"Room",value:""},
        {label:"Subject Code",value:""}
      ], async (v) => {
        const r = await apiPost('add_schedule', {
          instructor_id: currentInstructorId,
          day: v[0] || "", time: v[1] || "", room: v[2] || "", subject: v[3] || ""
        });
        if (r.success) { await viewInstructor(currentInstructorId); showSuccessModal("Added!", "Schedule added successfully."); }
        else alert(r.message);
      });
    }

    function editSchedule(scheduleId) {
      const s = currentInstructorData.schedules.find(x => x.id == scheduleId);
      if (!s) return;
      showInputModal("Edit Schedule", [
        {label:"Day",value:s.day},{label:"Time",value:s.time},{label:"Room",value:s.room},{label:"Subject Code",value:s.subject}
      ], async (v) => {
        const r = await apiPost('edit_schedule', {
          schedule_id: scheduleId,
          day: v[0] || s.day, time: v[1] || s.time, room: v[2] || s.room, subject: v[3] || s.subject
        });
        if (r.success) { await viewInstructor(currentInstructorId); showSuccessModal("Updated!", "Schedule updated successfully."); }
        else alert(r.message);
      });
    }

    function deleteSchedule(scheduleId) {
      showWarningModal("Delete Schedule", "Are you sure you want to delete this schedule?", "Delete", async () => {
        const r = await apiPost('delete_schedule', { schedule_id: scheduleId });
        if (r.success) { await viewInstructor(currentInstructorId); showSuccessModal("Deleted!", "Schedule deleted successfully."); }
        else alert(r.message);
      });
    }

    /* ============ Archive / Restore / Delete ============ */
    function archiveInstructor(id) {
      showWarningModal("Archive Instructor", "Are you sure you want to archive this instructor? They will move to Former Instructors.", "Archive", async () => {
        const r = await apiPost('archive', { id });
        if (r.success) { await loadInstructors(); showSuccessModal("Archived!", "Instructor moved to Former Instructors."); }
        else alert(r.message);
      });
    }

    function restoreInstructor(id) {
      showWarningModal("Restore Instructor", "Are you sure you want to restore this instructor?", "Restore", async () => {
        const r = await apiPost('restore', { id });
        if (r.success) { await loadInstructors(); showSuccessModal("Restored!", "Instructor restored."); }
        else alert(r.message);
      });
    }

    function deleteInstructor(id) {
      showWarningModal("Delete Instructor", "Are you sure you want to permanently delete this instructor?", "Delete", async () => {
        const r = await apiPost('delete', { id });
        if (r.success) { await loadInstructors(); showSuccessModal("Deleted!", "Instructor deleted permanently."); }
        else alert(r.message);
      });
    }

    /* ============ Click outside modals ============ */
    document.getElementById('viewInstructorModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewInstructorModal();
    });
    document.getElementById('operationModal').addEventListener('click', function(e) {
      if (e.target === this) closeOperationModal();
    });

    document.addEventListener('DOMContentLoaded', loadInstructors);
  </script>
</body>
</html>