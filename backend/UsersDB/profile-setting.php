<?php
session_start();

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
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$username = $_SESSION['username'];

// =====================================================
// ENSURE users.student_code exists
// =====================================================
try { $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS student_code VARCHAR(20) DEFAULT NULL"); } catch (PDOException $e) {}

// =====================================================
// AJAX HANDLERS
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    // -------------------------------------------------
    // UPDATE PROFILE
    // -------------------------------------------------
    if ($action === 'update_profile') {
        $first  = trim($_POST['first_name']  ?? '');
        $middle = trim($_POST['middle_name'] ?? '');
        $last   = trim($_POST['last_name']   ?? '');

        if ($first === '' || $last === '') {
            echo json_encode(['success' => false, 'message' => 'First and last name are required.']);
            exit;
        }

        try {
            // Fetch student_code first, so we can also update the enrolled row
            $stmt = $conn->prepare("SELECT student_code FROM users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $code = $stmt->fetchColumn();

            // Update users
            $stmt = $conn->prepare("UPDATE users
                                    SET first_name = :fn,
                                        middle_name = :mn,
                                        last_name = :ln
                                    WHERE username = :u");
            $stmt->execute([
                ':fn' => $first,
                ':mn' => $middle,
                ':ln' => $last,
                ':u'  => $username,
            ]);

            // Keep the enrolled row's name in sync (only if we know the student_code)
            if (!empty($code)) {
                $stmt = $conn->prepare("UPDATE enrolled
                                        SET firstname = :fn,
                                            middlename = :mn,
                                            lastname = :ln
                                        WHERE student_code = :code");
                $stmt->execute([
                    ':fn'   => $first,
                    ':mn'   => $middle,
                    ':ln'   => $last,
                    ':code' => $code,
                ]);
            }

            $_SESSION['student_name'] = trim($first . ' ' . $last);

            echo json_encode([
                'success'   => true,
                'message'   => 'Your profile information has been saved successfully.',
                'full_name' => trim($first . ' ' . $middle . ' ' . $last),
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // -------------------------------------------------
    // UPDATE PASSWORD
    // -------------------------------------------------
    if ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit;
        }
        if (strlen($new) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
            exit;
        }
        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $hash = $stmt->fetchColumn();

            if (!$hash) {
                echo json_encode(['success' => false, 'message' => 'Account not found.']);
                exit;
            }
            if (!password_verify($current, $hash)) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }
            if (password_verify($new, $hash)) {
                echo json_encode(['success' => false, 'message' => 'New password must differ from the current one.']);
                exit;
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE users SET password = :pw WHERE username = :u")
                 ->execute([':pw' => $newHash, ':u' => $username]);

            echo json_encode(['success' => true, 'message' => 'Your password has been changed successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// =====================================================
// FETCH LOGGED-IN STUDENT
// =====================================================
try {
    $stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, username, student_code
                            FROM users
                            WHERE username = :username
                            LIMIT 1");
    $stmt->execute([':username' => $username]);
    $student = $stmt->fetch();

    if (!$student) {
        session_destroy();
        header("Location: login.html");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

$first_name    = htmlspecialchars($student['first_name'] ?? '');
$middle_name   = htmlspecialchars($student['middle_name'] ?? '');
$last_name     = htmlspecialchars($student['last_name'] ?? '');
$full_name     = htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? '')));
$username_disp = htmlspecialchars($student['username'] ?? '');
$initial       = strtoupper(substr($student['first_name'] ?? 'U', 0, 1));
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
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
            min-height: 100vh; display: flex; flex-direction: column;
            overflow-x: hidden; position: relative;
        }
        .navbar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10, 25, 47, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-teal);
            padding: 14px 5%;
            display: flex; justify-content: space-between; align-items: center;
            gap: 16px; flex-wrap: wrap;
        }
        .navbar-brand {
            display: flex; align-items: center; gap: 14px;
            text-decoration: none; color: inherit;
        }
        .navbar-brand .logo-box {
            width: 46px; height: 46px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(13, 245, 227, 0.06);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 12px; padding: 6px;
        }
        .navbar-brand .logo-box img { width: 100%; height: 100%; object-fit: contain; }
        .navbar-brand .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .navbar-brand .brand-text h1 { font-size: 1.15rem; font-weight: 800; color: var(--primary-neon); }
        .navbar-brand .brand-text span { font-size: 0.62rem; font-weight: 600; color: var(--text-muted-teal); letter-spacing: 1.4px; text-transform: uppercase; margin-top: 4px; }

        .navbar-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn-back-profile {
            background: transparent;
            border: 1px solid rgba(13, 245, 227, 0.4);
            color: var(--primary-neon);
            padding: 8px 18px; border-radius: 50px;
            text-decoration: none; font-size: 0.8rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-logout {
            background: transparent;
            border: 1px solid rgba(255, 107, 107, 0.5);
            color: #ff6b6b; padding: 8px 18px; border-radius: 50px;
            text-decoration: none; font-size: 0.8rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
        }

        .hero-banner {
            padding: 3.5rem 5% 3rem; text-align: center;
            background: linear-gradient(180deg, rgba(13, 245, 227, 0.04) 0%, transparent 100%);
            border-bottom: 1px solid var(--border-teal);
        }
        .hero-banner .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.7rem; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.08);
            border: 1px solid rgba(13, 245, 227, 0.2);
            padding: 6px 14px; border-radius: 20px;
            margin-bottom: 14px;
        }
        .hero-banner h1 {
            font-size: 2.2rem; font-weight: 800;
            letter-spacing: -0.5px; margin-bottom: 10px;
        }
        .hero-banner h1 .accent {
            background: linear-gradient(135deg, #0df5e3 30%, #00a89d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-banner p { font-size: 0.92rem; color: var(--text-muted-teal); max-width: 620px; margin: 0 auto; }

        .main-content { flex: 1; padding: 2.5rem 5% 3rem; position: relative; z-index: 1; }
        .settings-wrapper { max-width: 1100px; margin: 0 auto; }

        .settings-card {
            background: linear-gradient(160deg, var(--bg-card) 0%, #06152b 100%);
            border: 1px solid var(--border-teal);
            border-radius: 20px; padding: 2.5rem;
            margin-bottom: 2rem;
        }
        .section-title {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 1.75rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-teal);
        }
        .section-title .icon-box {
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(13, 245, 227, 0.15), rgba(13, 245, 227, 0.05));
            border: 1px solid rgba(13, 245, 227, 0.25);
            border-radius: 12px; color: var(--primary-neon); font-size: 1.05rem;
        }
        .section-title h2 { font-size: 1.15rem; font-weight: 700; }
        .section-title .section-desc { display: block; font-size: 0.78rem; color: var(--text-muted-teal); font-weight: 500; margin-top: 3px; }

        .profile-header {
            display: flex; align-items: center; gap: 1.5rem;
            padding: 1.5rem; background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 14px; margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .profile-avatar {
            width: 88px; height: 88px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-neon), #00b4a4);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; font-weight: 800;
            color: var(--bg-deep-abyss);
            box-shadow: 0 0 30px rgba(13, 245, 227, 0.4);
            flex-shrink: 0;
        }
        .profile-header-text { flex: 1; min-width: 220px; }
        .profile-header-text h3 { font-size: 1.35rem; font-weight: 800; margin-bottom: 4px; }
        .profile-header-text .username-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.78rem; color: var(--primary-neon);
            background: rgba(13, 245, 227, 0.08);
            border: 1px solid rgba(13, 245, 227, 0.2);
            padding: 4px 12px; border-radius: 20px; font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label {
            font-size: 0.72rem; font-weight: 700;
            color: var(--text-muted-teal);
            text-transform: uppercase; letter-spacing: 1.2px;
            display: flex; align-items: center; gap: 6px;
        }
        .form-group label i { color: var(--primary-neon); font-size: 0.72rem; }
        .form-control {
            width: 100%; padding: 13px 16px;
            background: rgba(2, 12, 27, 0.8);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            color: var(--text-high-contrast);
            font-family: inherit; font-size: 0.88rem;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary-neon);
            box-shadow: 0 0 0 4px rgba(13, 245, 227, 0.08);
        }
        .form-control:disabled { opacity: 0.55; cursor: not-allowed; }

        .password-wrap { position: relative; }
        .password-wrap .toggle-pw {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            background: transparent; border: none;
            color: var(--text-muted-teal); font-size: 0.85rem;
            cursor: pointer; padding: 6px;
        }
        .password-wrap .toggle-pw:hover { color: var(--primary-neon); }

        .strength-meter { display: flex; gap: 4px; margin-top: 4px; height: 4px; }
        .strength-bar { flex: 1; border-radius: 2px; background: var(--border-teal); }
        .strength-bar.active.weak { background: #ef4444; }
        .strength-bar.active.fair { background: #f59e0b; }
        .strength-bar.active.good { background: #3b82f6; }
        .strength-bar.active.strong { background: #10b981; }

        .action-row {
            display: flex; gap: 12px;
            justify-content: flex-end; margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; padding: 12px 26px;
            border-radius: 50px; font-weight: 700;
            font-size: 0.85rem; cursor: pointer;
            border: none; font-family: inherit;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
        }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-teal);
            color: var(--text-muted-teal);
        }
        .btn-outline:hover { border-color: var(--primary-neon); color: var(--primary-neon); }

        .info-note {
            display: flex; align-items: flex-start; gap: 10px;
            background: rgba(13, 245, 227, 0.06);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 12px; padding: 12px 16px;
            font-size: 0.78rem; color: var(--text-muted-teal);
            line-height: 1.6; margin-top: 1rem;
        }
        .info-note i { color: var(--primary-neon); flex-shrink: 0; margin-top: 2px; }

        .danger-zone {
            border: 1px solid rgba(255, 107, 107, 0.3);
            background: rgba(255, 107, 107, 0.04);
            border-radius: 14px; padding: 1.5rem;
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap;
        }
        .danger-zone h4 { font-size: 0.95rem; font-weight: 700; color: #ff6b6b; margin-bottom: 4px; }
        .danger-zone p { font-size: 0.8rem; color: var(--text-muted-teal); }
        .btn-danger {
            background: transparent;
            border: 1px solid rgba(255, 107, 107, 0.5);
            color: #ff6b6b; padding: 11px 24px;
            border-radius: 50px; font-weight: 700;
            font-size: 0.82rem; cursor: pointer;
            font-family: inherit;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-danger:hover { background: rgba(255, 107, 107, 0.12); }

        .alert-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(2, 12, 27, 0.92);
            backdrop-filter: blur(10px);
            z-index: 2000; justify-content: center; align-items: center;
            padding: 20px;
        }
        .alert-overlay.active { display: flex; }
        .alert-box {
            background: linear-gradient(180deg, var(--bg-card) 0%, #06152b 100%);
            border-radius: 20px; padding: 2rem;
            max-width: 420px; width: 100%; text-align: center;
            border: 1px solid var(--border-teal);
            color: var(--text-high-contrast);
        }
        .alert-box.success { border-color: #10b981; }
        .alert-box.warning { border-color: #fbbf24; }
        .alert-icon { font-size: 3rem; margin-bottom: 1rem; display: inline-block; }
        .alert-box.success .alert-icon { color: #10b981; }
        .alert-box.warning .alert-icon { color: #fbbf24; }
        .alert-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 0.75rem; }
        .alert-message { font-size: 0.88rem; color: var(--text-muted-teal); line-height: 1.7; margin-bottom: 1.5rem; white-space: pre-line; }
        .alert-close-btn {
            padding: 11px 36px; border: none; border-radius: 50px;
            font-weight: 800; font-size: 0.88rem; cursor: pointer;
            font-family: inherit;
        }
        .alert-box.success .alert-close-btn { background: linear-gradient(135deg, #10b981, #059669); color: var(--bg-deep-abyss); }
        .alert-box.warning .alert-close-btn { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: var(--bg-deep-abyss); }

        @media (max-width: 820px) {
            .form-grid { grid-template-columns: 1fr; }
            .action-row { justify-content: stretch; }
            .action-row .btn { flex: 1; }
            .danger-zone { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="profile.php" class="navbar-brand">
            <div class="logo-box"><img src="../../BackGroundimage/ICFLogo.png" alt="ICF Logo"></div>
            <div class="brand-text">
                <h1>ICF Student Portal</h1>
                <span>Interworld Colleges Foundation Inc.</span>
            </div>
        </a>
        <div class="navbar-actions">
            <a href="profile.php" class="btn-back-profile"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <section class="hero-banner">
        <div class="eyebrow"><i class="fas fa-cog"></i> Account Settings</div>
        <h1>Profile <span class="accent">Settings</span></h1>
        <p>Manage your personal information, update your account password, and control your account preferences.</p>
    </section>

    <main class="main-content">
        <div class="settings-wrapper">

            <div class="settings-card">
                <div class="profile-header">
                    <div class="profile-avatar" id="profileAvatar"><?php echo $initial; ?></div>
                    <div class="profile-header-text">
                        <h3 id="profileDisplayName"><?php echo $full_name; ?></h3>
                        <span class="username-badge">
                            <i class="fas fa-user"></i>
                            <?php echo $username_disp; ?>
                        </span>
                    </div>
                </div>

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
                            <input type="text" id="firstName" name="first_name" class="form-control" value="<?php echo $first_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middleName"><i class="fas fa-signature"></i> Middle Name</label>
                            <input type="text" id="middleName" name="middle_name" class="form-control" value="<?php echo $middle_name; ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastName"><i class="fas fa-signature"></i> Last Name</label>
                            <input type="text" id="lastName" name="last_name" class="form-control" value="<?php echo $last_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="usernameField"><i class="fas fa-id-badge"></i> Username</label>
                            <input type="text" id="usernameField" class="form-control" value="<?php echo $username_disp; ?>" disabled>
                        </div>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Your username is tied to your enrollment records and cannot be changed here.</span>
                    </div>

                    <div class="action-row">
                        <button type="reset" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</button>
                        <button type="submit" class="btn btn-primary" id="savePersonalBtn"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </div>
                </form>
            </div>

            <div class="settings-card">
                <div class="section-title">
                    <div class="icon-box"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <h2>Security &amp; Password</h2>
                        <span class="section-desc">Change your account password.</span>
                    </div>
                </div>

                <form id="passwordForm" onsubmit="savePassword(event)">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="currentPassword"><i class="fas fa-lock"></i> Current Password</label>
                            <div class="password-wrap">
                                <input type="password" id="currentPassword" class="form-control" placeholder="Enter your current password" required>
                                <button type="button" class="toggle-pw" onclick="togglePw('currentPassword', this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="newPassword"><i class="fas fa-key"></i> New Password</label>
                            <div class="password-wrap">
                                <input type="password" id="newPassword" class="form-control" placeholder="Enter new password" required oninput="updateStrength(this.value)">
                                <button type="button" class="toggle-pw" onclick="togglePw('newPassword', this)"><i class="fas fa-eye"></i></button>
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
                                <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm new password" required>
                                <button type="button" class="toggle-pw" onclick="togglePw('confirmPassword', this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Password must be at least 8 characters and should include uppercase, lowercase, numbers, and special characters.</span>
                    </div>

                    <div class="action-row">
                        <button type="reset" class="btn btn-outline" onclick="resetStrength()"><i class="fas fa-rotate-left"></i> Clear</button>
                        <button type="submit" class="btn btn-primary" id="savePasswordBtn"><i class="fas fa-shield-halved"></i> Update Password</button>
                    </div>
                </form>
            </div>

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
                        <p>End all active sessions on this account.</p>
                    </div>
                    <button type="button" class="btn-danger" onclick="signOutEverywhere()"><i class="fas fa-power-off"></i> Sign Out All Devices</button>
                </div>
            </div>

        </div>
    </main>

    <div class="alert-overlay" id="alertModal">
        <div class="alert-box" id="alertBox">
            <div class="alert-icon" id="alertIcon"></div>
            <div class="alert-title" id="alertTitle"></div>
            <div class="alert-message" id="alertMessage"></div>
            <button class="alert-close-btn" onclick="closeAlert()">OK</button>
        </div>
    </div>

    <script>
        function showAlert(type, title, message) {
            const box = document.getElementById('alertBox');
            const icon = document.getElementById('alertIcon');
            box.classList.remove('success', 'warning');
            if (type === 'success') { box.classList.add('success'); icon.innerHTML = '<i class="fas fa-check-circle"></i>'; }
            else { box.classList.add('warning'); icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>'; }
            document.getElementById('alertTitle').textContent = title;
            document.getElementById('alertMessage').textContent = message;
            document.getElementById('alertModal').classList.add('active');
        }
        function closeAlert() { document.getElementById('alertModal').classList.remove('active'); }

        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const isPw = input.type === 'password';
            input.type = isPw ? 'text' : 'password';
            btn.innerHTML = isPw ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        }

        function updateStrength(value) {
            const bars = document.querySelectorAll('#strengthMeter .strength-bar');
            let score = 0;
            if (value.length >= 8) score++;
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
        function resetStrength() { setTimeout(() => updateStrength(''), 0); }

        async function savePersonalInfo(e) {
            e.preventDefault();
            const first = document.getElementById('firstName').value.trim();
            const middle = document.getElementById('middleName').value.trim();
            const last = document.getElementById('lastName').value.trim();

            if (!first || !last) { showAlert('warning', 'Missing Field', 'First and last name are required.'); return; }

            const btn = document.getElementById('savePersonalBtn');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

            try {
                const fd = new FormData();
                fd.append('action', 'update_profile');
                fd.append('first_name', first);
                fd.append('middle_name', middle);
                fd.append('last_name', last);

                const res = await fetch('profile-setting.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('profileDisplayName').textContent = data.full_name || (first + ' ' + last);
                    document.getElementById('profileAvatar').textContent = first.charAt(0).toUpperCase();
                    showAlert('success', 'Profile Updated', data.message);
                } else {
                    showAlert('warning', 'Update Failed', data.message || 'Something went wrong.');
                }
            } catch (err) {
                showAlert('warning', 'Network Error', 'Could not reach the server.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        async function savePassword(e) {
            e.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const newPw = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (!current) { showAlert('warning', 'Missing Field', 'Enter your current password.'); return; }
            if (newPw.length < 8) { showAlert('warning', 'Weak Password', 'Minimum 8 characters.'); return; }
            if (newPw !== confirm) { showAlert('warning', 'Passwords Do Not Match', 'New password and confirmation differ.'); return; }

            const btn = document.getElementById('savePasswordBtn');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';

            try {
                const fd = new FormData();
                fd.append('action', 'update_password');
                fd.append('current_password', current);
                fd.append('new_password', newPw);
                fd.append('confirm_password', confirm);

                const res = await fetch('profile-setting.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('passwordForm').reset();
                    updateStrength('');
                    showAlert('success', 'Password Updated', data.message);
                } else {
                    showAlert('warning', 'Update Failed', data.message || 'Something went wrong.');
                }
            } catch (err) {
                showAlert('warning', 'Network Error', 'Could not reach the server.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        function signOutEverywhere() {
            if (confirm('Sign out from all devices?')) window.location.href = 'logout.php';
        }

        document.getElementById('alertModal').addEventListener('click', function(e) {
            if (e.target === this) closeAlert();
        });
    </script>
</body>

</html>