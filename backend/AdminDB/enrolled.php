<?php
/* =====================================================
   TEMPORARY DEBUG — remove the two ini_set lines after
   everything works to avoid exposing errors in production
   ===================================================== */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

/* =====================================================
   SAFE QUERY WRAPPER
   ===================================================== */
function safeQuery($conn, $sql) {
    try {
        return $conn->query($sql);
    } catch (Throwable $e) {
        error_log("safeQuery failed: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/* =====================================================
   ENSURE `enrolled` TABLE EXISTS (minimal safe schema)
   ===================================================== */
safeQuery($conn, "CREATE TABLE IF NOT EXISTS enrolled (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) DEFAULT NULL,
    firstname VARCHAR(100) DEFAULT '',
    middlename VARCHAR(100) DEFAULT '',
    lastname VARCHAR(100) DEFAULT '',
    suffix VARCHAR(20) DEFAULT '',
    gender VARCHAR(20) DEFAULT '',
    birthday DATE DEFAULT NULL,
    birthplace VARCHAR(150) DEFAULT '',
    citizenship VARCHAR(50) DEFAULT '',
    civilstatus VARCHAR(30) DEFAULT '',
    employment VARCHAR(50) DEFAULT '',
    mother VARCHAR(150) DEFAULT '',
    mphone_number VARCHAR(30) DEFAULT '',
    father VARCHAR(150) DEFAULT '',
    fphone_number VARCHAR(30) DEFAULT '',
    guardian VARCHAR(150) DEFAULT '',
    gphone_number VARCHAR(30) DEFAULT '',
    course VARCHAR(150) DEFAULT '',
    major VARCHAR(150) DEFAULT '',
    school_address VARCHAR(255) DEFAULT '',
    academic_year VARCHAR(50) DEFAULT '',
    scholarship VARCHAR(100) DEFAULT '',
    full_address VARCHAR(255) DEFAULT '',
    mobile_number VARCHAR(30) DEFAULT '',
    email VARCHAR(150) DEFAULT '',
    year_level VARCHAR(50) DEFAULT '',
    block VARCHAR(50) DEFAULT '',
    ip_address VARCHAR(64) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'offline',
    last_login DATETIME DEFAULT NULL,
    enrollment_status VARCHAR(20) DEFAULT 'pending',
    requirements_files LONGTEXT DEFAULT NULL,
    subjects_json LONGTEXT DEFAULT NULL,
    assessment_json LONGTEXT DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* =====================================================
   DEFENSIVE COLUMN MIGRATION for `enrolled`
   ===================================================== */
$enrolledRequired = [
    'student_code'       => "VARCHAR(20) DEFAULT NULL",
    'firstname'          => "VARCHAR(100) DEFAULT ''",
    'middlename'         => "VARCHAR(100) DEFAULT ''",
    'lastname'           => "VARCHAR(100) DEFAULT ''",
    'suffix'             => "VARCHAR(20) DEFAULT ''",
    'gender'             => "VARCHAR(20) DEFAULT ''",
    'birthday'           => "DATE DEFAULT NULL",
    'birthplace'         => "VARCHAR(150) DEFAULT ''",
    'citizenship'        => "VARCHAR(50) DEFAULT ''",
    'civilstatus'        => "VARCHAR(30) DEFAULT ''",
    'employment'         => "VARCHAR(50) DEFAULT ''",
    'mother'             => "VARCHAR(150) DEFAULT ''",
    'mphone_number'      => "VARCHAR(30) DEFAULT ''",
    'father'             => "VARCHAR(150) DEFAULT ''",
    'fphone_number'      => "VARCHAR(30) DEFAULT ''",
    'guardian'           => "VARCHAR(150) DEFAULT ''",
    'gphone_number'      => "VARCHAR(30) DEFAULT ''",
    'course'             => "VARCHAR(150) DEFAULT ''",
    'major'              => "VARCHAR(150) DEFAULT ''",
    'school_address'     => "VARCHAR(255) DEFAULT ''",
    'academic_year'      => "VARCHAR(50) DEFAULT ''",
    'scholarship'        => "VARCHAR(100) DEFAULT ''",
    'full_address'       => "VARCHAR(255) DEFAULT ''",
    'mobile_number'      => "VARCHAR(30) DEFAULT ''",
    'email'              => "VARCHAR(150) DEFAULT ''",
    'year_level'         => "VARCHAR(50) DEFAULT ''",
    'block'              => "VARCHAR(50) DEFAULT ''",
    'enrollment_status'  => "VARCHAR(20) DEFAULT 'pending'",
    'requirements_files' => "LONGTEXT DEFAULT NULL",
    'subjects_json'      => "LONGTEXT DEFAULT NULL",
    'assessment_json'    => "LONGTEXT DEFAULT NULL",
    'created_at'         => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
];
foreach ($enrolledRequired as $col => $def) {
    $safeCol = $conn->real_escape_string($col);
    $check = safeQuery($conn, "SHOW COLUMNS FROM enrolled LIKE '{$safeCol}'");
    if ($check && $check->num_rows === 0) {
        safeQuery($conn, "ALTER TABLE enrolled ADD COLUMN `{$col}` {$def}");
    }
}

/* =====================================================
   ENSURE student_payments TABLE EXISTS
   ===================================================== */
safeQuery($conn, "CREATE TABLE IF NOT EXISTS student_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL DEFAULT '',
    payment_date DATE NOT NULL DEFAULT '2000-01-01',
    or_number VARCHAR(30) NOT NULL DEFAULT '',
    purpose VARCHAR(50) NOT NULL DEFAULT 'Partial Payment',
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    gcash_number VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_code (student_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* Defensive migration for student_payments columns */
$paymentRequired = [
    'student_code' => "VARCHAR(20) NOT NULL DEFAULT ''",
    'payment_date' => "DATE NOT NULL DEFAULT '2000-01-01'",
    'or_number'    => "VARCHAR(30) NOT NULL DEFAULT ''",
    'purpose'      => "VARCHAR(50) NOT NULL DEFAULT 'Partial Payment'",
    'amount'       => "DECIMAL(12,2) NOT NULL DEFAULT 0",
    'gcash_number' => "VARCHAR(20) DEFAULT NULL",
    'created_at'   => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
];
foreach ($paymentRequired as $col => $def) {
    $safeCol = $conn->real_escape_string($col);
    $check = safeQuery($conn, "SHOW COLUMNS FROM student_payments LIKE '{$safeCol}'");
    if ($check && $check->num_rows === 0) {
        safeQuery($conn, "ALTER TABLE student_payments ADD COLUMN `{$col}` {$def}");
    }
}

/* =====================================================
   HELPER: parse formatted money strings
   ===================================================== */
function parseMoney($v) {
    if ($v === null || $v === '') return 0.0;
    $clean = preg_replace('/[^0-9.\-]/', '', (string)$v);
    return (float)$clean;
}

/* =====================================================
   FETCH ALL ENROLLED STUDENTS + DERIVED DATA
   ===================================================== */
$students = [];
$res = safeQuery($conn, "SELECT * FROM enrolled ORDER BY student_id DESC");

if ($res) {
    while ($row = $res->fetch_assoc()) {

        /* Parse subjects JSON */
        $subjects = [];
        if (!empty($row['subjects_json'])) {
            $d = json_decode($row['subjects_json'], true);
            if (is_array($d)) $subjects = $d;
        }

        /* Parse assessment JSON */
        $assessment = [];
        if (!empty($row['assessment_json'])) {
            $d = json_decode($row['assessment_json'], true);
            if (is_array($d)) $assessment = $d;
        }

        /* Units totals */
        $totalUnits    = 0.0;
        $bridgingUnits = 0.0;
        $nstpUnits     = 0.0;
        foreach ($subjects as $s) {
            $u = (float)($s['units'] ?? 0);
            $totalUnits += $u;
            $code = strtoupper($s['code'] ?? '');
            if (strpos($code, 'BRIDGING') !== false) $bridgingUnits += $u;
            if (strpos($code, 'NSTP') !== false)     $nstpUnits += $u;
        }

        /* Total assessment */
        $totalAssessment = parseMoney($assessment['total'] ?? 0);
        if ($totalAssessment <= 0) {
            $totalAssessment = parseMoney($assessment['tuition_fee'] ?? 0)
                             + parseMoney($assessment['academic'] ?? 0)
                             + parseMoney($assessment['computer'] ?? 0)
                             + parseMoney($assessment['misc_fee'] ?? 0)
                             + parseMoney($assessment['nstp'] ?? 0)
                             + parseMoney($assessment['others'] ?? 0);
        }

        /* Fetch payments safely */
        $payments  = [];
        $totalPaid = 0.0;
        $studentCode = trim($row['student_code'] ?? '');

        if ($studentCode !== '') {
            try {
                $stmt = $conn->prepare("SELECT * FROM student_payments WHERE student_code = ? ORDER BY id ASC");
                if ($stmt) {
                    $stmt->bind_param("s", $studentCode);
                    $stmt->execute();
                    $pr = $stmt->get_result();
                    while ($p = $pr->fetch_assoc()) {
                        $p['payment_date'] = $p['payment_date'] ?? null;
                        $p['or_number']    = $p['or_number']    ?? '';
                        $p['purpose']      = $p['purpose']      ?? 'Payment';
                        $p['amount']       = $p['amount']       ?? 0;
                        $p['gcash_number'] = $p['gcash_number'] ?? '';
                        $payments[] = $p;
                        $totalPaid += (float)$p['amount'];
                    }
                    $stmt->close();
                }
            } catch (Throwable $e) {
                /* silently skip */
            }
        }

        /* Realtime balance */
        $balance = max(0, $totalAssessment - $totalPaid);

        /* Attach derived data */
        $row['parsed_subjects']   = $subjects;
        $row['parsed_assessment'] = $assessment;
        $row['payments_list']     = $payments;
        $row['total_units']       = $totalUnits;
        $row['bridging_units']    = $bridgingUnits;
        $row['nstp_units']        = $nstpUnits;
        $row['total_assessment']  = $totalAssessment;
        $row['total_paid']        = $totalPaid;
        $row['balance']           = $balance;

        $students[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Enrolled Students | ICF Interworld Colleges Foundation Inc.</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
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
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { display: flex; background-color: #0b1120; color: var(--text-high-contrast); min-height: 100vh; }

    .sidebar {
      background: linear-gradient(180deg, #071f30 0%, #020c1b 100%);
      width: var(--sidebar-collapsed); display: flex; flex-direction: column;
      position: fixed; height: 100vh; left: 0; top: 0; z-index: 100;
      border-right: 1px solid var(--border-teal);
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5);
      overflow-x: visible; overflow-y: auto;
      transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar.expanded { width: var(--sidebar-expanded); }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }

    .sidebar-toggle {
      position: fixed; top: 50%;
      left: calc(var(--sidebar-collapsed) + 8px);
      width: 20px; height: 40px;
      background: transparent; border: none;
      color: var(--primary-neon); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem; z-index: 150;
      transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s ease;
      flex-shrink: 0;
    }
    body.sidebar-open .sidebar-toggle { left: calc(var(--sidebar-expanded) + 8px); }
    .sidebar-toggle:hover { transform: scale(1.2); text-shadow: 0 0 10px rgba(13, 245, 227, 0.5); }
    .sidebar-toggle i { transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
    body.sidebar-open .sidebar-toggle i { transform: rotate(180deg); }

    .school-brand {
      display: flex; align-items: center;
      padding: 20px 0 18px; border-bottom: 1px solid var(--border-teal);
      background: rgba(2, 12, 27, 0.6); flex-shrink: 0; min-height: 80px;
      overflow: hidden; cursor: pointer; transition: var(--transition-smooth);
    }
    .school-brand:hover { background: rgba(13, 245, 227, 0.03); }
    .school-logo { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; margin-left: 16px; border-radius: 10px; }
    .school-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .school-name {
      display: flex; flex-direction: column; margin-left: 12px;
      white-space: nowrap; overflow: hidden; opacity: 0; width: 0;
      transition: opacity 0.25s ease 0.1s, width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar.expanded .school-name { opacity: 1; width: auto; }
    .school-name .title { font-size: 0.95rem; font-weight: 700; color: var(--text-high-contrast); line-height: 1.25; }
    .school-name .sub { font-size: 0.6rem; color: var(--text-muted-teal); margin-top: 3px; letter-spacing: 1.2px; text-transform: uppercase; }

    .nav-links { list-style: none; padding: 8px 0; flex-shrink: 0; }
    .nav-links li { margin-bottom: 3px; }
    .nav-links li a {
      display: flex; align-items: center; padding: 11px 0;
      color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 500;
      transition: var(--transition-smooth); position: relative; white-space: nowrap; overflow: hidden;
    }
    .nav-links li a .nav-icon { width: var(--sidebar-collapsed); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #64748b; transition: var(--transition-smooth); }
    .nav-links li a .nav-label { opacity: 0; transform: translateX(-10px); transition: opacity 0.25s ease 0.05s, transform 0.3s ease 0.05s; }
    .sidebar.expanded .nav-links li a .nav-label { opacity: 1; transform: translateX(0); }
    .nav-links li a:hover { background-color: #1e293b; color: var(--primary-neon); }
    .nav-links li a:hover .nav-icon { color: var(--primary-neon); text-shadow: 0 0 8px rgba(13, 245, 227, 0.5); }
    .nav-links li.active a { background: linear-gradient(90deg, #1e3a5f 0%, #0f2a45 100%); color: var(--primary-neon); font-weight: 700; }
    .nav-links li.active a::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--primary-neon); box-shadow: 0 0 10px rgba(13, 245, 227, 0.7); }
    .nav-links li.active a .nav-icon { color: var(--primary-neon); }

    .sidebar-footer { padding: 12px 0 16px; border-top: 1px solid var(--border-teal); background: rgba(2, 12, 27, 0.85); flex-shrink: 0; margin-top: auto; }
    .admin-profile-card { display: flex; align-items: center; gap: 12px; padding: 8px 0; cursor: pointer; transition: var(--transition-smooth); overflow: hidden; }
    .admin-profile-card:hover { background: rgba(13, 245, 227, 0.04); }
    .admin-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-neon), #009f9a); color: #020c1b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.15rem; flex-shrink: 0; box-shadow: 0 0 12px rgba(13, 245, 227, 0.4); margin-left: 18px; }
    .admin-info { display: flex; flex-direction: column; overflow: hidden; white-space: nowrap; opacity: 0; width: 0; transition: opacity 0.25s ease 0.05s, width 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
    .sidebar.expanded .admin-info { opacity: 1; width: auto; flex: 1; }
    .admin-name { font-weight: 600; font-size: 0.85rem; color: var(--text-high-contrast); overflow: hidden; text-overflow: ellipsis; }
    .admin-role { font-size: 0.68rem; color: var(--text-muted-teal); display: flex; align-items: center; gap: 5px; }
    .admin-role i { font-size: 0.58rem; color: var(--primary-neon); }
    .admin-toggle-icon { color: var(--text-muted-teal); font-size: 0.75rem; transition: var(--transition-smooth); flex-shrink: 0; margin-right: 16px; opacity: 0; width: 0; }
    .sidebar.expanded .admin-toggle-icon { opacity: 1; width: auto; }
    .admin-profile-card.active .admin-toggle-icon { transform: rotate(180deg); color: var(--primary-neon); }

    .sidebar-actions { display: flex; flex-direction: column; gap: 6px; max-height: 0; overflow: hidden; opacity: 0; margin-top: 0; padding: 0; transition: max-height 0.4s ease, opacity 0.3s ease, margin-top 0.3s ease; }
    .sidebar.expanded .sidebar-actions.open { max-height: 220px; opacity: 1; margin-top: 10px; padding: 0 12px; }
    .btn-sidebar { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 14px; background-color: transparent; color: var(--text-muted-teal); border: 1px solid var(--border-teal); border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.82rem; transition: var(--transition-smooth); cursor: pointer; white-space: nowrap; overflow: hidden; }
    .btn-sidebar i { width: 18px; font-size: 0.9rem; text-align: center; flex-shrink: 0; }
    .btn-sidebar:hover { background-color: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }
    .btn-sidebar.profile-settings:hover { color: var(--primary-neon); border-color: var(--primary-neon); box-shadow: var(--neon-glow); }
    .btn-sidebar.homepage { color: var(--primary-neon); border-color: rgba(13, 245, 227, 0.5); }
    .btn-sidebar.homepage:hover { background-color: var(--primary-neon); color: var(--bg-deep-abyss); box-shadow: var(--neon-glow); }
    .btn-sidebar.logout { color: #f87171; border-color: rgba(239, 68, 68, 0.5); }
    .btn-sidebar.logout:hover { background-color: #ef4444; color: white; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); border-color: #ef4444; }

    .main-content {
      flex: 1; padding: 24px 32px 40px; overflow-y: auto;
      margin-left: var(--sidebar-collapsed);
      background:
        radial-gradient(circle at 15% 10%, rgba(13, 245, 227, 0.06) 0%, transparent 40%),
        radial-gradient(circle at 85% 80%, rgba(139, 92, 246, 0.06) 0%, transparent 45%),
        linear-gradient(135deg, #0b1120 0%, #0d1528 50%, #0a1120 100%);
      background-attachment: fixed;
      transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      min-height: 100vh;
    }
    body.sidebar-open .main-content { margin-left: var(--sidebar-expanded); }

    .top-header { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 24px; margin-bottom: 24px; }
    .top-header-left h1 { font-size: 1.5rem; font-weight: 600; color: var(--text-high-contrast); letter-spacing: -0.3px; }
    .top-header-left h1 i { color: var(--primary-neon); margin-right: 10px; }
    .top-header-left p { font-size: 0.82rem; color: var(--text-muted-teal); margin-top: 4px; }

    .live-clock { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 6px 20px; background: linear-gradient(135deg, rgba(13, 245, 227, 0.06), rgba(13, 245, 227, 0.01)); border: 1px solid var(--border-teal); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4); position: relative; overflow: hidden; min-width: 200px; transition: var(--transition-smooth); }
    .live-clock:hover { border-color: var(--primary-neon); }
    .live-clock::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, var(--primary-neon), transparent); animation: clockPulse 2s ease-in-out infinite; }
    @keyframes clockPulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }
    .clock-time { font-size: 1.4rem; font-weight: 700; color: var(--primary-neon); letter-spacing: 1.2px; font-variant-numeric: tabular-nums; text-shadow: 0 0 12px rgba(13, 245, 227, 0.5); line-height: 1; }
    .clock-ampm { font-size: 0.65rem; font-weight: 600; color: var(--primary-neon); margin-left: 3px; opacity: 0.85; vertical-align: super; }
    .clock-date { font-size: 0.62rem; color: var(--text-muted-teal); margin-top: 4px; letter-spacing: 0.4px; text-transform: uppercase; font-weight: 600; }

    .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
    .metric-card { background: var(--bg-card); border: 1px solid var(--border-teal); border-radius: var(--border-radius); padding: 20px; display: flex; flex-direction: column; transition: var(--transition-smooth); box-shadow: var(--card-shadow); position: relative; overflow: hidden; }
    .metric-card:hover { transform: translateY(-4px); border-color: var(--primary-neon); box-shadow: 0 12px 28px rgba(13, 245, 227, 0.15); }
    .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--primary-neon), #00b4d8); opacity: 0.8; }
    .metric-card:nth-child(2)::before { background: linear-gradient(90deg, var(--success-green), #34d399); }
    .metric-card:nth-child(3)::before { background: linear-gradient(90deg, var(--warning-amber), #fbbf24); }
    .metric-card:nth-child(4)::before { background: linear-gradient(90deg, var(--purple-accent), #a78bfa); }
    .metric-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .metric-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted-teal); font-weight: 600; }
    .metric-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: var(--primary-neon); background: rgba(13, 245, 227, 0.08); }
    .metric-card:nth-child(2) .metric-icon { color: var(--success-green); background: rgba(16, 185, 129, 0.1); }
    .metric-card:nth-child(3) .metric-icon { color: var(--warning-amber); background: rgba(245, 158, 11, 0.1); }
    .metric-card:nth-child(4) .metric-icon { color: var(--purple-accent); background: rgba(139, 92, 246, 0.1); }
    .metric-value { font-size: 2rem; font-weight: 700; color: var(--text-high-contrast); line-height: 1.15; letter-spacing: -0.5px; }

    .table-card { background: var(--bg-card); border: 1px solid var(--border-teal); border-radius: var(--border-radius); padding: 22px 24px; box-shadow: var(--card-shadow); margin-bottom: 24px; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; gap: 15px; flex-wrap: wrap; }
    .table-header h2 { font-size: 1.05rem; font-weight: 600; color: var(--text-high-contrast); display: flex; align-items: center; gap: 10px; }
    .table-header h2 i { color: var(--primary-neon); font-size: 1rem; }
    .table-wrapper { overflow-x: auto; max-height: 640px; overflow-y: auto; }
    .table-wrapper::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-wrapper::-webkit-scrollbar-thumb { background: var(--border-teal); border-radius: 3px; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { padding: 12px 16px; font-size: 0.73rem; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted-teal); font-weight: 600; border-bottom: 1px solid var(--border-teal); background: rgba(2, 12, 27, 0.6); white-space: nowrap; position: sticky; top: 0; z-index: 2; }
    td { padding: 14px 16px; color: var(--text-high-contrast); border-bottom: 1px solid #1a2d45; font-size: 0.87rem; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background-color: rgba(13, 245, 227, 0.03); }
    th.sortable { cursor: pointer; user-select: none; }
    th.sortable:hover { color: var(--primary-neon); }
    th.no-sort { cursor: default; }

    .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.68rem; font-weight: 700; display: inline-block; letter-spacing: 0.3px; text-transform: uppercase; }
    .badge.success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge.warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .badge.danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

    .btn { padding: 8px 18px; border: 1px solid var(--border-teal); background: transparent; border-radius: 8px; cursor: pointer; font-size: 0.82rem; font-weight: 500; transition: var(--transition-smooth); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted-teal); }
    .btn:hover { background: #1e293b; color: var(--text-high-contrast); border-color: #2d4a6b; }
    .btn.primary { background: var(--primary-neon); color: #020c1b; border: none; font-weight: 600; }
    .btn.primary:hover { background: var(--primary-neon-hover); box-shadow: var(--neon-glow); }
    .btn-action { padding: 5px 12px; background: transparent; border: 1px solid var(--border-teal); color: var(--text-muted-teal); border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 500; transition: var(--transition-smooth); display: inline-flex; align-items: center; gap: 5px; }
    .btn-action:hover { background: #1e293b; color: var(--text-high-contrast); border-color: var(--primary-neon); }
    .btn-action.view { border-color: rgba(13, 245, 227, 0.5); color: var(--primary-neon); }
    .btn-action.view:hover { background: var(--primary-neon); color: #020c1b; box-shadow: var(--neon-glow); }
    .action-group { display: flex; gap: 6px; flex-wrap: wrap; }

    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2, 12, 27, 0.9); backdrop-filter: blur(6px); z-index: 3000; justify-content: center; align-items: center; padding: 20px; }
    .modal-overlay.active { display: flex; }
    .modal-container { background: var(--bg-card); border: 1px solid var(--border-teal); border-radius: 16px; width: 100%; max-width: 1100px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9); position: relative; padding: 30px; color: var(--text-high-contrast); transform: translateY(20px); transition: transform 0.3s ease; }
    .modal-overlay.active .modal-container { transform: translateY(0); }
    .modal-close { position: absolute; top: 18px; right: 22px; background: transparent; border: none; color: var(--text-muted-teal); font-size: 1.6rem; cursor: pointer; z-index: 10; transition: color 0.2s ease; line-height: 1; }
    .modal-close:hover { color: #ef4444; }
    .modal-title { font-size: 1.1rem; font-weight: 700; color: var(--primary-neon); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

    .student-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 12px; padding: 22px; }
    .student-info-left, .student-info-right { display: flex; flex-direction: column; gap: 12px; }
    .student-info-right { align-items: flex-end; text-align: right; }
    .info-line { font-size: 0.85rem; color: var(--text-muted-teal); }
    .info-line strong { color: var(--text-high-contrast); font-weight: 600; }
    .info-line strong i { color: var(--primary-neon); margin-right: 8px; }
    .info-line .highlight { color: var(--primary-neon); font-weight: 700; }

    .course-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; background: var(--bg-deep-abyss); border-radius: 10px; overflow: hidden; margin-bottom: 24px; border: 1px solid var(--border-teal); }
    .course-table th { background: rgba(2, 12, 27, 0.8); color: var(--primary-neon); font-weight: 600; padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--border-teal); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; position: static; }
    .course-table td { padding: 12px 10px; border-bottom: 1px solid #1a2d45; color: var(--text-high-contrast); }
    .course-table tr:last-child td { border-bottom: none; }
    .course-table .total-row td { background: rgba(13, 245, 227, 0.06); font-weight: 700; color: var(--primary-neon); border-top: 1px solid rgba(13, 245, 227, 0.3); }
    .course-table .empty-row td { text-align: center; color: var(--text-muted-teal); padding: 24px; font-style: italic; }

    .payment-assessment-wrap { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; }
    .payment-assess-card { background: var(--bg-deep-abyss); border: 1px solid var(--border-teal); border-radius: 12px; overflow: hidden; }
    .card-header { background: rgba(2, 12, 27, 0.8); padding: 14px 18px; font-size: 0.82rem; font-weight: 700; color: var(--primary-neon); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border-teal); display: flex; align-items: center; gap: 8px; }

    .data-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    .data-table th, .data-table td { padding: 11px 18px; text-align: left; border-bottom: 1px solid #1a2d45; }
    .data-table th { background: rgba(2, 12, 27, 0.5); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted-teal); font-weight: 600; }
    .data-table td { color: var(--text-high-contrast); }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table .assessment-item td:last-child { text-align: right; font-weight: 600; }
    .data-table .assessment-total td { background: rgba(13, 245, 227, 0.06); font-weight: 800; color: var(--primary-neon); font-size: 0.95rem; border-top: 1px solid rgba(13, 245, 227, 0.3); }
    .data-table .assessment-total td:last-child { text-align: right; font-size: 1.1rem; }
    .data-table .assessment-remaining td { background: rgba(245, 158, 11, 0.08); color: #fbbf24; font-weight: 800; font-size: 0.95rem; border-top: 1px solid rgba(245, 158, 11, 0.3); }
    .data-table .assessment-remaining td:last-child { text-align: right; font-size: 1.1rem; }
    .data-table .assessment-remaining.fully-paid td { background: rgba(16, 185, 129, 0.08); color: #34d399; border-top: 1px solid rgba(16, 185, 129, 0.3); }
    .data-table .total-paid-row td { background: rgba(16, 185, 129, 0.06); font-weight: 800; color: #34d399; font-size: 0.95rem; border-top: 1px solid rgba(16, 185, 129, 0.3); }
    .data-table .total-paid-row td:last-child { text-align: right; font-size: 1.1rem; }
    .data-table .empty-row td { text-align: center; color: var(--text-muted-teal); padding: 24px; font-style: italic; }

    @media (max-width: 1200px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } .top-header { grid-template-columns: 1fr; gap: 18px; } }
    @media (max-width: 768px) {
      .main-content { padding: 20px; }
      .cards-grid { grid-template-columns: 1fr; }
      .student-info-grid { grid-template-columns: 1fr; }
      .student-info-right { align-items: flex-start; text-align: left; }
      .payment-assessment-wrap { grid-template-columns: 1fr; }
      .live-clock { min-width: 100%; }
      .clock-time { font-size: 1.2rem; }
      .sidebar-toggle { top: 20px; }
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
      <li><a href="adminpanel.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span><span class="nav-label">Overview</span></a></li>
      <li><a href="users.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-users"></i></span><span class="nav-label">Users</span></a></li>
      <li><a href="enrollments.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-file-signature"></i></span><span class="nav-label">Enrollments</span></a></li>
      <li class="active"><a href="enrolled.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-user-graduate"></i></span><span class="nav-label">Enrolled</span></a></li>
      <li><a href="instructor.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-chalkboard-user"></i></span><span class="nav-label">Instructors</span></a></li>
      <li><a href="curriculum.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-book"></i></span><span class="nav-label">Curriculum</span></a></li>
      <li><a href="received-mail.php" onclick="pinSidebar(event)"><span class="nav-icon"><i class="fa-solid fa-envelope"></i></span><span class="nav-label">Received Mail</span></a></li>
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
        <a href="profile-settings.php" class="btn-sidebar profile-settings"><i class="fa-solid fa-gear"></i> Profile Settings</a>
        <a href="../../index.html" class="btn-sidebar homepage"><i class="fa-solid fa-house"></i> Homepage</a>
        <a href="logout.php" class="btn-sidebar logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </div>
  </aside>

  <main class="main-content" id="mainContent">
    <header class="top-header">
      <div class="top-header-left">
        <h1><i class="fa-solid fa-user-graduate"></i> Enrolled Students</h1>
        <p>Manage and review all officially enrolled students of the college.</p>
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
          <span class="metric-label">Total Students</span>
          <div class="metric-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="metric-value" id="totalStudentsCard"><?php echo count($students); ?></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Fully Paid</span>
          <div class="metric-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="metric-value" id="fullyPaidCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">With Balance</span>
          <div class="metric-icon"><i class="fa-solid fa-clock"></i></div>
        </div>
        <div class="metric-value" id="withBalanceCard">0</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Total Units Enrolled</span>
          <div class="metric-icon"><i class="fa-solid fa-layer-group"></i></div>
        </div>
        <div class="metric-value" id="totalUnitsCard">0</div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-list-ul"></i> List of Enrolled Students</h2>
        <div class="search-container" style="position:relative;">
          <input type="text" id="userSearchInput" placeholder="Search students..." autocomplete="off"
            style="padding:10px 16px 10px 40px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:10px; color:var(--text-high-contrast); font-size:0.85rem; outline:none; width:240px;" />
          <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted-teal); font-size:0.85rem; pointer-events:none;"></i>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="enrolledTable">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable(0)">Student ID <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(1)">Full Name <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(2)">Year Level <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(3)">Course <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(4)">Block <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(5)">Units <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(6)">Balance <i class="fas fa-sort"></i></th>
              <th class="no-sort">Action</th>
            </tr>
          </thead>
          <tbody id="enrolledTableBody">
            <?php foreach ($students as $s):
              $fullName = trim(($s['lastname'] ?? '') . ', ' . ($s['firstname'] ?? '') . ' ' . ($s['middlename'] ?? ''));
              $studentIdDisplay = !empty($s['student_code'])
                ? htmlspecialchars($s['student_code'])
                : '#STU-' . str_pad($s['student_id'] ?? 0, 4, '0', STR_PAD_LEFT);
              $balanceFormatted = '₱ ' . number_format($s['balance'], 2);
              if ($s['balance'] <= 0 && $s['total_assessment'] > 0) {
                $badgeClass = 'success';
              } elseif ($s['total_paid'] > 0) {
                $badgeClass = 'warning';
              } else {
                $badgeClass = 'danger';
              }
            ?>
            <tr data-record='<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES, "UTF-8"); ?>'>
              <td><?php echo $studentIdDisplay; ?></td>
              <td><?php echo htmlspecialchars($fullName); ?></td>
              <td><?php echo htmlspecialchars($s['year_level'] ?? '—'); ?></td>
              <td><?php echo htmlspecialchars($s['course'] ?? '—'); ?></td>
              <td><?php echo htmlspecialchars($s['block'] ?? '—'); ?></td>
              <td><?php echo number_format($s['total_units'], 1); ?></td>
              <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $balanceFormatted; ?></span></td>
              <td><button class="btn-action view" onclick="viewStudent(this)"><i class="fa-solid fa-eye"></i> View</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($students) === 0): ?>
              <tr><td colspan="8" style="text-align:center; color:var(--text-muted-teal); padding:40px;">No enrolled students found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- VIEW STUDENT MODAL -->
  <div class="modal-overlay" id="viewStudentModal">
    <div class="modal-container">
      <button class="modal-close" onclick="closeViewStudentModal()">&times;</button>
      <div class="modal-title"><i class="fa-solid fa-id-card"></i> Student Enrollment Details</div>

      <div class="student-info-grid">
        <div class="student-info-left">
          <div class="info-line"><strong><i class="fas fa-id-card"></i> Student ID:</strong> <span class="highlight" id="modalStudentID">-</span></div>
          <div class="info-line"><strong><i class="fas fa-user"></i> Name:</strong> <span id="modalStudentName">-</span></div>
          <div class="info-line"><strong><i class="fas fa-book-open"></i> Course:</strong> <span id="modalStudentCourse">-</span></div>
        </div>
        <div class="student-info-right">
          <div class="info-line"><strong><i class="fas fa-calendar"></i> Academic Year:</strong> <span id="modalStudentYear">-</span></div>
          <div class="info-line"><strong><i class="fas fa-print"></i> Date Printed:</strong> <span id="modalStudentDate">-</span></div>
        </div>
      </div>

      <table class="course-table">
        <thead>
          <tr><th>Code</th><th>Description</th><th>Units</th><th>Date</th><th>Time</th><th>Room</th><th>Block</th></tr>
        </thead>
        <tbody id="modalCourseBody"></tbody>
      </table>

      <div class="payment-assessment-wrap">
        <div class="payment-assess-card">
          <div class="card-header"><i class="fas fa-receipt"></i> Payment Details</div>
          <table class="data-table">
            <thead><tr><th>#</th><th>Date</th><th>O.R. No.</th><th>Amount</th></tr></thead>
            <tbody id="modalPaymentBody"></tbody>
          </table>
        </div>

        <div class="payment-assess-card">
          <div class="card-header"><i class="fas fa-calculator"></i> Assessment</div>
          <table class="data-table">
            <tbody id="modalAssessmentBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    /* ============================================
       SIDEBAR
       ============================================ */
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    let isPinned = false;
    let collapseTimer = null;

    function expandSidebar() { sidebar.classList.add('expanded'); body.classList.add('sidebar-open'); }
    function collapseSidebar() { if (isPinned) return; sidebar.classList.remove('expanded'); body.classList.remove('sidebar-open'); }
    function pinSidebar(event) { if (event) event.stopPropagation(); isPinned = true; expandSidebar(); }
    function unpinSidebar() {
      isPinned = false;
      document.getElementById('adminActions').classList.remove('open');
      document.getElementById('adminProfileToggle').classList.remove('active');
      collapseSidebar();
    }
    function toggleSidebar(event) {
      if (event) event.stopPropagation();
      if (sidebar.classList.contains('expanded')) unpinSidebar(); else pinSidebar();
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
        document.getElementById('adminActions').classList.toggle('open');
        document.getElementById('adminProfileToggle').classList.toggle('active');
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
      hours = hours % 12; hours = hours ? hours : 12;
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
       HELPERS
       ============================================ */
    function money(n) {
      return '₱ ' + (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function parseMoneyJS(v) {
      if (v === null || v === undefined || v === '') return 0;
      const clean = String(v).replace(/[^0-9.\-]/g, '');
      return parseFloat(clean) || 0;
    }

    /* ============================================
       SEARCH + METRICS
       ============================================ */
    document.addEventListener("DOMContentLoaded", () => {
      const searchInput = document.getElementById("userSearchInput");
      const enrollmentTable = document.getElementById("enrolledTable");
      if (searchInput && enrollmentTable) {
        searchInput.addEventListener("input", (e) => {
          const term = e.target.value.toLowerCase();
          enrollmentTable.querySelectorAll("tbody tr").forEach((row) => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
          });
        });
      }
      updateMetrics();
    });

    function updateMetrics() {
      const rows = document.querySelectorAll("#enrolledTableBody tr");
      let totalUnits = 0, fullyPaid = 0, withBalance = 0;

      rows.forEach((row) => {
        const dataAttr = row.getAttribute('data-record');
        if (!dataAttr) return;
        try {
          const d = JSON.parse(dataAttr);
          totalUnits += parseFloat(d.total_units) || 0;
          if (parseFloat(d.balance) <= 0 && parseFloat(d.total_assessment) > 0) fullyPaid++;
          else if (parseFloat(d.balance) > 0) withBalance++;
        } catch (e) { /* skip */ }
      });

      document.getElementById("totalUnitsCard").textContent = totalUnits.toFixed(1);
      document.getElementById("fullyPaidCard").textContent = fullyPaid;
      document.getElementById("withBalanceCard").textContent = withBalance;
    }

    /* ============================================
       SORT
       ============================================ */
    let sortDirection = 1;
    function sortTable(columnIndex) {
      const table = document.getElementById("enrolledTable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.rows).filter(r => !r.querySelector('td[colspan]'));
      sortDirection = sortDirection === 1 ? -1 : 1;

      rows.sort((a, b) => {
        if (columnIndex === 5) {
          const aN = parseFloat(a.cells[5].textContent) || 0;
          const bN = parseFloat(b.cells[5].textContent) || 0;
          return (aN - bN) * sortDirection;
        }
        if (columnIndex === 6) {
          const aD = JSON.parse(a.getAttribute('data-record') || '{}');
          const bD = JSON.parse(b.getAttribute('data-record') || '{}');
          return ((parseFloat(aD.balance) || 0) - (parseFloat(bD.balance) || 0)) * sortDirection;
        }
        const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
        const bText = b.cells[columnIndex].textContent.trim().toLowerCase();
        if (aText < bText) return -1 * sortDirection;
        if (aText > bText) return  1 * sortDirection;
        return 0;
      });
      rows.forEach(row => tbody.appendChild(row));
      document.querySelectorAll('th.sortable i').forEach(i => i.className = 'fas fa-sort');
      const activeTh = table.querySelector(`th.sortable:nth-child(${columnIndex + 1}) i`);
      if (activeTh) activeTh.className = sortDirection === 1 ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }

    /* ============================================
       VIEW STUDENT MODAL — FULLY DYNAMIC
       ============================================ */
    function viewStudent(button) {
      const row = button.closest("tr");
      const dataAttr = row.getAttribute("data-record");
      if (!dataAttr) return;

      let data;
      try { data = JSON.parse(dataAttr); } catch (e) { console.error(e); return; }

      const idDisplay = data.student_code ? data.student_code : ('#STU-' + String(data.student_id).padStart(4, '0'));
      document.getElementById("modalStudentID").textContent = idDisplay;
      const fullName = ((data.lastname || '') + ', ' + (data.firstname || '') + ' ' + (data.middlename || '')).trim();
      document.getElementById("modalStudentName").textContent = fullName || 'N/A';
      document.getElementById("modalStudentYear").textContent = data.academic_year || '—';
      document.getElementById("modalStudentCourse").textContent = data.course || '—';
      document.getElementById("modalStudentDate").textContent =
        new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

      const subjects = Array.isArray(data.parsed_subjects) ? data.parsed_subjects : [];
      let courseHtml = '';
      if (subjects.length > 0) {
        subjects.forEach(s => {
          // Prefer separate date/time; fall back to legacy day_time
          let dateVal = s.date || '';
          let timeVal = s.time || '';
          if (!dateVal && !timeVal && s.day_time) {
            const parts = String(s.day_time).trim().split(/\s+/);
            if (parts.length >= 2 && /^\d{4}-\d{2}-\d{2}$/.test(parts[0])) {
              dateVal = parts[0];
              timeVal = parts.slice(1).join(' ');
            } else {
              dateVal = s.day_time;
            }
          }
          courseHtml += `<tr>
            <td>${s.code || ''}</td>
            <td>${s.description || ''}</td>
            <td>${(parseFloat(s.units) || 0).toFixed(1)}</td>
            <td>${dateVal}</td>
            <td>${timeVal}</td>
            <td>${s.room || ''}</td>
            <td>${s.block || ''}</td>
          </tr>`;
        });
        courseHtml += `<tr class="total-row">
          <td colspan="2" style="text-align:right; padding-right:20px;">TOTAL UNITS</td>
          <td>${(parseFloat(data.total_units) || 0).toFixed(1)}</td>
          <td colspan="4" style="text-align:left;">Bridging: ${(parseFloat(data.bridging_units) || 0).toFixed(1)} | NSTP: ${(parseFloat(data.nstp_units) || 0).toFixed(1)}</td>
        </tr>`;
      } else {
        courseHtml = `<tr class="empty-row"><td colspan="7"><i class="fa-solid fa-circle-info"></i> No subjects assigned yet. Approve the enrollment first to assign subjects.</td></tr>`;
      }
      document.getElementById("modalCourseBody").innerHTML = courseHtml;

      const payments = Array.isArray(data.payments_list) ? data.payments_list : [];
      let payHtml = '';
      if (payments.length > 0) {
        payments.forEach((p, i) => {
          const d = p.payment_date ? new Date(p.payment_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : '';
          payHtml += `<tr>
            <td>${i + 1}</td>
            <td>${d}</td>
            <td>${p.or_number || ''}</td>
            <td>${money(p.amount)}</td>
          </tr>`;
        });
      } else {
        payHtml = `<tr class="empty-row"><td colspan="4"><i class="fa-solid fa-circle-info"></i> No payments recorded yet.</td></tr>`;
      }
      payHtml += `<tr class="total-paid-row"><td colspan="3" style="text-align:right;">TOTAL PAID:</td><td>${money(data.total_paid)}</td></tr>`;
      document.getElementById("modalPaymentBody").innerHTML = payHtml;

      const a = data.parsed_assessment || {};
      const balance = parseFloat(data.balance) || 0;
      const remainingClass = balance <= 0 ? 'assessment-remaining fully-paid' : 'assessment-remaining';

      const assessHtml = `
        <tr class="assessment-item"><td>Tuition Fee:</td><td>${money(parseMoneyJS(a.tuition_fee))}</td></tr>
        <tr class="assessment-item"><td>Academic:</td><td>${money(parseMoneyJS(a.academic))}</td></tr>
        <tr class="assessment-item"><td>Computer:</td><td>${money(parseMoneyJS(a.computer))}</td></tr>
        <tr class="assessment-item"><td>Misc. Fee:</td><td>${money(parseMoneyJS(a.misc_fee))}</td></tr>
        <tr class="assessment-item"><td>NSTP:</td><td>${money(parseMoneyJS(a.nstp))}</td></tr>
        <tr class="assessment-item"><td>Others:</td><td>${money(parseMoneyJS(a.others))}</td></tr>
        <tr class="assessment-total"><td>TOTAL:</td><td>${money(data.total_assessment)}</td></tr>
        <tr class="${remainingClass}"><td>Balance:</td><td>${money(balance)}</td></tr>`;
      document.getElementById("modalAssessmentBody").innerHTML = assessHtml;

      document.getElementById("viewStudentModal").classList.add("active");
    }

    function closeViewStudentModal() {
      document.getElementById("viewStudentModal").classList.remove("active");
    }

    document.getElementById("viewStudentModal").addEventListener("click", function(e) {
      if (e.target === this) closeViewStudentModal();
    });
  </script>
</body>
</html>