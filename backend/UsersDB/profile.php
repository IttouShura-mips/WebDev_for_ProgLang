<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['student_name']) || !isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Database Connection
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

// Fetch full student record from database
$username = $_SESSION['username'];
try {
    $stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, username FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: login.html");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Build display values
$full_name     = htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
$first_name    = htmlspecialchars($student['first_name']);
$middle_name   = htmlspecialchars($student['middle_name']);
$last_name     = htmlspecialchars($student['last_name']);
$username_disp = htmlspecialchars($student['username']);
$user_id       = htmlspecialchars($student['user_id']);
$initial       = strtoupper(substr($student['first_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | ICF Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --neon-glow: 0 0 15px rgba(13, 245, 227, 0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #071f30, var(--bg-deep-abyss));
            color: var(--text-high-contrast);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-teal);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
            color: var(--primary-neon);
        }

        .navbar-brand i { font-size: 24px; }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-pill {
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            color: var(--text-muted-teal);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-pill i { color: var(--primary-neon); }

        .btn-logout {
            background: transparent;
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: rgba(255, 107, 107, 0.1);
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.3);
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .profile-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 20px;
            padding: 50px 40px;
            width: 100%;
            max-width: 560px;
            text-align: center;
            box-shadow: 0 0px 30px rgba(13, 245, 227, 0.15);
            transition: var(--transition-smooth);
        }

        .profile-card:hover {
            box-shadow: 0 0px 40px rgba(13, 245, 227, 0.25);
            border-color: rgba(13, 245, 227, 0.3);
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 42px;
            color: var(--bg-deep-abyss);
            font-weight: 800;
            box-shadow: var(--neon-glow);
        }

        .welcome-text {
            font-size: 13px;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .student-name {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-high-contrast);
            margin-bottom: 6px;
        }

        .student-id {
            font-size: 14px;
            color: var(--primary-neon);
            font-family: monospace;
            background: rgba(13, 245, 227, 0.1);
            padding: 4px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .divider {
            height: 1px;
            background: var(--border-teal);
            margin: 24px 0;
        }

        .section-title {
            font-size: 12px;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 16px;
            text-align: left;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .info-item {
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            padding: 16px;
            text-align: left;
            transition: var(--transition-smooth);
        }

        .info-item:hover {
            border-color: rgba(13, 245, 227, 0.3);
            transform: translateY(-2px);
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item i {
            color: var(--primary-neon);
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
        }

        .info-label {
            font-size: 11px;
            color: var(--text-muted-teal);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-high-contrast);
            word-break: break-word;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 24px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
        }

        @media (max-width: 600px) {
            .navbar { padding: 14px 20px; }
            .navbar-brand span { display: none; }
            .profile-card { padding: 35px 22px; }
            .info-grid { grid-template-columns: 1fr; }
            .student-name { font-size: 22px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>ICF Portal</span>
        </div>
        <div class="navbar-actions">
            <div class="user-pill">
                <i class="fas fa-user"></i>
                <span><?php echo $username_disp; ?></span>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="profile-card">
            <div class="avatar-circle"><?php echo $initial; ?></div>

            <div class="welcome-text">Registered Student</div>
            <div class="student-name"><?php echo $full_name; ?></div>
            <div class="student-id">
                <i class="fas fa-id-card" style="margin-right:6px;"></i><?php echo $username_disp; ?>
            </div>

            <div class="divider"></div>

            <div class="section-title">Personal Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-hashtag"></i>
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?php echo $user_id; ?></div>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-label">First Name</div>
                    <div class="info-value"><?php echo $first_name; ?></div>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-label">Middle Name</div>
                    <div class="info-value"><?php echo $middle_name; ?></div>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-label">Last Name</div>
                    <div class="info-value"><?php echo $last_name; ?></div>
                </div>
                <div class="info-item full-width">
                    <i class="fas fa-fingerprint"></i>
                    <div class="info-label">Username / Student ID</div>
                    <div class="info-value"><?php echo $username_disp; ?></div>
                </div>
            </div>

            <div class="status-badge">Account Active</div>
        </div>
    </main>

</body>
</html>