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
// FETCH LOGGED-IN STUDENT (from users table)
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
// FETCH MATCHING ENROLLED RECORD (via student_code)
// =====================================================
$enrolled = null;
try {
    // Ensure new columns/tables exist (safe no-op if already present)
    $conn->exec("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS student_code VARCHAR(20) DEFAULT NULL AFTER student_id");
    $conn->exec("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS subjects_json LONGTEXT DEFAULT NULL");
    $conn->exec("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS assessment_json LONGTEXT DEFAULT NULL");

    // student_payments table (auto-create)
    $conn->exec("CREATE TABLE IF NOT EXISTS student_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_code VARCHAR(20) NOT NULL,
        payment_date DATE NOT NULL,
        or_number VARCHAR(30) NOT NULL,
        purpose VARCHAR(50) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        gcash_number VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (student_code)
    )");

    $stmt = $conn->prepare("SELECT * FROM enrolled WHERE student_code = :code LIMIT 1");
    $stmt->execute([':code' => $username]);
    $enrolled = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback: match by email using student_name (some records may not have student_code yet)
    if (!$enrolled && !empty($_SESSION['student_name'])) {
        $stmt = $conn->prepare("SELECT * FROM enrolled WHERE CONCAT(firstname,' ',lastname) = :nm ORDER BY student_id DESC LIMIT 1");
        $stmt->execute([':nm' => $_SESSION['student_name']]);
        $enrolled = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Non-fatal, continue with empty data
    $enrolled = null;
}

// =====================================================
// PARSE SUBJECTS / ASSESSMENT
// =====================================================
$subjects   = [];
$assessment = [];
$totalUnits = 0;
$bridging   = 0;
$nstpUnits  = 0;

if ($enrolled) {
    if (!empty($enrolled['subjects_json'])) {
        $decoded = json_decode($enrolled['subjects_json'], true);
        if (is_array($decoded)) $subjects = $decoded;
    }
    if (!empty($enrolled['assessment_json'])) {
        $decoded = json_decode($enrolled['assessment_json'], true);
        if (is_array($decoded)) $assessment = $decoded;
    }
    foreach ($subjects as $s) {
        $u = (float)($s['units'] ?? 0);
        $totalUnits += $u;
        if (stripos($s['code'] ?? '', 'BRIDGING') !== false) $bridging += $u;
        if (stripos($s['code'] ?? '', 'NSTP') !== false)     $nstpUnits += $u;
    }
}

// =====================================================
// PAYMENTS (real + defaults)
// =====================================================
$payments = [];
$totalPaid = 0.0;
try {
    $stmt = $conn->prepare("SELECT payment_date, or_number, purpose, amount, gcash_number FROM student_payments WHERE student_code = :code ORDER BY payment_date ASC, id ASC");
    $stmt->execute([':code' => $username]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($payments as $p) $totalPaid += (float)$p['amount'];
} catch (PDOException $e) {
    $payments = [];
}

// =====================================================
// ASSESSMENT TOTAL / BALANCE
// =====================================================
function parseMoney($v)
{
    if ($v === null || $v === '') return 0.0;
    $clean = preg_replace('/[^0-9.\-]/', '', (string)$v);
    return (float)$clean;
}

$assessTuition  = $assessment['tuition_fee'] ?? 0;
$assessAcademic = $assessment['academic']    ?? 0;
$assessComputer = $assessment['computer']    ?? 0;
$assessMisc     = $assessment['misc_fee']    ?? 0;
$assessNSTP     = $assessment['nstp']        ?? 0;
$assessOthers   = $assessment['others']      ?? 0;

$totalAssessment = parseMoney($assessment['total'] ?? 0);
if ($totalAssessment <= 0) {
    $totalAssessment = parseMoney($assessTuition) + parseMoney($assessAcademic) + parseMoney($assessComputer)
        + parseMoney($assessMisc)    + parseMoney($assessNSTP)    + parseMoney($assessOthers);
}
$remainingBalance = max(0, $totalAssessment - $totalPaid);

// =====================================================
// DISPLAY VALUES
// =====================================================
$full_name     = htmlspecialchars(trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']));
$lastFirst     = strtoupper(htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']));
$first_name    = htmlspecialchars($student['first_name']);
$middle_name   = htmlspecialchars($student['middle_name']);
$last_name     = htmlspecialchars($student['last_name']);
$username_disp = htmlspecialchars($student['username']);
$user_id       = htmlspecialchars($student['user_id']);
$initial       = strtoupper(substr($student['first_name'], 0, 1));

// Enrolled-derived
$course       = htmlspecialchars($enrolled['course'] ?? 'N/A');
$major        = htmlspecialchars($enrolled['major'] ?? '');
$academicYear = htmlspecialchars($enrolled['academic_year'] ?? '2026-2027');
$yearLevel    = '3rd Year'; // adjust if you add a year_level column later
$datePrinted  = date('F j, Y');

// Money formatter
function money($n)
{
    return '₱ ' . number_format((float)$n, 2);
}

// Course acronym for greeting
$courseAcronym = '';
if (!empty($enrolled['course'])) {
    $courseAcronym = implode('', array_map(fn($w) => $w[0] ?? '', preg_split('/\s+/', $enrolled['course'])));
    $courseAcronym = strtoupper(preg_replace('/[^A-Z]/', '', $courseAcronym));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | ICF Portal</title>
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
            transition: var(--transition-smooth);
        }

        .user-pill:hover i {
            transform: scale(1.15);
        }

        .user-pill .settings-hint {
            font-size: 0.68rem;
            color: var(--text-muted-teal);
            margin-left: 4px;
        }

        .user-pill:hover .settings-hint {
            color: var(--primary-neon);
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

        .cor-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ============================================
           COR CARD
           ============================================ */
        .cor-card {
            background: linear-gradient(160deg, var(--bg-card) 0%, #06152b 100%);
            border: 1px solid var(--border-teal);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            transition: var(--transition-smooth);
        }

        .cor-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-neon), #00b4d8, var(--purple-accent));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .cor-card:hover::before { transform: scaleX(1); }

        .cor-card:hover {
            border-color: rgba(13, 245, 227, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6), 0 0 40px rgba(13, 245, 227, 0.12);
        }

        /* ============================================
           COR HEADER
           ============================================ */
        .cor-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-teal);
            position: relative;
        }

        .cor-header-top {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .cor-logo {
            width: 76px;
            height: 76px;
            object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(13, 245, 227, 0.3));
            transition: var(--transition-smooth);
        }

        .cor-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 30px rgba(13, 245, 227, 0.5));
        }

        .cor-header-text {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cor-school-name {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-high-contrast);
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }

        .cor-address {
            font-size: 0.78rem;
            color: var(--text-muted-teal);
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .cor-address i {
            color: var(--primary-neon);
            font-size: 0.72rem;
        }

        .cor-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 12px;
            padding: 8px 20px;
            background: rgba(13, 245, 227, 0.06);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 30px;
            display: inline-block;
        }

        .cor-session {
            font-size: 0.82rem;
            color: var(--text-muted-teal);
            margin-top: 10px;
            font-style: italic;
        }

        /* ============================================
           STUDENT INFO
           ============================================ */
        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 14px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: var(--transition-smooth);
        }

        .student-info-grid:hover {
            border-color: rgba(13, 245, 227, 0.3);
            background: rgba(2, 12, 27, 0.8);
        }

        .student-info-left,
        .student-info-right {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .student-info-right {
            align-items: flex-end;
            text-align: right;
        }

        .info-line {
            font-size: 0.88rem;
            color: var(--text-muted-teal);
            line-height: 1.5;
        }

        .info-line strong {
            color: var(--text-high-contrast);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .info-line strong i {
            color: var(--primary-neon);
            font-size: 0.8rem;
            width: 16px;
            text-align: center;
        }

        .info-line .highlight {
            color: var(--primary-neon);
            font-weight: 700;
            padding: 3px 10px;
            background: rgba(13, 245, 227, 0.08);
            border: 1px solid rgba(13, 245, 227, 0.2);
            border-radius: 20px;
            font-size: 0.82rem;
        }

        /* ============================================
           SECTION TITLE
           ============================================ */
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.2rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-teal);
        }

        .section-title .icon-box {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(13, 245, 227, 0.15), rgba(13, 245, 227, 0.05));
            border: 1px solid rgba(13, 245, 227, 0.25);
            border-radius: 10px;
            color: var(--primary-neon);
            font-size: 0.95rem;
            transition: var(--transition-smooth);
        }

        .section-title:hover .icon-box {
            background: var(--primary-neon);
            color: var(--bg-deep-abyss);
            box-shadow: var(--neon-glow);
            transform: scale(1.05) rotate(-4deg);
        }

        .section-title h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-high-contrast);
            letter-spacing: -0.2px;
        }

        /* ============================================
           COURSE TABLE
           ============================================ */
        .course-table-wrap {
            background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: var(--transition-smooth);
        }

        .course-table-wrap:hover {
            border-color: rgba(13, 245, 227, 0.3);
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .course-table th {
            background: rgba(13, 245, 227, 0.06);
            color: var(--primary-neon);
            font-weight: 700;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border-teal);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .course-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-teal);
            color: var(--text-high-contrast);
            font-size: 0.85rem;
        }

        .course-table tr:last-child td {
            border-bottom: none;
        }

        .course-table tbody tr {
            transition: var(--transition-smooth);
        }

        .course-table tbody tr:hover {
            background: rgba(13, 245, 227, 0.04);
        }

        .course-table .total-row td {
            background: rgba(13, 245, 227, 0.08);
            font-weight: 800;
            color: var(--primary-neon);
            border-top: 2px solid rgba(13, 245, 227, 0.3);
            font-size: 0.85rem;
        }

        .course-table .empty-row td {
            text-align: center;
            color: var(--text-muted-teal);
            font-style: italic;
            padding: 30px 14px;
        }

        /* ============================================
           PAYMENT & ASSESSMENT
           ============================================ */
        .payment-assessment-wrap {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .payment-assess-card {
            background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 14px;
            overflow: hidden;
            transition: var(--transition-smooth);
            position: relative;
        }

        .payment-assess-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-neon), transparent);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .payment-assess-card:hover::before { transform: scaleX(1); }

        .payment-assess-card:hover {
            border-color: rgba(13, 245, 227, 0.4);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 25px rgba(13, 245, 227, 0.1);
        }

        .card-header {
            background: rgba(13, 245, 227, 0.06);
            padding: 12px 18px;
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 1px solid var(--border-teal);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            font-size: 0.85rem;
        }

        .table-container {
            padding: 0;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .data-table th,
        .data-table td {
            padding: 10px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border-teal);
        }

        .data-table th {
            color: var(--text-muted-teal);
            font-weight: 700;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-table td {
            color: var(--text-high-contrast);
            font-size: 0.85rem;
        }

        .data-table tbody tr {
            transition: var(--transition-smooth);
        }

        .data-table tbody tr:hover {
            background: rgba(13, 245, 227, 0.03);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table .assessment-item td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .data-table .assessment-total td {
            background: rgba(13, 245, 227, 0.08);
            font-weight: 800;
            color: var(--primary-neon);
            font-size: 0.9rem;
            border-top: 2px solid rgba(13, 245, 227, 0.3);
        }

        .data-table .assessment-total td:last-child {
            text-align: right;
            font-size: 1rem;
        }

        .data-table .assessment-remaining td {
            background: rgba(139, 92, 246, 0.08);
            color: var(--text-high-contrast);
            font-weight: 800;
            font-size: 0.9rem;
            border-top: 2px solid rgba(139, 92, 246, 0.3);
        }

        .data-table .assessment-remaining td:last-child {
            text-align: right;
            font-size: 1.05rem;
            color: var(--purple-accent);
        }

        .data-table .empty-row td {
            text-align: center;
            color: var(--text-muted-teal);
            font-style: italic;
            padding: 24px;
        }

        /* ============================================
           PAY BUTTON
           ============================================ */
        .pay-btn-wrap {
            padding: 15px 18px;
        }

        .btn-pay-tuition {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            border: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-align: center;
            box-shadow: 0 4px 15px rgba(13, 245, 227, 0.3);
            font-family: inherit;
            letter-spacing: 0.3px;
        }

        .btn-pay-tuition:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(13, 245, 227, 0.6), 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .btn-pay-tuition:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ============================================
           SIGNATURES
           ============================================ */
        .signature-box {
            margin-top: 1.5rem;
            padding: 1.75rem;
            background: rgba(2, 12, 27, 0.5);
            border: 1px dashed rgba(13, 245, 227, 0.25);
            border-radius: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            align-items: end;
            text-align: center;
        }

        .sig-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
        }

        .sig-line {
            width: 100%;
            max-width: 180px;
            border-bottom: 1px solid var(--text-muted-teal);
            margin: 0 auto 6px auto;
        }

        .sig-item strong {
            color: var(--text-high-contrast);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .sig-item span {
            color: var(--text-muted-teal);
            font-size: 0.72rem;
        }

        .student-copy {
            font-size: 0.72rem;
            color: var(--text-muted-teal);
            margin-top: 1rem;
            text-align: center;
            font-style: italic;
        }

        /* ============================================
           CONTACT SECTION
           ============================================ */
        .contact-section {
            margin-top: 2.5rem;
            background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 16px;
            padding: 2rem;
            transition: var(--transition-smooth);
        }

        .contact-section:hover {
            border-color: rgba(13, 245, 227, 0.3);
        }

        .contact-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 1.5rem;
        }

        .contact-header .icon-circle {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(13, 245, 227, 0.15), rgba(13, 245, 227, 0.05));
            border: 1px solid rgba(13, 245, 227, 0.25);
            border-radius: 50%;
            color: var(--primary-neon);
            font-size: 1.1rem;
            transition: var(--transition-smooth);
        }

        .contact-section:hover .contact-header .icon-circle {
            background: var(--primary-neon);
            color: var(--bg-deep-abyss);
            box-shadow: var(--neon-glow);
            transform: scale(1.08) rotate(-4deg);
        }

        .contact-header h2 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-high-contrast);
            letter-spacing: -0.3px;
        }

        .contact-header p {
            font-size: 0.8rem;
            color: var(--text-muted-teal);
            margin-top: 3px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .contact-card {
            background: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 14px;
            padding: 1.5rem;
            display: flex;
            gap: 14px;
            align-items: flex-start;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, var(--primary-neon), transparent);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .contact-card:hover::before { transform: scaleY(1); }

        .contact-card:hover {
            border-color: rgba(13, 245, 227, 0.35);
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.35);
        }

        .contact-card .icon-box {
            background: rgba(13, 245, 227, 0.1);
            border: 1px solid rgba(13, 245, 227, 0.25);
            border-radius: 12px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--transition-smooth);
        }

        .contact-card:hover .icon-box {
            background: var(--primary-neon);
            color: var(--bg-deep-abyss);
            box-shadow: var(--neon-glow);
            transform: scale(1.08) rotate(-4deg);
        }

        .contact-card .icon-box i {
            font-size: 1.05rem;
            color: var(--primary-neon);
            transition: var(--transition-smooth);
        }

        .contact-card:hover .icon-box i {
            color: var(--bg-deep-abyss);
        }

        .contact-card .contact-info h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-high-contrast);
            margin-bottom: 6px;
        }

        .contact-card .contact-info p {
            font-size: 0.82rem;
            color: var(--text-muted-teal);
            margin-bottom: 12px;
            line-height: 1.55;
        }

        .btn-contact {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            border: none;
            padding: 9px 18px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-decoration: none;
            font-family: inherit;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 10px rgba(13, 245, 227, 0.2);
        }

        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(13, 245, 227, 0.5), 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-contact i { font-size: 0.72rem; }

        /* ============================================
           MODALS (Payment / Inquiry / Alert)
           ============================================ */
        .modal-overlay,
        .inquiry-modal-overlay,
        .alert-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 12, 27, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-overlay.active,
        .inquiry-modal-overlay.active,
        .alert-overlay.active {
            display: flex;
        }

        .inquiry-modal-overlay { z-index: 3000; }
        .alert-overlay { z-index: 2000; }

        .modal-box,
        .inquiry-modal-box,
        .alert-box {
            background: linear-gradient(180deg, var(--bg-card) 0%, #06152b 100%);
            border: 1px solid var(--primary-neon);
            border-radius: 20px;
            padding: 2rem;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 0 40px rgba(13, 245, 227, 0.25), 0 25px 60px rgba(0, 0, 0, 0.8);
            position: relative;
            animation: modalFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--text-high-contrast);
        }

        .inquiry-modal-box {
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            border-color: var(--border-teal);
            padding: 2.2rem;
        }

        .alert-box {
            max-width: 420px;
            text-align: center;
            border-color: var(--border-teal);
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

        .modal-close,
        .inquiry-close-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            background: transparent;
            border: none;
            color: var(--text-muted-teal);
            font-size: 1.4rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover,
        .inquiry-close-btn:hover {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
            transform: rotate(90deg);
        }

        .modal-title,
        .inquiry-modal-header h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-neon);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.3px;
        }

        .inquiry-modal-header {
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-teal);
        }

        .inquiry-modal-header h2 {
            color: var(--text-high-contrast);
            font-size: 1.35rem;
            margin-bottom: 6px;
        }

        .inquiry-modal-header p {
            font-size: 0.82rem;
            color: var(--text-muted-teal);
        }

        .form-group,
        .inquiry-form-group {
            margin-bottom: 1rem;
        }

        .form-group label,
        .inquiry-form-group label {
            display: block;
            font-size: 0.72rem;
            color: var(--text-muted-teal);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 700;
        }

        .form-group select,
        .form-group input,
        .form-group textarea,
        .inquiry-form-group input,
        .inquiry-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(2, 12, 27, 0.8);
            border: 1px solid var(--border-teal);
            border-radius: 12px;
            color: var(--text-high-contrast);
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: var(--transition-smooth);
        }

        .form-group textarea,
        .inquiry-form-group textarea {
            resize: vertical;
            min-height: 120px;
            border-radius: 14px;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus,
        .inquiry-form-group input:focus,
        .inquiry-form-group textarea:focus {
            border-color: var(--primary-neon);
            box-shadow: 0 0 0 4px rgba(13, 245, 227, 0.08), 0 0 12px rgba(13, 245, 227, 0.2);
            background: rgba(2, 12, 27, 0.95);
        }

        .btn-pay-confirm,
        .btn-send-inquiry {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-neon), #00cbb9);
            color: var(--bg-deep-abyss);
            border: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            margin-top: 1rem;
            font-family: inherit;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 15px rgba(13, 245, 227, 0.3);
        }

        .btn-pay-confirm:hover:not(:disabled),
        .btn-send-inquiry:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(13, 245, 227, 0.55);
        }

        .btn-pay-confirm:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        /* Alert specific */
        .alert-icon {
            font-size: 3.2rem;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .alert-box.success .alert-icon { color: #10b981; text-shadow: 0 0 25px rgba(16, 185, 129, 0.5); }
        .alert-box.warning .alert-icon { color: #fbbf24; text-shadow: 0 0 25px rgba(251, 191, 36, 0.5); }

        .alert-title {
            font-size: 1.25rem;
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

        .alert-close-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 900px) {
            .cor-school-name { font-size: 1.3rem; }
            .hero-banner h1 { font-size: 1.7rem; }
        }

        @media (max-width: 820px) {
            .payment-assessment-wrap { grid-template-columns: 1fr; }
            .signature-box { grid-template-columns: 1fr; gap: 1rem; }
            .student-info-grid { grid-template-columns: 1fr; }
            .student-info-right { align-items: flex-start; text-align: left; }
            .contact-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 700px) {
            .navbar { padding: 12px 4%; }
            .cor-card { padding: 1.75rem 1.25rem; }
            .cor-school-name { font-size: 1.1rem; }
            .hero-banner { padding: 2.5rem 4% 2rem; }
            .hero-banner h1 { font-size: 1.4rem; }
            .course-table { font-size: 0.75rem; }
            .course-table th, .course-table td { padding: 9px 10px; }
            .cor-title { font-size: 0.85rem; letter-spacing: 2px; }
        }

        @media (max-width: 480px) {
            .navbar-brand .brand-text h1 { font-size: 0.95rem; }
            .navbar-brand .brand-text span { font-size: 0.55rem; letter-spacing: 1px; }
            .navbar-brand .logo-box { width: 40px; height: 40px; }
            .user-pill { font-size: 0.72rem; padding: 6px 12px; }
            .btn-logout { font-size: 0.72rem; padding: 6px 14px; }
            .cor-header-top { flex-direction: column; }
            .cor-logo { width: 60px; height: 60px; }
            .cor-card { padding: 1.25rem 1rem; }
            .hero-banner h1 { font-size: 1.2rem; }
            .section-title h2 { font-size: 0.9rem; }
            .contact-card { padding: 1rem; }
            .modal-box, .inquiry-modal-box, .alert-box { padding: 1.5rem; }
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
            <a href="profile-setting.php" class="user-pill" title="Open Profile Settings">
                <i class="fas fa-user-graduate"></i>
                <span><?php echo $username_disp; ?></span>
                <i class="fas fa-cog settings-hint" style="font-size: 0.7rem;"></i>
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
        <div class="eyebrow"><i class="fas fa-file-invoice"></i> Official Document</div>
        <h1>Certificate of <span class="accent">Registration</span></h1>
        <p>Welcome back, <?php echo $full_name; ?>! Here's your official enrollment record for this semester.</p>
    </section>

    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <main class="main-content">
        <div class="cor-wrapper">
            <div class="cor-card">

                <!-- ===== COR HEADER ===== -->
                <div class="cor-header">
                    <div class="cor-header-top">
                        <img src="../../BackGroundimage/ICFLogo.png" alt="ICF Logo" class="cor-logo">
                        <div class="cor-header-text">
                            <div class="cor-school-name">Interworld Colleges Foundation Inc.</div>
                            <div class="cor-address">
                                <i class="fas fa-map-marker-alt"></i>
                                Burgos St., Paniqui, Tarlac · Tel No. (045) 470-8645
                            </div>
                        </div>
                    </div>
                    <div class="cor-title">Certificate of Registration (COR)</div>
                    <div class="cor-session"><?php echo $academicYear; ?> &mdash; 1st Semester</div>
                </div>

                <!-- ===== STUDENT INFO ===== -->
                <div class="student-info-grid">
                    <div class="student-info-left">
                        <div class="info-line"><strong><i class="fas fa-id-card"></i> Student ID:</strong> <span class="highlight"><?php echo $username_disp; ?></span></div>
                        <div class="info-line"><strong><i class="fas fa-user"></i> Name:</strong> <?php echo $lastFirst; ?></div>
                        <div class="info-line"><strong><i class="fas fa-book-open"></i> Course:</strong> <?php echo strtoupper($course . ($major ? ' — ' . $major : '')); ?></div>
                    </div>
                    <div class="student-info-right">
                        <div class="info-line"><strong><i class="fas fa-graduation-cap"></i> Year Level:</strong> <?php echo $yearLevel; ?></div>
                        <div class="info-line"><strong><i class="fas fa-print"></i> Date Printed:</strong> <?php echo $datePrinted; ?></div>
                    </div>
                </div>

                <!-- ===== ENROLLED COURSES ===== -->
                <div class="section-title">
                    <div class="icon-box"><i class="fas fa-list-check"></i></div>
                    <h2>Enrolled Subjects</h2>
                </div>

                <div class="course-table-wrap">
                    <table class="course-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Units</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Block</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($subjects) > 0): ?>
                                <?php foreach ($subjects as $s):
                                    // Prefer separate date/time; fall back to legacy day_time
                                    $sDate = trim($s['date'] ?? '');
                                    $sTime = trim($s['time'] ?? '');
                                    if ($sDate === '' && $sTime === '' && !empty($s['day_time'])) {
                                        $parts = preg_split('/\s+/', trim($s['day_time']));
                                        if (count($parts) >= 2 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $parts[0])) {
                                            $sDate = $parts[0];
                                            $sTime = implode(' ', array_slice($parts, 1));
                                        } else {
                                            $sDate = $s['day_time'];
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['code'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($s['description'] ?? ''); ?></td>
                                        <td><?php echo number_format((float)($s['units'] ?? 0), 1); ?></td>
                                        <td><?php echo htmlspecialchars($sDate); ?></td>
                                        <td><?php echo htmlspecialchars($sTime); ?></td>
                                        <td><?php echo htmlspecialchars($s['room'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($s['block'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="2" style="text-align:right; padding-right:20px;">TOTAL UNITS</td>
                                    <td><?php echo number_format($totalUnits, 1); ?></td>
                                    <td colspan="4" style="text-align:left;">Bridging: <?php echo number_format($bridging, 1); ?> &nbsp;|&nbsp; NSTP: <?php echo number_format($nstpUnits, 1); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="7"><i class="fas fa-info-circle"></i> No enrolled subjects on record yet. Please contact the registrar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ===== PAYMENT & ASSESSMENT ===== -->
                <div class="payment-assessment-wrap">

                    <!-- LEFT: Payment Details -->
                    <div class="payment-assess-card">
                        <div class="card-header"><i class="fas fa-receipt"></i> Payment Details</div>
                        <div class="table-container">
                            <table class="data-table" id="paymentTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Date</th>
                                        <th>O.R. No.</th>
                                        <th style="text-align: right;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentBody">
                                    <?php if (count($payments) > 0): ?>
                                        <?php foreach ($payments as $i => $p): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($p['or_number']); ?></td>
                                                <td style="text-align: right;"><?php echo money($p['amount']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="empty-row">
                                            <td colspan="4"><i class="fas fa-info-circle"></i> No payments recorded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="3" style="text-align: right; font-weight: 700; color: var(--primary-neon); background: rgba(13,245,227,0.08); border-top: 2px solid rgba(13,245,227,0.3);">TOTAL PAID:</td>
                                        <td id="totalPaid" style="text-align: right; font-weight: 800; color: var(--primary-neon); background: rgba(13,245,227,0.08); border-top: 2px solid rgba(13,245,227,0.3);"><?php echo money($totalPaid); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RIGHT: Assessment -->
                    <div class="payment-assess-card">
                        <div class="card-header"><i class="fas fa-calculator"></i> Assessment</div>
                        <div class="table-container">
                            <table class="data-table">
                                <tbody>
                                    <tr class="assessment-item">
                                        <td>Tuition Fee:</td>
                                        <td><?php echo money(parseMoney($assessTuition)); ?></td>
                                    </tr>
                                    <tr class="assessment-item">
                                        <td>Academic:</td>
                                        <td><?php echo money(parseMoney($assessAcademic)); ?></td>
                                    </tr>
                                    <tr class="assessment-item">
                                        <td>Computer:</td>
                                        <td><?php echo money(parseMoney($assessComputer)); ?></td>
                                    </tr>
                                    <tr class="assessment-item">
                                        <td>Misc. Fee:</td>
                                        <td><?php echo money(parseMoney($assessMisc)); ?></td>
                                    </tr>
                                    <tr class="assessment-item">
                                        <td>NSTP:</td>
                                        <td><?php echo money(parseMoney($assessNSTP)); ?></td>
                                    </tr>
                                    <tr class="assessment-item">
                                        <td>Others:</td>
                                        <td><?php echo money(parseMoney($assessOthers)); ?></td>
                                    </tr>
                                    <tr class="assessment-total">
                                        <td>TOTAL:</td>
                                        <td><?php echo money($totalAssessment); ?></td>
                                    </tr>
                                    <tr class="assessment-remaining">
                                        <td>Balance:</td>
                                        <td id="remainingBalance"><?php echo money($remainingBalance); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pay-btn-wrap">
                            <button class="btn-pay-tuition" onclick="openPaymentModal()" <?php echo ($remainingBalance <= 0) ? 'disabled' : ''; ?>>
                                <i class="fas fa-credit-card"></i>
                                <?php echo ($remainingBalance <= 0) ? 'Fully Paid' : 'Pay Tuition'; ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ===== SIGNATURES ===== -->
                <div class="signature-box">
                    <div class="sig-item">
                        <div class="sig-line"></div>
                        <strong>ENGR. CESAR C. GASPAR</strong>
                        <span>Registrar / Authorized Official</span>
                    </div>
                    <div class="sig-item">
                        <div class="sig-line"></div>
                        <strong>Accounting Officer</strong>
                        <span>Finance Department</span>
                    </div>
                    <div class="sig-item">
                        <div class="sig-line"></div>
                        <strong><?php echo $full_name; ?></strong>
                        <span>Student's Signature</span>
                    </div>
                </div>

                <div class="student-copy">( Student's Copy )</div>

                <!-- ===== CONTACT SECTION ===== -->
                <div class="contact-section">
                    <div class="contact-header">
                        <div class="icon-circle"><i class="fas fa-headset"></i></div>
                        <div>
                            <h2>Contact Us / Inquire Us</h2>
                            <p>For additional concerns, our customer service team is here to help you.</p>
                        </div>
                    </div>

                    <div class="contact-grid">
                        <div class="contact-card">
                            <div class="icon-box"><i class="fas fa-envelope-open-text"></i></div>
                            <div class="contact-info">
                                <h4>Email &amp; Landline</h4>
                                <p>Reach us anytime for inquiries regarding enrollment, payments, and student records.</p>
                                <button class="btn-contact" onclick="openInquiryModal()"><i class="fas fa-envelope"></i> Send Message</button>
                            </div>
                        </div>
                        <div class="contact-card">
                            <div class="icon-box"><i class="fas fa-map-marked-alt"></i></div>
                            <div class="contact-info">
                                <h4>Office Address</h4>
                                <p>Visit our registrar or accounting office for in-person assistance.</p>
                                <p style="font-size: 0.78rem; color: var(--primary-neon); margin-bottom: 0;"><i class="fas fa-map-pin"></i> Burgos St., Paniqui, Tarlac</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== INQUIRY MODAL ===== -->
    <div class="inquiry-modal-overlay" id="inquiryModal">
        <div class="inquiry-modal-box">
            <button class="inquiry-close-btn" onclick="closeInquiryModal()">&times;</button>
            <div class="inquiry-modal-header">
                <h2>Inquire Us</h2>
                <p>Send an official message directly to our administrative team.</p>
            </div>

            <form id="inquiryForm" onsubmit="submitInquiry(event)">
                <div class="inquiry-form-group">
                    <label>Full Name</label>
                    <input type="text" id="inqFullName" value="<?php echo $full_name; ?>" required />
                </div>
                <div class="inquiry-form-group">
                    <label>Email Address</label>
                    <input type="email" id="inqEmail" value="<?php echo htmlspecialchars($enrolled['email'] ?? ''); ?>" required />
                </div>
                <div class="inquiry-form-group">
                    <label>Subject</label>
                    <input type="text" id="inqSubject" placeholder="e.g. Admission Inquiry / Prospectus Request" required />
                </div>
                <div class="inquiry-form-group">
                    <label>Concern</label>
                    <textarea id="inqMessage" placeholder="Please describe your message or concern here..." required></textarea>
                </div>

                <button type="submit" class="btn-send-inquiry">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- ===== PAYMENT MODAL ===== -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            <div class="modal-title"><i class="fas fa-mobile-alt"></i> Pay via GCash</div>

            <div class="form-group">
                <label>Payment Purpose</label>
                <select id="paymentPurpose">
                    <option value="Partial Payment">Partial Payment</option>
                    <option value="Full Payment">Full Payment</option>
                </select>
            </div>

            <div class="form-group">
                <label>Amount (₱)</label>
                <input type="number" id="paymentAmount" placeholder="Enter amount" min="500" step="100">
            </div>

            <div class="form-group">
                <label>GCash Mobile Number</label>
                <input type="tel" id="gcashNumber" placeholder="0917-123-4567" maxlength="13" pattern="[0-9]{4}-[0-9]{3}-[0-9]{4}" oninput="formatGCashNumber(this)">
            </div>

            <button class="btn-pay-confirm" id="confirmPayBtn" onclick="processPayment()">
                <i class="fas fa-check-circle"></i> Confirm Payment
            </button>
        </div>
    </div>

    <!-- ===== CUSTOM ALERT ===== -->
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
           STATE (mirrors PHP values)
           ============================================================ */
        let totalPaid = <?php echo json_encode(round($totalPaid, 2)); ?>;
        let remainingBalance = <?php echo json_encode(round($remainingBalance, 2)); ?>;
        let paymentCounter = <?php echo json_encode(count($payments) + 1); ?>;
        const studentCode = <?php echo json_encode($username); ?>;
        const currentDate = new Date().toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric'
        });

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
           GCASH INPUT FORMATTER
           ============================================================ */
        function formatGCashNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            let f = '';
            if (value.length > 0) f += value.substring(0, 4);
            if (value.length >= 5) f += '-' + value.substring(4, 7);
            if (value.length >= 8) f += '-' + value.substring(7, 11);
            input.value = f;
        }

        /* ============================================================
           PAYMENT MODAL
           ============================================================ */
        function openPaymentModal() {
            if (remainingBalance <= 0) {
                showAlert('success', 'Fully Paid', 'You have no remaining balance.');
                return;
            }
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentAmount').placeholder = 'Max: ₱ ' + remainingBalance.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
            document.getElementById('gcashNumber').value = '';
            document.getElementById('paymentModal').classList.add('active');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }

        /* ============================================================
           PROCESS PAYMENT — saves to DB via AJAX
           ============================================================ */
        async function processPayment() {
            const purpose = document.getElementById('paymentPurpose').value;
            const amountInput = document.getElementById('paymentAmount').value;
            const gcashNum = document.getElementById('gcashNumber').value;

            if (!amountInput || amountInput < 500) {
                showAlert('warning', 'Invalid Amount', 'Please enter a valid amount (minimum ₱500).');
                return;
            }
            const gcashRegex = /^[0-9]{4}-[0-9]{3}-[0-9]{4}$/;
            if (!gcashRegex.test(gcashNum)) {
                showAlert('warning', 'Invalid Mobile Number', 'Please enter a valid GCash number in the format: 0917-123-4567');
                return;
            }

            let amount = parseFloat(amountInput);
            if (purpose === 'Full Payment') amount = remainingBalance;
            if (amount > remainingBalance) {
                showAlert('warning', 'Insufficient Balance', 'Amount exceeds the remaining balance. Please enter a valid amount.');
                return;
            }

            const btn = document.getElementById('confirmPayBtn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

            try {
                const fd = new FormData();
                fd.append('save_payment', '1');
                fd.append('student_code', studentCode);
                fd.append('amount', amount);
                fd.append('purpose', purpose);
                fd.append('gcash_number', gcashNum);

                const res = await fetch('profile.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (!data.success) {
                    showAlert('warning', 'Payment Failed', data.message || 'Something went wrong.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    return;
                }

                // Update local state
                totalPaid += amount;
                remainingBalance -= amount;

                document.getElementById('totalPaid').textContent = '₱ ' + totalPaid.toLocaleString('en-US', {
                    minimumFractionDigits: 2
                });
                document.getElementById('remainingBalance').textContent = '₱ ' + remainingBalance.toLocaleString('en-US', {
                    minimumFraction