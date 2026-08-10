<?php
// Database credentials
$host     = "localhost";
$dbname   = "enrollmentdb";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<h2 style='color:red;'>Database Connection Failed: " . $e->getMessage() . "</h2>");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    function clean_input($data) {
        return trim(htmlspecialchars($data ?? ''));
    }

    // Capture HTML inputs
    $firstname      = clean_input($_POST['first_name'] ?? '');
    $middlename     = clean_input($_POST['middle_name'] ?? '');
    $lastname       = clean_input($_POST['last_name'] ?? '');
    $suffix         = clean_input($_POST['suffix'] ?? '');
    $gender         = clean_input($_POST['gender'] ?? '');

    // Format Date: YYYY-MM-DD
    $birth_year     = clean_input($_POST['birth_year'] ?? '');
    $birth_month    = clean_input($_POST['birth_month'] ?? '');
    $birth_day      = clean_input($_POST['birth_day'] ?? '');

    $birth_month    = str_pad($birth_month, 2, '0', STR_PAD_LEFT);
    $birth_day      = str_pad($birth_day, 2, '0', STR_PAD_LEFT);
    $birthday       = "$birth_year-$birth_month-$birth_day";

    // Validate date
    if (!checkdate((int)$birth_month, (int)$birth_day, (int)$birth_year)) {
        header("Location: enrollment_success.php?status=error&message=" . urlencode("Invalid birth date provided."));
        exit();
    }

    $birthplace     = clean_input($_POST['birthplace'] ?? '');
    $citizenship    = clean_input($_POST['citizenship'] ?? '');
    $civilstatus    = clean_input($_POST['civil_status'] ?? '');
    $employment     = clean_input($_POST['employment_status'] ?? '');

    $mother         = clean_input($_POST['mother_name'] ?? '');
    $mphone_number  = clean_input($_POST['mother_mobile'] ?? '');

    $father         = clean_input($_POST['father_name'] ?? '');
    $fphone_number  = clean_input($_POST['father_mobile'] ?? '');

    $guardian       = clean_input($_POST['guardian_name'] ?? '');
    $gphone_number  = clean_input($_POST['guardian_mobile'] ?? '');

    $course         = clean_input($_POST['program'] ?? '');
    $major          = clean_input($_POST['major'] ?? '');
    $school_address = clean_input($_POST['school_address'] ?? '');
    $academic_year  = clean_input($_POST['academic_year'] ?? '');

    $scholarship       = clean_input($_POST['scholarship'] ?? '');
    $scholarship_other = clean_input($_POST['scholarship_other'] ?? '');
    if (!empty($scholarship_other)) {
        $scholarship   = $scholarship_other;
    }

    $full_address   = clean_input($_POST['address'] ?? '');
    $mobile_number  = clean_input($_POST['mobile_number'] ?? '');
    $email          = clean_input($_POST['email'] ?? '');

    // Handle file uploads (requirements)
    $uploaded_files = [];
    if (!empty($_FILES['requirementFiles']['name'][0])) {
        $uploadDir = 'uploads/requirements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
        $fileCount = count($_FILES['requirementFiles']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['requirementFiles']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['requirementFiles']['tmp_name'][$i];
                $originalName = basename($_FILES['requirementFiles']['name'][$i]);
                $fileType = $_FILES['requirementFiles']['type'][$i];

                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip invalid file types
                }

                $safeName = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $targetPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploaded_files[] = $safeName;
                }
            }
        }
    }

    $sql = "INSERT INTO enrolled (
        firstname, middlename, lastname, suffix, gender, birthday, birthplace, 
        citizenship, civilstatus, employment, mother, mphone_number, father, 
        fphone_number, guardian, gphone_number, course, major, school_address, 
        academic_year, scholarship, full_address, mobile_number, email
    ) VALUES (
        :firstname, :middlename, :lastname, :suffix, :gender, :birthday, :birthplace, 
        :citizenship, :civilstatus, :employment, :mother, :mphone_number, :father, 
        :fphone_number, :guardian, :gphone_number, :course, :major, :school_address, 
        :academic_year, :scholarship, :full_address, :mobile_number, :email
    )";

    try {
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':firstname'      => $firstname,
            ':middlename'     => $middlename,
            ':lastname'       => $lastname,
            ':suffix'         => $suffix,
            ':gender'         => $gender,
            ':birthday'       => $birthday,
            ':birthplace'     => $birthplace,
            ':citizenship'    => $citizenship,
            ':civilstatus'    => $civilstatus,
            ':employment'     => $employment,
            ':mother'         => $mother,
            ':mphone_number'  => $mphone_number,
            ':father'         => $father,
            ':fphone_number'  => $fphone_number,
            ':guardian'       => $guardian,
            ':gphone_number'  => $gphone_number,
            ':course'         => $course,
            ':major'          => $major,
            ':school_address' => $school_address,
            ':academic_year'  => $academic_year,
            ':scholarship'    => $scholarship,
            ':full_address'   => $full_address,
            ':mobile_number'  => $mobile_number,
            ':email'          => $email
        ]);

        // Redirect to success page with student info
        header("Location: enrollment_success.php?status=success&name=" . urlencode($firstname . " " . $lastname) . "&course=" . urlencode($course));
        exit();

    } catch (PDOException $e) {
        header("Location: enrollment_success.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    header("Location: enrollmentpage.html");
    exit();
}
?>