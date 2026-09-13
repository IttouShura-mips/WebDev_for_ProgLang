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
function parseMoney($v) {
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
function money($n) { return '₱ ' . number_format((float)$n, 2); }

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

        /* ===== NAVBAR ===== */
        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-teal);
            padding: 16px 100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
            flex-wrap: wrap;
            gap: 10px;
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; font-weight: 800; color: var(--text-high-contrast); text-decoration: none; }
        .navbar-brand img { width: 45px; height: 45px; object-fit: contain; }
        .navbar-brand .brand-text { display: flex; flex-direction: column; line-height: 1.5; }
        .navbar-brand .brand-text .school-title { font-size: 20px; font-weight: 800; color: var(--text-high-contrast); text-transform: uppercase; letter-spacing: 0.5px; }
        .navbar-brand .brand-text .school-subtitle { font-size: 13px; color: var(--primary-neon); font-weight: 400; letter-spacing: 0.5px; text-transform: uppercase; }
        .navbar-actions { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .user-pill { background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); padding: 8px 16px; border-radius: 50px; font-size: 14px; color: var(--text-muted-teal); display: flex; align-items: center; gap: 8px; }
        .user-pill i { color: var(--primary-neon); }
        .btn-logout { background: transparent; border: 1px solid #ff6b6b; color: #ff6b6b; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-size: 14px; font-weight: 600; transition: var(--transition-smooth); display: flex; align-items: center; gap: 6px; }
        .btn-logout:hover { background: rgba(255,107,107,0.1); box-shadow: 0 0 15px rgba(255,107,107,0.3); }

        /* ===== MAIN ===== */
        .main-content { flex: 1; display: flex; justify-content: center; align-items: flex-start; padding: 40px 20px; }
        .profile-card { background-color: var(--bg-card); border: 1px solid var(--border-teal); border-radius: 20px; padding: 40px 35px; width: 100%; max-width: 1100px; box-shadow: 0 0 30px rgba(13,245,227,0.15); transition: var(--transition-smooth); margin-bottom: 40px; }
        .profile-card:hover { box-shadow: 0 0 40px rgba(13,245,227,0.25); border-color: rgba(13,245,227,0.3); }

        /* ===== HEADER ===== */
        .cor-header { display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid var(--border-teal); }
        .cor-header-top { display: flex; align-items: center; justify-content: center; text-align: center; gap: 15px; margin-bottom: 10px; }
        .cor-header-text { display: flex; flex-direction: column; align-items: center; }
        .cor-logo { width: 80px; height: 80px; object-fit: contain; }
        .cor-school-name { font-size: 28px; font-weight: 800; color: var(--text-high-contrast); text-transform: uppercase; letter-spacing: 1px; line-height: 1.2; }
        .cor-address { font-size: 13px; color: var(--text-muted-teal); margin-top: 4px; display: flex; align-items: center; gap: 6px; text-align: center; }
        .cor-address i { color: var(--primary-neon); }
        .cor-title { font-size: 22px; font-weight: 700; color: var(--primary-neon); text-transform: uppercase; letter-spacing: 2px; margin-top: 10px; border-bottom: 1px solid var(--border-teal); padding-bottom: 5px; }
        .cor-session { font-size: 14px; color: var(--text-muted-teal); margin-top: 5px; font-style: italic; }

        /* ===== STUDENT INFO ===== */
        .student-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 12px; padding: 20px; }
        .student-info-left, .student-info-right { display: flex; flex-direction: column; gap: 10px; }
        .student-info-right { align-items: flex-end; text-align: right; }
        .info-line { font-size: 14px; color: var(--text-muted-teal); }
        .info-line strong { color: var(--text-high-contrast); font-weight: 600; }
        .info-line strong i { color: var(--primary-neon); margin-right: 5px; }
        .info-line .highlight { color: var(--primary-neon); font-weight: 700; }

        /* ===== COURSE TABLE ===== */
        .course-table { width: 100%; border-collapse: collapse; font-size: 13px; background: var(--bg-deep-abyss); border-radius: 12px; overflow: hidden; margin-bottom: 20px; border: 1px solid var(--border-teal); }
        .course-table th { background: #0e1f36; color: var(--primary-neon); font-weight: 600; padding: 10px 8px; text-align: left; border-bottom: 2px solid var(--border-teal); }
        .course-table td { padding: 10px 8px; border-bottom: 1px solid var(--border-teal); color: var(--text-high-contrast); }
        .course-table tr:last-child td { border-bottom: none; }
        .course-table .total-row td { background: #0e1f36; font-weight: 700; color: var(--primary-neon); border-top: 2px solid var(--border-teal); }
        .course-table .empty-row td { text-align: center; color: var(--text-muted-teal); font-style: italic; padding: 25px 10px; }

        /* ===== PAYMENT & ASSESSMENT ===== */
        .payment-assessment-wrap { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; margin-bottom: 20px; }
        .payment-assess-card { background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 12px; overflow: hidden; }
        .card-header { background: #0e1f36; padding: 10px 15px; font-size: 14px; font-weight: 700; color: var(--primary-neon); text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid var(--primary-neon); display: flex; align-items: center; gap: 8px; }
        .card-header i { font-size: 16px; }
        .table-container { padding: 0; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th, .data-table td { padding: 8px 15px; text-align: left; border-bottom: 1px solid var(--border-teal); }
        .data-table th { color: var(--text-muted-teal); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .data-table td { color: var(--text-high-contrast); font-size: 13px; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table .assessment-item td:last-child { text-align: right; font-weight: 600; }
        .data-table .assessment-total td { background: #0e1f36; font-weight: 800; color: var(--primary-neon); font-size: 15px; border-top: 2px solid var(--primary-neon); }
        .data-table .assessment-total td:last-child { text-align: right; font-size: 18px; }
        .data-table .assessment-remaining td { background: var(--bg-card-hover); color: var(--text-high-contrast); font-weight: 800; font-size: 15px; border-top: 2px solid var(--primary-neon); }
        .data-table .assessment-remaining td:last-child { text-align: right; font-size: 18px; }
        .data-table .empty-row td { text-align: center; color: var(--text-muted-teal); font-style: italic; padding: 20px; }

        /* ===== PAY BUTTON ===== */
        .btn-pay-tuition { display: block; width: 100%; margin-top: 15px; padding: 14px; background: linear-gradient(135deg, var(--primary-neon), #00cbb9); color: var(--bg-deep-abyss); border: none; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; transition: var(--transition-smooth); text-align: center; box-shadow: 0 4px 15px rgba(13,245,227,0.3); }
        .btn-pay-tuition:hover { transform: translateY(-3px); box-shadow: var(--neon-glow); }
        .btn-pay-tuition:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* ===== SIGNATURES ===== */
        .signature-box { margin-top: 20px; padding: 20px; background: var(--bg-deep-abyss); border: 1px dashed var(--border-teal); border-radius: 12px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; align-items: end; text-align: center; }
        .sig-item { display: flex; flex-direction: column; gap: 5px; }
        .sig-line { width: 150px; border-bottom: 1px solid var(--text-muted-teal); margin: 0 auto 5px auto; }
        .sig-item strong { color: var(--text-high-contrast); font-size: 14px; }
        .sig-item span { color: var(--text-muted-teal); font-size: 12px; }
        .student-copy { font-size: 12px; color: var(--text-muted-teal); margin-top: 15px; text-align: center; font-style: italic; }

        /* ===== CONTACT ===== */
        .contact-section { margin-top: 40px; background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 16px; padding: 30px; }
        .contact-header { display: flex; align-items: center; gap: 12px; margin-bottom: 25px; }
        .contact-header i { font-size: 24px; color: var(--primary-neon); background: rgba(13,245,227,0.1); padding: 12px; border-radius: 50%; }
        .contact-header h2 { font-size: 20px; font-weight: 800; color: var(--text-high-contrast); }
        .contact-header p { font-size: 13px; color: var(--text-muted-teal); margin-top: 2px; }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .contact-card { background: var(--bg-card); border: 1px solid var(--border-teal); border-radius: 12px; padding: 20px; display: flex; gap: 15px; align-items: flex-start; transition: var(--transition-smooth); }
        .contact-card:hover { border-color: rgba(13,245,227,0.3); transform: translateY(-2px); }
        .contact-card .icon-box { background: rgba(13,245,227,0.1); border: 1px solid rgba(13,245,227,0.2); border-radius: 10px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .contact-card .icon-box i { font-size: 18px; color: var(--primary-neon); }
        .contact-card .contact-info h4 { font-size: 15px; font-weight: 700; color: var(--text-high-contrast); margin-bottom: 6px; }
        .contact-card .contact-info p { font-size: 13px; color: var(--text-muted-teal); margin-bottom: 10px; line-height: 1.5; }
        .btn-contact { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, var(--primary-neon), #00cbb9); color: var(--bg-deep-abyss); border: none; padding: 8px 18px; border-radius: 25px; font-weight: 700; font-size: 13px; cursor: pointer; transition: var(--transition-smooth); text-decoration: none; }
        .btn-contact:hover { transform: scale(1.05); box-shadow: var(--neon-glow); }

        /* ===== MODALS ===== */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,12,27,0.9); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: center; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--bg-card); border: 1px solid var(--primary-neon); border-radius: 20px; padding: 30px; max-width: 450px; width: 100%; box-shadow: 0 0 40px rgba(13,245,227,0.3); position: relative; animation: modalFadeIn 0.3s ease; }
        @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-20px) scale(0.95);} to { opacity: 1; transform: translateY(0) scale(1);} }
        .modal-close { position: absolute; top: 15px; right: 15px; background: transparent; border: none; color: var(--text-muted-teal); font-size: 24px; cursor: pointer; }
        .modal-close:hover { color: #ff6b6b; transform: rotate(90deg); }
        .modal-title { font-size: 20px; font-weight: 800; color: var(--primary-neon); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; color: var(--text-muted-teal); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 12px; background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 10px; color: var(--text-high-contrast); font-size: 14px; font-family: 'Inter', sans-serif; outline: none; transition: var(--transition-smooth); }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus { border-color: var(--primary-neon); box-shadow: 0 0 10px rgba(13,245,227,0.2); }
        .btn-pay-confirm { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary-neon), #00cbb9); color: var(--bg-deep-abyss); border: none; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; transition: var(--transition-smooth); margin-top: 10px; }
        .btn-pay-confirm:hover { transform: translateY(-2px); box-shadow: var(--neon-glow); }

        /* ===== INQUIRY MODAL ===== */
        .inquiry-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,12,27,0.95); backdrop-filter: blur(8px); z-index: 3000; justify-content: center; align-items: center; padding: 20px; }
        .inquiry-modal-overlay.active { display: flex; }
        .inquiry-modal-box { background: var(--bg-card); border: 1px solid var(--border-teal); border-radius: 20px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.8); position: relative; animation: modalFadeIn 0.3s ease; padding: 30px; color: var(--text-high-contrast); }
        .inquiry-modal-header { margin-bottom: 20px; }
        .inquiry-modal-header h2 { font-size: 28px; font-weight: 800; color: var(--text-high-contrast); margin-bottom: 8px; }
        .inquiry-modal-header p { font-size: 14px; color: var(--text-muted-teal); }
        .inquiry-close-btn { position: absolute; top: 15px; right: 15px; background: transparent; border: none; color: var(--text-muted-teal); font-size: 24px; cursor: pointer; }
        .inquiry-close-btn:hover { color: #ff6b6b; transform: rotate(90deg); }
        .inquiry-form-group { margin-bottom: 15px; }
        .inquiry-form-group label { display: block; font-size: 13px; color: var(--text-muted-teal); margin-bottom: 6px; font-weight: 600; }
        .inquiry-form-group input, .inquiry-form-group textarea { width: 100%; padding: 12px; background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 8px; color: var(--text-high-contrast); font-size: 14px; font-family: 'Inter', sans-serif; outline: none; }
        .inquiry-form-group textarea { resize: vertical; min-height: 120px; }
        .btn-send-inquiry { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary-neon), #00cbb9); color: var(--bg-deep-abyss); border: none; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; margin-top: 10px; }

        /* ===== ALERT MODAL ===== */
        .alert-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,12,27,0.95); backdrop-filter: blur(8px); z-index: 2000; justify-content: center; align-items: center; padding: 20px; }
        .alert-overlay.active { display: flex; }
        .alert-box { background: var(--bg-card); border-radius: 20px; padding: 30px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 0 40px rgba(13,245,227,0.3); animation: modalFadeIn 0.3s ease; border: 1px solid var(--border-teal); }
        .alert-box.success { border-color: #10b981; box-shadow: 0 0 40px rgba(16,185,129,0.3); }
        .alert-box.warning { border-color: #fbbf24; box-shadow: 0 0 40px rgba(251,191,36,0.3); }
        .alert-icon { font-size: 50px; margin-bottom: 15px; }
        .alert-box.success .alert-icon { color: #10b981; }
        .alert-box.warning .alert-icon { color: #fbbf24; }
        .alert-title { font-size: 20px; font-weight: 800; color: var(--text-high-contrast); margin-bottom: 10px; }
        .alert-message { font-size: 14px; color: var(--text-muted-teal); line-height: 1.6; margin-bottom: 20px; white-space: pre-line; }
        .alert-close-btn { padding: 10px 30px; border: none; border-radius: 25px; font-weight: 700; font-size: 14px; cursor: pointer; }
        .alert-box.success .alert-close-btn { background: #10b981; color: var(--bg-deep-abyss); }
        .alert-box.warning .alert-close-btn { background: #fbbf24; color: var(--bg-deep-abyss); }

        @media (max-width: 820px) {
            .payment-assessment-wrap { grid-template-columns: 1fr; }
            .signature-box { grid-template-columns: 1fr; }
            .student-info-grid { grid-template-columns: 1fr; }
            .student-info-right { align-items: flex-start; text-align: left; }
            .contact-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .navbar { padding: 14px 20px; }
            .profile-card { padding: 25px 18px; }
            .cor-school-name { font-size: 22px; }
            .course-table { font-size: 12px; }
        }
        @media (max-width: 480px) {
            .cor-header-top { flex-direction: column; }
            .cor-logo { width: 60px; height: 60px; }
            .navbar-brand .brand-text .school-title { font-size: 14px; }
            .navbar-brand img { width: 35px; height: 35px; }
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="../../BackGroundimage/ICFLogo.png" alt="School Logo">
            <div class="brand-text">
                <span class="school-title">ICF</span>
                <span class="school-subtitle">Interworld Colleges Foundation Inc.</span>
            </div>
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

            <!-- ===== HEADER ===== -->
            <div class="cor-header">
                <div class="cor-header-top">
                    <img src="../../BackGroundimage/ICFLogo.png" alt="School Logo" class="cor-logo">
                    <div class="cor-header-text">
                        <div class="cor-school-name">Interworld Colleges Foundation Inc.</div>
                        <div class="cor-address"><i class="fas fa-map-marker-alt"></i> Burgos St., Paniqui, Tarlac · Tel No. (045) 470-8645</div>
                    </div>
                </div>
                <div class="cor-title">Certificate of Registration (COR)</div>
                <div class="cor-session"><?php echo $academicYear; ?> — 1st Semester</div>
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

            <!-- ===== ENROLLED COURSES TABLE ===== -->
            <table class="course-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Units</th>
                        <th>Day/Time</th>
                        <th>Room</th>
                        <th>Block</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($subjects) > 0): ?>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['code'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($s['description'] ?? ''); ?></td>
                                <td><?php echo number_format((float)($s['units'] ?? 0), 1); ?></td>
                                <td><?php echo htmlspecialchars($s['day_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($s['room'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($s['block'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2" style="text-align:right; padding-right:20px;">TOTAL UNITS</td>
                            <td><?php echo number_format($totalUnits, 1); ?></td>
                            <td colspan="3" style="text-align:left;">Bridging Subjects: <?php echo number_format($bridging, 1); ?> | NSTP: <?php echo number_format($nstpUnits, 1); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="6"><i class="fas fa-info-circle"></i> No enrolled subjects on record yet. Please contact the registrar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ===== PAYMENT DETAILS & ASSESSMENT ===== -->
            <div class="payment-assessment-wrap">

                <!-- LEFT: Payment Details -->
                <div class="payment-assess-card">
                    <div class="card-header"><i class="fas fa-receipt"></i> Payment Details</div>
                    <div class="table-container">
                        <table class="data-table" id="paymentTable">
                            <thead>
                                <tr>
                                    <th style="width: 30px;">#</th>
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
                                    <tr class="empty-row"><td colspan="4"><i class="fas fa-info-circle"></i> No payments recorded yet.</td></tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: 700; color: var(--primary-neon); background: #0e1f36; border-top: 2px solid var(--primary-neon);">TOTAL PAID:</td>
                                    <td id="totalPaid" style="text-align: right; font-weight: 800; color: var(--primary-neon); background: #0e1f36; border-top: 2px solid var(--primary-neon);"><?php echo money($totalPaid); ?></td>
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
                                <tr class="assessment-item"><td>Tuition Fee:</td><td><?php echo money(parseMoney($assessTuition)); ?></td></tr>
                                <tr class="assessment-item"><td>Academic:</td><td><?php echo money(parseMoney($assessAcademic)); ?></td></tr>
                                <tr class="assessment-item"><td>Computer:</td><td><?php echo money(parseMoney($assessComputer)); ?></td></tr>
                                <tr class="assessment-item"><td>Misc. Fee:</td><td><?php echo money(parseMoney($assessMisc)); ?></td></tr>
                                <tr class="assessment-item"><td>NSTP:</td><td><?php echo money(parseMoney($assessNSTP)); ?></td></tr>
                                <tr class="assessment-item"><td>Others:</td><td><?php echo money(parseMoney($assessOthers)); ?></td></tr>
                                <tr class="assessment-total"><td>TOTAL:</td><td><?php echo money($totalAssessment); ?></td></tr>
                                <tr class="assessment-remaining"><td>Balance:</td><td id="remainingBalance"><?php echo money($remainingBalance); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 15px;">
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
                    <div class="sig-line" style="border-bottom: none;"></div>
                    <strong>Accounting Officer</strong>
                </div>
                <div class="sig-item">
                    <div class="sig-line"></div>
                    <strong><?php echo $full_name; ?></strong>
                    <span>Student's Signature</span>
                </div>
            </div>

            <div class="student-copy">( Student's Copy )</div>

            <!-- ===== CONTACT US ===== -->
            <div class="contact-section">
                <div class="contact-header">
                    <i class="fas fa-headset"></i>
                    <div>
                        <h2>Contact Us / Inquire Us</h2>
                        <p>For additional concerns, our customer service team is here to help you.</p>
                    </div>
                </div>

                <div class="contact-grid">
                    <div class="contact-card">
                        <div class="icon-box"><i class="fas fa-envelope-open-text"></i></div>
                        <div class="contact-info">
                            <h4>Email & Landline</h4>
                            <p>Reach us anytime for inquiries regarding enrollment, payments, and student records.</p>
                            <button class="btn-contact" onclick="openInquiryModal()"><i class="fas fa-envelope"></i> Send Message</button>
                        </div>
                    </div>
                    <div class="contact-card">
                        <div class="icon-box"><i class="fas fa-map-marked-alt"></i></div>
                        <div class="contact-info">
                            <h4>Office Address</h4>
                            <p>Visit our registrar or accounting office for in-person assistance.</p>
                            <p style="font-size: 12px; color: var(--primary-neon);"><i class="fas fa-map-pin"></i> Burgos St., Paniqui, Tarlac</p>
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
        const currentDate = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

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
            if (type === 'success') { alertBox.classList.add('success'); alertIcon.innerHTML = '<i class="fas fa-check-circle"></i>'; }
            else { alertBox.classList.add('warning'); alertIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>'; }

            alertTitle.textContent = title;
            alertMessage.textContent = message;
            alertModal.classList.add('active');
        }
        function closeAlert() { document.getElementById('alertModal').classList.remove('active'); }

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
            if (remainingBalance <= 0) { showAlert('success', 'Fully Paid', 'You have no remaining balance.'); return; }
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentAmount').placeholder = 'Max: ₱ ' + remainingBalance.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.getElementById('gcashNumber').value = '';
            document.getElementById('paymentModal').classList.add('active');
        }
        function closePaymentModal() { document.getElementById('paymentModal').classList.remove('active'); }

        /* ============================================================
           PROCESS PAYMENT — saves to DB via AJAX
           ============================================================ */
        async function processPayment() {
            const purpose = document.getElementById('paymentPurpose').value;
            const amountInput = document.getElementById('paymentAmount').value;
            const gcashNum = document.getElementById('gcashNumber').value;

            if (!amountInput || amountInput < 500) { showAlert('warning', 'Invalid Amount', 'Please enter a valid amount (minimum ₱500).'); return; }
            const gcashRegex = /^[0-9]{4}-[0-9]{3}-[0-9]{4}$/;
            if (!gcashRegex.test(gcashNum)) { showAlert('warning', 'Invalid Mobile Number', 'Please enter a valid GCash number in the format: 0917-123-4567'); return; }

            let amount = parseFloat(amountInput);
            if (purpose === 'Full Payment') amount = remainingBalance;
            if (amount > remainingBalance) { showAlert('warning', 'Insufficient Balance', 'Amount exceeds the remaining balance. Please enter a valid amount.'); return; }

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

                const res = await fetch('profile.php', { method: 'POST', body: fd });
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

                document.getElementById('totalPaid').textContent = '₱ ' + totalPaid.toLocaleString('en-US', { minimumFractionDigits: 2 });
                document.getElementById('remainingBalance').textContent = '₱ ' + remainingBalance.toLocaleString('en-US', { minimumFractionDigits: 2 });

                const paymentBody = document.getElementById('paymentBody');
                const emptyRow = paymentBody.querySelector('.empty-row');
                if (emptyRow) emptyRow.remove();

                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>${paymentCounter}</td>
                    <td>${currentDate}</td>
                    <td>${data.or_number}</td>
                    <td style="text-align: right;">₱ ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>`;

                const totalRow = paymentBody.lastElementChild;
                paymentBody.insertBefore(newRow, totalRow);

                paymentCounter++;

                if (remainingBalance <= 0) {
                    const payBtn = document.querySelector('.btn-pay-tuition');
                    if (payBtn) {
                        payBtn.disabled = true;
                        payBtn.innerHTML = '<i class="fas fa-check-circle"></i> Fully Paid';
                    }
                }

                showAlert('success', 'Payment Successful!',
                    'Payment via GCash (' + gcashNum + ') completed.\nPurpose: ' + purpose +
                    '\nAmount: ₱ ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2}) +
                    '\nO.R. No: ' + data.or_number);

                closePaymentModal();
            } catch (err) {
                console.error(err);
                showAlert('warning', 'Error', 'Failed to reach the server. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        /* ============================================================
           INQUIRY MODAL — sends to submit_inquiry.php
           ============================================================ */
        function openInquiryModal() { document.getElementById('inquiryModal').classList.add('active'); }
        function closeInquiryModal() { document.getElementById('inquiryModal').classList.remove('active'); }

        async function submitInquiry(e) {
            e.preventDefault();
            const fullname = document.getElementById('inqFullName').value.trim();
            const email = document.getElementById('inqEmail').value.trim();
            const subject = document.getElementById('inqSubject').value.trim();
            const concern = document.getElementById('inqMessage').value.trim();

            try {
                const fd = new FormData();
                fd.append('fullname', fullname);
                fd.append('email', email);
                fd.append('subject', subject);
                fd.append('concern', concern);

                const res = await fetch('../../backend/AdminDB/submit_inquiry.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    closeInquiryModal();
                    document.getElementById('inquiryForm').reset();
                    showAlert('success', 'Message Sent!', 'Your inquiry has been submitted. Our team will respond shortly.');
                } else {
                    showAlert('warning', 'Submission Failed', data.message || 'Please try again.');
                }
            } catch (err) {
                console.error(err);
                showAlert('warning', 'Error', 'Failed to submit your inquiry. Please check your connection.');
            }
        }
    </script>
</body>
</html>