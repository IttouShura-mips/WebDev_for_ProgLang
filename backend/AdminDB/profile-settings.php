<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);
$admin_user = htmlspecialchars($_SESSION['admin_user']);

// =====================================================
// FETCH CURRENT ADMIN DATA (if admin table exists)
// =====================================================
$admin_data = [
    'username'     => $admin_user,
    'display_name' => $admin_name,
    'email'        => '',
    'role'         => 'System Administrator',
    'date_created' => null,
];

try {
    // Check if admin table exists and has matching record
    $check = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $admin_user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $admin_data['username']     = $row['username']     ?? $admin_user;
            $admin_data['display_name'] = $row['display_name'] ?? $row['full_name'] ?? $row['name'] ?? $admin_name;
            $admin_data['email']        = $row['email']        ?? '';
            $admin_data['role']         = $row['role']         ?? 'System Administrator';
            $admin_data['date_created'] = $row['date_created'] ?? null;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Silently fall back to session values
}

// Initials for the avatar
$initials = strtoupper(substr($admin_name, 0, 1));
$createdDisplay = $admin_data['date_created']
    ? date('F j, Y', strtotime($admin_data['date_created']))
    : date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Settings | ICF Admin</title>
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
      left: 0; top: 0;
      z-index: 100;
      border-right: 1px solid var(--border-teal);
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5);
      overflow-x: visible;
      overflow-y: auto;
      transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.expanded { width: var(--sidebar-expanded); }

    .sidebar::-webkit-scrollbar { width: 6px; }
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
    }

    body.sidebar-open .sidebar-toggle { left: calc(var(--sidebar-expanded) + 8px); }

    .sidebar-toggle:hover {
      transform: scale(1.2);
      text-shadow: 0 0 10px rgba(13, 245, 227, 0.5);
    }

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

    .sidebar.expanded .nav-links li a .nav-label {
      opacity: 1;
      transform: translateX(0);
    }

    .nav-links li a:hover { background-color: #1e293b; color: var(--primary-neon); }
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

    .btn-sidebar i { width: 18px; font-size: 0.9rem; text-align: center; flex-shrink: 0; }

    .btn-sidebar:hover {
      background-color: #1e293b;
      color: var(--text-high-contrast);
      border-color: var(--primary-neon);
    }

    .btn-sidebar.profile-settings {
      color: var(--primary-neon);
      border-color: rgba(13, 245, 227, 0.5);
      background: rgba(13, 245, 227, 0.06);
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
       PROFILE HEADER CARD
       ============================================ */
    .profile-hero {
      background: linear-gradient(160deg, var(--bg-card) 0%, #06152b 100%);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 28px;
      margin-bottom: 24px;
      box-shadow: var(--card-shadow);
      display: flex;
      align-items: center;
      gap: 24px;
      flex-wrap: wrap;
      position: relative;
      overflow: hidden;
      transition: var(--transition-smooth);
    }

    .profile-hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--primary-neon), #00b4d8, var(--purple-accent));
    }

    .profile-hero:hover {
      border-color: rgba(13, 245, 227, 0.4);
      box-shadow: 0 12px 30px rgba(13, 245, 227, 0.1), var(--card-shadow);
    }

    .hero-avatar {
      width: 96px; height: 96px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-neon), #009f9a);
      color: #020c1b;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.4rem;
      font-weight: 800;
      box-shadow: 0 0 30px rgba(13, 245, 227, 0.4);
      flex-shrink: 0;
      transition: var(--transition-smooth);
    }

    .hero-avatar:hover {
      transform: scale(1.05) rotate(-4deg);
      box-shadow: 0 0 45px rgba(13, 245, 227, 0.6);
    }

    .hero-text { flex: 1; min-width: 220px; }

    .hero-text h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-high-contrast);
      letter-spacing: -0.3px;
      margin-bottom: 6px;
    }

    .hero-text .hero-user {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      color: var(--primary-neon);
      background: rgba(13, 245, 227, 0.08);
      border: 1px solid rgba(13, 245, 227, 0.25);
      padding: 4px 12px;
      border-radius: 20px;
      font-weight: 600;
      margin-bottom: 6px;
    }

    .hero-text .hero-meta {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      font-size: 0.78rem;
      color: var(--text-muted-teal);
      margin-top: 6px;
    }

    .hero-text .hero-meta span {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .hero-text .hero-meta i {
      color: var(--primary-neon);
      font-size: 0.72rem;
    }

    /* ============================================
       SETTINGS CARD
       ============================================ */
    .settings-card {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: var(--border-radius);
      padding: 26px 28px;
      box-shadow: var(--card-shadow);
      margin-bottom: 24px;
      transition: var(--transition-smooth);
      position: relative;
      overflow: hidden;
    }

    .settings-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg, var(--primary-neon), transparent);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .settings-card:hover::before { transform: scaleX(1); }

    .settings-card:hover {
      border-color: rgba(13, 245, 227, 0.35);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 25px rgba(13, 245, 227, 0.08);
    }

    .card-title {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 22px;
      padding-bottom: 14px;
      border-bottom: 1px solid var(--border-teal);
    }

    .card-title .icon-box {
      width: 42px;
      height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(13, 245, 227, 0.15), rgba(13, 245, 227, 0.05));
      border: 1px solid rgba(13, 245, 227, 0.25);
      border-radius: 10px;
      color: var(--primary-neon);
      font-size: 1rem;
      transition: var(--transition-smooth);
      flex-shrink: 0;
    }

    .settings-card:hover .card-title .icon-box {
      background: var(--primary-neon);
      color: var(--bg-deep-abyss);
      box-shadow: var(--neon-glow);
      transform: scale(1.05) rotate(-4deg);
    }

    .card-title h3 {
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--text-high-contrast);
      letter-spacing: -0.2px;
    }

    .card-title .sub {
      font-size: 0.75rem;
      color: var(--text-muted-teal);
      margin-top: 2px;
    }

    /* ============================================
       FORMS
       ============================================ */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 18px;
    }

    .form-group { display: flex; flex-direction: column; gap: 8px; }

    .form-group.full-width { grid-column: 1 / -1; }

    .form-group label {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: var(--text-muted-teal);
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .form-group label i {
      color: var(--primary-neon);
      font-size: 0.72rem;
    }

    .form-control {
      width: 100%;
      padding: 13px 16px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      color: var(--text-high-contrast);
      font-size: 0.87rem;
      font-family: inherit;
      outline: none;
      transition: var(--transition-smooth);
    }

    .form-control::placeholder { color: #4a5568; }

    .form-control:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 4px rgba(13, 245, 227, 0.08), 0 0 12px rgba(13, 245, 227, 0.2);
      background: rgba(2, 12, 27, 0.95);
    }

    .form-control:disabled {
      opacity: 0.55;
      cursor: not-allowed;
      background: rgba(2, 12, 27, 0.4);
    }

    /* Password field with toggle */
    .password-wrap { position: relative; }

    .password-wrap .toggle-pw {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      font-size: 0.85rem;
      cursor: pointer;
      padding: 6px;
      transition: var(--transition-smooth);
      line-height: 1;
    }

    .password-wrap .toggle-pw:hover { color: var(--primary-neon); }

    /* Strength meter */
    .strength-meter {
      display: flex;
      gap: 4px;
      margin-top: 4px;
      height: 4px;
    }

    .strength-bar {
      flex: 1;
      border-radius: 2px;
      background: var(--border-teal);
      transition: var(--transition-smooth);
    }

    .strength-bar.active.weak   { background: #ef4444; }
    .strength-bar.active.fair   { background: #f59e0b; }
    .strength-bar.active.good   { background: #3b82f6; }
    .strength-bar.active.strong { background: #10b981; }

    /* Info note */
    .info-note {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: rgba(13, 245, 227, 0.06);
      border: 1px solid rgba(13, 245, 227, 0.2);
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.78rem;
      color: var(--text-muted-teal);
      line-height: 1.6;
      margin-top: 6px;
    }

    .info-note i {
      color: var(--primary-neon);
      flex-shrink: 0;
      margin-top: 3px;
      font-size: 0.85rem;
    }

    /* Actions */
    .action-row {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .btn {
      padding: 11px 24px;
      border: 1px solid var(--border-teal);
      background: transparent;
      border-radius: 50px;
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
      letter-spacing: 0.3px;
    }

    .btn:hover {
      background: #1e293b;
      color: var(--text-high-contrast);
      border-color: var(--primary-neon);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
      color: #020c1b;
      border: none;
      box-shadow: 0 4px 15px rgba(13, 245, 227, 0.3);
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 0 25px rgba(13, 245, 227, 0.55), 0 8px 20px rgba(0, 0, 0, 0.4);
      background: linear-gradient(135deg, #0df5e3, #00cbb9);
    }

    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Danger Zone */
    .danger-zone {
      border: 1px solid rgba(239, 68, 68, 0.35);
      background: rgba(239, 68, 68, 0.05);
      border-radius: 12px;
      padding: 18px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      transition: var(--transition-smooth);
    }

    .danger-zone:hover {
      border-color: rgba(239, 68, 68, 0.6);
      background: rgba(239, 68, 68, 0.08);
    }

    .danger-zone h4 {
      font-size: 0.95rem;
      font-weight: 700;
      color: #f87171;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .danger-zone p {
      font-size: 0.8rem;
      color: var(--text-muted-teal);
      line-height: 1.55;
    }

    .btn-danger {
      background: transparent;
      border: 1px solid rgba(239, 68, 68, 0.5);
      color: #f87171;
      padding: 11px 22px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 0.82rem;
      cursor: pointer;
      transition: var(--transition-smooth);
      font-family: inherit;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      letter-spacing: 0.3px;
    }

    .btn-danger:hover {
      background: rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
      color: #fca5a5;
      box-shadow: 0 0 15px rgba(239, 68, 68, 0.35);
      transform: translateY(-2px);
    }

    /* ============================================
       ALERT MODAL
       ============================================ */
    .alert-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(2, 12, 27, 0.92);
      backdrop-filter: blur(10px);
      z-index: 2000;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .alert-overlay.active { display: flex; }

    .alert-box {
      background: var(--bg-card);
      border-radius: 16px;
      padding: 30px;
      max-width: 420px;
      width: 100%;
      text-align: center;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      border: 1px solid var(--border-teal);
      color: var(--text-high-contrast);
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .alert-overlay.active .alert-box { transform: translateY(0); }

    .alert-box.success {
      border-color: #10b981;
      box-shadow: 0 0 40px rgba(16, 185, 129, 0.3), 0 25px 60px rgba(0, 0, 0, 0.8);
    }

    .alert-box.warning {
      border-color: #fbbf24;
      box-shadow: 0 0 40px rgba(251, 191, 36, 0.3), 0 25px 60px rgba(0, 0, 0, 0.8);
    }

    .alert-icon {
      font-size: 3.2rem;
      margin-bottom: 16px;
      display: inline-block;
    }

    .alert-box.success .alert-icon { color: #10b981; text-shadow: 0 0 25px rgba(16, 185, 129, 0.5); }
    .alert-box.warning .alert-icon { color: #fbbf24; text-shadow: 0 0 25px rgba(251, 191, 36, 0.5); }

    .alert-title {
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--text-high-contrast);
      margin-bottom: 12px;
    }

    .alert-message {
      font-size: 0.88rem;
      color: var(--text-muted-teal);
      line-height: 1.7;
      margin-bottom: 22px;
      white-space: pre-line;
    }

    .alert-close-btn {
      padding: 11px 36px;
      border: none;
      border-radius: 50px;
      font-weight: 800;
      font-size: 0.88rem;
      cursor: pointer;
      font-family: inherit;
      transition: var(--transition-smooth);
      letter-spacing: 0.3px;
    }

    .alert-box.success .alert-close-btn { background: #10b981; color: #020c1b; }
    .alert-box.warning .alert-close-btn { background: #fbbf24; color: #020c1b; }

    .alert-close-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 1200px) {
      .top-header { grid-template-columns: 1fr; gap: 18px; }
    }

    @media (max-width: 768px) {
      .main-content { padding: 20px; }
      .live-clock { min-width: 100%; }
      .clock-time { font-size: 1.2rem; }
      .sidebar-toggle { top: 20px; }
      .profile-hero { padding: 22px 20px; gap: 18px; }
      .hero-avatar { width: 76px; height: 76px; font-size: 1.9rem; }
      .hero-text h2 { font-size: 1.2rem; }
      .settings-card { padding: 22px 20px; }
      .action-row { flex-direction: column-reverse; }
      .action-row .btn { width: 100%; justify-content: center; }
      .danger-zone { flex-direction: column; align-items: flex-start; }
      .danger-zone .btn-danger { width: 100%; justify-content: center; }
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
      <li>
        <a href="received-mail.php" onclick="pinSidebar(event)">
          <span class="nav-icon"><i class="fa-solid fa-envelope"></i></span>
          <span class="nav-label">Received Mail</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-footer">
      <div class="admin-profile-card active" id="adminProfileToggle" onclick="onAdminProfileClick(event)">
        <div class="admin-avatar"><?php echo $initials; ?></div>
        <div class="admin-info">
          <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
          <span class="admin-role"><i class="fa-solid fa-circle-check"></i> System Administrator</span>
        </div>
        <i class="fa-solid fa-chevron-up admin-toggle-icon" id="adminToggleIcon"></i>
      </div>

      <div class="sidebar-actions open" id="adminActions">
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
        <h1><i class="fa-solid fa-gear"></i> Profile Settings</h1>
        <p>Manage your admin account details and update your security credentials.</p>
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

    <!-- PROFILE HERO -->
    <div class="profile-hero">
      <div class="hero-avatar"><?php echo $initials; ?></div>
      <div class="hero-text">
        <h2><?php echo htmlspecialchars($admin_data['display_name']); ?></h2>
        <span class="hero-user">
          <i class="fa-solid fa-user-shield"></i>
          <?php echo htmlspecialchars($admin_data['username']); ?>
        </span>
        <div class="hero-meta">
          <span><i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($admin_data['role']); ?></span>
          <span><i class="fa-solid fa-calendar"></i> Member since <?php echo htmlspecialchars($createdDisplay); ?></span>
        </div>
      </div>
    </div>

    <!-- ===== PERSONAL INFORMATION CARD ===== -->
    <div class="settings-card">
      <div class="card-title">
        <div class="icon-box"><i class="fa-solid fa-user-pen"></i></div>
        <div>
          <h3>Personal Information</h3>
          <div class="sub">Update your display name, email address, and role.</div>
        </div>
      </div>

      <form id="personalForm" onsubmit="savePersonalInfo(event)">
        <div class="form-grid">
          <div class="form-group">
            <label for="displayName"><i class="fa-solid fa-signature"></i> Display Name</label>
            <input type="text" id="displayName" name="display_name" class="form-control"
                   value="<?php echo htmlspecialchars($admin_data['display_name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="adminUsername"><i class="fa-solid fa-id-badge"></i> Username</label>
            <input type="text" id="adminUsername" name="username" class="form-control"
                   value="<?php echo htmlspecialchars($admin_data['username']); ?>" disabled>
          </div>

          <div class="form-group">
            <label for="adminEmail"><i class="fa-solid fa-envelope"></i> Email Address</label>
            <input type="email" id="adminEmail" name="email" class="form-control"
                   value="<?php echo htmlspecialchars($admin_data['email']); ?>"
                   placeholder="admin@icfpaniqui.edu.ph">
          </div>

          <div class="form-group">
            <label for="adminRole"><i class="fa-solid fa-user-shield"></i> Role</label>
            <input type="text" id="adminRole" name="role" class="form-control"
                   value="<?php echo htmlspecialchars($admin_data['role']); ?>" disabled>
          </div>
        </div>

        <div class="info-note">
          <i class="fa-solid fa-circle-info"></i>
          <span>Your username and role are tied to system permissions and cannot be changed from this page. Contact the school IT administrator if changes are needed.</span>
        </div>

        <div class="action-row">
          <button type="reset" class="btn">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary" id="savePersonalBtn">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
          </button>
        </div>
      </form>
    </div>

    <!-- ===== SECURITY CARD ===== -->
    <div class="settings-card">
      <div class="card-title">
        <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <h3>Security &amp; Password</h3>
          <div class="sub">Change your password. Use a strong password to keep your admin account secure.</div>
        </div>
      </div>

      <form id="passwordForm" onsubmit="savePassword(event)">
        <div class="form-grid">
          <div class="form-group full-width">
            <label for="currentPassword"><i class="fa-solid fa-lock"></i> Current Password</label>
            <div class="password-wrap">
              <input type="password" id="currentPassword" name="current_password" class="form-control"
                     placeholder="Enter your current password" required>
              <button type="button" class="toggle-pw" onclick="togglePw('currentPassword', this)">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="newPassword"><i class="fa-solid fa-key"></i> New Password</label>
            <div class="password-wrap">
              <input type="password" id="newPassword" name="new_password" class="form-control"
                     placeholder="Enter new password" required oninput="updateStrength(this.value)">
              <button type="button" class="toggle-pw" onclick="togglePw('newPassword', this)">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
            <div class="strength-meter" id="strengthMeter">
              <div class="strength-bar" data-bar="1"></div>
              <div class="strength-bar" data-bar="2"></div>
              <div class="strength-bar" data-bar="3"></div>
              <div class="strength-bar" data-bar="4"></div>
            </div>
          </div>

          <div class="form-group">
            <label for="confirmPassword"><i class="fa-solid fa-key"></i> Confirm New Password</label>
            <div class="password-wrap">
              <input type="password" id="confirmPassword" name="confirm_password" class="form-control"
                     placeholder="Confirm new password" required>
              <button type="button" class="toggle-pw" onclick="togglePw('confirmPassword', this)">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="info-note">
          <i class="fa-solid fa-circle-info"></i>
          <span>Password must be at least 8 characters long and should include uppercase, lowercase, numbers, and special characters.</span>
        </div>

        <div class="action-row">
          <button type="reset" class="btn" onclick="resetStrength()">
            <i class="fa-solid fa-rotate-left"></i> Clear
          </button>
          <button type="submit" class="btn btn-primary" id="savePasswordBtn">
            <i class="fa-solid fa-shield-halved"></i> Update Password
          </button>
        </div>
      </form>
    </div>

    <!-- ===== DANGER ZONE CARD ===== -->
    <div class="settings-card">
      <div class="card-title">
        <div class="icon-box" style="background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.35); color: #f87171;">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
          <h3>Danger Zone</h3>
          <div class="sub">Irreversible actions that require caution.</div>
        </div>
      </div>

      <div class="danger-zone">
        <div>
          <h4><i class="fa-solid fa-right-from-bracket"></i> Sign Out All Sessions</h4>
          <p>End all active admin sessions on this account. You will need to log in again on any device.</p>
        </div>
        <button type="button" class="btn-danger" onclick="signOutAll()">
          <i class="fa-solid fa-power-off"></i> Sign Out Everywhere
        </button>
      </div>
    </div>
  </main>

  <!-- ===== ALERT MODAL ===== -->
  <div class="alert-overlay" id="alertModal">
    <div class="alert-box" id="alertBox">
      <div class="alert-icon" id="alertIcon"></div>
      <div class="alert-title" id="alertTitle"></div>
      <div class="alert-message" id="alertMessage"></div>
      <button class="alert-close-btn" onclick="closeAlert()">OK</button>
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

    /* Auto-open the sidebar footer on the settings page */
    document.addEventListener('DOMContentLoaded', () => {
      pinSidebar();
    });

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
       ALERT MODAL HELPERS
       ============================================ */
    function showAlert(type, title, message) {
      const alertModal = document.getElementById('alertModal');
      const alertBox   = document.getElementById('alertBox');
      const alertIcon  = document.getElementById('alertIcon');
      const alertTitle = document.getElementById('alertTitle');
      const alertMsg   = document.getElementById('alertMessage');

      alertBox.classList.remove('success', 'warning');
      if (type === 'success') {
        alertBox.classList.add('success');
        alertIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
      } else {
        alertBox.classList.add('warning');
        alertIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
      }

      alertTitle.textContent = title;
      alertMsg.textContent = message;
      alertModal.classList.add('active');
    }

    function closeAlert() {
      document.getElementById('alertModal').classList.remove('active');
    }

    /* Click outside to close alert */
    document.getElementById('alertModal').addEventListener('click', function(e) {
      if (e.target === this) closeAlert();
    });

    /* ============================================
       PASSWORD VISIBILITY TOGGLE
       ============================================ */
    function togglePw(inputId, btn) {
      const input = document.getElementById(inputId);
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.innerHTML = isPassword
        ? '<i class="fa-solid fa-eye-slash"></i>'
        : '<i class="fa-solid fa-eye"></i>';
    }

    /* ============================================
       PASSWORD STRENGTH METER
       ============================================ */
    function updateStrength(value) {
      const bars = document.querySelectorAll('#strengthMeter .strength-bar');
      let score = 0;

      if (value.length >= 8)  score++;
      if (value.length >= 12) score++;
      if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
      if (/\d/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value)) score++;

      if (score > 4) score = 4;

      bars.forEach((bar, idx) => {
        bar.classList.remove('active', 'weak', 'fair', 'good', 'strong');
        if (idx < score) {
          bar.classList.add('active');
          if (score === 1)      bar.classList.add('weak');
          else if (score === 2) bar.classList.add('fair');
          else if (score === 3) bar.classList.add('good');
          else if (score === 4) bar.classList.add('strong');
        }
      });
    }

    function resetStrength() {
      setTimeout(() => updateStrength(''), 0);
    }

    /* ============================================
       SAVE PERSONAL INFORMATION
       ============================================ */
    function savePersonalInfo(e) {
      e.preventDefault();

      const displayName = document.getElementById('displayName').value.trim();
      const email       = document.getElementById('adminEmail').value.trim();

      if (!displayName) {
        showAlert('warning', 'Missing Field', 'Display name cannot be empty.');
        return;
      }

      const btn = document.getElementById('savePersonalBtn');
      const originalHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

      // Placeholder — connect to backend endpoint when ready
      // e.g. fetch('backend/AdminDB/update_profile.php', { method:'POST', body: new FormData(document.getElementById('personalForm')) })

      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        showAlert('success', 'Profile Updated',
          'Your profile information has been saved successfully.' +
          (email ? '\n\nContact email: ' + email : ''));
      }, 700);
    }

    /* ============================================
       SAVE PASSWORD
       ============================================ */
    function savePassword(e) {
      e.preventDefault();

      const current = document.getElementById('currentPassword').value.trim();
      const newPw   = document.getElementById('newPassword').value;
      const confirm = document.getElementById('confirmPassword').value;

      if (!current) {
        showAlert('warning', 'Missing Field', 'Please enter your current password.');
        return;
      }
      if (newPw.length < 8) {
        showAlert('warning', 'Weak Password', 'Your new password must be at least 8 characters long.');
        return;
      }
      if (newPw !== confirm) {
        showAlert('warning', 'Passwords Do Not Match', 'The new password and confirmation do not match.');
        return;
      }

      const btn = document.getElementById('savePasswordBtn');
      const originalHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';

      // Placeholder — connect to backend endpoint when ready
      // e.g. fetch('backend/AdminDB/update_password.php', { method:'POST', body: new FormData(document.getElementById('passwordForm')) })

      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        document.getElementById('passwordForm').reset();
        updateStrength('');
        showAlert('success', 'Password Updated',
          'Your admin password has been changed successfully.\nUse your new password the next time you log in.');
      }, 800);
    }

    /* ============================================
       SIGN OUT ALL SESSIONS
       ============================================ */
    function signOutAll() {
      if (confirm('Sign out from all admin devices? You will need to log in again.')) {
        // When a backend route exists: window.location.href = 'logout_all.php';
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>