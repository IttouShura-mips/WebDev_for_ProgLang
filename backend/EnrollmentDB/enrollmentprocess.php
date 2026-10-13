<?php
session_start();

// Database connection (same credentials as your db.php)
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "enrollmentdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: enrollment_success.php?status=error&message=" . urlencode("Invalid request method."));
    exit();
}

// =====================================================
// ENSURE INITIAL-PAYMENT TRACKING COLUMNS EXIST ON `enrolled`
// These hold the downpayment until the admin approves the
// application and a real student_code can be assigned.
// =====================================================
$paymentTrackCols = [
    'initial_payment_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
    'initial_payment_method' => "VARCHAR(50) DEFAULT ''",
    'initial_payment_date'   => "DATE DEFAULT NULL",
    'initial_payment_or'     => "VARCHAR(30) DEFAULT ''",
    'initial_payment_gcash'  => "VARCHAR(30) DEFAULT ''",
];
foreach ($paymentTrackCols as $col => $def) {
    $safeCol = $conn->real_escape_string($col);
    $check = $conn->query("SHOW COLUMNS FROM enrolled LIKE '{$safeCol}'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE enrolled ADD COLUMN `{$col}` {$def}");
    }
}

// =====================================================
// ENSURE student_payments TABLE EXISTS (defensive)
// =====================================================
$conn->query("CREATE TABLE IF NOT EXISTS student_payments (
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

// Helper: clean phone numbers
function cleanPhone($phone) {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($digits) === 12 && strpos($digits, '63') === 0) {
        $digits = '0' . substr($digits, 2);
    }
    if (strlen($digits) === 10 && $digits[0] === '9') {
        $digits = '0' . $digits;
    }
    return $digits;
}

// Helper: sanitize string input
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// --- 1. Collect & map form fields to DB columns ---
$firstname     = getPost('first_name');
$middlename    = getPost('middle_name');
$lastname      = getPost('last_name');
$suffix        = getPost('suffix');
$gender        = getPost('gender');
$birthplace    = getPost('birthplace');
$citizenship   = getPost('citizenship');
$civilstatus   = getPost('civil-status');
$employment    = getPost('employment-status');
$mother        = getPost('mother_name');
$father        = getPost('father_name');
$guardian      = getPost('guardian_name');
$course        = getPost('program');
$major         = getPost('major');
$school_address= getPost('school_address');
$academic_year = getPost('academic_year');
$full_address  = getPost('address');
$email         = getPost('email');

// Phone numbers: clean and validate
$mphone_number = cleanPhone(getPost('mphone_number'));
$fphone_number = cleanPhone(getPost('fphone_number'));
$gphone_number = cleanPhone(getPost('gphone_number'));
$mobile_number = cleanPhone(getPost('mobile_number'));

// Birthday: combine three selects into YYYY-MM-DD
$birth_year  = getPost('birth_year');
$birth_month = getPost('birth_month');
$birth_day   = getPost('birth_day');
$birthday = $birth_year . '-' . str_pad($birth_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($birth_day, 2, '0', STR_PAD_LEFT);

// Scholarship
$scholarship = getPost('scholarship', 'none');
$scholarship_other = getPost('scholarship_other');
if ($scholarship === 'none' && !empty($scholarship_other)) {
    $scholarship = $scholarship_other;
}

// IP & defaults
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$status = 'offline';
$enrollment_status = 'pending';

// =====================================================
// NEW: Read the downpayment fields from the enrollment form
// =====================================================
$partialAmount = (float)($_POST['partial_amount'] ?? 0);
$paymentMethod = trim($_POST['paymentMethod'] ?? '');
$ewalletNumber = trim($_POST['ewallet_number'] ?? '');

// If a downpayment was entered, record date + generate a temporary OR
$initialPaymentDate = ($partialAmount > 0) ? date('Y-m-d') : null;
$initialPaymentOR   = '';
if ($partialAmount > 0) {
    $initialPaymentOR = 'OR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

// --- 2. Handle file uploads (requirements) ---
$requirements_files = null;
if (!empty($_FILES['requirementFiles']['name'][0])) {
    $uploadDir = __DIR__ . '/../../uploads/requirements/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedExts = ['docx', 'pdf', 'jpg', 'jpeg', 'png'];
    $filePaths = [];

    foreach ($_FILES['requirementFiles']['name'] as $key => $originalName) {
        if ($_FILES['requirementFiles']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpName = $_FILES['requirementFiles']['tmp_name'][$key];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            continue;
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $newName  = uniqid() . '_' . $safeName . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $filePaths[] = 'uploads/requirements/' . $newName;
        }
    }

    if (!empty($filePaths)) {
        $requirements_files = json_encode($filePaths);
    }
}

// --- 3. Insert into database (includes initial payment columns) ---
$sql = "INSERT INTO enrolled (
    firstname, middlename, lastname, suffix, gender, birthday, birthplace, citizenship,
    civilstatus, employment, mother, mphone_number, father, fphone_number, guardian,
    gphone_number, course, major, school_address, academic_year, scholarship,
    full_address, mobile_number, email, ip_address, status, enrollment_status, requirements_files,
    initial_payment_amount, initial_payment_method, initial_payment_date, initial_payment_or, initial_payment_gcash
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header("Location: enrollment_success.php?status=error&message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}

$types = str_repeat('s', 33);
$stmt->bind_param($types,
    $firstname, $middlename, $lastname, $suffix, $gender, $birthday, $birthplace, $citizenship,
    $civilstatus, $employment, $mother, $mphone_number, $father, $fphone_number, $guardian,
    $gphone_number, $course, $major, $school_address, $academic_year, $scholarship,
    $full_address, $mobile_number, $email, $ip_address, $status, $enrollment_status, $requirements_files,
    $partialAmount, $paymentMethod, $initialPaymentDate, $initialPaymentOR, $ewalletNumber
);

if ($stmt->execute()) {
    $studentName = urlencode(trim($firstname . ' ' . $lastname));
    $courseName  = urlencode($course);
    header("Location: enrollment_success.php?status=success&name=" . $studentName . "&course=" . $courseName);
    exit();
} else {
    header("Location: enrollment_success.php?status=error&message=" . urlencode($stmt->error));
    exit();
}
?>