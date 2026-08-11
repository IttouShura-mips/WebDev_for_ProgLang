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
    foreach ($fields as $f) { $values[] = $_POST[$f] ?? ''; }
    $stmt->bind_param($types, ...$values);
    if ($stmt->execute()) {
        header("Location: adminpanel.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Student</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    .form-page { padding: 30px; max-width: 900px; margin: 0 auto; }
    .form-page h2 { color: var(--primary-neon); margin-bottom: 25px; font-size: 1.5rem; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-grid.full { grid-template-columns: 1fr; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-size: 12px; margin-bottom: 6px; color: var(--text-muted-teal); text-transform: uppercase; font-weight: 600; }
    .form-group input, .form-group select {
      padding: 12px 14px; background: var(--bg-deep-abyss); border: 1px solid var(--border-teal);
      border-radius: 8px; color: var(--text-high-contrast); font-size: 0.9rem; outline: none;
      transition: border-color 0.2s ease; font-family: inherit;
    }
    .form-group input:focus, .form-group select:focus { border-color: var(--primary-neon); }
    fieldset { border: 1px solid var(--border-teal); border-radius: 10px; padding: 25px; margin-bottom: 25px; }
    legend { font-weight: bold; padding: 0 12px; color: var(--primary-neon); font-size: 1rem; }
    .form-actions { display: flex; gap: 12px; margin-top: 10px; }
    .error-banner { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div>
      <div class="brand"><h2>Admin Panel</h2></div>
      <ul class="nav-links">
        <li><a href="adminpanel.php">Dashboard</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="enrollments.php">Enrollments</a></li>
        <li><a href="received-mail.php">Received Mail</a></li>
      </ul>
    </div>
    <div class="sidebar-footer">
      <a href="../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
      <a href="logout.php" class="btn-homepage" style="margin-top:10px; border-color:#ef4444; color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Add New Student</h1>
      <div class="user-profile">
        <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']); ?></span>
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_name'] ?? $_SESSION['admin_user'],0,1)); ?></div>
      </div>
    </header>

    <div class="form-page">
      <?php if (isset($error)): ?>
        <div class="error-banner"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
      <?php endif; ?>

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

        <div class="form-actions">
          <button type="submit" class="btn primary">Save Record</button>
          <a href="adminpanel.php" class="btn">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>