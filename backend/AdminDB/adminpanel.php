<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
  header("Location: login.html");
  exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

$count_result = $conn->query("SELECT COUNT(*) as total FROM enrolled");
$total_enrollments = $count_result->fetch_assoc()['total'] ?? 0;

$course_result = $conn->query("SELECT COUNT(DISTINCT course) as total FROM enrolled");
$total_courses = $course_result->fetch_assoc()['total'] ?? 0;

$pendingCount = 0;
$colCheck = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'enrollment_status'");
if ($colCheck && $colCheck->num_rows > 0) {
  $r = $conn->query("SELECT COUNT(*) as t FROM enrolled WHERE enrollment_status='pending'");
  if ($r) $pendingCount = $r->fetch_assoc()['t'];
}

$instructorCount = 0;
$insCheck = $conn->query("SHOW TABLES LIKE 'instructors'");
if ($insCheck && $insCheck->num_rows > 0) {
  $r = $conn->query("SELECT COUNT(*) as t FROM instructors");
  if ($r) $instructorCount = $r->fetch_assoc()['t'];
}

$logins = [];
$login_check = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'last_login'");
if ($login_check && $login_check->num_rows > 0) {
  $logins_result = $conn->query("SELECT student_id, firstname, lastname, email, ip_address, last_login, status FROM enrolled WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 5");
  while ($row = $logins_result->fetch_assoc()) {
    $logins[] = $row;
  }
}

$enrollments = [];
$enr_result = $conn->query("SELECT student_id, firstname, lastname, course, academic_year, mobile_number, email FROM enrolled ORDER BY student_id DESC LIMIT 15");
while ($row = $enr_result->fetch_assoc()) {
  $enrollments[] = $row;
}

