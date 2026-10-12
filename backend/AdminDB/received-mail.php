<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

/* =====================================================
   CHECK MAIL TABLES
   ===================================================== */
$receivedTable = $conn->query("SHOW TABLES LIKE 'received_mail'") && $conn->query("SHOW TABLES LIKE 'received_mail'")->num_rows > 0;
$composedTable = $conn->query("SHOW TABLES LIKE 'composed_mail'") && $conn->query("SHOW TABLES LIKE 'composed_mail'")->num_rows > 0;

/* =====================================================
   COMPOSE / SAVE MESSAGE
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compose_submit'])) {
    $sender = 'ICFAdmin';
    $email = trim($_POST['recipientEmail'] ?? '');
    $receiver = trim($_POST['recipientName'] ?? '');
    $subject = trim($_POST['emailSubject'] ?? '');
    $message = trim($_POST['emailMessage'] ?? '');
    $date = date('Y-m-d');
    $editingId = intval($_POST['editingId'] ?? 0);

    if ($composedTable) {
        if ($editingId > 0) {
            $stmt = $conn->prepare("UPDATE composed_mail SET email=?, receiver=?, subject=?, message=?, date_sent=? WHERE id=?");
            $stmt->bind_param("sssssi", $email, $receiver, $subject, $message, $date, $editingId);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO composed_mail (sender, email, receiver, subject, message, date_sent, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending Send')");
            $stmt->bind_param("ssssss", $sender, $email, $receiver, $subject, $message, $date);
            $stmt->execute();
        }
    }
    header("Location: received-mail.php?success=1");
    exit();
}

/* =====================================================
   SEND / CANCEL COMPOSED MESSAGE
   ===================================================== */
if (isset($_GET['send'])) {
    $id = intval($_GET['send']);
    if ($composedTable) $conn->query("UPDATE composed_mail SET status='Sent' WHERE id=$id");
    header("Location: received-mail.php?sent=1");
    exit();
}

if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    if ($composedTable) $conn->query("UPDATE composed_mail SET status='Cancelled' WHERE id=$id");
    header("Location: received-mail.php?cancelled=1");
    exit();
}

/* =====================================================
   FETCH MAIL DATA
   ===================================================== */
$received = [];
if ($receivedTable) {
    $res = $conn->query("SELECT * FROM received_mail ORDER BY date_received DESC, id DESC");
    while ($row = $res->fetch_assoc()) { $received[] = $row; }
}

$composed = [];
if ($composedTable) {
    $res = $conn->query("SELECT * FROM composed_mail ORDER BY date_sent DESC, id DESC");
    while ($row = $res->fetch_assoc()) { $composed[] = $row; }
}

/* =====================================================
   FALLBACK DEMO DATA
   ===================================================== */
if (!$receivedTable) {
    $received = [
        ['id'=>1, 'email'=>'clarissa.villamor@gmail.com', 'sender_name'=>'Clarissa Villamor', 'subject'=>'Inquiry Regarding BS Computer Science Tuition Fees', 'date_received'=>'2026-08-05', 'status'=>'unread', 'message'=>'Hello, I would like to inquire about the tuition fees for BS Computer Science.'],
        ['id'=>2, 'email'=>'mark.reyes@yahoo.com', 'sender_name'=>'Mark Anthony Reyes', 'subject'=>'Request for Transcript of Records (TOR)', 'date_received'=>'2026-08-04', 'status'=>'urgent', 'message'=>'I need my TOR as soon as possible for my job application.'],
        ['id'=>3, 'email'=>'jasmine.cruz@gmail.com', 'sender_name'=>'Jasmine Cruz', 'subject'=>'Status of Academic Scholarship Application', 'date_received'=>'2026-08-03', 'status'=>'read', 'message'=>'May I know the status of my scholarship application?'],
    ];
}
if (!$composedTable) {
    $composed = [
        ['id'=>1, 'sender'=>'ICFAdmin', 'email'=>'clarissa.villamor@gmail.com', 'receiver'=>'Clarissa Villamor', 'date_sent'=>'2026-08-06', 'status'=>'Sent', 'subject'=>'Tuition Fee Response', 'message'=>'Hello Clarissa, here are the details regarding the BS Computer Science tuition fees...'],
        ['id'=>2, 'sender'=>'ICFAdmin', 'email'=>'mark.reyes@yahoo.com', 'receiver'=>'Mark Anthony Reyes', 'date_sent'=>'2026-08-06', 'status'=>'Pending Send', 'subject'=>'TOR Request Status', 'message'=>'Dear Mark, we have received your TOR request and it is currently being processed.'],
    ];
}

