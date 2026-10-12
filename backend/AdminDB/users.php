<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
  header("Location: login.html");
  exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

$users_list = [];
$res = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
while ($row = $res->fetch_assoc()) {
  $users_list[] = $row;
}

// Handle delete
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $conn->query("DELETE FROM users WHERE user_id = $id");
  header("Location: users.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users | ICF Interworld Colleges Foundation Inc.</title>
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

    .sidebar.expanded {
      width: var(--sidebar-expanded);
    }

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

    .nav-links {
      list-style: none;
      padding: 8px 0;
      flex-shrink: 0;
    }

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

    .metric-card:nth-child(2)::before { background: linear-gradient(90deg, var(--success-green), #34d399); }
    .metric-card:nth-child(3)::before { background: linear-gradient(90deg, var(--warning-amber), #fbbf24); }
    .metric-card:nth-child(4)::before { background: linear-gradient(90deg, var(--purple-accent), #a78bfa); }

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

    .table-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .table-wrapper {
      overflow-x: auto;
      max-height: 640px;
      overflow-y: auto;
    }

    .table-wrapper::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-wrapper::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }

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

    /* Sortable header (matches enrolled.php) */
    th.sortable {
      cursor: pointer;
      user-select: none;
    }

    th.sortable:hover {
      color: var(--primary-neon);
    }

    th.sortable i {
      margin-left: 6px;
      font-size: 0.75rem;
      opacity: 0.6;
      transition: var(--transition-smooth);
    }

    th.sortable:hover i {
      opacity: 1;
    }

    th.no-sort {
      cursor: default;
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

    .search-wrap input,
    .section-search {
      padding: 10px 16px 10px 40px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 10px;
      color: var(--text-high-contrast);
      font-size: 0.85rem;
      outline: none;
      width: 260px;
      transition: var(--transition-smooth);
      font-family: inherit;
    }

    .search-wrap input:focus,
    .section-search:focus {
      border-color: var(--primary-neon);
      box-shadow: 0 0 0 3px rgba(13, 245, 227, 0.1);
    }

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
      font-family: inherit;
    }

    .btn-action:hover {
      background: #1e293b;
      color: var(--text-high-contrast);
      border-color: var(--primary-neon);
    }

    .btn-action.view {
      border-color: rgba(13, 245, 227, 0.5);
      color: var(--primary-neon);
    }

    .btn-action.view:hover {
      background: var(--primary-neon);
      color: #020c1b;
      box-shadow: var(--neon-glow);
    }

    .btn-action.delete {
      border-color: rgba(239, 68, 68, 0.5);
      color: #f87171;
    }

    .btn-action.delete:hover {
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

    .modal-overlay.active .modal-container { transform: translateY(0); }

    .modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border-teal);
      border-radius: 16px;
      width: 100%;
      max-width: 460px;
      padding: 26px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
      color: var(--text-high-contrast);
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-box { transform: translateY(0); }

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

    .modal-close-btn,
    .modal-close {
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      font-size: 1.2rem;
      cursor: pointer;
      transition: color 0.2s ease;
      line-height: 1;
    }

    .modal-close-btn:hover,
    .modal-close:hover { color: #ef4444; }

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

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .view-modal-section { margin-bottom: 22px; }

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
      word-break: break-word;
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

    .btn-secondary:hover {
      background: var(--bg-card-hover);
      color: var(--text-high-contrast);
    }

    .btn-danger {
      background: #ef4444;
      color: white;
      border: none;
      padding: 11px 22px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 600;
      font-family: inherit;
      transition: var(--transition-smooth);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-danger:hover {
      background: #dc2626;
      box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
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
      .view-modal-grid { grid-template-columns: 1fr; }
      .live-clock { min-width: 100%; }
      .clock-time { font-size: 1.2rem; }
      .sidebar-toggle { top: 20px; }
      .search-wrap input,
      .section-search { width: 100%; }
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
      <li class="active">
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
        <h1><i class="fa-solid fa-users"></i> User Table List</h1>
        <p>Manage all registered user accounts in the system. Click any column header to sort.</p>
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
          <span class="metric-label">Total Users</span>
          <div class="metric-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="metric-value" id="totalUsersCard"><?php echo count($users_list); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Active Accounts</span>
          <div class="metric-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="metric-value" id="activeUsersCard"><?php echo count($users_list); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Students</span>
          <div class="metric-icon"><i class="fa-solid fa-user-graduate"></i></div>
        </div>
        <div class="metric-value"><?php echo count($users_list); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">New This Month</span>
          <div class="metric-icon"><i class="fa-solid fa-user-plus"></i></div>
        </div>
        <div class="metric-value">12</div>
      </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-list-ul"></i> Registered Users</h2>
        <div class="table-controls">
          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="section-search" placeholder="Search username or name..." data-table="userTable" />
          </div>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="userTable">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable(0)">User ID <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(1)">Username <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(2)">Full Name <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(3)">Date Created <i class="fas fa-sort"></i></th>
              <th class="no-sort">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($users_list) > 0): ?>
              <?php foreach ($users_list as $u):
                $full_name = trim(($u['first_name'] ?? '') . ' ' . ($u['middle_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
              ?>
                <tr data-record='<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8"); ?>'>
                  <td>#USR-<?php echo str_pad($u['user_id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td class="username"><?php echo htmlspecialchars($u['username']); ?></td>
                  <td><?php echo htmlspecialchars($full_name); ?></td>
                  <td class="date-created">
                    <?php
                      echo !empty($u['date_created'])
                        ? htmlspecialchars(date('M d, Y', strtotime($u['date_created'])))
                        : '<span style="color:var(--text-muted-teal); font-style:italic;">N/A</span>';
                    ?>
                  </td>
                  <td>
                    <div class="action-group">
                      <button class="btn-action view" onclick="openViewModal(this)"><i class="fa-solid fa-eye"></i> View</button>
                      <button class="btn-action delete" onclick="openDeleteModal(<?php echo $u['user_id']; ?>, '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>')"><i class="fa-solid fa-user-slash"></i> Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" style="text-align:center; color:#8892b0; padding:40px;">No users found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- VIEW USER MODAL -->
  <div class="modal-overlay" id="viewUserModal">
    <div class="modal-container" style="max-width: 700px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-user"></i> User Details</h3>
        <button class="modal-close-btn" onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="viewModalBody"></div>
      <div class="modal-footer">
        <button class="btn primary" onclick="closeViewModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- TERMINATE MODAL -->
  <div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header" style="background:transparent; padding:0 0 14px; border-bottom:1px solid var(--border-teal); margin-bottom:18px;">
        <h3 style="color:var(--primary-neon); font-size:1.05rem; font-weight:600; display:flex; align-items:center; gap:10px;">
          <i class="fa-solid fa-triangle-exclamation"></i> Confirm Termination
        </h3>
        <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
      </div>
      <p style="color: var(--text-muted-teal); font-size: 0.88rem; margin-bottom: 20px; line-height: 1.6;">
        Are you sure you want to terminate account <strong id="deleteUserName" style="color:var(--primary-neon);"></strong>? This action cannot be undone.
      </p>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <a id="deleteLink" href="#"><button type="button" class="btn-danger"><i class="fa-solid fa-user-slash"></i> Terminate</button></a>
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
       SEARCH / FILTER
       ============================================ */
    document.querySelectorAll('.section-search').forEach(el => el.addEventListener('input', filterTables));

    function filterTables() {
      document.querySelectorAll('.section-search').forEach(search => {
        const tableId = search.getAttribute('data-table');
        const val = search.value.toLowerCase().trim();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
          const uname = row.querySelector('.username') ? row.querySelector('.username').textContent.toLowerCase() : '';
          const fullName = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
          const dateCreated = row.querySelector('.date-created') ? row.querySelector('.date-created').textContent.toLowerCase() : '';
          row.style.display = (uname.includes(val) || fullName.includes(val) || dateCreated.includes(val)) ? '' : 'none';
        });
      });
    }

    /* ============================================
       TABLE SORTING (matches enrolled.php method)
       ============================================ */
    let sortDirection = 1;

    function sortTable(columnIndex) {
      const table = document.getElementById("userTable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.rows);

      // Skip if there are no data rows (only "No users found")
      if (rows.length === 1 && rows[0].querySelector('td[colspan]')) return;

      sortDirection = sortDirection === 1 ? -1 : 1;

      rows.sort((a, b) => {
        let aText, bText;

        // Column 3 = Date Created → sort by actual date value
        // Column 0 = User ID → sort by numeric ID
        if (columnIndex === 3) {
          const aData = JSON.parse(a.getAttribute('data-record') || '{}');
          const bData = JSON.parse(b.getAttribute('data-record') || '{}');
          const aVal = aData.date_created ? new Date(aData.date_created).getTime() : 0;
          const bVal = bData.date_created ? new Date(bData.date_created).getTime() : 0;
          if (aVal < bVal) return -1 * sortDirection;
          if (aVal > bVal) return  1 * sortDirection;
          return 0;
        }

        if (columnIndex === 0) {
          const aNum = parseInt(a.cells[0].textContent.replace(/\D/g, ''), 10) || 0;
          const bNum = parseInt(b.cells[0].textContent.replace(/\D/g, ''), 10) || 0;
          if (aNum < bNum) return -1 * sortDirection;
          if (aNum > bNum) return  1 * sortDirection;
          return 0;
        }

        // Default: text sort
        aText = a.cells[columnIndex].textContent.trim().toLowerCase();
        bText = b.cells[columnIndex].textContent.trim().toLowerCase();
        if (aText < bText) return -1 * sortDirection;
        if (aText > bText) return  1 * sortDirection;
        return 0;
      });

      rows.forEach(row => tbody.appendChild(row));

      // Reset all header icons
      document.querySelectorAll('#userTable th.sortable i').forEach(i => i.className = 'fas fa-sort');
      // Mark active header
      const activeTh = table.querySelector(`th.sortable:nth-child(${columnIndex + 1}) i`);
      if (activeTh) activeTh.className = sortDirection === 1 ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }

    /* ============================================
       DELETE MODAL
       ============================================ */
    function openDeleteModal(id, name) {
      document.getElementById('deleteUserName').textContent = name;
      document.getElementById('deleteLink').href = 'users.php?delete=' + id;
      document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('active');
    }

    /* ============================================
       VIEW MODAL
       ============================================ */
    function openViewModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;

      const dateCreated = data.date_created
        ? new Date(data.date_created).toLocaleString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
          })
        : 'N/A';

      const sections = [{
        title: 'Account Information',
        fields: [
          ['User ID', '#USR-' + String(data.user_id).padStart(3, '0')],
          ['Username', data.username],
          ['First Name', data.first_name],
          ['Middle Name', data.middle_name],
          ['Last Name', data.last_name],
          ['Date Created', dateCreated]
        ]
      }];
      let html = '';
      sections.forEach(sec => {
        html += `<div class="view-modal-section"><h4>${sec.title}</h4><div class="view-modal-grid">`;
        sec.fields.forEach(([label, value]) => {
          const displayValue = (value !== null && value !== undefined && String(value).trim() !== '') ? value : 'N/A';
          html += `<div class="view-modal-row"><span class="view-modal-label">${label}:</span><span class="view-modal-value">${displayValue}</span></div>`;
        });
        html += '</div></div>';
      });
      document.getElementById('viewModalBody').innerHTML = html;
      document.getElementById('viewUserModal').classList.add('active');
    }

    function closeViewModal() {
      document.getElementById('viewUserModal').classList.remove('active');
    }

    /* Click outside modals */
    ['viewUserModal', 'deleteModal'].forEach(id => {
      document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
      });
    });
  </script>
</body>

</html>