$full_records = [];
$all_res = $conn->query("SELECT * FROM enrolled ORDER BY student_id DESC LIMIT 15");
while ($row = $all_res->fetch_assoc()) {
  $full_records[$row['student_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Control Panel | ICF Interworld Colleges Foundation Inc.</title>
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

      /* Sidebar widths */
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
       - Default: collapsed (icon-only)
       - .expanded: full width
       - .pinned: stays expanded until clicking outside
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

    /* -------- Toggle Button (outside the sidebar) -------- */
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
      transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease, transform 0.2s ease;
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

    /* -------- School Brand -------- */
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
      transition: var(--transition-smooth);
    }

    .school-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    .school-logo .logo-fallback {
      font-size: 1.6rem;
      color: var(--primary-neon);
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
      letter-spacing: 0.2px;
    }

    .school-name .sub {
      font-size: 0.6rem;
      font-weight: 400;
      color: var(--text-muted-teal);
      margin-top: 3px;
      letter-spacing: 1.2px;
      text-transform: uppercase;
    }

    /* -------- Search -------- */
    .sidebar-search {
      padding: 14px 0 10px;
      flex-shrink: 0;
      position: relative;
      display: flex;
      justify-content: center;
    }

    .sidebar-search .search-wrapper {
      position: relative;
      width: 100%;
      display: flex;
      justify-content: center;
    }

    .search-toggle-icon {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      color: var(--text-muted-teal);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      cursor: pointer;
      transition: var(--transition-smooth);
      flex-shrink: 0;
    }

    .search-toggle-icon:hover {
      border-color: var(--primary-neon);
      color: var(--primary-neon);
      box-shadow: var(--neon-glow);
    }

    .sidebar-search input {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      left: 16px;
      right: 16px;
      width: calc(100% - 32px);
      padding: 11px 16px 11px 40px;
      background-color: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      color: var(--text-high-contrast);
      font-size: 0.85rem;
      outline: none;
      transition: var(--transition-smooth);
      opacity: 0;
      pointer-events: none;
    }

    .sidebar.expanded .sidebar-search input {
      opacity: 1;
      pointer-events: auto;
    }

    .sidebar.expanded .search-toggle-icon {
      opacity: 0;
      pointer-events: none;
      position: absolute;
    }

    .sidebar-search .search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted-teal);
      font-size: 0.85rem;
      pointer-events: none;
      z-index: 1;
    }

    .sidebar-search input:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1);
    }

    .sidebar-search .search-results-dropdown {
      position: absolute;
      top: calc(100% - 0px);
      left: 16px;
      right: 16px;
      background-color: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      max-height: 260px;
      overflow-y: auto;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.8);
      z-index: 1200;
      display: none;
    }

    .sidebar-search .search-results-dropdown.active {
      display: block;
    }

    .search-result-item {
      padding: 11px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-teal);
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .search-result-item:last-child {
      border-bottom: none;
    }

    .search-result-item:hover {
      background-color: var(--bg-card-hover);
    }

    .search-result-info {
      display: flex;
      flex-direction: column;
    }

    .search-result-name {
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--text-high-contrast);
    }

    .search-result-role {
      font-size: 0.72rem;
      color: var(--text-muted-teal);
    }

    .no-results {
      padding: 14px;
      font-size: 0.82rem;
      color: var(--text-muted-teal);
      text-align: center;
    }

    /* -------- Navigation -------- */
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
      border-radius: 0;
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
      font-weight: 500;
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

    /* -------- Admin Profile + Actions -------- */
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
      background: transparent;
      border: none;
      cursor: pointer;
      transition: var(--transition-smooth);
      user-select: none;
      overflow: hidden;
      position: relative;
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
      transition: var(--transition-smooth);
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
      justify-content: flex-start;
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
       MAIN CONTENT — with gradient background
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
       TOP HEADER — Title (left) + Smaller Clock (center)
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

    /* ---- LIVE CLOCK (smaller) ---- */
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

      0%,
      100% {
        opacity: 0.4;
      }

      50% {
        opacity: 1;
      }
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
      grid-template-columns: 2fr 1fr 1fr;
      grid-template-rows: auto auto;
      gap: 20px;
      margin-bottom: 24px;
    }

    .metric-card.featured {
      grid-row: span 2;
      padding: 26px 28px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: radial-gradient(circle at top right, rgba(13, 245, 227, 0.08), transparent 60%), var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      position: relative;
      overflow: hidden;
      box-shadow: var(--card-shadow);
    }

    .metric-card.featured::after {
      content: '\f501';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      right: -10px;
      bottom: -20px;
      font-size: 9rem;
      color: rgba(13, 245, 227, 0.04);
      pointer-events: none;
    }

    .metric-card.featured .metric-value {
      font-size: 3.6rem;
      letter-spacing: -1.5px;
      margin: 12px 0 6px;
      background: linear-gradient(135deg, #e2e8f0 40%, #0df5e3);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .metric-card.featured .featured-sub {
      font-size: 0.82rem;
      color: var(--text-muted-teal);
      margin-bottom: 18px;
    }

    .featured-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      padding-top: 18px;
      border-top: 1px solid var(--border-teal);
      margin-top: auto;
    }

    .featured-stat {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .featured-stat-label {
      font-size: 0.7rem;
      color: var(--text-muted-teal);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-weight: 600;
    }

    .featured-stat-value {
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--text-high-contrast);
      letter-spacing: -0.3px;
    }

    .featured-stat-value.accent {
      color: var(--primary-neon);
    }

    /* Regular Metric Cards — clickable shortcuts */
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
      text-decoration: none;
      color: inherit;
      cursor: pointer;
      padding-bottom: 44px;
    }

    .metric-card:not(.featured):hover {
      transform: translateY(-4px);
      border-color: var(--primary-neon);
      box-shadow: 0 12px 28px rgba(13, 245, 227, 0.15);
    }

    .metric-card:not(.featured):hover .card-shortcut {
      background: var(--primary-neon);
      color: #020c1b;
      border-color: var(--primary-neon);
      transform: translate(2px, -2px);
      box-shadow: 0 0 12px rgba(13, 245, 227, 0.5);
    }

    .metric-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .metric-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--text-muted-teal);
      font-weight: 600;
    }

    .metric-trend-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 20px;
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .metric-trend-badge.down {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border-color: rgba(239, 68, 68, 0.3);
    }

    .metric-trend-badge.neutral {
      background: rgba(148, 163, 184, 0.15);
      color: #94a3b8;
      border-color: rgba(148, 163, 184, 0.3);
    }

    .metric-trend-badge i {
      font-size: 0.6rem;
    }

    .metric-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-high-contrast);
      line-height: 1.15;
      letter-spacing: -0.5px;
      margin-bottom: 8px;
    }

    .metric-footer {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.72rem;
      color: var(--text-muted-teal);
      margin-top: auto;
    }

    .metric-footer i {
      color: var(--primary-neon);
      font-size: 0.7rem;
    }

    .metric-footer .footer-divider {
      width: 1px;
      height: 12px;
      background: var(--border-teal);
      margin: 0 4px;
    }

    .metric-footer strong {
      color: var(--text-high-contrast);
      font-weight: 600;
    }

    .metric-card.staff-card {
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), transparent 70%), var(--bg-card);
    }

    .metric-card.staff-card .metric-footer i {
      color: var(--purple-accent);
    }

    /* Shortcut arrow (bottom-right of card) */
    .card-shortcut {
      position: absolute;
      right: 14px;
      bottom: 14px;
      width: 26px;
      height: 26px;
      border-radius: 8px;
      border: 1px solid var(--border-teal);
      background: rgba(13, 245, 227, 0.05);
      color: var(--text-muted-teal);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
      transition: var(--transition-smooth);
    }

    /* ============================================
       CHART PANELS
       ============================================ */
    .charts-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 22px;
      margin-bottom: 22px;
    }

    .charts-grid .full-width {
      grid-column: 1 / -2;
    }

    .chart-panel {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 22px 24px;
      box-shadow: var(--card-shadow);
      transition: var(--transition-smooth);
    }

    .chart-panel:hover {
      border-color: #1f3a5f;
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      flex-wrap: wrap;
      gap: 12px;
    }

    .chart-header h3 {
      font-size: 0.98rem;
      font-weight: 600;
      color: var(--text-high-contrast);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .chart-header h3 i {
      color: var(--primary-neon);
    }

    .chart-legend {
      display: flex;
      gap: 16px;
      font-size: 0.75rem;
      color: var(--text-muted-teal);
      flex-wrap: wrap;
    }

    .legend-dot {
      width: 10px;
      height: 10px;
      border-radius: 3px;
      display: inline-block;
      margin-right: 5px;
    }

    .line-chart-container {
      position: relative;
      width: 100%;
      height: 260px;
    }

    .line-chart-svg {
      width: 100%;
      height: 100%;
      display: block;
    }

    .line-chart-svg .grid-line {
      stroke: #1a2d45;
      stroke-width: 1;
      stroke-dasharray: 4 4;
    }

    .line-chart-svg .axis-label {
      fill: var(--text-muted-teal);
      font-size: 10px;
      font-family: 'Segoe UI', sans-serif;
    }

    .line-chart-svg .chart-line-enroll {
      fill: none;
      stroke: var(--primary-neon);
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      filter: drop-shadow(0 0 6px rgba(13, 245, 227, 0.5));
    }

    .line-chart-svg .chart-line-inquiry {
      fill: none;
      stroke: var(--blue-accent);
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      filter: drop-shadow(0 0 6px rgba(59, 130, 246, 0.5));
      stroke-dasharray: 6 4;
    }

    .line-chart-svg .chart-area-enroll {
      fill: url(#enrollGradient);
      opacity: 0.25;
    }

    .line-chart-svg .data-point-enroll {
      fill: var(--bg-card);
      stroke: var(--primary-neon);
      stroke-width: 2.5;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .line-chart-svg .data-point-enroll:hover {
      fill: var(--primary-neon);
      r: 6;
    }

    .line-chart-svg .data-point-inquiry {
      fill: var(--bg-card);
      stroke: var(--blue-accent);
      stroke-width: 2.5;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .line-chart-svg .data-point-inquiry:hover {
      fill: var(--blue-accent);
      r: 6;
    }

    .line-chart-svg .chart-line-male {
      fill: none;
      stroke: var(--blue-accent);
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      filter: drop-shadow(0 0 6px rgba(59, 130, 246, 0.5));
    }

    .line-chart-svg .chart-line-female {
      fill: none;
      stroke: var(--pink-accent);
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      filter: drop-shadow(0 0 6px rgba(236, 72, 153, 0.5));
    }

    .line-chart-svg .chart-area-male {
      fill: url(#maleGradient);
      opacity: 0.25;
    }

    .line-chart-svg .chart-area-female {
      fill: url(#femaleGradient);
      opacity: 0.25;
    }

    .line-chart-svg .data-point-male {
      fill: var(--bg-card);
      stroke: var(--blue-accent);
      stroke-width: 2.5;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .line-chart-svg .data-point-male:hover {
      fill: var(--blue-accent);
      r: 6;
    }

    .line-chart-svg .data-point-female {
      fill: var(--bg-card);
      stroke: var(--pink-accent);
      stroke-width: 2.5;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .line-chart-svg .data-point-female:hover {
      fill: var(--pink-accent);
      r: 6;
    }

    /* ============================================
       DONUT CHART — enlarged
       ============================================ */
    .donut-chart-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 16px;
      padding: 5px 0;
      flex-wrap: wrap;
    }

    .donut-svg-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 22px;
      flex-wrap: wrap;
      width: 100%;
    }

    .donut-svg {
      width: 190px;
      height: 190px;
      flex-shrink: 0;
    }

    .donut-svg .donut-track {
      fill: none;
      stroke: #1a2d45;
      stroke-width: 16;
    }

    .donut-svg .donut-segment {
      fill: none;
      stroke-width: 16;
      stroke-linecap: butt;
      transition: all 0.4s ease;
      cursor: pointer;
    }

    .donut-svg .donut-segment:hover {
      opacity: 0.8;
    }

    .donut-svg .donut-center-text {
      fill: var(--text-high-contrast);
      font-size: 24px;
      font-weight: 700;
      font-family: 'Segoe UI', sans-serif;
    }

    .donut-svg .donut-center-label {
      fill: var(--text-muted-teal);
      font-size: 9px;
      font-family: 'Segoe UI', sans-serif;
      letter-spacing: 0.8px;
      text-transform: uppercase;
    }

    .donut-legend {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 7px 16px;
      min-width: 220px;
    }

    .donut-legend-item {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.75rem;
      color: var(--text-muted-teal);
    }

    .donut-legend-dot {
      width: 10px;
      height: 10px;
      border-radius: 3px;
      flex-shrink: 0;
    }

    .donut-legend-item .donut-legend-value {
      margin-left: auto;
      font-weight: 700;
      color: var(--text-high-contrast);
    }

    /* Total Student text below donut */
    .donut-total-student {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 18px;
      background: linear-gradient(135deg, rgba(13, 245, 227, 0.08), rgba(13, 245, 227, 0.02));
      border: 1px solid rgba(13, 245, 227, 0.2);
      border-radius: 10px;
      font-size: 0.82rem;
      color: var(--text-muted-teal);
      font-weight: 500;
      letter-spacing: 0.3px;
      margin-top: 4px;
      width: 100%;
      max-width: 420px;
    }

    .donut-total-student i {
      color: var(--primary-neon);
      font-size: 0.95rem;
    }

    .donut-total-student strong {
      color: var(--primary-neon);
      font-size: 1.05rem;
      font-weight: 700;
      letter-spacing: -0.2px;
      text-shadow: 0 0 10px rgba(13, 245, 227, 0.4);
    }

    /* ============================================
       CIRCLE / RADIAL PROGRESS
       ============================================ */
    .circle-charts-container {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      justify-items: center;
      align-items: center;
      gap: 14px 8px;
      padding: 5px 0;
    }

    .circle-metric {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }

    .circle-svg {
      width: 80px;
      height: 80px;
    }

    .circle-svg .circle-track {
      fill: none;
      stroke: #1a2d45;
      stroke-width: 8;
    }

    .circle-svg .circle-progress {
      fill: none;
      stroke-width: 8;
      stroke-linecap: round;
      transform: rotate(-90deg);
      transform-origin: 50% 50%;
      transition: stroke-dashoffset 1s ease;
    }

    .circle-svg .circle-progress.teal {
      stroke: var(--primary-neon);
      filter: drop-shadow(0 0 5px rgba(13, 245, 227, 0.5));
    }

    .circle-svg .circle-progress.purple {
      stroke: var(--purple-accent);
      filter: drop-shadow(0 0 5px rgba(139, 92, 246, 0.5));
    }

    .circle-svg .circle-progress.amber {
      stroke: var(--warning-amber);
      filter: drop-shadow(0 0 5px rgba(245, 158, 11, 0.5));
    }

    .circle-svg .circle-progress.blue {
      stroke: var(--blue-accent);
      filter: drop-shadow(0 0 5px rgba(59, 130, 246, 0.5));
    }

    .circle-svg .circle-progress.pink {
      stroke: var(--pink-accent);
      filter: drop-shadow(0 0 5px rgba(236, 72, 153, 0.5));
    }

    .circle-svg .circle-progress.orange {
      stroke: var(--orange-accent);
      filter: drop-shadow(0 0 5px rgba(251, 146, 60, 0.5));
    }

    .circle-svg .circle-progress.green {
      stroke: var(--success-green);
      filter: drop-shadow(0 0 5px rgba(16, 185, 129, 0.5));
    }

    .circle-svg .circle-progress.cyan {
      stroke: #06b6d4;
      filter: drop-shadow(0 0 5px rgba(6, 182, 212, 0.5));
    }

    .circle-svg .circle-progress.red {
      stroke: #ef4444;
      filter: drop-shadow(0 0 5px rgba(239, 68, 68, 0.5));
    }

    .circle-svg .circle-progress.lime {
      stroke: #84cc16;
      filter: drop-shadow(0 0 5px rgba(132, 204, 22, 0.5));
    }

    .circle-svg .circle-text {
      fill: var(--text-high-contrast);
      font-size: 14px;
      font-weight: 700;
      font-family: 'Segoe UI', sans-serif;
      text-anchor: middle;
      dominant-baseline: middle;
    }

    .circle-metric-label {
      font-size: 0.65rem;
      color: var(--text-muted-teal);
      text-align: center;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    /* ============================================
       MESSAGES
       ============================================ */
    .messages-panel {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 22px 24px;
      box-shadow: var(--card-shadow);
      margin-bottom: 28px;
      transition: var(--transition-smooth);
    }

    .messages-panel:hover {
      border-color: #1f3a5f;
    }

    .messages-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      flex-wrap: wrap;
      gap: 12px;
    }

    .messages-header h3 {
      font-size: 0.98rem;
      font-weight: 600;
      color: var(--text-high-contrast);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .messages-header h3 i {
      color: var(--primary-neon);
    }

    .messages-badge {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.3);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.3px;
      text-transform: uppercase;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .messages-badge i {
      font-size: 0.65rem;
    }

    .messages-list {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .message-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 12px;
      border-radius: 10px;
      border: 1px solid transparent;
      transition: var(--transition-smooth);
      cursor: pointer;
      position: relative;
    }

    .message-item:hover {
      background: rgba(13, 245, 227, 0.04);
      border-color: var(--border-teal);
    }

    .message-item.unread::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 60%;
      background: var(--primary-neon);
      border-radius: 0 3px 3px 0;
      box-shadow: 0 0 8px rgba(13, 245, 227, 0.6);
    }

    .message-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.95rem;
      flex-shrink: 0;
      color: #020c1b;
    }

    .message-avatar.teal {
      background: linear-gradient(135deg, #0df5e3, #00a89d);
    }

    .message-avatar.purple {
      background: linear-gradient(135deg, #a78bfa, #7c3aed);
    }

    .message-avatar.amber {
      background: linear-gradient(135deg, #fbbf24, #d97706);
    }

    .message-avatar.blue {
      background: linear-gradient(135deg, #60a5fa, #2563eb);
    }

    .message-avatar.pink {
      background: linear-gradient(135deg, #f472b6, #db2777);
    }

    .message-content {
      flex: 1;
      min-width: 0;
    }

    .message-top {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 10px;
      margin-bottom: 3px;
    }

    .message-sender {
      font-weight: 600;
      font-size: 0.88rem;
      color: var(--text-high-contrast);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .message-time {
      font-size: 0.7rem;
      color: var(--text-muted-teal);
      white-space: nowrap;
      flex-shrink: 0;
    }

    .message-preview {
      font-size: 0.78rem;
      color: var(--text-muted-teal);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .message-subject {
      display: inline-block;
      background: rgba(13, 245, 227, 0.08);
      color: var(--primary-neon);
      font-size: 0.65rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 4px;
      margin-right: 6px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .message-actions {
      display: flex;
      gap: 6px;
      flex-shrink: 0;
    }

    .message-action-btn {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      border: 1px solid var(--border-teal);
      background: transparent;
      color: var(--text-muted-teal);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
      transition: var(--transition-smooth);
    }

    .message-action-btn:hover {
      border-color: var(--primary-neon);
      color: var(--primary-neon);
      background: rgba(13, 245, 227, 0.08);
    }

    .message-action-btn.reply:hover {
      border-color: var(--success-green);
      color: var(--success-green);
      background: rgba(16, 185, 129, 0.08);
    }

    /* ============================================
       TABLES
       ============================================ */
    .tables-container {
      display: flex;
      flex-direction: column;
      gap: 28px;
    }

    .table-card {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 22px 24px;
      box-shadow: var(--card-shadow);
      transition: var(--transition-smooth);
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
      max-width: 700px;
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

    .modal-close-btn {
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      font-size: 1.2rem;
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .modal-close-btn:hover {
      color: var(--text-high-contrast);
    }

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

    .view-modal-section {
      margin-bottom: 22px;
    }

    .view-modal-section h4 {
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--primary-neon);
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid var(--border-teal);
    }

    .view-modal-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 20px;
    }

    .view-modal-row {
      display: flex;
      font-size: 0.85rem;
      padding: 4px 0;
    }

    .view-modal-label {
      width: 130px;
      font-weight: 500;
      color: var(--text-muted-teal);
      flex-shrink: 0;
    }

    .view-modal-value {
      flex: 1;
      color: var(--text-high-contrast);
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 1400px) {
      .donut-legend {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 1200px) {
      .cards-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
      }

      .metric-card.featured {
        grid-column: 1 / -1;
        grid-row: auto;
      }

      .charts-grid {
        grid-template-columns: 1fr;
      }

      .charts-grid .full-width {
        grid-column: 1 / -1;
      }

      .circle-charts-container {
        grid-template-columns: repeat(5, 1fr);
      }

      .top-header {
        grid-template-columns: 1fr;
        gap: 18px;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }

      .cards-grid {
        grid-template-columns: 1fr;
      }

      .view-modal-grid {
        grid-template-columns: 1fr;
      }

      .donut-svg-wrapper {
        flex-direction: column;
      }

      .circle-charts-container {
        grid-template-columns: repeat(3, 1fr);
      }

      .message-actions {
        display: none;
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
    }
  </style>
</head>

<body>
  <!-- ============================================
       Toggle Button — positioned OUTSIDE the sidebar
       ============================================ -->
  <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar(event)" title="Toggle Sidebar">
    <i class="fa-solid fa-chevron-right"></i>
  </button>

  <!-- ============================================
       COLLAPSIBLE SIDEBAR
       Hover = expand, click = pin (stays open)
       Click outside = unpin (collapse back)
       ============================================ -->
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
      <li class="active">
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
        <a href="college-staff.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-id-badge"></i></span>
          <span class="nav-label">College Staff</span>
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

    <div class="sidebar-search">
      <div class="search-wrapper">
        <div class="search-toggle-icon" id="searchToggleIcon" onclick="pinSidebar(event)">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <input type="text" id="userSearchInput" placeholder="Search students, courses..." autocomplete="off" />
        <div id="searchResults" class="search-results-dropdown"></div>
      </div>
    </div>

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
        <h1><i class="fa-solid fa-gauge-high"></i> Dashboard Overview</h1>
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
    <section class="cards-grid">
      <div class="metric-card featured">
        <div class="metric-top">
          <span class="metric-label">Enrolled Students</span>
          <span class="metric-trend-badge">
            <i class="fa-solid fa-arrow-up"></i> +12.5%
          </span>
        </div>
        <div class="metric-value"><?php echo $total_enrollments; ?></div>
        <div class="featured-sub">
          Total students actively enrolled across all 10 programs this semester.
        </div>
        <div class="featured-stats">
          <div class="featured-stat">
            <span class="featured-stat-label">New This Academic Year</span>
            <span class="featured-stat-value accent">+228</span>
          </div>
          <div class="featured-stat">
            <span class="featured-stat-label">Graduating</span>
            <span class="featured-stat-value">126</span>
          </div>
        </div>
      </div>

      <a href="instructor.php" class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Total Instructors</span>
          <span class="metric-trend-badge">
            <i class="fa-solid fa-arrow-up"></i> +2.5%
          </span>
        </div>
        <div class="metric-value"><?php echo $instructorCount; ?></div>
        <div class="metric-footer">
          <i class="fa-solid fa-chalkboard-user"></i>
          <span>Active faculty</span>
          <span class="footer-divider"></span>
          <strong>12</strong>
          <span>departments</span>
        </div>
        <span class="card-shortcut"><i class="fa-solid fa-arrow-right"></i></span>
      </a>

      <a href="enrollments.php" class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Pending Enrollment</span>
          <span class="metric-trend-badge down">
            <i class="fa-solid fa-arrow-down"></i> -1.2%
          </span>
        </div>
        <div class="metric-value"><?php echo $pendingCount; ?></div>
        <div class="metric-footer">
          <i class="fa-solid fa-clock"></i>
          <span>Awaiting review</span>
          <span class="footer-divider"></span>
          <strong>3</strong>
          <span>urgent</span>
        </div>
        <span class="card-shortcut"><i class="fa-solid fa-arrow-right"></i></span>
      </a>

      <a href="college-staff.php" class="metric-card staff-card">
        <div class="metric-top">
          <span class="metric-label">College Staff</span>
          <span class="metric-trend-badge">
            <i class="fa-solid fa-arrow-up"></i> +1.8%
          </span>
        </div>
        <div class="metric-value">86</div>
        <div class="metric-footer">
          <i class="fa-solid fa-id-badge"></i>
          <span>Non-teaching</span>
          <span class="footer-divider"></span>
          <strong>9</strong>
          <span>offices</span>
        </div>
        <span class="card-shortcut"><i class="fa-solid fa-arrow-right"></i></span>
      </a>

      <a href="curriculum.php" class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Courses Offered</span>
          <span class="metric-trend-badge neutral">
            <i class="fa-solid fa-minus"></i> 0.0%
          </span>
        </div>
        <div class="metric-value"><?php echo $total_courses; ?></div>
        <div class="metric-footer">
          <i class="fa-solid fa-book-open"></i>
          <span>Active programs</span>
          <span class="footer-divider"></span>
          <strong>4</strong>
          <span>colleges</span>
        </div>
        <span class="card-shortcut"><i class="fa-solid fa-arrow-right"></i></span>
      </a>
    </section>

    <div class="charts-grid">
      <div class="chart-panel full-width">
        <div class="chart-header">
          <h3><i class="fa-solid fa-chart-line"></i> Enrollment Activity (Last 9 months)</h3>
          <div class="chart-legend">
            <span><span class="legend-dot" style="background: var(--primary-neon);"></span> Enrollments</span>
            <span><span class="legend-dot" style="background: var(--blue-accent);"></span> Inquiries</span>
          </div>
        </div>
        <div class="line-chart-container">
          <svg class="line-chart-svg" viewBox="0 0 820 260" preserveAspectRatio="xMidYMid meet">
            <defs>
              <linearGradient id="enrollGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#0df5e3" stop-opacity="0.4" />
                <stop offset="100%" stop-color="#0df5e3" stop-opacity="0" />
              </linearGradient>
            </defs>

            <line class="grid-line" x1="50" y1="40" x2="800" y2="40" />
            <line class="grid-line" x1="50" y1="85" x2="800" y2="85" />
            <line class="grid-line" x1="50" y1="130" x2="800" y2="130" />
            <line class="grid-line" x1="50" y1="175" x2="800" y2="175" />
            <line class="grid-line" x1="50" y1="220" x2="800" y2="220" />

            <text class="axis-label" x="15" y="44">100</text>
            <text class="axis-label" x="15" y="89">75</text>
            <text class="axis-label" x="15" y="134">50</text>
            <text class="axis-label" x="15" y="179">25</text>
            <text class="axis-label" x="22" y="224">0</text>

            <polygon class="chart-area-enroll"
              points="80,155 170,135 260,180 350,105 440,140 530,90 620,150 710,75 790,110 790,220 80,220" />

            <polyline class="chart-line-enroll"
              points="80,155 170,135 260,180 350,105 440,140 530,90 620,150 710,75 790,110" />

            <polyline class="chart-line-inquiry"
              points="80,185 170,165 260,145 350,155 440,120 530,130 620,95 710,105 790,80" />

            <circle class="data-point-enroll" cx="80" cy="155" r="4.5" />
            <circle class="data-point-enroll" cx="170" cy="135" r="4.5" />
            <circle class="data-point-enroll" cx="260" cy="180" r="4.5" />
            <circle class="data-point-enroll" cx="350" cy="105" r="4.5" />
            <circle class="data-point-enroll" cx="440" cy="140" r="4.5" />
            <circle class="data-point-enroll" cx="530" cy="90" r="4.5" />
            <circle class="data-point-enroll" cx="620" cy="150" r="4.5" />
            <circle class="data-point-enroll" cx="710" cy="75" r="4.5" />
            <circle class="data-point-enroll" cx="790" cy="110" r="4.5" />

            <circle class="data-point-inquiry" cx="80" cy="185" r="4.5" />
            <circle class="data-point-inquiry" cx="170" cy="165" r="4.5" />
            <circle class="data-point-inquiry" cx="260" cy="145" r="4.5" />
            <circle class="data-point-inquiry" cx="350" cy="155" r="4.5" />
            <circle class="data-point-inquiry" cx="440" cy="120" r="4.5" />
            <circle class="data-point-inquiry" cx="530" cy="130" r="4.5" />
            <circle class="data-point-inquiry" cx="620" cy="95" r="4.5" />
            <circle class="data-point-inquiry" cx="710" cy="105" r="4.5" />
            <circle class="data-point-inquiry" cx="790" cy="80" r="4.5" />

            <text class="axis-label" x="80" y="245" text-anchor="middle">Nov</text>
            <text class="axis-label" x="170" y="245" text-anchor="middle">Dec</text>
            <text class="axis-label" x="260" y="245" text-anchor="middle">Jan</text>
            <text class="axis-label" x="350" y="245" text-anchor="middle">Feb</text>
            <text class="axis-label" x="440" y="245" text-anchor="middle">Mar</text>
            <text class="axis-label" x="530" y="245" text-anchor="middle">Apr</text>
            <text class="axis-label" x="620" y="245" text-anchor="middle">May</text>
            <text class="axis-label" x="710" y="245" text-anchor="middle">Jun</text>
            <text class="axis-label" x="790" y="245" text-anchor="middle">Jul</text>
          </svg>
        </div>
      </div>

      <!-- ============================================
           COURSE STUDENT METRICS (enlarged + Total Student text)
           ============================================ -->
      <div class="chart-panel">
        <div class="chart-header">
          <h3><i class="fa-solid fa-chart-pie"></i> Course Student Metrics</h3>
        </div>
        <div class="donut-chart-container">
          <div class="donut-svg-wrapper">
            <svg class="donut-svg" viewBox="0 0 120 120">
              <circle class="donut-track" cx="60" cy="60" r="46" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#0df5e3" stroke-dasharray="28.9 289" stroke-dashoffset="0" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#8b5cf6" stroke-dasharray="28.9 289" stroke-dashoffset="-28.9" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#ec4899" stroke-dasharray="28.9 289" stroke-dashoffset="-57.8" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#f59e0b" stroke-dasharray="28.9 289" stroke-dashoffset="-86.7" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#3b82f6" stroke-dasharray="28.9 289" stroke-dashoffset="-115.6" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#10b981" stroke-dasharray="28.9 289" stroke-dashoffset="-144.5" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#fb923c" stroke-dasharray="28.9 289" stroke-dashoffset="-173.4" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#06b6d4" stroke-dasharray="28.9 289" stroke-dashoffset="-202.3" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#ef4444" stroke-dasharray="28.9 289" stroke-dashoffset="-231.2" transform="rotate(-90 60 60)" />
              <circle class="donut-segment" cx="60" cy="60" r="46" stroke="#84cc16" stroke-dasharray="28.9 289" stroke-dashoffset="-260.1" transform="rotate(-90 60 60)" />

              <text class="donut-center-text" x="60" y="58" text-anchor="middle"><?php echo $total_courses; ?></text>
              <text class="donut-center-label" x="60" y="74" text-anchor="middle">COURSES</text>
            </svg>
            <div class="donut-legend">
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #0df5e3;"></span><span>BSCS</span><span class="donut-legend-value">142</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #8b5cf6;"></span><span>BSPA</span><span class="donut-legend-value">118</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #ec4899;"></span><span>BSTM</span><span class="donut-legend-value">96</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #f59e0b;"></span><span>BSED</span><span class="donut-legend-value">134</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #3b82f6;"></span><span>BTVTE</span><span class="donut-legend-value">78</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #10b981;"></span><span>BEED</span><span class="donut-legend-value">112</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #fb923c;"></span><span>BSBA</span><span class="donut-legend-value">105</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #06b6d4;"></span><span>BSA</span><span class="donut-legend-value">88</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #ef4444;"></span><span>ACT</span><span class="donut-legend-value">64</span></div>
              <div class="donut-legend-item"><span class="donut-legend-dot" style="background: #84cc16;"></span><span>CRIM</span><span class="donut-legend-value">72</span></div>
            </div>
          </div>

          <!-- Total Student label below the donut -->
          <div class="donut-total-student">
            <i class="fa-solid fa-users"></i>
            <span>Total Student:</span>
            <strong><?php echo $total_enrollments; ?></strong>
          </div>
        </div>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-panel full-width">
        <div class="chart-header">
          <h3><i class="fa-solid fa-circle-notch"></i> Department Overview</h3>
        </div>
        <div class="circle-charts-container">
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress teal" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="56.5" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">80%</text>
            </svg>
            <span class="circle-metric-label">BSCS</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress purple" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="70.7" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">75%</text>
            </svg>
            <span class="circle-metric-label">BSPA</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress pink" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="84.8" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">70%</text>
            </svg>
            <span class="circle-metric-label">BSTM</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress amber" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="42.4" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">85%</text>
            </svg>
            <span class="circle-metric-label">BSED</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress blue" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="98.9" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">65%</text>
            </svg>
            <span class="circle-metric-label">BTVTE</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress green" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="56.5" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">80%</text>
            </svg>
            <span class="circle-metric-label">BEED</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress orange" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="70.7" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">75%</text>
            </svg>
            <span class="circle-metric-label">BSBA</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress cyan" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="84.8" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">70%</text>
            </svg>
            <span class="circle-metric-label">BSA</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress red" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="113.1" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">60%</text>
            </svg>
            <span class="circle-metric-label">ACT</span>
          </div>
          <div class="circle-metric">
            <svg class="circle-svg" viewBox="0 0 110 110">
              <circle class="circle-track" cx="55" cy="55" r="45" />
              <circle class="circle-progress lime" cx="55" cy="55" r="45" stroke-dasharray="282.7" stroke-dashoffset="98.9" />
              <text class="circle-text" x="55" y="55" text-anchor="middle" dominant-baseline="middle">65%</text>
            </svg>
            <span class="circle-metric-label">CRIM</span>
          </div>
        </div>
      </div>

      <div class="chart-panel">
        <div class="chart-header">
          <h3><i class="fa-solid fa-venus-mars"></i> Male vs Female Enrollment</h3>
          <div class="chart-legend">
            <span><span class="legend-dot" style="background: var(--blue-accent);"></span> Male</span>
            <span><span class="legend-dot" style="background: var(--pink-accent);"></span> Female</span>
          </div>
        </div>
        <div class="line-chart-container" style="height: 220px;">
          <svg class="line-chart-svg" viewBox="0 0 400 220" preserveAspectRatio="xMidYMid meet">
            <defs>
              <linearGradient id="maleGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.4" />
                <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
              </linearGradient>
              <linearGradient id="femaleGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#ec4899" stop-opacity="0.4" />
                <stop offset="100%" stop-color="#ec4899" stop-opacity="0" />
              </linearGradient>
            </defs>

            <line class="grid-line" x1="40" y1="30" x2="380" y2="30" />
            <line class="grid-line" x1="40" y1="70" x2="380" y2="70" />
            <line class="grid-line" x1="40" y1="110" x2="380" y2="110" />
            <line class="grid-line" x1="40" y1="150" x2="380" y2="150" />
            <line class="grid-line" x1="40" y1="190" x2="380" y2="190" />

            <text class="axis-label" x="10" y="34">300</text>
            <text class="axis-label" x="10" y="74">225</text>
            <text class="axis-label" x="10" y="114">150</text>
            <text class="axis-label" x="10" y="154">75</text>
            <text class="axis-label" x="18" y="194">0</text>

            <polygon class="chart-area-male" points="60,120 130,105 200,130 270,95 350,110 350,190 60,190" />
            <polygon class="chart-area-female" points="60,135 130,120 200,140 270,115 350,100 350,190 60,190" />

            <polyline class="chart-line-male" points="60,120 130,105 200,130 270,95 350,110" />
            <polyline class="chart-line-female" points="60,135 130,120 200,140 270,115 350,100" />

            <circle class="data-point-male" cx="60" cy="120" r="4" />
            <circle class="data-point-male" cx="130" cy="105" r="4" />
            <circle class="data-point-male" cx="200" cy="130" r="4" />
            <circle class="data-point-male" cx="270" cy="95" r="4" />
            <circle class="data-point-male" cx="350" cy="110" r="4" />

            <circle class="data-point-female" cx="60" cy="135" r="4" />
            <circle class="data-point-female" cx="130" cy="120" r="4" />
            <circle class="data-point-female" cx="200" cy="140" r="4" />
            <circle class="data-point-female" cx="270" cy="115" r="4" />
            <circle class="data-point-female" cx="350" cy="100" r="4" />

            <text class="axis-label" x="60" y="210" text-anchor="middle">2021-22</text>
            <text class="axis-label" x="130" y="210" text-anchor="middle">2022-23</text>
            <text class="axis-label" x="200" y="210" text-anchor="middle">2023-24</text>
            <text class="axis-label" x="270" y="210" text-anchor="middle">2024-25</text>
            <text class="axis-label" x="350" y="210" text-anchor="middle">2025-26</text>
          </svg>
        </div>
      </div>
    </div>

    <div class="messages-panel">
      <div class="messages-header">
        <h3><i class="fa-solid fa-envelope-open-text"></i> New Messages</h3>
        <span class="messages-badge"><i class="fa-solid fa-circle"></i> 4 Unread</span>
      </div>
      <div class="messages-list">
        <div class="message-item unread">
          <div class="message-avatar teal">MR</div>
          <div class="message-content">
            <div class="message-top">
              <span class="message-sender">Maria Reyes</span>
              <span class="message-time">2 min ago</span>
            </div>
            <div class="message-preview">
              <span class="message-subject">Enrollment</span>
              Good day! I would like to inquire about the requirements for BSCS enrollment...
            </div>
          </div>
          <div class="message-actions">
            <button class="message-action-btn reply" title="Reply"><i class="fa-solid fa-reply"></i></button>
            <button class="message-action-btn" title="Archive"><i class="fa-solid fa-box-archive"></i></button>
          </div>
        </div>

        <div class="message-item unread">
          <div class="message-avatar purple">JC</div>
          <div class="message-content">
            <div class="message-top">
              <span class="message-sender">John Cruz</span>
              <span class="message-time">18 min ago</span>
            </div>
            <div class="message-preview">
              <span class="message-subject">Documents</span>
              I have submitted my Form 137 and good moral certificate. Please confirm receipt...
            </div>
          </div>
          <div class="message-actions">
            <button class="message-action-btn reply" title="Reply"><i class="fa-solid fa-reply"></i></button>
            <button class="message-action-btn" title="Archive"><i class="fa-solid fa-box-archive"></i></button>
          </div>
        </div>

        <div class="message-item unread">
          <div class="message-avatar amber">AL</div>
          <div class="message-content">
            <div class="message-top">
              <span class="message-sender">Ana Lopez</span>
              <span class="message-time">1 hour ago</span>
            </div>
            <div class="message-preview">
              <span class="message-subject">Scholarship</span>
              May I ask if there are available scholarships for incoming BSED students this semester...
            </div>
          </div>
          <div class="message-actions">
            <button class="message-action-btn reply" title="Reply"><i class="fa-solid fa-reply"></i></button>
            <button class="message-action-btn" title="Archive"><i class="fa-solid fa-box-archive"></i></button>
          </div>
        </div>

        <div class="message-item unread">
          <div class="message-avatar blue">RS</div>
          <div class="message-content">
            <div class="message-top">
              <span class="message-sender">Robert Santos</span>
              <span class="message-time">3 hours ago</span>
            </div>
            <div class="message-preview">
              <span class="message-subject">Schedule</span>
              When will the entrance exam for BSTM applicants be scheduled? Thank you...
            </div>
          </div>
          <div class="message-actions">
            <button class="message-action-btn reply" title="Reply"><i class="fa-solid fa-reply"></i></button>
            <button class="message-action-btn" title="Archive"><i class="fa-solid fa-box-archive"></i></button>
          </div>
        </div>

        <div class="message-item">
          <div class="message-avatar pink">KD</div>
          <div class="message-content">
            <div class="message-top">
              <span class="message-sender">Kim Dela Cruz</span>
              <span class="message-time">Yesterday</span>
            </div>
            <div class="message-preview">
              <span class="message-subject">Tuition</span>
              Good day! I would like to ask about the tuition fee breakdown for BSA...
            </div>
          </div>
          <div class="message-actions">
            <button class="message-action-btn reply" title="Reply"><i class="fa-solid fa-reply"></i></button>
            <button class="message-action-btn" title="Archive"><i class="fa-solid fa-box-archive"></i></button>
          </div>
        </div>
      </div>
    </div>

    <section class="tables-container">
      <div class="table-card">
        <div class="table-header">
          <h2><i class="fa-solid fa-user-plus"></i> New Enrolled Students</h2>
          <a href="enrolled.php"><button class="btn"><i class="fa-solid fa-arrow-right"></i> View All</button></a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Academic Year</th>
                <th>Email</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($enrollments) > 0): ?>
                <?php foreach ($enrollments as $row):
                  $full = $full_records[$row['student_id']] ?? [];
                ?>
                  <tr data-record='<?php echo htmlspecialchars(json_encode($full), ENT_QUOTES, "UTF-8"); ?>'>
                    <td>#STU-<?php echo str_pad($row['student_id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                    <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                    <td>
                      <div class="action-group">
                        <button class="btn-action" onclick="openViewModal(this)"><i class="fa-solid fa-eye"></i> View</button>
                        <a href="edit.php?id=<?php echo $row['student_id']; ?>"><button class="btn-action"><i class="fa-solid fa-pen"></i> Edit</button></a>
                        <a href="delete.php?id=<?php echo $row['student_id']; ?>" onclick="return confirm('Delete this record?')"><button class="btn-action decline"><i class="fa-solid fa-trash"></i> Delete</button></a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align:center; color:#8892b0;">No enrolled students found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <div class="modal-overlay" id="viewEnrollmentModal">
    <div class="modal-container" style="max-width: 750px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-id-card"></i> Enrollment Details</h3>
        <button class="modal-close-btn" onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="viewModalBody"></div>
      <div class="modal-footer">
        <button class="btn primary" onclick="closeViewModal()">Close</button>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    /* ============================================
       SIDEBAR — hover expands, click pins
       - Hover in → expand
       - Hover out → collapse (only if NOT pinned)
       - Click inside → pin (stays open)
       - Click outside → unpin & collapse
       ============================================ */
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    let isPinned = false; // true = locked open until clicking outside
    let collapseTimer = null;

    function expandSidebar() {
      sidebar.classList.add('expanded');
      body.classList.add('sidebar-open');
    }

    function collapseSidebar() {
      // Only collapse if not pinned
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

    /* ---------- Hover behavior ---------- */
    sidebar.addEventListener('mouseenter', () => {
      if (collapseTimer) {
        clearTimeout(collapseTimer);
        collapseTimer = null;
      }
      expandSidebar();
    });

    sidebar.addEventListener('mouseleave', () => {
      // If pinned, do nothing
      if (isPinned) return;

      // If admin dropdown open, do nothing
      if (document.getElementById('adminActions').classList.contains('open')) return;

      collapseTimer = setTimeout(() => {
        collapseSidebar();
      }, 250);
    });

    /* ---------- Click-outside → unpin & collapse ---------- */
    document.addEventListener('click', function(e) {
      // If click is inside the sidebar, ignore (handled by inner elements)
      if (sidebar.contains(e.target)) return;

      // If pinned, unpin and collapse
      if (isPinned) {
        unpinSidebar();
      }
    });

    /* ============================================
       ADMIN PROFILE CLICK
       Pins the sidebar and toggles the action dropdown.
       ============================================ */
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
      const dayName = days[now.getDay()];
      const monthName = months[now.getMonth()];
      const date = now.getDate();
      const year = now.getFullYear();

      document.getElementById('liveClockDate').textContent = `${dayName}, ${monthName} ${date}, ${year}`;
    }

    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    /* ============================================
       VIEW MODAL
       ============================================ */
    function openViewModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;
      const sections = [{
          title: 'Personal Information',
          fields: [
            ['First Name', data.firstname],
            ['Middle Name', data.middlename],
            ['Last Name', data.lastname],
            ['Suffix', data.suffix || 'N/A'],
            ['Gender', data.gender],
            ['Birthday', data.birthday],
            ['Birthplace', data.birthplace],
            ['Citizenship', data.citizenship],
            ['Civil Status', data.civilstatus],
            ['Employment', data.employment]
          ]
        },
        {
          title: 'Family / Guardian',
          fields: [
            ["Mother's Name", data.mother],
            ["Mother's Phone", data.mphone_number],
            ["Father's Name", data.father],
            ["Father's Phone", data.fphone_number],
            ["Guardian's Name", data.guardian],
            ["Guardian's Phone", data.gphone_number]
          ]
        },
        {
          title: 'Academic Information',
          fields: [
            ['Course', data.course],
            ['Major', data.major],
            ['School Address', data.school_address],
            ['Academic Year', data.academic_year],
            ['Scholarship', data.scholarship]
          ]
        },
        {
          title: 'Contact & Address',
          fields: [
            ['Full Address', data.full_address],
            ['Mobile Number', data.mobile_number],
            ['Email', data.email]
          ]
        }
      ];
      let html = '';
      sections.forEach(sec => {
        html += `<div class="view-modal-section"><h4>${sec.title}</h4><div class="view-modal-grid">`;
        sec.fields.forEach(([label, value]) => {
          html += `<div class="view-modal-row"><span class="view-modal-label">${label}:</span><span class="view-modal-value">${(value !== null && value !== undefined && String(value).trim() !== '') ? value : 'N/A'}</span></div>`;
        });
        html += '</div></div>';
      });
      document.getElementById('viewModalBody').innerHTML = html;
      document.getElementById('viewEnrollmentModal').classList.add('active');
    }

    function closeViewModal() {
      document.getElementById('viewEnrollmentModal').classList.remove('active');
    }
    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewModal();
    });
  </script>
</body>

</html>