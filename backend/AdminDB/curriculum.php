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
$conn->query("CREATE TABLE IF NOT EXISTS curriculums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    year_level VARCHAR(100) DEFAULT '',
    academic_year VARCHAR(50) DEFAULT '',
    total_units INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS curriculum_semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curriculum_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    FOREIGN KEY (curriculum_id) REFERENCES curriculums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS curriculum_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_id INT NOT NULL,
    prereq VARCHAR(100) DEFAULT 'None',
    code VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    lec DECIMAL(4,1) DEFAULT 0,
    lab DECIMAL(4,1) DEFAULT 0,
    units DECIMAL(4,1) DEFAULT 0,
    FOREIGN KEY (semester_id) REFERENCES curriculum_semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ============================================
   AJAX HANDLER
   ============================================ */
if (isset($_REQUEST['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_REQUEST['action'];

  if ($action === 'list') {
    $rows = [];
    $res = $conn->query("SELECT * FROM curriculums ORDER BY id DESC");
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
  }

  if ($action === 'add_curriculum') {
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $ay = trim($_POST['academic_year'] ?? '');
    $units = intval($_POST['units'] ?? 0);
    if ($code === '' || $title === '') {
      echo json_encode(['success' => false, 'message' => 'Code and Title are required.']);
      exit;
    }
    $stmt = $conn->prepare("INSERT INTO curriculums (code,title,year_level,academic_year,total_units) VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssssi", $code, $title, $year, $ay, $units);
    if (!$stmt->execute()) {
      echo json_encode(['success' => false, 'message' => $stmt->error]);
      exit;
    }
    $cid = $stmt->insert_id;
    $conn->query("INSERT INTO curriculum_semesters (curriculum_id, name) VALUES ($cid, '1st Year - 1st Semester')");
    echo json_encode(['success' => true, 'id' => $cid]);
    exit;
  }

  if ($action === 'delete_curriculum') {
    $id = intval($_POST['id'] ?? 0);
    $conn->query("DELETE FROM curriculums WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $res = $conn->query("SELECT * FROM curriculums WHERE id = $id");
    $cur = $res->fetch_assoc();
    if (!$cur) {
      echo json_encode(['success' => false, 'message' => 'Not found']);
      exit;
    }
    $sems = [];
    $sr = $conn->query("SELECT * FROM curriculum_semesters WHERE curriculum_id = $id ORDER BY id ASC");
    while ($s = $sr->fetch_assoc()) {
      $subs = [];
      $subres = $conn->query("SELECT * FROM curriculum_subjects WHERE semester_id = {$s['id']} ORDER BY id ASC");
      while ($sub = $subres->fetch_assoc()) $subs[] = $sub;
      $s['subjects'] = $subs;
      $sems[] = $s;
    }
    $cur['semesters'] = $sems;
    echo json_encode(['success' => true, 'data' => $cur]);
    exit;
  }

  if ($action === 'add_semester') {
    $cid = intval($_POST['curriculum_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
      echo json_encode(['success' => false, 'message' => 'Name required']);
      exit;
    }
    $stmt = $conn->prepare("INSERT INTO curriculum_semesters (curriculum_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $cid, $name);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === 'edit_semester') {
    $sid = intval($_POST['semester_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $stmt = $conn->prepare("UPDATE curriculum_semesters SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $sid);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === 'delete_semester') {
    $sid = intval($_POST['semester_id'] ?? 0);
    $conn->query("DELETE FROM curriculum_semesters WHERE id = $sid");
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === 'add_subject') {
    $sid = intval($_POST['semester_id'] ?? 0);
    $prereq = trim($_POST['prereq'] ?? 'None');
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $lec = floatval($_POST['lec'] ?? 0);
    $lab = floatval($_POST['lab'] ?? 0);
    $units = floatval($_POST['units'] ?? 0);
    $stmt = $conn->prepare("INSERT INTO curriculum_subjects (semester_id, prereq, code, title, lec, lab, units) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("isssddd", $sid, $prereq, $code, $title, $lec, $lab, $units);
    if (!$stmt->execute()) {
      echo json_encode(['success' => false, 'message' => $stmt->error]);
      exit;
    }
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === 'edit_subject') {
    $subId = intval($_POST['subject_id'] ?? 0);
    $prereq = trim($_POST['prereq'] ?? 'None');
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $lec = floatval($_POST['lec'] ?? 0);
    $lab = floatval($_POST['lab'] ?? 0);
    $units = floatval($_POST['units'] ?? 0);
    $stmt = $conn->prepare("UPDATE curriculum_subjects SET prereq=?, code=?, title=?, lec=?, lab=?, units=? WHERE id=?");
    $stmt->bind_param("sssdddi", $prereq, $code, $title, $lec, $lab, $units, $subId);
    if (!$stmt->execute()) {
      echo json_encode(['success' => false, 'message' => $stmt->error]);
      exit;
    }
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === 'delete_subject') {
    $subId = intval($_POST['subject_id'] ?? 0);
    $conn->query("DELETE FROM curriculum_subjects WHERE id = $subId");
    echo json_encode(['success' => true]);
    exit;
  }

  echo json_encode(['success' => false, 'message' => 'Unknown action']);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curriculum Management | ICF Interworld Colleges Foundation Inc.</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    /* ============================================
       ICF Admin Panel — Collapsible Sidebar Theme
       (Shared design system with adminpanel.php)
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
       COLLAPSIBLE SIDEBAR
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

    .sidebar.expanded {
      width: var(--sidebar-expanded);
    }

    .sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: var(--border-teal);
      border-radius: 3px;
    }

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

    body.sidebar-open .sidebar-toggle {
      left: calc(var(--sidebar-expanded) + 8px);
    }

    .sidebar-toggle:hover {
      transform: scale(1.2);
      text-shadow: 0 0 10px rgba(13, 245, 227, 0.5);
    }

    .sidebar-toggle i {
      transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.sidebar-open .sidebar-toggle i {
      transform: rotate(180deg);
    }

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

    .school-brand:hover {
      background: rgba(13, 245, 227, 0.03);
    }

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

    .school-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

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

    .sidebar.expanded .school-name {
      opacity: 1;
      width: auto;
    }

    .school-name .title {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-high-contrast);
      line-height: 1.25;
    }

    .school-name .sub {
      font-size: 0.6rem;
      color: var(--text-muted-teal);
      margin-top: 3px;
      letter-spacing: 1.2px;
      text-transform: uppercase;
    }

    .nav-links {
      list-style: none;
      padding: 8px 0;
      flex-shrink: 0;
    }

    .nav-links li {
      margin-bottom: 3px;
    }

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

    .sidebar.expanded .nav-links li a .nav-label {
      opacity: 1;
      transform: translateX(0);
    }

    .nav-links li a:hover {
      background-color: #1e293b;
      color: var(--primary-neon);
    }

    .nav-links li a:hover .nav-icon {
      color: var(--primary-neon);
      text-shadow: 0 0 8px rgba(13, 245, 227, 0.5);
    }

    .nav-links li.active a {
      background: linear-gradient(90deg, #1e3a5f 0%, #0f2a45 100%);
      color: var(--primary-neon);
      font-weight: 700;
    }

    .nav-links li.active a::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: var(--primary-neon);
      box-shadow: 0 0 10px rgba(13, 245, 227, 0.7);
    }

    .nav-links li.active a .nav-icon {
      color: var(--primary-neon);
    }

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

    .admin-profile-card:hover {
      background: rgba(13, 245, 227, 0.04);
    }

    .admin-avatar {
      width: 40px;
      height: 40px;
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

    .sidebar.expanded .admin-info {
      opacity: 1;
      width: auto;
      flex: 1;
    }

    .admin-name {
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--text-high-contrast);
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .admin-role {
      font-size: 0.68rem;
      color: var(--text-muted-teal);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .admin-role i {
      font-size: 0.58rem;
      color: var(--primary-neon);
    }

    .admin-toggle-icon {
      color: var(--text-muted-teal);
      font-size: 0.75rem;
      transition: var(--transition-smooth);
      flex-shrink: 0;
      margin-right: 16px;
      opacity: 0;
      width: 0;
    }

    .sidebar.expanded .admin-toggle-icon {
      opacity: 1;
      width: auto;
    }

    .admin-profile-card.active .admin-toggle-icon {
      transform: rotate(180deg);
      color: var(--primary-neon);
    }

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

    .sidebar.expanded .sidebar-actions.open {
      max-height: 220px;
      opacity: 1;
      margin-top: 10px;
      padding: 0 12px;
    }

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

    .btn-sidebar i {
      width: 18px;
      font-size: 0.9rem;
      text-align: center;
      flex-shrink: 0;
    }

    .btn-sidebar:hover {
      background-color: #1e293b;
      color: var(--text-high-contrast);
      border-color: var(--primary-neon);
    }

    .btn-sidebar.profile-settings:hover {
      color: var(--primary-neon);
      border-color: var(--primary-neon);
      box-shadow: var(--neon-glow);
    }

    .btn-sidebar.homepage {
      color: var(--primary-neon);
      border-color: rgba(13, 245, 227, 0.5);
    }

    .btn-sidebar.homepage:hover {
      background-color: var(--primary-neon);
      color: var(--bg-deep-abyss);
      box-shadow: var(--neon-glow);
    }

    .btn-sidebar.logout {
      color: #f87171;
      border-color: rgba(239, 68, 68, 0.5);
    }

    .btn-sidebar.logout:hover {
      background-color: #ef4444;
      color: white;
      box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
      border-color: #ef4444;
    }

    /* ============================================
       MAIN CONTENT — gradient background
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

    body.sidebar-open .main-content {
      margin-left: var(--sidebar-expanded);
    }

    /* ============================================
       TOP HEADER
       ============================================ */
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

    .top-header-left h1 i {
      color: var(--primary-neon);
      margin-right: 10px;
    }

    .top-header-left p {
      font-size: 0.82rem;
      color: var(--text-muted-teal);
      margin-top: 4px;
    }

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

    .live-clock:hover {
      border-color: var(--primary-neon);
      box-shadow: 0 6px 25px rgba(13, 245, 227, 0.15);
    }

    .live-clock::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 2px;
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

    .clock-ampm {
      font-size: 0.65rem;
      font-weight: 600;
      color: var(--primary-neon);
      margin-left: 3px;
      opacity: 0.85;
      vertical-align: super;
    }

    .clock-date {
      font-size: 0.62rem;
      color: var(--text-muted-teal);
      margin-top: 4px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
      font-weight: 600;
    }

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
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, var(--primary-neon), #00b4d8);
      opacity: 0.8;
    }

    .metric-card:nth-child(2)::before {
      background: linear-gradient(90deg, var(--purple-accent), #a78bfa);
    }

    .metric-card:nth-child(3)::before {
      background: linear-gradient(90deg, var(--blue-accent), #60a5fa);
    }

    .metric-card:nth-child(4)::before {
      background: linear-gradient(90deg, var(--warning-amber), #fbbf24);
    }

    .metric-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .metric-label {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--text-muted-teal);
      font-weight: 600;
    }

    .metric-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      color: var(--primary-neon);
      background: rgba(13, 245, 227, 0.08);
    }

    .metric-card:nth-child(2) .metric-icon {
      color: var(--purple-accent);
      background: rgba(139, 92, 246, 0.1);
    }

    .metric-card:nth-child(3) .metric-icon {
      color: var(--blue-accent);
      background: rgba(59, 130, 246, 0.1);
    }

    .metric-card:nth-child(4) .metric-icon {
      color: var(--warning-amber);
      background: rgba(245, 158, 11, 0.1);
    }

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

    .table-card:hover {
      border-color: #1f3a5f;
    }

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

    .table-header h2 i {
      color: var(--primary-neon);
      font-size: 1rem;
    }

    .table-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .table-wrapper {
      overflow-x: auto;
      max-height: 640px;
      overflow-y: auto;
    }

    .table-wrapper::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
      background: var(--border-teal);
      border-radius: 3px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

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

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover td {
      background-color: rgba(13, 245, 227, 0.03);
    }

    th.sortable {
      cursor: pointer;
      user-select: none;
    }

    th.sortable:hover {
      color: var(--primary-neon);
    }

    th.no-sort {
      cursor: default;
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

    .btn:hover {
      background: #1e293b;
      color: var(--text-high-contrast);
      border-color: #2d4a6b;
    }

    .btn.primary {
      background: var(--primary-neon);
      color: #020c1b;
      border: none;
      font-weight: 600;
    }

    .btn.primary:hover {
      background: var(--primary-neon-hover);
      box-shadow: var(--neon-glow);
    }

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

    .btn-action:hover {
      background: #1e293b;
      color: var(--text-high-contrast);
      border-color: var(--primary-neon);
    }

    .btn-action.view {
      border-color: rgba(14, 165, 233, 0.5);
      color: #38bdf8;
    }

    .btn-action.view:hover {
      background: rgba(14, 165, 233, 0.15);
      border-color: #0ea5e9;
      color: #7dd3fc;
    }

    .btn-action.decline {
      border-color: rgba(239, 68, 68, 0.5);
      color: #f87171;
    }

    .btn-action.decline:hover {
      background: rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
      color: #fca5a5;
    }

    .action-group {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    /* ============================================
       MODALS
       ============================================ */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(2, 12, 27, 0.85);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .modal-container {
      background-color: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 600px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      transform: translateY(20px);
      transition: transform 0.3s ease;
      overflow: hidden;
    }

    .modal-overlay.active .modal-container {
      transform: translateY(0);
    }

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

    .modal-close {
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      font-size: 1.4rem;
      cursor: pointer;
      transition: color 0.2s ease;
      line-height: 1;
    }

    .modal-close:hover {
      color: #ef4444;
    }

    .modal-body {
      padding: 24px;
    }

    .modal-footer {
      padding: 16px 24px;
      background-color: #06152b;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      border-top: 1px solid var(--border-teal);
    }

    .btn-cancel {
      background: transparent;
      color: var(--text-muted-teal);
      border: 1px solid var(--border-teal);
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      font-size: 0.82rem;
      transition: var(--transition-smooth);
      font-family: inherit;
    }

    .btn-cancel:hover {
      background: var(--bg-card-hover);
      color: var(--text-high-contrast);
    }

    .btn-save {
      background: var(--primary-neon);
      color: #020c1b;
      border: none;
      padding: 10px 22px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.82rem;
      transition: var(--transition-smooth);
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: inherit;
    }

    .btn-save:hover {
      background: var(--primary-neon-hover);
      box-shadow: var(--neon-glow);
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

    .form-group input::placeholder {
      color: #4a5568;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1);
    }

    /* ============================================
       OPERATION MODAL (Success / Warning / Input)
       ============================================ */
    .operation-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(2, 12, 27, 0.9);
      backdrop-filter: blur(6px);
      z-index: 5000;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .operation-modal-overlay.active {
      display: flex;
    }

    .operation-modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 480px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      padding: 30px;
      color: var(--text-high-contrast);
      text-align: center;
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .operation-modal-overlay.active .operation-modal-box {
      transform: translateY(0);
    }

    .operation-icon {
      font-size: 3rem;
      margin-bottom: 15px;
    }

    .operation-modal-box.success {
      border-color: rgba(16, 185, 129, 0.4);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(16, 185, 129, 0.15);
    }

    .operation-modal-box.success .operation-icon {
      color: var(--success-green);
    }

    .operation-modal-box.warning {
      border-color: rgba(245, 158, 11, 0.4);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(245, 158, 11, 0.15);
    }

    .operation-modal-box.warning .operation-icon {
      color: var(--warning-amber);
    }

    .operation-modal-box h3 {
      font-size: 1.15rem;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .operation-modal-box p {
      font-size: 0.85rem;
      color: var(--text-muted-teal);
      margin-bottom: 20px;
      line-height: 1.6;
    }

    .operation-form-group {
      margin-bottom: 15px;
      text-align: left;
    }

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
      padding: 11px 14px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 8px;
      font-size: 0.88rem;
      color: var(--text-high-contrast);
      outline: none;
      font-family: inherit;
      transition: var(--transition-smooth);
    }

    .operation-form-group input:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1);
    }

    .operation-modal-actions {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }

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

    .op-btn.cancel {
      background: transparent;
      color: var(--text-muted-teal);
      border: 1px solid var(--border-teal);
    }

    .op-btn.cancel:hover {
      background: var(--bg-card-hover);
      color: var(--text-high-contrast);
    }

    .op-btn.confirm-success {
      background: var(--primary-neon);
      color: #020c1b;
    }

    .op-btn.confirm-success:hover {
      background: var(--primary-neon-hover);
      box-shadow: var(--neon-glow);
    }

    .op-btn.confirm-danger {
      background: #ef4444;
      color: white;
    }

    .op-btn.confirm-danger:hover {
      background: #dc2626;
      box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
    }

    /* ============================================
       VIEW CURRICULUM MODAL
       ============================================ */
    .view-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(2, 12, 27, 0.9);
      backdrop-filter: blur(6px);
      z-index: 3000;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .view-modal-overlay.active {
      display: flex;
    }

    .view-modal-container {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 1100px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9);
      color: var(--text-high-contrast);
    }

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

    .view-modal-close:hover {
      color: #ef4444;
    }

    .view-modal-body {
      padding: 24px;
    }

    .curriculum-info {
      text-align: center;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--border-teal);
    }

    .curriculum-info h2 {
      font-size: 1.3rem;
      color: var(--text-high-contrast);
      font-weight: 800;
      margin-bottom: 5px;
      letter-spacing: 0.5px;
    }

    .curriculum-info h3 {
      font-size: 0.9rem;
      color: var(--primary-neon);
      font-weight: 600;
      margin-bottom: 12px;
    }

    .curriculum-info p {
      font-size: 0.82rem;
      color: var(--text-muted-teal);
      margin-top: 4px;
    }

    .semester-section {
      margin-bottom: 28px;
    }

    .semester-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      gap: 10px;
      flex-wrap: wrap;
    }

    .semester-title {
      font-size: 0.92rem;
      font-weight: 700;
      color: var(--primary-neon);
      background: rgba(13, 245, 227, 0.06);
      border: 1px solid rgba(13, 245, 227, 0.2);
      padding: 10px 16px;
      border-radius: 10px;
      flex: 1;
      min-width: 200px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .semester-actions {
      display: flex;
      gap: 6px;
    }

    .btn-small {
      padding: 6px 12px;
      border: 1px solid var(--border-teal);
      background: transparent;
      color: var(--text-muted-teal);
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.72rem;
      font-weight: 500;
      transition: var(--transition-smooth);
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-family: inherit;
    }

    .btn-small:hover {
      background: #1e293b;
      color: var(--text-high-contrast);
      border-color: var(--primary-neon);
    }

    .btn-small.add {
      border-color: rgba(13, 245, 227, 0.4);
      color: var(--primary-neon);
    }

    .btn-small.add:hover {
      background: var(--primary-neon);
      color: #020c1b;
    }

    .btn-small.danger {
      border-color: rgba(239, 68, 68, 0.5);
      color: #f87171;
    }

    .btn-small.danger:hover {
      background: rgba(239, 68, 68, 0.15);
      color: #fca5a5;
    }

    .curriculum-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.83rem;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border-teal);
    }

    .curriculum-table th,
    .curriculum-table td {
      padding: 11px 12px;
      border-bottom: 1px solid #1a2d45;
      text-align: left;
      color: var(--text-high-contrast);
    }

    .curriculum-table th {
      background: rgba(2, 12, 27, 0.7);
      color: var(--text-muted-teal);
      font-weight: 600;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .curriculum-table tr:last-child td {
      border-bottom: none;
    }

    .curriculum-table tr:hover td {
      background-color: rgba(13, 245, 227, 0.03);
    }

    .curriculum-table .total-row td {
      background: rgba(13, 245, 227, 0.06);
      font-weight: 700;
      color: var(--primary-neon);
      border-top: 1px solid rgba(13, 245, 227, 0.3);
    }

    .add-semester-bar {
      text-align: center;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid var(--border-teal);
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 1200px) {
      .cards-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      .top-header {
        grid-template-columns: 1fr;
        gap: 18px;
      }
    }

    @media (max-width: 992px) {
      .main-content {
        padding: 24px;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }

      .cards-grid {
        grid-template-columns: 1fr;
      }

      .top-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .live-clock {
        min-width: 100%;
      }

      .clock-time {
        font-size: 1.2rem;
      }

      .sidebar-toggle {
        top: 20px;
      }

      .semester-header {
        flex-direction: column;
        align-items: stretch;
      }

      .curriculum-table {
        font-size: 0.75rem;
      }

      .curriculum-table th,
      .curriculum-table td {
        padding: 8px 6px;
      }
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
      <li class="active">
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
        <h1><i class="fa-solid fa-book"></i> Curriculum Management</h1>
        <p>Manage courses, semesters, and subjects offered by the college.</p>
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
          <span class="metric-label">Total Curriculums</span>
          <div class="metric-icon"><i class="fa-solid fa-book"></i></div>
        </div>
        <div class="metric-value" id="totalCurriculumsCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Active Programs</span>
          <div class="metric-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        </div>
        <div class="metric-value" id="activeProgramsCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Courses Offered</span>
          <div class="metric-icon"><i class="fa-solid fa-list-check"></i></div>
        </div>
        <div class="metric-value" id="totalCoursesCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Total Units Enrolled</span>
          <div class="metric-icon"><i class="fa-solid fa-layer-group"></i></div>
        </div>
        <div class="metric-value" id="totalUnitsCard">0</div>
      </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-list-ul"></i> List of Courses</h2>
        <div class="table-actions">
          <button class="btn primary" onclick="openAddCurriculumModal()"><i class="fas fa-plus"></i> Add Course</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="curriculumTable">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable(0)">Course Code <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(1)">Course Title <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(2)">Academic Year <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(3)">Total Units <i class="fas fa-sort"></i></th>
              <th class="no-sort">Action</th>
            </tr>
          </thead>
          <tbody id="curriculumTableBody"></tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- ADD CURRICULUM MODAL -->
  <div class="modal-overlay" id="addCurriculumModal">
    <div class="modal-container">
      <div class="modal-header">
        <h3><i class="fas fa-plus-circle"></i> Add Course</h3>
        <button class="modal-close" onclick="closeAddCurriculumModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="addCurriculumForm" onsubmit="event.preventDefault(); saveCurriculum();">
          <div class="form-group"><label>Course Code</label><input type="text" id="curriculumCode" placeholder="e.g., BSCS-2024" required /></div>
          <div class="form-group"><label>Course Title</label><input type="text" id="curriculumTitle" placeholder="e.g., Bachelor of Science in Computer Science" required /></div>
          <div class="form-group"><label>Year Level Covered</label><input type="text" id="curriculumYear" placeholder="e.g., 1st - 4th Year" required /></div>
          <div class="form-group"><label>Academic Year</label><input type="text" id="curriculumAcademicYear" placeholder="e.g., S.Y. 2023-2024" required /></div>
          <div class="form-group"><label>Total Units</label><input type="number" id="curriculumUnits" placeholder="e.g., 210" required /></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" onclick="closeAddCurriculumModal()">Cancel</button>
        <button class="btn-save" onclick="saveCurriculum()"><i class="fas fa-save"></i> Save Course</button>
      </div>
    </div>
  </div>

  <!-- VIEW CURRICULUM MODAL -->
  <div class="view-modal-overlay" id="viewCurriculumModal">
    <div class="view-modal-container">
      <div class="view-modal-header">
        <h3><i class="fas fa-book-open"></i> Full Curriculum Details</h3>
        <button class="view-modal-close" onclick="closeViewCurriculumModal()">&times;</button>
      </div>
      <div class="view-modal-body" id="viewCurriculumBody"></div>
    </div>
  </div>

  <!-- OPERATION MODAL (Success/Warning/Input) -->
  <div class="operation-modal-overlay" id="operationModal">
    <div class="operation-modal-box" id="operationBox">
      <div class="operation-icon" id="operationIcon"></div>
      <h3 id="operationTitle"></h3>
      <p id="operationMessage"></p>
      <div id="operationFormContainer" style="display:none;"></div>
      <div class="operation-modal-actions" id="operationActions"></div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    /* ============================================
       SIDEBAR — hover expands, click pins
       ============================================ */
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    let isPinned = false;
    let collapseTimer = null;

    function expandSidebar() {
      sidebar.classList.add('expanded');
      body.classList.add('sidebar-open');
    }

    function collapseSidebar() {
      if (isPinned) return;
      sidebar.classList.remove('expanded');
      body.classList.remove('sidebar-open');
    }

    function pinSidebar(event) {
      if (event) event.stopPropagation();
      isPinned = true;
      expandSidebar();
    }

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
      if (sidebar.classList.contains('expanded')) {
        unpinSidebar();
      } else {
        pinSidebar();
      }
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
       CURRICULUM LOGIC (unchanged)
       ============================================ */
    let sortDirection = 1;
    let operationCallback = null;
    let currentCurriculumId = null;
    let currentCurriculumData = null;

    function escapeHtml(s) {
      return String(s ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      } [m]));
    }

    async function apiPost(action, data = {}) {
      const fd = new FormData();
      fd.append('action', action);
      for (const k in data) fd.append(k, data[k]);
      const res = await fetch('curriculum.php', { method: 'POST', body: fd });
      return res.json();
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadCurriculumData();
    });

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
        formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" id="op_field_${i}" value="${escapeHtml(field.value || '')}" /></div>`;
      });
      document.getElementById('operationFormContainer').innerHTML = formHtml;
      document.getElementById('operationFormContainer').style.display = 'block';
      operationCallback = callback;
      document.getElementById('operationActions').innerHTML = `
        <button class="op-btn cancel" onclick="closeOperationModal()">Cancel</button>
        <button class="op-btn confirm-success" onclick="executeInputOperation()">Save</button>`;
      modal.classList.add('active');
    }

    function executeOperation() {
      const cb = operationCallback;
      closeOperationModal();
      if (cb) cb();
    }

    function executeInputOperation() {
      const cb = operationCallback;
      const values = [];
      document.querySelectorAll('#operationFormContainer input').forEach(i => values.push(i.value));
      closeOperationModal();
      if (cb) cb(values);
    }

    function closeOperationModal() {
      document.getElementById('operationModal').classList.remove('active');
      operationCallback = null;
    }

    /* ============ Load list ============ */
    async function loadCurriculumData() {
      const tbody = document.getElementById('curriculumTableBody');
      const r = await apiPost('list');
      if (!r.success) return;
      tbody.innerHTML = '';
      let grandTotalUnits = 0;
      r.data.forEach(c => {
        grandTotalUnits += parseInt(c.total_units) || 0;
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escapeHtml(c.code)}</td><td>${escapeHtml(c.title)}</td><td>${escapeHtml(c.academic_year)}</td><td>${c.total_units}</td>
          <td><div class="action-group">
            <button class="btn-action view" onclick="viewCurriculum(${c.id})"><i class="fas fa-eye"></i> View</button>
            <button class="btn-action decline" onclick="deleteCurriculum(${c.id})"><i class="fas fa-trash"></i> Delete</button>
          </div></td>`;
        tbody.appendChild(tr);
      });
      document.getElementById('totalCurriculumsCard').textContent = r.data.length;
      document.getElementById('activeProgramsCard').textContent = r.data.length;
      document.getElementById('totalCoursesCard').textContent = r.data.length;
      document.getElementById('totalUnitsCard').textContent = grandTotalUnits;
    }

    /* ============ View curriculum ============ */
    async function viewCurriculum(id) {
      currentCurriculumId = id;
      const r = await apiPost('get', { id });
      if (!r.success) { alert(r.message); return; }
      currentCurriculumData = r.data;
      const c = r.data;
      const body = document.getElementById('viewCurriculumBody');
      let html = `<div class="curriculum-info">
        <h2>INTERWORLD COLLEGES FOUNDATION, INC</h2>
        <h3>Burgos St., Paniqui, Tarlac</h3>
        <p><strong>${escapeHtml(c.title)}</strong></p>
        <p>${escapeHtml(c.academic_year)}</p></div>`;
      c.semesters.forEach(sem => {
        html += `<div class="semester-section"><div class="semester-header">
          <div class="semester-title"><i class="fas fa-calendar-alt"></i> ${escapeHtml(sem.name)}</div>
          <div class="semester-actions">
            <button class="btn-small add" onclick="addSubject(${sem.id})"><i class="fas fa-plus"></i> Add Subject</button>
            <button class="btn-small" onclick="editSemesterName(${sem.id})"><i class="fas fa-pen"></i> Rename</button>
            <button class="btn-small danger" onclick="deleteSemester(${sem.id})"><i class="fas fa-trash"></i> Delete</button>
          </div></div>
          <table class="curriculum-table"><thead><tr><th>Pre-req</th><th>Code</th><th>Title</th><th>Lec</th><th>Lab</th><th>Units</th><th>Actions</th></tr></thead><tbody>`;
        sem.subjects.forEach(s => {
          html += `<tr><td>${escapeHtml(s.prereq)}</td><td>${escapeHtml(s.code)}</td><td>${escapeHtml(s.title)}</td>
            <td>${s.lec}</td><td>${s.lab}</td><td>${s.units}</td>
            <td><div class="action-group">
              <button class="btn-action" onclick="editSubject(${s.id})"><i class="fas fa-pen"></i> Edit</button>
              <button class="btn-action decline" onclick="deleteSubject(${s.id})"><i class="fas fa-trash"></i> Delete</button>
            </div></td></tr>`;
        });
        const tL = sem.subjects.reduce((sum, s) => sum + parseFloat(s.lec), 0);
        const tB = sem.subjects.reduce((sum, s) => sum + parseFloat(s.lab), 0);
        const tU = sem.subjects.reduce((sum, s) => sum + parseFloat(s.units), 0);
        html += `</tbody><tr class="total-row"><td colspan="2" style="text-align:right;">TOTAL</td><td></td><td>${tL}</td><td>${tB}</td><td>${tU}</td><td></td></tr></table></div>`;
      });
      html += `<div class="add-semester-bar"><button class="btn primary" onclick="addSemester()"><i class="fas fa-plus"></i> Add Semester</button></div>`;
      body.innerHTML = html;
      document.getElementById('viewCurriculumModal').classList.add('active');
    }

    function findSubject(subjectId) {
      if (!currentCurriculumData) return null;
      for (const sem of currentCurriculumData.semesters)
        for (const sub of sem.subjects)
          if (sub.id == subjectId) return sub;
      return null;
    }

    /* ============ Semester CRUD ============ */
    function addSemester() {
      showInputModal("Add New Semester", [{ label: "Semester Name", value: "" }], async (values) => {
        if (!values[0]) return;
        const r = await apiPost('add_semester', { curriculum_id: currentCurriculumId, name: values[0] });
        if (r.success) {
          await viewCurriculum(currentCurriculumId);
          showSuccessModal("Success!", "Semester added successfully.");
        } else alert(r.message);
      });
    }

    function editSemesterName(semesterId) {
      const sem = currentCurriculumData.semesters.find(s => s.id == semesterId);
      if (!sem) return;
      showInputModal("Edit Semester Name", [{ label: "Semester Name", value: sem.name }], async (values) => {
        if (!values[0]) return;
        const r = await apiPost('edit_semester', { semester_id: semesterId, name: values[0] });
        if (r.success) {
          await viewCurriculum(currentCurriculumId);
          showSuccessModal("Updated!", "Semester name updated.");
        } else alert(r.message);
      });
    }

    function deleteSemester(semesterId) {
      showWarningModal("Delete Semester", "Are you sure you want to delete this semester?", "Delete", async () => {
        const r = await apiPost('delete_semester', { semester_id: semesterId });
        if (r.success) {
          await viewCurriculum(currentCurriculumId);
          showSuccessModal("Deleted!", "Semester deleted successfully.");
        } else alert(r.message);
      });
    }

    /* ============ Subject CRUD ============ */
    function addSubject(semesterId) {
      showInputModal("Add New Subject", [
        { label: "Pre-requisite", value: "None" },
        { label: "Subject Code", value: "" },
        { label: "Subject Title", value: "" },
        { label: "Lecture Units", value: "3" },
        { label: "Lab Units", value: "0" },
        { label: "Total Units", value: "3" }
      ], async (values) => {
        const r = await apiPost('add_subject', {
          semester_id: semesterId,
          prereq: values[0] || "None",
          code: values[1] || "",
          title: values[2] || "",
          lec: values[3] || 0,
          lab: values[4] || 0,
          units: values[5] || 0
        });
        if (r.success) {
          await viewCurriculum(currentCurriculumId);
          showSuccessModal("Success!", "Subject added successfully.");
        } else alert(r.message);
      });
    }

    function editSubject(subjectId) {
      const sub = findSubject(subjectId);
      if (!sub) return;
      showInputModal("Edit Subject", [
        { label: "Pre-requisite", value: sub.prereq },
        { label: "Subject Code", value: sub.code },
        { label: "Subject Title", value: sub.title },
        { label: "Lecture Units", value: sub.lec },
        { label: "Lab Units", value: sub.lab },
        { label: "Total Units", value: sub.units }
      ], async (values) => {
        const r = await apiPost('edit_subject', {
          subject_id: subjectId,
          prereq: values[0] || sub.prereq,
          code: values[1] || sub.code,
          title: values[2] || sub.title,
          lec: values[3] || sub.lec,
          lab: values[4] || sub.lab,
          units: values[5] || sub.units
        });
        if (r.success) {
          await viewCurriculum(currentCurriculumId);
          showSuccessModal("Updated!", "Subject updated successfully.");
        } else alert(r.message);
      });
    }

    function deleteSubject(subjectId) {
      showWarningModal("Delete Subject", "Are you sure you want to delete this subject?", "Delete", async () => {
        const r = await apiPost('delete_subject', { subject_id: subjectId });
        if (r.success) {
          await viewCurriculum(currentCurriculumId);
          showSuccessModal("Deleted!", "Subject deleted successfully.");
        } else alert(r.message);
      });
    }

    function deleteCurriculum(id) {
      showWarningModal("Delete Curriculum", "Are you sure you want to delete this entire curriculum? All semesters and subjects will be removed.", "Delete", async () => {
        const r = await apiPost('delete_curriculum', { id });
        if (r.success) {
          await loadCurriculumData();
          showSuccessModal("Deleted!", "Curriculum deleted successfully.");
        } else alert(r.message);
      });
    }

    function closeViewCurriculumModal() {
      document.getElementById('viewCurriculumModal').classList.remove('active');
    }

    /* ============ Add Course modal ============ */
    function openAddCurriculumModal() {
      document.getElementById('addCurriculumForm').reset();
      document.getElementById('addCurriculumModal').classList.add('active');
    }

    function closeAddCurriculumModal() {
      document.getElementById('addCurriculumModal').classList.remove('active');
    }

    async function saveCurriculum() {
      const code = document.getElementById('curriculumCode').value.trim();
      const title = document.getElementById('curriculumTitle').value.trim();
      const year = document.getElementById('curriculumYear').value.trim();
      const academicYear = document.getElementById('curriculumAcademicYear').value.trim();
      const units = document.getElementById('curriculumUnits').value.trim();
      if (!code || !title || !year || !academicYear || !units) {
        showWarningModal("Missing Information", "Please fill in all fields before saving.", "OK", null);
        return;
      }
      const r = await apiPost('add_curriculum', { code, title, year, academic_year: academicYear, units });
      if (r.success) {
        await loadCurriculumData();
        closeAddCurriculumModal();
        showSuccessModal("Course Added!", "Course has been added successfully! Click 'View' to add detailed subjects.");
      } else alert(r.message);
    }

    /* ============ Sorting ============ */
    function sortTable(columnIndex) {
      const table = document.getElementById('curriculumTable');
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.rows);
      sortDirection = sortDirection === 1 ? -1 : 1;
      rows.sort((a, b) => {
        const aT = a.cells[columnIndex].textContent.trim().toLowerCase();
        const bT = b.cells[columnIndex].textContent.trim().toLowerCase();
        if (aT < bT) return -1 * sortDirection;
        if (aT > bT) return 1 * sortDirection;
        return 0;
      });
      rows.forEach(r => tbody.appendChild(r));
      document.querySelectorAll('th.sortable i').forEach(i => i.className = 'fas fa-sort');
      const active = table.querySelector(`th.sortable:nth-child(${columnIndex + 1}) i`);
      if (active) active.className = sortDirection === 1 ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }
  </script>
</body>

</html>