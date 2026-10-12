<?php
  session_start();
  if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
  }
  require 'db.php';

  $admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

  $fields = ['firstname', 'middlename', 'lastname', 'suffix', 'gender', 'birthday', 'birthplace', 'citizenship', 'civilstatus', 'employment', 'mother', 'mphone_number', 'father', 'fphone_number', 'guardian', 'gphone_number', 'course', 'major', 'school_address', 'academic_year', 'scholarship', 'full_address', 'mobile_number', 'email'];

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $types = str_repeat('s', count($fields));
    $sql = "INSERT INTO enrolled (" . implode(',', $fields) . ") VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $values = [];
    foreach ($fields as $f) {
      $values[] = $_POST[$f] ?? '';
    }
    $stmt->bind_param($types, ...$values);
    if ($stmt->execute()) {
      header("Location: adminpanel.php");
      exit();
    } else {
      $error = "Error: " . $stmt->error;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Student | ICF Interworld Colleges Foundation Inc.</title>
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

    /* -------- Toggle Button (outside) -------- */
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
      cursor: pointer;
      transition: var(--transition-smooth);
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
       TOP HEADER — Title (left) + Live Clock (center)
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

    /* ---- LIVE CLOCK ---- */
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
       FORM PAGE
       ============================================ */
    .form-page {
      max-width: 1100px;
      margin: 0 auto;
    }

    /* Error banner */
    .error-banner {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 18px;
      background: rgba(239, 68, 68, 0.08);
      border: 1px solid rgba(239, 68, 68, 0.3);
      border-radius: var(--border-radius);
      color: #f87171;
      font-size: 0.88rem;
      margin-bottom: 20px;
      animation: fadeIn 0.4s ease;
    }

    .error-banner i {
      font-size: 1rem;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Progress Steps */
    .form-steps {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
      padding: 16px 22px;
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      box-shadow: var(--card-shadow);
      overflow-x: auto;
    }

    .form-step {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .form-step-num {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(13, 245, 227, 0.08);
      border: 1px solid rgba(13, 245, 227, 0.3);
      color: var(--primary-neon);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    .form-step-label {
      font-size: 0.78rem;
      color: var(--text-muted-teal);
      font-weight: 600;
      white-space: nowrap;
    }

    .form-step-divider {
      flex: 1;
      height: 1px;
      background: var(--border-teal);
      min-width: 20px;
    }

    /* ============================================
       FIELDSET / FORM SECTIONS
       ============================================ */
    form {
      display: block;
    }

    fieldset {
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 22px 24px 24px;
      margin-bottom: 20px;
      background: var(--bg-card);
      box-shadow: var(--card-shadow);
      transition: var(--transition-smooth);
      position: relative;
    }

    fieldset:hover {
      border-color: #1f3a5f;
    }

    legend {
      padding: 0 12px;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--primary-neon);
      text-transform: uppercase;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    legend i {
      font-size: 0.9rem;
    }

    /* Form grid */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group.full {
      grid-column: 1 / -1;
    }

    .form-group label {
      font-size: 0.74rem;
      color: var(--text-muted-teal);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .form-group label .required {
      color: var(--primary-neon);
      font-size: 0.8rem;
      line-height: 1;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
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
    .form-group select:focus,
    .form-group textarea:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1);
      background-color: rgba(2, 12, 27, 0.6);
    }

    .form-group select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238892b0' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 38px;
    }

    .form-group select option {
      background-color: var(--bg-card);
      color: var(--text-high-contrast);
    }

    /* ============================================
       FORM ACTIONS
       ============================================ */
    .form-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      padding: 18px 0 4px;
      flex-wrap: wrap;
    }

    .btn {
      padding: 11px 24px;
      border: 1px solid var(--border-teal);
      background: transparent;
      border-radius: 10px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 600;
      transition: var(--transition-smooth);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted-teal);
      font-family: inherit;
      letter-spacing: 0.2px;
    }

    .btn:hover {
      background: #1e293b;
      color: var(--text-high-contrast);
      border-color: #2d4a6b;
      transform: translateY(-1px);
    }

    .btn.primary {
      background: linear-gradient(135deg, var(--primary-neon) 0%, #00b4a4 100%);
      color: #020c1b;
      border: none;
      font-weight: 700;
      box-shadow: var(--neon-glow);
    }

    .btn.primary:hover {
      background: linear-gradient(135deg, var(--primary-neon-hover) 0%, #009f9a 100%);
      box-shadow: 0 0 25px rgba(13, 245, 227, 0.5);
      transform: translateY(-2px);
    }

    .btn.secondary {
      color: var(--text-muted-teal);
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 1200px) {
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

      .form-grid {
        grid-template-columns: 1fr;
      }

      .form-actions {
        flex-direction: column-reverse;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }

      .form-steps {
        padding: 12px 14px;
      }

      .form-step-label {
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
      <li class="active">
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
        <h1><i class="fa-solid fa-user-plus"></i> Add New Student</h1>
        <p>Fill in the details below to register a new student record.</p>
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

    <div class="form-page">
      <?php if (isset($error)): ?>
        <div class="error-banner">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <!-- Progress Steps -->
      <div class="form-steps">
        <div class="form-step">
          <span class="form-step-num">1</span>
          <span class="form-step-label">Personal</span>
        </div>
        <div class="form-step-divider"></div>
        <div class="form-step">
          <span class="form-step-num">2</span>
          <span class="form-step-label">Family</span>
        </div>
        <div class="form-step-divider"></div>
        <div class="form-step">
          <span class="form-step-num">3</span>
          <span class="form-step-label">Academic</span>
        </div>
        <div class="form-step-divider"></div>
        <div class="form-step">
          <span class="form-step-num">4</span>
          <span class="form-step-label">Contact</span>
        </div>
      </div>

      <form method="POST">
        <fieldset>
          <legend><i class="fa-solid fa-user"></i> Personal Information</legend>
          <div class="form-grid">
            <div class="form-group">
              <label>First Name <span class="required">*</span></label>
              <input type="text" name="firstname" placeholder="Enter first name" required>
            </div>
            <div class="form-group">
              <label>Middle Name <span class="required">*</span></label>
              <input type="text" name="middlename" placeholder="Enter middle name" required>
            </div>
            <div class="form-group">
              <label>Last Name <span class="required">*</span></label>
              <input type="text" name="lastname" placeholder="Enter last name" required>
            </div>
            <div class="form-group">
              <label>Suffix</label>
              <input type="text" name="suffix" placeholder="e.g. Jr., Sr., III">
            </div>
            <div class="form-group">
              <label>Gender <span class="required">*</span></label>
              <select name="gender" required>
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="form-group">
              <label>Birthday <span class="required">*</span></label>
              <input type="date" name="birthday" required>
            </div>
            <div class="form-group">
              <label>Birthplace <span class="required">*</span></label>
              <input type="text" name="birthplace" placeholder="City / Municipality" required>
            </div>
            <div class="form-group">
              <label>Citizenship <span class="required">*</span></label>
              <input type="text" name="citizenship" placeholder="e.g. Filipino" required>
            </div>
            <div class="form-group">
              <label>Civil Status <span class="required">*</span></label>
              <input type="text" name="civilstatus" placeholder="e.g. Single" required>
            </div>
            <div class="form-group">
              <label>Employment <span class="required">*</span></label>
              <input type="text" name="employment" placeholder="e.g. Student / Employed" required>
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend><i class="fa-solid fa-people-roof"></i> Family / Guardian</legend>
          <div class="form-grid">
            <div class="form-group">
              <label>Mother's Name <span class="required">*</span></label>
              <input type="text" name="mother" placeholder="Full name" required>
            </div>
            <div class="form-group">
              <label>Mother's Phone <span class="required">*</span></label>
              <input type="text" name="mphone_number" placeholder="Contact number" required>
            </div>
            <div class="form-group">
              <label>Father's Name <span class="required">*</span></label>
              <input type="text" name="father" placeholder="Full name" required>
            </div>
            <div class="form-group">
              <label>Father's Phone <span class="required">*</span></label>
              <input type="text" name="fphone_number" placeholder="Contact number" required>
            </div>
            <div class="form-group">
              <label>Guardian's Name <span class="required">*</span></label>
              <input type="text" name="guardian" placeholder="Full name" required>
            </div>
            <div class="form-group">
              <label>Guardian's Phone <span class="required">*</span></label>
              <input type="text" name="gphone_number" placeholder="Contact number" required>
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend><i class="fa-solid fa-graduation-cap"></i> Academic Information</legend>
          <div class="form-grid">
            <div class="form-group">
              <label>Course <span class="required">*</span></label>
              <input type="text" name="course" placeholder="e.g. BSCS" required>
            </div>
            <div class="form-group">
              <label>Major <span class="required">*</span></label>
              <input type="text" name="major" placeholder="e.g. Software Engineering" required>
            </div>
            <div class="form-group full">
              <label>School Address <span class="required">*</span></label>
              <input type="text" name="school_address" placeholder="Full school address" required>
            </div>
            <div class="form-group">
              <label>Academic Year <span class="required">*</span></label>
              <input type="text" name="academic_year" placeholder="e.g. 2026-2027" required>
            </div>
            <div class="form-group">
              <label>Scholarship <span class="required">*</span></label>
              <input type="text" name="scholarship" placeholder="e.g. None / Full Scholar" required>
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend><i class="fa-solid fa-address-book"></i> Contact &amp; Address</legend>
          <div class="form-grid">
            <div class="form-group full">
              <label>Full Address <span class="required">*</span></label>
              <input type="text" name="full_address" placeholder="House No., Street, Barangay, City" required>
            </div>
            <div class="form-group">
              <label>Mobile Number <span class="required">*</span></label>
              <input type="text" name="mobile_number" placeholder="e.g. 09XX XXX XXXX" required>
            </div>
            <div class="form-group">
              <label>Email <span class="required">*</span></label>
              <input type="email" name="email" placeholder="student@example.com" required>
            </div>
          </div>
        </fieldset>

        <div class="form-actions">
          <a href="adminpanel.php" class="btn secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
          <button type="submit" class="btn primary"><i class="fa-solid fa-floppy-disk"></i> Save Record</button>
        </div>
      </form>
    </div>
  </main>

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
      if (collapseTimer) {
        clearTimeout(collapseTimer);
        collapseTimer = null;
      }
      expandSidebar();
    });

    sidebar.addEventListener('mouseleave', () => {
      if (isPinned) return;
      if (document.getElementById('adminActions').classList.contains('open')) return;

      collapseTimer = setTimeout(() => {
        collapseSidebar();
      }, 250);
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
      const dayName = days[now.getDay()];
      const monthName = months[now.getMonth()];
      const date = now.getDate();
      const year = now.getFullYear();

      document.getElementById('liveClockDate').textContent = `${dayName}, ${monthName} ${date}, ${year}`;
    }

    updateLiveClock();
    setInterval(updateLiveClock, 1000);
  </script>
</body>

</html>