$statusFilter = $_GET['status'] ?? 'all';

/* =====================================================
   AJAX: MARK MESSAGE AS READ
   ===================================================== */
if (isset($_GET['mark_read'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_GET['mark_read']);
    if ($receivedTable) {
        $stmt = $conn->prepare("UPDATE received_mail SET status = 'read' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Received Mail | ICF Interworld Colleges Foundation Inc.</title>
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

    .metric-card:nth-child(2)::before { background: linear-gradient(90deg, var(--warning-amber), #fbbf24); }
    .metric-card:nth-child(3)::before { background: linear-gradient(90deg, #ef4444, #f87171); }
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

    .metric-card:nth-child(2) .metric-icon { color: var(--warning-amber); background: rgba(245, 158, 11, 0.1); }
    .metric-card:nth-child(3) .metric-icon { color: #f87171; background: rgba(239, 68, 68, 0.1); }
    .metric-card:nth-child(4) .metric-icon { color: var(--purple-accent); background: rgba(139, 92, 246, 0.1); }

    .metric-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-high-contrast);
      line-height: 1.15;
      letter-spacing: -0.5px;
    }

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
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--text-high-contrast);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .table-header h2 i { color: var(--primary-neon); font-size: 1rem; }

    .table-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .table-wrapper { overflow-x: auto; max-height: 640px; overflow-y: auto; }
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
       BADGES
       ============================================ */
    .badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.68rem;
      font-weight: 700;
      display: inline-block;
      letter-spacing: 0.3px;
      text-transform: uppercase;
    }

    .badge.success {
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .badge.warning {
      background: rgba(245, 158, 11, 0.15);
      color: #fbbf24;
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge.danger {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .badge.info {
      background: rgba(59, 130, 246, 0.15);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

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
       FILTER SELECT
       ============================================ */
    .filter-select {
      padding: 10px 16px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      color: var(--text-high-contrast);
      font-size: 0.85rem;
      font-weight: 500;
      outline: none;
      cursor: pointer;
      font-family: inherit;
      transition: var(--transition-smooth);
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238892b0' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 38px;
    }

    .filter-select:focus { border-color: var(--primary-neon); box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1); }
    .filter-select option { background: var(--bg-card); color: var(--text-high-contrast); }

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
      font-family: inherit;
    }

    .btn-action:hover { background: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }

    .btn-action.btn-view { border-color: rgba(13, 245, 227, 0.5); color: var(--primary-neon); }
    .btn-action.btn-view:hover { background: var(--primary-neon); color: #020c1b; box-shadow: var(--neon-glow); }

    .btn-action.btn-edit { border-color: rgba(245, 158, 11, 0.5); color: #fbbf24; }
    .btn-action.btn-edit:hover { background: var(--warning-amber); color: white; }

    .btn-action.btn-send { border-color: rgba(16, 185, 129, 0.5); color: #34d399; }
    .btn-action.btn-send:hover { background: var(--success-green); color: white; }

    .btn-action.btn-cancel { border-color: rgba(239, 68, 68, 0.5); color: #f87171; }
    .btn-action.btn-cancel:hover { background: rgba(239, 68, 68, 0.15); border-color: #ef4444; color: #fca5a5; }

    .action-group { display: flex; gap: 6px; flex-wrap: wrap; }

    /* ============================================
       MODALS
       ============================================ */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(2, 12, 27, 0.9);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .modal-overlay.active { opacity: 1; visibility: visible; }

    .modal-container {
      background-color: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 700px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      transform: translateY(20px);
      transition: transform 0.3s ease;
      overflow: hidden;
    }

    .modal-overlay.active .modal-container { transform: translateY(0); }

    .modal-header {
      padding: 18px 24px;
      background-color: #06152b;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-teal);
    }

    .modal-header h3 {
      color: var(--primary-neon);
      font-size: 1.05rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .modal-close-btn {
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      font-size: 1.2rem;
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .modal-close-btn:hover { color: #ef4444; }

    .modal-body {
      padding: 24px;
      max-height: 65vh;
      overflow-y: auto;
    }

    .modal-footer {
      padding: 16px 24px;
      background-color: #06152b;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      border-top: 1px solid var(--border-teal);
    }

    /* ============================================
       FORM CONTROLS
       ============================================ */
    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 0.74rem;
      color: var(--text-muted-teal);
      margin-bottom: 6px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 11px 14px;
      background-color: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 8px;
      color: var(--text-high-contrast);
      font-size: 0.88rem;
      outline: none;
      transition: var(--transition-smooth);
      font-family: inherit;
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
      color: #4a5568;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 150px;
    }

    .btn-secondary {
      background: transparent;
      color: var(--text-muted-teal);
      border: 1px solid var(--border-teal);
      padding: 11px 22px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 500;
      font-family: inherit;
      transition: var(--transition-smooth);
    }

    .btn-secondary:hover { background: var(--bg-card-hover); color: var(--text-high-contrast); }

    /* ============================================
       VIEW MESSAGE DETAILS
       ============================================ */
    .view-detail-row {
      display: flex;
      margin-bottom: 12px;
      font-size: 0.88rem;
    }

    .view-detail-label {
      width: 130px;
      font-weight: 600;
      color: var(--text-muted-teal);
      flex-shrink: 0;
    }

    .view-detail-value {
      flex: 1;
      color: var(--text-high-contrast);
    }

    .view-message-content {
      margin-top: 15px;
      padding: 16px;
      background-color: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      min-height: 100px;
      color: var(--text-high-contrast);
      font-size: 0.88rem;
      white-space: pre-wrap;
      line-height: 1.6;
    }

    /* ============================================
       SUCCESS MODAL
       ============================================ */
    .success-modal-body {
      text-align: center;
      padding: 30px 20px;
    }

    .success-icon {
      font-size: 3.5rem;
      color: var(--success-green);
      margin-bottom: 15px;
      filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.5));
    }

    .success-modal-body h4 {
      font-size: 1.15rem;
      color: var(--text-high-contrast);
      margin-bottom: 8px;
      font-weight: 700;
    }

    .success-modal-body p {
      font-size: 0.88rem;
      color: var(--text-muted-teal);
    }

    /* ============================================
       NOTICE BANNER
       ============================================ */
    .notice-banner {
      background: rgba(245, 158, 11, 0.1);
      color: #fbbf24;
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 0.85rem;
      border: 1px solid rgba(245, 158, 11, 0.3);
      display: flex;
      align-items: center;
      gap: 10px;
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
      .live-clock { min-width: 100%; }
      .clock-time { font-size: 1.2rem; }
      .sidebar-toggle { top: 20px; }
      .search-wrap input { width: 100%; }
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
      <li>
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
      <li class="active">
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
        <h1><i class="fa-solid fa-envelope"></i> Received Mail</h1>
        <p>Review incoming inquiries and manage your composed messages.</p>
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

    <?php if (!$receivedTable || !$composedTable): ?>
    <div class="notice-banner">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span><strong>Note:</strong> Mail tables not found in database. Showing demo data. Run <code>database.sql</code> to enable full mail functionality.</span>
    </div>
    <?php endif; ?>

    <!-- METRIC CARDS -->
    <div class="cards-grid">
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Total Messages</span>
          <div class="metric-icon"><i class="fa-solid fa-envelope"></i></div>
        </div>
        <div class="metric-value"><?php echo count($received); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Unread Mail</span>
          <div class="metric-icon"><i class="fa-solid fa-envelope-open"></i></div>
        </div>
        <div class="metric-value"><?php echo count(array_filter($received, fn($r)=>$r['status']==='unread')); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Urgent Inquiries</span>
          <div class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
        <div class="metric-value"><?php echo count(array_filter($received, fn($r)=>$r['status']==='urgent')); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Composed Messages</span>
          <div class="metric-icon"><i class="fa-solid fa-paper-plane"></i></div>
        </div>
        <div class="metric-value"><?php echo count($composed); ?></div>
      </div>
    </div>

    <!-- INBOX TABLE -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-inbox"></i> Inbox Messages</h2>
        <div class="table-actions">
          <select id="statusFilter" class="filter-select" onchange="filterMail()">
            <option value="all" <?php echo $statusFilter==='all'?'selected':''; ?>>All Statuses</option>
            <option value="read" <?php echo $statusFilter==='read'?'selected':''; ?>>Read</option>
            <option value="unread" <?php echo $statusFilter==='unread'?'selected':''; ?>>Unread</option>
            <option value="urgent" <?php echo $statusFilter==='urgent'?'selected':''; ?>>Urgent</option>
          </select>
          <button class="btn primary" onclick="toggleComposeModal(true)"><i class="fa-solid fa-paper-plane"></i> Compose</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="mailTable">
          <thead>
            <tr>
              <th>Mail Address</th>
              <th>Sender Name</th>
              <th>Subject</th>
              <th>Date Received</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($received as $mail):
              $bc = $mail['status'] === 'read' ? 'success' : ($mail['status'] === 'urgent' ? 'danger' : 'warning');
            ?>
            <tr data-id="<?php echo $mail['id']; ?>" data-status="<?php echo $mail['status']; ?>"
                data-subject="<?php echo htmlspecialchars($mail['subject']); ?>"
                data-message="<?php echo htmlspecialchars($mail['message']); ?>"
                data-sender="<?php echo htmlspecialchars($mail['sender_name']); ?>"
                data-email="<?php echo htmlspecialchars($mail['email']); ?>"
                data-date="<?php echo $mail['date_received']; ?>">
              <td><?php echo htmlspecialchars($mail['email']); ?></td>
              <td><?php echo htmlspecialchars($mail['sender_name']); ?></td>
              <td><?php echo htmlspecialchars($mail['subject']); ?></td>
              <td><?php echo $mail['date_received']; ?></td>
              <td><span class="badge <?php echo $bc; ?>"><?php echo ucfirst($mail['status']); ?></span></td>
              <td><button class="btn-action btn-view" onclick="viewMessage(this, 'inbox')"><i class="fa-solid fa-eye"></i> View</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- COMPOSED TABLE -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-paper-plane"></i> Composed Messages</h2>
      </div>
      <div class="table-wrapper">
        <table id="composedMailTable">
          <thead>
            <tr>
              <th>Sender</th>
              <th>Mail Address</th>
              <th>Receiver</th>
              <th>Date Sent</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($composed as $mail):
              $bc = $mail['status'] === 'Sent' ? 'success' : ($mail['status'] === 'Pending Send' ? 'info' : 'danger');
            ?>
            <tr data-subject="<?php echo htmlspecialchars($mail['subject']); ?>" data-message="<?php echo htmlspecialchars($mail['message']); ?>">
              <td><?php echo htmlspecialchars($mail['sender']); ?></td>
              <td><?php echo htmlspecialchars($mail['email']); ?></td>
              <td><?php echo htmlspecialchars($mail['receiver']); ?></td>
              <td><?php echo $mail['date_sent']; ?></td>
              <td><span class="badge <?php echo $bc; ?>"><?php echo $mail['status']; ?></span></td>
              <td>
                <div class="action-group">
                  <button class="btn-action btn-view" onclick="viewMessage(this, 'composed')"><i class="fa-solid fa-eye"></i> View</button>
                  <?php if ($mail['status'] === 'Pending Send'): ?>
                  <button class="btn-action btn-edit" onclick="editComposedMessage(<?php echo $mail['id']; ?>, '<?php echo htmlspecialchars($mail['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mail['receiver'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mail['subject'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mail['message'], ENT_QUOTES); ?>')"><i class="fa-solid fa-pen"></i> Edit</button>
                  <a href="received-mail.php?send=<?php echo $mail['id']; ?>"><button class="btn-action btn-send"><i class="fa-solid fa-paper-plane"></i> Send</button></a>
                  <a href="received-mail.php?cancel=<?php echo $mail['id']; ?>"><button class="btn-action btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</button></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- ===== VIEW MESSAGE MODAL ===== -->
  <div class="modal-overlay" id="viewMessageModal">
    <div class="modal-container">
      <div class="modal-header">
        <h3><i class="fa-solid fa-envelope-open-text"></i> View Message Details</h3>
        <button class="modal-close-btn" onclick="toggleViewModal(false)"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="view-detail-row"><span class="view-detail-label" id="viewLabelContact">From/To:</span><span class="view-detail-value" id="viewContactVal">-</span></div>
        <div class="view-detail-row"><span class="view-detail-label">Mail Address:</span><span class="view-detail-value" id="viewEmailVal">-</span></div>
        <div class="view-detail-row"><span class="view-detail-label">Date:</span><span class="view-detail-value" id="viewDateVal">-</span></div>
        <div class="view-detail-row"><span class="view-detail-label">Status:</span><span class="view-detail-value" id="viewStatusVal">-</span></div>
        <div class="view-detail-row" id="viewSubjectRow"><span class="view-detail-label">Subject:</span><span class="view-detail-value" id="viewSubjectVal">-</span></div>
        <div style="margin-top:15px;">
          <span class="view-detail-label">Message:</span>
          <div class="view-message-content" id="viewBodyVal">No message content available.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn primary" onclick="toggleViewModal(false)">Close</button>
      </div>
    </div>
  </div>

  <!-- ===== COMPOSE MESSAGE MODAL ===== -->
  <div class="modal-overlay" id="composeModal">
    <div class="modal-container">
      <div class="modal-header">
        <h3 id="composeModalTitle"><i class="fa-solid fa-paper-plane"></i> New Message</h3>
        <button class="modal-close-btn" onclick="toggleComposeModal(false)"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="received-mail.php" id="composeForm">
          <input type="hidden" name="compose_submit" value="1">
          <input type="hidden" id="editingId" name="editingId" value="" />
          <div class="form-group"><label>From</label><input type="text" value="ICFAdmin" readonly /></div>
          <div class="form-group"><label>Recipient Mail Address</label><input type="email" name="recipientEmail" id="recipientEmail" placeholder="example@gmail.com" required /></div>
          <div class="form-group"><label>Receiver Name</label><input type="text" name="recipientName" id="recipientName" placeholder="Enter receiver name..." required /></div>
          <div class="form-group"><label>Subject</label><input type="text" name="emailSubject" id="emailSubject" placeholder="Enter subject..." required /></div>
          <div class="form-group"><label>Message</label><textarea name="emailMessage" id="emailMessage" placeholder="Type your message here..." required></textarea></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="toggleComposeModal(false)">Cancel</button>
        <button class="btn primary" type="submit" form="composeForm"><i class="fa-solid fa-floppy-disk"></i> Save Message</button>
      </div>
    </div>
  </div>

  <!-- ===== SUCCESS MODAL ===== -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-container" style="max-width: 420px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-circle-check"></i> Success</h3>
        <button class="modal-close-btn" onclick="toggleSuccessModal(false)"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body success-modal-body">
        <i class="fa-solid fa-circle-check success-icon"></i>
        <h4 id="successModalTitle">Message Saved!</h4>
        <p id="successModalBody">Your composed message has been added to the queue.</p>
      </div>
      <div class="modal-footer">
        <button class="btn primary" onclick="toggleSuccessModal(false)">OK</button>
      </div>
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
       MAIL MODALS & LOGIC
       ============================================ */
    function toggleViewModal(show) {
      document.getElementById('viewMessageModal').classList.toggle('active', show);
    }

    function toggleComposeModal(show) {
      document.getElementById('composeModal').classList.toggle('active', show);
      if (!show) {
        document.getElementById('composeForm').reset();
        document.getElementById('editingId').value = '';
        document.getElementById('composeModalTitle').innerHTML = '<i class="fa-solid fa-paper-plane"></i> New Message';
      }
    }

    function toggleSuccessModal(show, title, body) {
      if (title) document.getElementById('successModalTitle').textContent = title;
      if (body) document.getElementById('successModalBody').textContent = body;
      document.getElementById('successModal').classList.toggle('active', show);
    }

    function viewMessage(btn, type) {
      const row = btn.closest('tr');
      if (type === 'inbox') {
        if (row.dataset.status === 'unread') {
          const msgId = row.dataset.id;
          if (msgId) {
            fetch('received-mail.php?mark_read=' + msgId)
              .then(r => r.json())
              .then(data => {
                if (data.success) {
                  row.dataset.status = 'read';
                  const badge = row.querySelector('.badge');
                  if (badge) {
                    badge.classList.remove('warning');
                    badge.classList.add('success');
                    badge.textContent = 'Read';
                  }
                }
              })
              .catch(() => {});
          }
        }
        document.getElementById('viewLabelContact').textContent = 'Sender Name:';
        document.getElementById('viewEmailVal').textContent = row.dataset.email || row.cells[0].textContent;
        document.getElementById('viewContactVal').textContent = row.dataset.sender || row.cells[1].textContent;
        document.getElementById('viewSubjectVal').textContent = row.dataset.subject || row.cells[2].textContent;
        document.getElementById('viewDateVal').textContent = row.cells[3].textContent;
        document.getElementById('viewStatusVal').innerHTML = row.cells[4].innerHTML;
        document.getElementById('viewBodyVal').textContent = row.dataset.message || 'No message content available.';
      } else {
        document.getElementById('viewLabelContact').textContent = 'Receiver Name:';
        document.getElementById('viewEmailVal').textContent = row.cells[1].textContent;
        document.getElementById('viewContactVal').textContent = row.cells[2].textContent;
        document.getElementById('viewSubjectVal').textContent = row.dataset.subject || 'N/A';
        document.getElementById('viewDateVal').textContent = row.cells[3].textContent;
        document.getElementById('viewStatusVal').innerHTML = row.cells[4].innerHTML;
        document.getElementById('viewBodyVal').textContent = row.dataset.message || 'No message content available.';
      }
      toggleViewModal(true);
    }

    function editComposedMessage(id, email, receiver, subject, message) {
      document.getElementById('recipientEmail').value = email;
      document.getElementById('recipientName').value = receiver;
      document.getElementById('emailSubject').value = subject;
      document.getElementById('emailMessage').value = message;
      document.getElementById('editingId').value = id;
      document.getElementById('composeModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Message';
      toggleComposeModal(true);
    }

    function filterMail() {
      const val = document.getElementById('statusFilter').value.toLowerCase();
      document.querySelectorAll('#mailTable tbody tr').forEach(row => {
        row.style.display = (val === 'all' || row.getAttribute('data-status') === val) ? '' : 'none';
      });
    }

    /* Click outside modals */
    ['viewMessageModal', 'composeModal', 'successModal'].forEach(id => {
      document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
      });
    });

    /* Auto-open success modal based on query string */
    <?php if (isset($_GET['success'])): ?>toggleSuccessModal(true, 'Message Saved!', 'Your composed message has been added to the queue.');<?php endif; ?>
    <?php if (isset($_GET['sent'])): ?>toggleSuccessModal(true, 'Message Sent!', 'The message has been successfully sent to the recipient.');<?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>toggleSuccessModal(true, 'Message Cancelled', 'The message has been cancelled.');<?php endif; ?>
  </script>
</body>
</html>