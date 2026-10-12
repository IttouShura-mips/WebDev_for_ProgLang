<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['student_name']) || !isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// =====================================================
// DATABASE CONNECTION
// =====================================================
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

// =====================================================
// FETCH LOGGED-IN STUDENT
// =====================================================
$username = $_SESSION['username'];

try {
    $stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, username FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        session_destroy();
        header("Location: login.html");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// =====================================================
// DISPLAY VALUES
// =====================================================
$first_name    = htmlspecialchars($student['first_name']);
$middle_name   = htmlspecialchars($student['middle_name']);
$last_name     = htmlspecialchars($student['last_name']);
$full_name     = htmlspecialchars(trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']));
$username_disp = htmlspecialchars($student['username']);
$user_id       = htmlspecialchars($student['user_id']);
$initial       = strtoupper(substr($student['first_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | ICF Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            --purple-accent: #8b5cf6;
            --border-radius: 12px;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --transition-slow: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            --neon-glow: 0 0 15px rgba(13, 245, 227, 0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at 20% 15%, rgba(13, 245, 227, 0.06) 0%, transparent 45%),
                radial-gradient(circle at 80% 85%, rgba(139, 92, 246, 0.05) 0%, transparent 50%),
                linear-gradient(135deg, #071f30 0%, #020c1b 100%);
            color: var(--text-high-contrast);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(13, 245, 227, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(13, 245, 227, 0.025) 1px, transparent 1px);
            background-size: 42px 42px;
            pointer-events: none;
            mask-image: radial-gradient(circle at center, black 20%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle at center, black 20%, transparent 80%);
            z-index: 0;
        }

        /* ============================================
           NAVBAR
           ============================================ */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10, 25, 47, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-teal);
            padding: 14px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            transition: var(--transition-smooth);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
        }

        .navbar-brand .logo-box {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 245, 227, 0.06);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 12px;
            padding: 6px;
            filter: drop-shadow(0 0 10px rgba(13, 245, 227, 0.25));
            transition: var(--transition-smooth);
        }

        .navbar-brand:hover .logo-box {
            transform: scale(1.05);
            filter: drop-shadow(0 0 16px rgba(13, 245, 227, 0.5));
        }

        .navbar-brand .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .navbar-brand .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .navbar-brand .brand-text h1 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary-neon);
            letter-spacing: 0.5px;
        }

        .navbar-brand .brand-text span {
            font-size: 0.62rem;
            font-weight: 600;
            color: var(--text-muted-teal);
            letter-spacing: 1.4px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-back-profile {
            background: transparent;
            border: 1px solid rgba(13, 245, 227, 0.4);
            color: var(--primary-neon);
            padding: 8px 18px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
            cursor: pointer;
        }

        .btn-back-profile:hover {
            background: rgba(13, 245, 227, 0.12);
            border-color: var(--primary-neon);
            box-shadow: 0 0 15px rgba(13, 245, 227, 0.35);
            transform: translateY(-2px);
        }

        .user-pill {
            background: rgba(2, 12, 27, 0.8);
            border: 1px solid var(--border-teal);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            color: var(--text-muted-teal);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: var(--transition-smooth);
            text-decoration: none;
            cursor: pointer;
        }

        .user-pill:hover {
            border-color: rgba(13, 245, 227, 0.6);
            color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.08);
            box-shadow: 0 0 15px rgba(13, 245, 227, 0.25);
            transform: translateY(-2px);
        }

        .user-pill i {
            color: var(--primary-neon);
            font-size: 0.75rem;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(255, 107, 107, 0.5);
            color: #ff6b6b;
            padding: 8px 18px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: rgba(255, 107, 107, 0.12);
            border-color: #ff6b6b;
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.35);
            transform: translateY(-2px);
        }

        /* ============================================
           HERO BANNER
           ============================================ */
        .hero-banner {
            padding: 3.5rem 5% 3rem;
            text-align: center;
            position: relative;
            z-index: 1;
            background: linear-gradient(180deg, rgba(13, 245, 227, 0.04) 0%, transparent 100%);
            border-bottom: 1px solid var(--border-teal);
        }

        .hero-banner .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.08);
            border: 1px solid rgba(13, 245, 227, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 14px;
            box-shadow: 0 0 15px rgba(13, 245, 227, 0.1);
        }

        .hero-banner h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-high-contrast);
            letter-spacing: -0.5px;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .hero-banner h1 .accent {
            background: linear-gradient(135deg, #0df5e3 30%, #00a89d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-banner p {
            font-size: 0.92rem;
            color: var(--text-muted-teal);
            max-width: 620px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            flex: 1;
            padding: 2.5rem 5% 3rem;
            position: relative;
            z-index: 1;
        }

        .settings-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ============================================
           SETTINGS CARD
           ============================================ */
        .settings-card {
            background: linear-gradient(160deg, var(--bg-card) 0%, #06152b 100%);
            border: 1px solid var(--border-teal);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            transition: var(--transition-smooth);
            margin-bottom: 2rem;
        }

        .settings-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-neon), #00b4d8, var(--purple-accent));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .settings-card:hover::before { transform: scaleX(1); }

        .settings-card:hover {
            border-color: rgba(13, 245, 227, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6), 0 0 40px rgba(13, 245, 227, 0.12);
        }

        /* Section title */
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-teal);
        }

        .section-title .icon-box {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(13, 245, 227, 0.15), rgba(13, 245, 227, 0.05));
            border: 1px solid rgba(13, 245, 227, 0.25);
            border-radius: 12px;
            color: var(--primary-neon);
            font-size: 1.05rem;
            transition: var(--transition-smooth);
            flex-shrink: 0;
        }

        .section-title:hover .icon-box {
            background: var(--primary-neon);
            color: var(--bg-deep-abyss);
            box-shadow: var(--neon-glow);
            transform: scale(1.05) rotate(-4deg);
        }

        .section-title h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-high-contrast);
            letter-spacing: -0.2px;
        }

        .section-title .section-desc {
            display: block;
            font-size: 0.78rem;
            color: var(--text-muted-teal);
            font-weight: 500;
            margin-top: 3px;
        }

        /* Profile header */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 14px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-neon), #00b4a4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--bg-deep-abyss);
            box-shadow: 0 0 30px rgba(13, 245, 227, 0.4);
            flex-shrink: 0;
            transition: var(--transition-smooth);
        }

        .profile-avatar:hover {
            transform: scale(1.05) rotate(-4deg);
            box-shadow: 0 0 45px rgba(13, 245, 227, 0.6);
        }

        .profile-header-text {
            flex: 1;
            min-width: 220px;
        }

        .profile-header-text h3 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-high-contrast);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }

        .profile-header-text .username-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.08);
            border: 1px solid rgba(13, 245, 227, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .profile-header-text .username-badge i {
            font-size: 0.68rem;
        }

        /* Form grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width { grid-column: 1 / -1; }

        .form-group label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 1.2px;
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
            background: rgba(2, 12, 27, 0.8);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            color: var(--text-high-contrast);
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            transition: var(--transition-smooth);
        }

        .form-control::placeholder {
            color: #4a5568;
        }

        .form-control:focus {
            border-color: var(--primary-neon);
            box-shadow: 0 0 0 4px rgba(13, 245, 227, 0.08), 0 0 12px rgba(13, 245, 227, 0.2);
            background: rgba(2, 12, 27, 0.95);
        }

        .form-control:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        /* Password field wrapper */
        .password-wrap {
            position: relative;
        }

        .password-wrap .toggle-pw {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-muted-teal);
            font-size: 0.85rem;
            cursor: pointer;
            padding: 6px;
            transition: var(--transition-smooth);
        }

        .password-wrap .toggle-pw:hover {
            color: var(--primary-neon);
        }

        /* Password strength meter */
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

        /* Action buttons */
        .action-row {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 26px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-decoration: none;
            border: none;
            font-family: inherit;
            letter-spacing: 0.3px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            box-shadow: 0 4px 15px rgba(13, 245, 227, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(13, 245, 227, 0.55), 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-teal);
            color: var(--text-muted-teal);
        }

        .btn-outline:hover {
            border-color: var(--primary-neon);
            color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.06);
        }

        /* Info note */
        .info-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(13, 245, 227, 0.06);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.78rem;
            color: var(--text-muted-teal);
            line-height: 1.6;
            margin-top: 1rem;
        }

        .info-note i {
            color: var(--primary-neon);
            flex-shrink: 0;
            margin-top: 2px;
            font-size: 0.85rem;
        }

        /* Danger zone */
        .danger-zone {
            border: 1px solid rgba(255, 107, 107, 0.3);
            background: rgba(255, 107, 107, 0.04);
            border-radius: 14px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .danger-zone h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ff6b6b;
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
            border: 1px solid rgba(255, 107, 107, 0.5);
            color: #ff6b6b;
            padding: 11px 24px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-danger:hover {
            background: rgba(255, 107, 107, 0.12);
            border-color: #ff6b6b;
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.35);
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
            -webkit-backdrop-filter: blur(10px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .alert-overlay.active { display: flex; }

        .alert-box {
            background: linear-gradient(180deg, var(--bg-card) 0%, #06152b 100%);
            border-radius: 20px;
            padding: 2rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
            position: relative;
            animation: modalFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid var(--border-teal);
            color: var(--text-high-contrast);
        }

        .alert-box.success {
            border-color: #10b981;
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.3), 0 25px 60px rgba(0, 0, 0, 0.8);
        }

        .alert-box.warning {
            border-color: #fbbf24;
            box-shadow: 0 0 40px rgba(251, 191, 36, 0.3), 0 25px 60px rgba(0, 0, 0, 0.8);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .alert-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .alert-box.success .alert-icon { color: #10b981; text-shadow: 0 0 25px rgba(16, 185, 129, 0.5); }
        .alert-box.warning .alert-icon { color: #fbbf24; text-shadow: 0 0 25px rgba(251, 191, 36, 0.5); }

        .alert-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-high-contrast);
            margin-bottom: 0.75rem;
        }

        .alert-message {
            font-size: 0.88rem;
            color: var(--text-muted-teal);
            line-height: 1.7;
            margin-bottom: 1.5rem;
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

        .alert-box.success .alert-close-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: var(--bg-deep-abyss);
        }

        .alert-box.warning .alert-close-btn {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: var(--bg-deep-abyss);
        }

        .alert-close-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 820px) {
            .form-grid { grid-template-columns: 1fr; }
            .action-row { justify-content: stretch; }
            .action-row .btn { flex: 1; }
            .danger-zone { flex-direction: column; align-items: flex-start; }
        }

        @media (max-width: 700px) {
            .navbar { padding: 12px 4%; }
            .hero-banner { padding: 2.5rem 4% 2rem; }
            .hero-banner h1 { font-size: 1.5rem; }
            .settings-card { padding: 1.75rem 1.25rem; }
            .profile-header { padding: 1.25rem; }
            .profile-avatar { width: 70px; height: 70px; font-size: 1.75rem; }
        }

        @media (max-width: 480px) {
            .navbar-brand .brand-text h1 { font-size: 0.95rem; }
            .navbar-brand .brand-text span { font-size: 0.55rem; letter-spacing: 1px; }
            .navbar-brand .logo-box { width: 40px; height: 40px; }
            .user-pill { font-size: 0.72rem; padding: 6px 12px; }
            .btn-logout { font-size: 0.72rem; padding: 6px 14px; }
            .btn-back-profile { font-size: 0.72rem; padding: 6px 14px; }
            .hero-banner h1 { font-size: 1.25rem; }
            .settings-card { padding: 1.25rem 1rem; }
            .section-title h2 { font-size: 1rem; }
            .profile-header-text h3 { font-size: 1.1rem; }
            .action-row .btn { width: 100%; }
        }
    </style>
</head>

<body>

    <!-- ============================================
         NAVBAR
         ============================================ -->
    <nav class="navbar">
        <a href="profile.php" class="navbar-brand">
            <div class="logo-box">
                <img src="../../BackGroundimage/ICFLogo.png" alt="ICF Logo">
            </div>
            <div class="brand-text">
                <h1>ICF Student Portal</h1>
                <span>Interworld Colleges Foundation Inc.</span>
            </div>
        </a>
        <div class="navbar-actions">
            <a href="profile.php" class="btn-back-profile">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- ============================================
         HERO BANNER
         ============================================ -->
    <section class="hero-banner">
        <div class="eyebrow"><i class="fas fa-cog"></i> Account Settings</div>
        <h1>Profile <span class="accent">Settings</span></h1>
        <p>Manage your personal information, update your account password, and control your account preferences.</p>
    </section>

    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <main class="main-content">
        <div class="settings-wrapper">

            <!-- ===== PROFILE CARD ===== -->
            <div class="settings-card">

                <!-- Profile header -->
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo $initial; ?></div>
                    <div class="profile-header-text">
                        <h3><?php echo $full_name; ?></h3>
                        <span class="username-badge">
                            <i class="fas fa-user"></i>
                            <?php echo $username_disp; ?>
                        </span>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="section-title">
                    <div class="icon-box"><i class="fas fa-user-edit"></i></div>
                    <div>
                        <h2>Personal Information</h2>
                        <span class="section-desc">Update your personal details. Changes are saved to your account.</span>
                    </div>
                </div>

                <form id="personalForm" onsubmit="savePersonalInfo(event)">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="firstName"><i class="fas fa-signature"></i> First Name</label>
                            <input type="text" id="firstName" name="first_name" class="form-control"
                                   value="<?php echo $first_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middleName"><i class="fas fa-signature"></i> Middle Name</label>
                            <input type="text" id="middleName" name="middle_name" class="form-control"
                                   value="<?php echo $middle_name; ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastName"><i class="fas fa-signature"></i> Last Name</label>
                            <input type="text" id="lastName" name="last_name" class="form-control"
                                   value="<?php echo $last_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="usernameField"><i class="fas fa-id-badge"></i> Username</label>
                            <input type="text" id="usernameField" name="username" class="form-control"
                                   value="<?php echo $username_disp; ?>" disabled>
                        </div>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Your username is tied to your enrollment records and cannot be changed here. Contact the registrar if you need to update it.</span>
                    </div>

                    <div class="action-row">
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary" id="savePersonalBtn">
                            <i class="fas fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===== SECURITY CARD ===== -->
            <div class="settings-card">
                <div class="section-title">
                    <div class="icon-box"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <h2>Security &amp; Password</h2>
                        <span class="section-desc">Change your account password. Use a strong password to keep your account secure.</span>
                    </div>
                </div>

                <form id="passwordForm" onsubmit="savePassword(event)">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="currentPassword"><i class="fas fa-lock"></i> Current Password</label>
                            <div class="password-wrap">
                                <input type="password" id="currentPassword" name="current_password"
                                       class="form-control" placeholder="Enter your current password" required>
                                <button type="button" class="toggle-pw" onclick="togglePw('currentPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="newPassword"><i class="fas fa-key"></i> New Password</label>
                            <div class="password-wrap">
                                <input type="password" id="newPassword" name="new_password"
                                       class="form-control" placeholder="Enter new password" required
                                       oninput="updateStrength(this.value)">
                                <button type="button" class="toggle-pw" onclick="togglePw('newPassword', this)">
                                    <i class="fas fa-eye"></i>
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
                            <label for="confirmPassword"><i class="fas fa-key"></i> Confirm New Password</label>
                            <div class="password-wrap">
                                <input type="password" id="confirmPassword" name="confirm_password"
                                       class="form-control" placeholder="Confirm new password" required>
                                <button type="button" class="toggle-pw" onclick="togglePw('confirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Password must be at least 8 characters long and should include uppercase, lowercase, numbers, and special characters.</span>
                    </div>

                    <div class="action-row">
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-rotate-left"></i> Clear
                        </button>
                        <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                            <i class="fas fa-shield-halved"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===== DANGER ZONE ===== -->
            <div class="settings-card">
                <div class="section-title">
                    <div class="icon-box" style="background: rgba(255,107,107,0.12); border-color: rgba(255,107,107,0.35); color: #ff6b6b;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h2>Danger Zone</h2>
                        <span class="section-desc">Irreversible actions that require caution.</span>
                    </div>
                </div>

                <div class="danger-zone">
                    <div>
                        <h4><i class="fas fa-right-from-bracket"></i> Sign Out Everywhere</h4>
                        <p>End all active sessions on this account. You will need to log in again on any device.</p>
                    </div>
                    <button type="button" class="btn-danger" onclick="signOutEverywhere()">
                        <i class="fas fa-power-off"></i> Sign Out All Devices
                    </button>
                </div>
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
        /* ============================================================
           ALERT MODAL
           ============================================================ */
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
            } else {
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

        /* ============================================================
           SHOW / HIDE PASSWORD
           ============================================================ */
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword
                ? '<i class="fas fa-eye-slash"></i>'
                : '<i class="fas fa-eye"></i>';
        }

        /* ============================================================
           PASSWORD STRENGTH METER
           ============================================================ */
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
                    if (score === 1) bar.classList.add('weak');
                    else if (score === 2) bar.classList.add('fair');
                    else if (score === 3) bar.classList.add('good');
                    else if (score === 4) bar.classList.add('strong');
                }
            });
        }

        /* ============================================================
           SAVE PERSONAL INFORMATION
           ============================================================ */
        function savePersonalInfo(e) {
            e.preventDefault();
            const btn = document.getElementById('savePersonalBtn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

            // NOTE: Hook this to a backend endpoint when ready, e.g.:
            // fetch('backend/update_profile.php', { method:'POST', body: new FormData(form) })
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showAlert('success', 'Profile Updated',
                    'Your personal information has been saved successfully.\nRefresh the page to see the changes reflected.');
            }, 700);
        }

        /* ============================================================
           SAVE PASSWORD
           ============================================================ */
        function savePassword(e) {
            e.preventDefault();

            const current = document.getElementById('currentPassword').value.trim();
            const newPw   = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (newPw.length < 8) {
                showAlert('warning', 'Weak Password', 'Your new password must be at least 8 characters long.');
                return;
            }
            if (newPw !== confirm) {
                showAlert('warning', 'Passwords Do Not Match', 'The new password and confirmation do not match.');
                return;
            }
            if (!current) {
                showAlert('warning', 'Missing Field', 'Please enter your current password.');
                return;
            }

            const btn = document.getElementById('savePasswordBtn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';

            // NOTE: Hook this to a backend endpoint when ready, e.g.:
            // fetch('backend/update_password.php', { method:'POST', body: new FormData(form) })
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                document.getElementById('passwordForm').reset();
                updateStrength('');
                showAlert('success', 'Password Updated',
                    'Your password has been changed successfully.\nUse your new password the next time you log in.');
            }, 800);
        }

        /* ============================================================
           SIGN OUT EVERYWHERE
           ============================================================ */
        function signOutEverywhere() {
            if (confirm('Sign out from all devices? You will need to log in again.')) {
                // When a backend route exists: window.location.href = 'logout_all.php';
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>

</html>