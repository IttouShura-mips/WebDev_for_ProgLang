<?php
session_start();
if (!isset($_SESSION['admin_user'])) { header("Location: login.html"); exit(); }
require 'db.php';

$fields = ['firstname','middlename','lastname','suffix','gender','birthday','birthplace','citizenship','civilstatus','employment','mother','mphone_number','father','fphone_number','guardian','gphone_number','course','major','school_address','academic_year','scholarship','full_address','mobile_number','email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $types = str_repeat('s', count($fields));
    
    $sql = "INSERT INTO enrolled (" . implode(',', $fields) . ") VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    $values = [];
    foreach ($fields as $f) {
        $values[] = $_POST[$f] ?? '';
    }
    
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
        <h2>Add New Student</h2>
        <form method="POST">
          
          <fieldset>
            <legend>Personal Information</legend>
            <div class="form-grid">
              <div class="form-group"><label>First Name</label><input type="text" name="firstname" required></div>
              <div class="form-group"><label>Middle Name</label><input type="text" name="middlename" required></div>
              <div class="form-group"><label>Last Name</label><input type="text" name="lastname" required></div>
              <div class="form-group"><label>Suffix</label><input type="text" name="suffix"></div>
              <div class="form-group"><label>Gender</label>
                <select name="gender" required>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="form-group"><label>Birthday</label><input type="date" name="birthday" required></div>
              <div class="form-group"><label>Birthplace</label><input type="text" name="birthplace" required></div>
              <div class="form-group"><label>Citizenship</label><input type="text" name="citizenship" required></div>
              <div class="form-group"><label>Civil Status</label><input type="text" name="civilstatus" required></div>
              <div class="form-group"><label>Employment</label><input type="text" name="employment" required></div>
            </div>
          </fieldset>

          <fieldset>
            <legend>Family / Guardian</legend>
            <div class="form-grid">
              <div class="form-group"><label>Mother's Name</label><input type="text" name="mother" required></div>
              <div class="form-group"><label>Mother's Phone</label><input type="text" name="mphone_number" required></div>
              <div class="form-group"><label>Father's Name</label><input type="text" name="father" required></div>
              <div class="form-group"><label>Father's Phone</label><input type="text" name="fphone_number" required></div>
              <div class="form-group"><label>Guardian's Name</label><input type="text" name="guardian" required></div>
              <div class="form-group"><label>Guardian's Phone</label><input type="text" name="gphone_number" required></div>
            </div>
          </fieldset>

          <fieldset>
            <legend>Academic Information</legend>
            <div class="form-grid">
              <div class="form-group"><label>Course</label><input type="text" name="course" required></div>
              <div class="form-group"><label>Major</label><input type="text" name="major" required></div>
              <div class="form-group"><label>School Address</label><input type="text" name="school_address" required></div>
              <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" placeholder="e.g. 2026-2027" required></div>
              <div class="form-group"><label>Scholarship</label><input type="text" name="scholarship" required></div>
            </div>
          </fieldset>

          <fieldset>
            <legend>Contact & Address</legend>
            <div class="form-grid">
              <div class="form-group full"><label>Full Address</label><input type="text" name="full_address" required></div>
              <div class="form-group"><label>Mobile Number</label><input type="text" name="mobile_number" required></div>
              <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            </div>
          </fieldset>

          <button type="submit" class="btn primary" style="margin-top:10px;">Save Record</button>
          <a href="adminpanel.php" class="btn" style="margin-top:10px; display:inline-block; text-decoration:none;">Cancel</a>
        </form>
    </div>
</body>
</html>