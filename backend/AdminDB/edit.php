<?php
session_start();
if (!isset($_SESSION['admin_user'])) { header("Location: login.html"); exit(); }
require 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: adminpanel.php"); exit(); }

$fields = ['firstname','middlename','lastname','suffix','gender','birthday','birthplace','citizenship','civilstatus','employment','mother','mphone_number','father','fphone_number','guardian','gphone_number','course','major','school_address','academic_year','scholarship','full_address','mobile_number','email'];

// Fetch record securely
$stmt = $conn->prepare("SELECT * FROM enrolled WHERE student_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    echo "Record not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updates = [];
    $values = [];
    foreach ($fields as $f) {
        $updates[] = "$f = ?";
        $values[] = $_POST[$f] ?? '';
    }
    $values[] = $id;
    
    $sql = "UPDATE enrolled SET " . implode(', ', $updates) . " WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($fields)) . 'i';
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        header("Location: adminpanel.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="../../admin/adminpanelstyle.css">
  <style>
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-grid.full { grid-template-columns: 1fr; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-size: 12px; margin-bottom: 4px; color: #555; text-transform: uppercase; }
    .form-group input, .form-group select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
    fieldset { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
    legend { font-weight: bold; padding: 0 10px; }
  </style>
</head>
<body style="padding: 40px; background:#f4f6f8;">
    <div class="table-card" style="max-width:900px; margin:0 auto;">
        <h2>Edit Student Record</h2>
        <form method="POST">
          
          <fieldset>
            <legend>Personal Information</legend>
            <div class="form-grid">
              <div class="form-group"><label>First Name</label><input type="text" name="firstname" value="<?php echo htmlspecialchars($record['firstname']); ?>" required></div>
              <div class="form-group"><label>Middle Name</label><input type="text" name="middlename" value="<?php echo htmlspecialchars($record['middlename']); ?>" required></div>
              <div class="form-group"><label>Last Name</label><input type="text" name="lastname" value="<?php echo htmlspecialchars($record['lastname']); ?>" required></div>
              <div class="form-group"><label>Suffix</label><input type="text" name="suffix" value="<?php echo htmlspecialchars($record['suffix']); ?>"></div>
              <div class="form-group"><label>Gender</label>
                <select name="gender" required>
                  <option value="Male" <?php if($record['gender']=='Male') echo 'selected'; ?>>Male</option>
                  <option value="Female" <?php if($record['gender']=='Female') echo 'selected'; ?>>Female</option>
                </select>
              </div>
              <div class="form-group"><label>Birthday</label><input type="date" name="birthday" value="<?php echo htmlspecialchars($record['birthday']); ?>" required></div>
              <div class="form-group"><label>Birthplace</label><input type="text" name="birthplace" value="<?php echo htmlspecialchars($record['birthplace']); ?>" required></div>
              <div class="form-group"><label>Citizenship</label><input type="text" name="citizenship" value="<?php echo htmlspecialchars($record['citizenship']); ?>" required></div>
              <div class="form-group"><label>Civil Status</label><input type="text" name="civilstatus" value="<?php echo htmlspecialchars($record['civilstatus']); ?>" required></div>
              <div class="form-group"><label>Employment</label><input type="text" name="employment" value="<?php echo htmlspecialchars($record['employment']); ?>" required></div>
            </div>
          </fieldset>

          <fieldset>
            <legend>Family / Guardian</legend>
            <div class="form-grid">
              <div class="form-group"><label>Mother's Name</label><input type="text" name="mother" value="<?php echo htmlspecialchars($record['mother']); ?>" required></div>
              <div class="form-group"><label>Mother's Phone</label><input type="text" name="mphone_number" value="<?php echo htmlspecialchars($record['mphone_number']); ?>" required></div>
              <div class="form-group"><label>Father's Name</label><input type="text" name="father" value="<?php echo htmlspecialchars($record['father']); ?>" required></div>
              <div class="form-group"><label>Father's Phone</label><input type="text" name="fphone_number" value="<?php echo htmlspecialchars($record['fphone_number']); ?>" required></div>
              <div class="form-group"><label>Guardian's Name</label><input type="text" name="guardian" value="<?php echo htmlspecialchars($record['guardian']); ?>" required></div>
              <div class="form-group"><label>Guardian's Phone</label><input type="text" name="gphone_number" value="<?php echo htmlspecialchars($record['gphone_number']); ?>" required></div>
            </div>
          </fieldset>

          <fieldset>
            <legend>Academic Information</legend>
            <div class="form-grid">
              <div class="form-group"><label>Course</label><input type="text" name="course" value="<?php echo htmlspecialchars($record['course']); ?>" required></div>
              <div class="form-group"><label>Major</label><input type="text" name="major" value="<?php echo htmlspecialchars($record['major']); ?>" required></div>
              <div class="form-group"><label>School Address</label><input type="text" name="school_address" value="<?php echo htmlspecialchars($record['school_address']); ?>" required></div>
              <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" value="<?php echo htmlspecialchars($record['academic_year']); ?>" required></div>
              <div class="form-group"><label>Scholarship</label><input type="text" name="scholarship" value="<?php echo htmlspecialchars($record['scholarship']); ?>" required></div>
            </div>
          </fieldset>

          <fieldset>
            <legend>Contact & Address</legend>
            <div class="form-grid">
              <div class="form-group full"><label>Full Address</label><input type="text" name="full_address" value="<?php echo htmlspecialchars($record['full_address']); ?>" required></div>
              <div class="form-group"><label>Mobile Number</label><input type="text" name="mobile_number" value="<?php echo htmlspecialchars($record['mobile_number']); ?>" required></div>
              <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($record['email']); ?>" required></div>
            </div>
          </fieldset>

          <button type="submit" class="btn primary" style="margin-top:10px;">Update Record</button>
          <a href="adminpanel.php" class="btn" style="margin-top:10px; display:inline-block; text-decoration:none;">Cancel</a>
        </form>
    </div>
</body>
</html>