<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

/* ============================================
   ENSURE TABLES EXIST
   ============================================ */
$conn->query("CREATE TABLE IF NOT EXISTS instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    department VARCHAR(50) NOT NULL,
    status ENUM('active','former') DEFAULT 'active',
    profile_pic VARCHAR(255) DEFAULT '',
    gender VARCHAR(20) DEFAULT '',
    birthday DATE DEFAULT NULL,
    citizenship VARCHAR(50) DEFAULT '',
    civil_status VARCHAR(30) DEFAULT '',
    employment VARCHAR(30) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(30) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS instructor_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    code VARCHAR(50) DEFAULT '',
    title VARCHAR(255) DEFAULT '',
    units DECIMAL(4,1) DEFAULT 0,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS instructor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    day VARCHAR(50) DEFAULT '',
    time VARCHAR(100) DEFAULT '',
    room VARCHAR(50) DEFAULT '',
    subject VARCHAR(50) DEFAULT '',
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ============================================
   AJAX HANDLER
   ============================================ */
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_REQUEST['action'];

    if ($action === 'list') {
        $rows = [];
        $res = $conn->query("SELECT * FROM instructors ORDER BY id ASC");
        while ($r = $res->fetch_assoc()) {
            // total units per instructor
            $tid = intval($r['id']);
            $u = $conn->query("SELECT COALESCE(SUM(units),0) AS total FROM instructor_subjects WHERE instructor_id = $tid")->fetch_assoc();
            $r['total_units'] = floatval($u['total']);
            $rows[] = $r;
        }
        echo json_encode(['success'=>true, 'data'=>$rows]); exit;
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $res = $conn->query("SELECT * FROM instructors WHERE id = $id");
        $inst = $res->fetch_assoc();
        if (!$inst) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        $subs = [];
        $sr = $conn->query("SELECT * FROM instructor_subjects WHERE instructor_id = $id ORDER BY id ASC");
        while ($s = $sr->fetch_assoc()) $subs[] = $s;
        $sch = [];
        $scr = $conn->query("SELECT * FROM instructor_schedules WHERE instructor_id = $id ORDER BY id ASC");
        while ($s = $scr->fetch_assoc()) $sch[] = $s;
        $inst['subjects'] = $subs;
        $inst['schedules'] = $sch;
        echo json_encode(['success'=>true, 'data'=>$inst]); exit;
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO instructors (full_name, department, gender, birthday, citizenship, civil_status, employment, email, phone, address, status) VALUES (?,?,?,?,?,?,?,?,?,?, 'active')");
        $full = trim($_POST['full_name'] ?? '');
        $dept = trim($_POST['department'] ?? 'CICS');
        $gender = trim($_POST['gender'] ?? '');
        $bday = trim($_POST['birthday'] ?? '') ?: null;
        $cit = trim($_POST['citizenship'] ?? '');
        $civil = trim($_POST['civil_status'] ?? '');
        $emp = trim($_POST['employment'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        if ($full === '') { echo json_encode(['success'=>false,'message'=>'Full name required.']); exit; }
        $stmt->bind_param("ssssssssss", $full, $dept, $gender, $bday, $cit, $civil, $emp, $email, $phone, $addr);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        echo json_encode(['success'=>true, 'id'=>$stmt->insert_id]); exit;
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $full = trim($_POST['full_name'] ?? '');
        $dept = trim($_POST['department'] ?? 'CICS');
        $gender = trim($_POST['gender'] ?? '');
        $bday = trim($_POST['birthday'] ?? '') ?: null;
        $cit = trim($_POST['citizenship'] ?? '');
        $civil = trim($_POST['civil_status'] ?? '');
        $emp = trim($_POST['employment'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        $stmt = $conn->prepare("UPDATE instructors SET full_name=?, department=?, gender=?, birthday=?, citizenship=?, civil_status=?, employment=?, email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $full, $dept, $gender, $bday, $cit, $civil, $emp, $email, $phone, $addr, $id);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'archive') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE instructors SET status='former' WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'restore') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE instructors SET status='active' WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("DELETE FROM instructors WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'add_subject') {
        $iid = intval($_POST['instructor_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $units = floatval($_POST['units'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO instructor_subjects (instructor_id, code, title, units) VALUES (?,?,?,?)");
        $stmt->bind_param("issd", $iid, $code, $title, $units);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'edit_subject') {
        $sid = intval($_POST['subject_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $units = floatval($_POST['units'] ?? 0);
        $stmt = $conn->prepare("UPDATE instructor_subjects SET code=?, title=?, units=? WHERE id=?");
        $stmt->bind_param("ssdi", $code, $title, $units, $sid);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete_subject') {
        $sid = intval($_POST['subject_id'] ?? 0);
        $conn->query("DELETE FROM instructor_subjects WHERE id=$sid");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'add_schedule') {
        $iid = intval($_POST['instructor_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $stmt = $conn->prepare("INSERT INTO instructor_schedules (instructor_id, day, time, room, subject) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $iid, $day, $time, $room, $subject);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'edit_schedule') {
        $sid = intval($_POST['schedule_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $stmt = $conn->prepare("UPDATE instructor_schedules SET day=?, time=?, room=?, subject=? WHERE id=?");
        $stmt->bind_param("ssssi", $day, $time, $room, $subject, $sid);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete_schedule') {
        $sid = intval($_POST['schedule_id'] ?? 0);
        $conn->query("DELETE FROM instructor_schedules WHERE id=$sid");
        echo json_encode(['success'=>true]); exit;
    }

    echo json_encode(['success'=>false, 'message'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel - Instructors</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    .department-section { margin-bottom: 40px; }
    .department-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; background:var(--bg-deep-abyss); padding:15px; border-radius:8px; border-left:4px solid var(--primary-neon); }
    .department-header h2 { color: var(--primary-neon); font-size:1.3rem; }
    .department-actions { display:flex; gap:10px; }
    .btn-action.archive { border-color:#f59e0b; color:#f59e0b; }
    .btn-action.archive:hover { background:#f59e0b; color:white; }
    .btn-action.restore { border-color:#10b981; color:#10b981; }
    .btn-action.restore:hover { background:#10b981; color:white; }
    .btn-action.view { border-color:#0ea5e9; color:#0ea5e9; }
    .btn-action.view:hover { background:#0ea5e9; color:white; }

    .operation-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:5000; justify-content:center; align-items:center; padding:20px; }
    .operation-modal-overlay.active { display:flex; }
    .operation-modal-box { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:30px; color:var(--text-high-contrast); text-align:center; }
    .operation-icon { font-size:50px; margin-bottom:15px; }
    .operation-modal-box.success { border-color:var(--success-green); box-shadow:0 20px 50px rgba(16,185,129,0.3); }
    .operation-modal-box.warning { border-color:#f59e0b; box-shadow:0 20px 50px rgba(245,158,11,0.3); }
    .operation-modal-box.success .operation-icon { color:var(--success-green); }
    .operation-modal-box.warning .operation-icon { color:#f59e0b; }
    .operation-modal-box h3 { font-size:20px; font-weight:800; margin-bottom:10px; }
    .operation-modal-box p { font-size:14px; color:var(--text-muted-teal); margin-bottom:20px; line-height:1.6; }
    .operation-form-group { margin-bottom:15px; text-align:left; }
    .operation-form-group label { display:block; font-size:12px; color:var(--text-muted-teal); font-weight:600; margin-bottom:6px; text-transform:uppercase; }
    .operation-form-group input { width:100%; padding:10px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; color:var(--text-high-contrast); outline:none; font-family:inherit; }
    .operation-form-group input[type="radio"] { width:auto; margin-right:5px; }
    .operation-form-group .radio-label { display:inline-block; margin-right:15px; text-transform:none; font-size:0.9rem; }
    .operation-modal-actions { display:flex; justify-content:center; gap:10px; flex-wrap:wrap; }
    .op-btn { padding:10px 25px; border:none; border-radius:6px; cursor:pointer; font-weight:700; font-size:0.9rem; }
    .op-btn.cancel { background:var(--bg-card); color:var(--text-high-contrast); border:1px solid var(--border-teal); }
    .op-btn.cancel:hover { background:var(--bg-card-hover); }
    .op-btn.confirm-success { background:var(--success-green); color:white; }
    .op-btn.confirm-success:hover { background:#059669; }
    .op-btn.confirm-danger { background:#dc2626; color:white; }
    .op-btn.confirm-danger:hover { background:#b91c1c; }

    .view-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:3000; justify-content:center; align-items:center; padding:20px; }
    .view-modal-overlay.active { display:flex; }
    .view-modal-container { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:900px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:0; color:var(--text-high-contrast); }
    .view-modal-header { background:var(--bg-deep-abyss); padding:20px; border-bottom:2px solid var(--primary-neon); display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; border-radius:16px 16px 0 0; }
    .view-modal-header h3 { font-size:20px; color:var(--primary-neon); display:flex; align-items:center; gap:10px; }
    .view-modal-close { background:transparent; border:none; color:var(--text-muted-teal); font-size:24px; cursor:pointer; }
    .view-modal-close:hover { color:#ef4444; }
    .view-modal-body { padding:20px; }
    .profile-pic-container { text-align:center; margin-bottom:20px; }
    .profile-pic { width:120px; height:120px; border-radius:50%; border:3px solid var(--primary-neon); box-shadow:var(--neon-glow); background:var(--bg-deep-abyss); display:flex; align-items:center; justify-content:center; font-size:40px; color:var(--primary-neon); margin:0 auto; }
    .profile-pic-container h2 { margin-top:10px; color:var(--primary-neon); }
    .profile-pic-container p { color:var(--text-muted-teal); }
    .info-section { margin-bottom:30px; }
    .info-section-title { font-size:16px; font-weight:700; color:var(--primary-neon); border-bottom:1px solid var(--border-teal); padding-bottom:10px; margin-bottom:15px; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
    .info-item { display:flex; gap:10px; }
    .info-label { width:140px; color:var(--text-muted-teal); font-size:14px; font-weight:600; }
    .info-value { color:var(--text-high-contrast); font-size:14px; flex:1; }
    .subject-table, .schedule-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
    .subject-table th, .subject-table td, .schedule-table th, .schedule-table td { padding:10px 8px; border:1px solid var(--border-teal); text-align:left; color:var(--text-high-contrast); }
    .subject-table th, .schedule-table th { background-color:var(--bg-deep-abyss); color:var(--primary-neon); font-weight:600; }
    .subject-table tr:nth-child(even), .schedule-table tr:nth-child(even) { background-color:var(--bg-deep-abyss); }
    .subject-table tr:hover, .schedule-table tr:hover { background-color:var(--bg-card-hover); }
    .btn-small { padding:4px 8px; border:1px solid var(--border-teal); background:transparent; color:var(--text-high-contrast); border-radius:4px; cursor:pointer; font-size:12px; }
    .btn-small:hover { background:var(--bg-card-hover); }
    .btn-small.add { border-color:var(--primary-neon); color:var(--primary-neon); }
    .btn-small.add:hover { background:var(--primary-neon); color:var(--bg-deep-abyss); }
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
        <li><a href="enrolled.php">Enrolled</a></li>
        <li class="active"><a href="instructor.php">Instructors</a></li>
        <li><a href="curriculum.php">Curriculum</a></li>
        <li><a href="received-mail.php">Received Mail</a></li>
      </ul>
    </div>
    <div class="sidebar-footer">
      <a href="../../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
      <a href="logout.php" class="btn-homepage" style="margin-top:10px; border-color:#ef4444; color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Instructor Management</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="instructorSearchInput" placeholder="Search instructors..." autocomplete="off"/>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <div class="cards-grid">
      <div class="card"><h3>Total Instructors</h3><div class="card-value" id="totalInstructorsCard">0</div></div>
      <div class="card"><h3>Active Instructors</h3><div class="card-value" id="activeInstructorsCard">0</div></div>
      <div class="card"><h3>Former Instructors</h3><div class="card-value" id="formerInstructorsCard">0</div></div>
      <div class="card"><h3>Total Departments</h3><div class="card-value" id="totalDepartmentsCard">2</div></div>
    </div>

    <div class="department-section">
      <div class="department-header">
        <h2>Active Instructors by Department</h2>
        <div class="department-actions">
          <button class="btn primary" onclick="openAddInstructorModal()"><i class="fas fa-plus"></i> Add Instructor</button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header"><h2>CICS - College of Information and Computing Sciences</h2></div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="CICS" data-status="active">
            <thead><tr><th>Full Name</th><th>Department</th><th>Units Handling</th><th>Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header"><h2>EDUC - College of Education</h2></div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="EDUC" data-status="active">
            <thead><tr><th>Full Name</th><th>Department</th><th>Units Handling</th><th>Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="department-section">
      <div class="department-header"><h2>Former Instructors by Department</h2></div>
      <div class="table-card">
        <div class="table-header"><h2>CICS - Former Instructors</h2></div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="CICS" data-status="former">
            <thead><tr><th>Full Name</th><th>Department</th><th>Units Handling</th><th>Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header"><h2>EDUC - Former Instructors</h2></div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="EDUC" data-status="former">
            <thead><tr><th>Full Name</th><th>Department</th><th>Units Handling</th><th>Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <div class="view-modal-overlay" id="viewInstructorModal">
    <div class="view-modal-container">
      <div class="view-modal-header">
        <h3><i class="fas fa-user-tie"></i> Instructor Full Details</h3>
        <button class="view-modal-close" onclick="closeViewInstructorModal()">&times;</button>
      </div>
      <div class="view-modal-body" id="viewInstructorBody"></div>
    </div>
  </div>

  <div class="operation-modal-overlay" id="operationModal">
    <div class="operation-modal-box" id="operationBox">
      <div class="operation-icon" id="operationIcon"></div>
      <h3 id="operationTitle"></h3>
      <p id="operationMessage"></p>
      <div id="operationFormContainer" style="display: none;"></div>
      <div class="operation-modal-actions" id="operationActions"></div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    let operationCallback = null;
    let currentInstructorId = null;
    let currentInstructorData = null;

    function escapeHtml(s) {
      return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    async function apiPost(action, data = {}) {
      const fd = new FormData();
      fd.append('action', action);
      for (const k in data) fd.append(k, data[k]);
      const res = await fetch('instructor.php', { method: 'POST', body: fd });
      return res.json();
    }

    function formatPhoneInput(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length > 11) value = value.substring(0, 11);
      let f = '';
      if (value.length > 0) f += value.substring(0, 4);
      if (value.length >= 5) f += '-' + value.substring(4, 7);
      if (value.length >= 8) f += '-' + value.substring(7, 11);
      input.value = f;
    }

    /* ============ Modals ============ */
    function showSuccessModal(title, message) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      box.className = 'operation-modal-box success';
      document.getElementById('operationIcon').innerHTML = '<i class="fas fa-check-circle"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = message;
      document.getElementById('operationFormContainer').style.display = 'none';
      document.getElementById('operationActions').innerHTML = `<button class="op-btn confirm-success" onclick="closeOperationModal()">OK</button>`;
      modal.classList.add('active');
    }
    function showWarningModal(title, message, confirmText, callback) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      box.className = 'operation-modal-box warning';
      document.getElementById('operationIcon').innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = message;
      document.getElementById('operationFormContainer').style.display = 'none';
      operationCallback = callback;
      document.getElementById('operationActions').innerHTML = `
        <button class="op-btn cancel" onclick="closeOperationModal()">Cancel</button>
        <button class="op-btn confirm-danger" onclick="executeOperation()">${confirmText}</button>`;
      modal.classList.add('active');
    }
    function showInputModal(title, fields, callback) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      box.className = 'operation-modal-box';
      document.getElementById('operationIcon').innerHTML = '<i class="fas fa-edit"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = '';
      let formHtml = '';
      fields.forEach((field, i) => {
        if (field.type === 'radio') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label>${field.options.map(opt => `<label class="radio-label"><input type="radio" name="op_radio_${i}" value="${opt}" ${field.value === opt ? 'checked' : ''}> ${opt}</label>`).join('')}</div>`;
        } else if (field.type === 'date') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="date" id="op_field_${i}" value="${field.value || ''}" /></div>`;
        } else if (field.type === 'phone') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" inputmode="numeric" id="op_field_${i}" value="${field.value || ''}" oninput="formatPhoneInput(this)" placeholder="0912-345-6789" /></div>`;
        } else {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" id="op_field_${i}" value="${escapeHtml(field.value || '')}" /></div>`;
        }
      });
      document.getElementById('operationFormContainer').innerHTML = formHtml;
      document.getElementById('operationFormContainer').style.display = 'block';
      operationCallback = callback;
      document.getElementById('operationActions').innerHTML = `
        <button class="op-btn cancel" onclick="closeOperationModal()">Cancel</button>
        <button class="op-btn confirm-success" onclick="executeInputOperation()">Save</button>`;
      modal.classList.add('active');
    }
    function executeOperation() { const cb = operationCallback; closeOperationModal(); if (cb) cb(); }
    function executeInputOperation() {
      const cb = operationCallback;
      const values = [];
      document.querySelectorAll('#operationFormContainer input').forEach(input => {
        if (input.type === 'radio') { if (input.checked) values.push(input.value); }
        else values.push(input.value);
      });
      closeOperationModal();
      if (cb) cb(values);
    }
    function closeOperationModal() {
      document.getElementById('operationModal').classList.remove('active');
      operationCallback = null;
    }

    /* ============ Load instructors ============ */
    async function loadInstructors() {
      const r = await apiPost('list');
      if (!r.success) return;
      const all = r.data;

      document.querySelectorAll('.instructor-table').forEach(table => {
        const dept = table.getAttribute('data-department');
        const status = table.getAttribute('data-status');
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        all.filter(i => i.department === dept && i.status === status).forEach(i => {
          const actions = status === 'active'
            ? `<button class="btn-action view" onclick="viewInstructor(${i.id})"><i class="fas fa-eye"></i> View</button>
               <button class="btn-action archive" onclick="archiveInstructor(${i.id})"><i class="fas fa-archive"></i> Archive</button>`
            : `<button class="btn-action view" onclick="viewInstructor(${i.id})"><i class="fas fa-eye"></i> View</button>
               <button class="btn-action restore" onclick="restoreInstructor(${i.id})"><i class="fas fa-undo"></i> Restore</button>
               <button class="btn-action delete" onclick="deleteInstructor(${i.id})"><i class="fas fa-trash"></i> Delete</button>`;
          const tr = document.createElement('tr');
          tr.innerHTML = `<td>${escapeHtml(i.full_name)}</td><td>${escapeHtml(i.department)}</td><td>${i.total_units}</td><td><div class="action-group">${actions}</div></td>`;
          tbody.appendChild(tr);
        });
      });

      document.getElementById('totalInstructorsCard').textContent = all.length;
      document.getElementById('activeInstructorsCard').textContent = all.filter(i => i.status === 'active').length;
      document.getElementById('formerInstructorsCard').textContent = all.filter(i => i.status === 'former').length;
    }

    /* ============ View instructor ============ */
    async function viewInstructor(id) {
      currentInstructorId = id;
      const r = await apiPost('get', { id });
      if (!r.success) { alert(r.message); return; }
      currentInstructorData = r.data;
      const inst = r.data;
      const canEdit = inst.status === 'active';
      const initials = (inst.full_name || '').split(' ').filter(w => w[0] && w[0] === w[0].toUpperCase()).map(w => w[0]).slice(0,2).join('');
      const totalUnits = inst.subjects.reduce((s, x) => s + parseFloat(x.units), 0);

      let subjectHtml = '';
      inst.subjects.forEach(s => {
        subjectHtml += `<tr><td>${escapeHtml(s.code)}</td><td>${escapeHtml(s.title)}</td><td>${s.units}</td>${canEdit ? `<td><button class="btn-small" onclick="editSubject(${s.id})">Edit</button> <button class="btn-small" onclick="deleteSubject(${s.id})">Delete</button></td>` : ''}</tr>`;
      });
      let scheduleHtml = '';
      inst.schedules.forEach(s => {
        scheduleHtml += `<tr><td>${escapeHtml(s.day)}</td><td>${escapeHtml(s.time)}</td><td>${escapeHtml(s.room)}</td><td>${escapeHtml(s.subject)}</td>${canEdit ? `<td><button class="btn-small" onclick="editSchedule(${s.id})">Edit</button> <button class="btn-small" onclick="deleteSchedule(${s.id})">Delete</button></td>` : ''}</tr>`;
      });

      document.getElementById('viewInstructorBody').innerHTML = `
        <div class="profile-pic-container">
          <div class="profile-pic">${initials}</div>
          <h2>${escapeHtml(inst.full_name)}</h2>
          <p>${escapeHtml(inst.department)} Department</p>
          <p style="color:var(--text-muted-teal); margin-top:5px;">Total Units Handling: <strong style="color:var(--primary-neon);">${totalUnits}</strong></p>
        </div>
        <div class="info-section"><div class="info-section-title">Bio Data</div>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Gender:</span><span class="info-value">${escapeHtml(inst.gender)}</span></div>
            <div class="info-item"><span class="info-label">Birthday:</span><span class="info-value">${escapeHtml(inst.birthday)}</span></div>
            <div class="info-item"><span class="info-label">Citizenship:</span><span class="info-value">${escapeHtml(inst.citizenship)}</span></div>
            <div class="info-item"><span class="info-label">Civil Status:</span><span class="info-value">${escapeHtml(inst.civil_status)}</span></div>
            <div class="info-item"><span class="info-label">Employment:</span><span class="info-value">${escapeHtml(inst.employment)}</span></div>
          </div>
        </div>
        <div class="info-section"><div class="info-section-title">Contact Info</div>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Email:</span><span class="info-value">${escapeHtml(inst.email)}</span></div>
            <div class="info-item"><span class="info-label">Phone:</span><span class="info-value">${escapeHtml(inst.phone)}</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">${escapeHtml(inst.address)}</span></div>
          </div>
        </div>
        <div class="info-section"><div class="info-section-title">Subject Handling</div>
          ${canEdit ? `<div style="margin-bottom:10px;"><button class="btn-small add" onclick="addSubject()">+ Add Subject</button></div>` : ''}
          <table class="subject-table"><thead><tr><th>Code</th><th>Title</th><th>Units</th>${canEdit ? '<th>Actions</th>' : ''}</tr></thead><tbody>${subjectHtml}</tbody></table>
        </div>
        <div class="info-section"><div class="info-section-title">Schedule List</div>
          ${canEdit ? `<div style="margin-bottom:10px;"><button class="btn-small add" onclick="addSchedule()">+ Add Schedule</button></div>` : ''}
          <table class="schedule-table"><thead><tr><th>Day</th><th>Time</th><th>Room</th><th>Subject</th>${canEdit ? '<th>Actions</th>' : ''}</tr></thead><tbody>${scheduleHtml}</tbody></table>
        </div>
        ${canEdit ? `<div style="text-align:center; margin-top:20px;"><button class="btn primary" onclick="openEditInstructorModal()"><i class="fas fa-edit"></i> Edit Instructor</button></div>` : ''}`;

      document.getElementById('viewInstructorModal').classList.add('active');
    }

    function closeViewInstructorModal() { document.getElementById('viewInstructorModal').classList.remove('active'); }

    /* ============ Add / Edit instructor ============ */
    function openAddInstructorModal() {
      showInputModal("Add New Instructor", [
        {label:"Full Name", value:""},
        {label:"Department", value:"CICS"},
        {label:"Gender", type:"radio", options:["Male","Female"], value:"Male"},
        {label:"Birthday", type:"date", value:""},
        {label:"Citizenship", value:"Filipino"},
        {label:"Civil Status", type:"radio", options:["Single","Married"], value:"Single"},
        {label:"Employment", type:"radio", options:["Full-Time","Part-Time"], value:"Full-Time"},
        {label:"Email", value:""},
        {label:"Phone Number", type:"phone", value:""},
        {label:"Address", value:""}
      ], async (v) => {
        if (!v[0]) { showWarningModal("Missing Information", "Please enter the Instructor's Full Name.", "OK", null); return; }
        const r = await apiPost('add', {
          full_name: v[0], department: v[1] || "CICS",
          gender: v[2] || "", birthday: v[3] || "",
          citizenship: v[4] || "", civil_status: v[5] || "",
          employment: v[6] || "", email: v[7] || "",
          phone: v[8] || "", address: v[9] || ""
        });
        if (r.success) { await loadInstructors(); showSuccessModal("Added!", "New instructor added successfully."); }
        else alert(r.message);
      });
    }

    function openEditInstructorModal() {
      const i = currentInstructorData;
      if (!i) return;
      showInputModal("Edit Instructor Information", [
        {label:"Full Name", value:i.full_name},
        {label:"Department", value:i.department},
        {label:"Gender", type:"radio", options:["Male","Female"], value:i.gender},
        {label:"Birthday", type:"date", value:i.birthday || ''},
        {label:"Citizenship", value:i.citizenship},
        {label:"Civil Status", type:"radio", options:["Single","Married"], value:i.civil_status},
        {label:"Employment", type:"radio", options:["Full-Time","Part-Time"], value:i.employment},
        {label:"Email", value:i.email},
        {label:"Phone Number", type:"phone", value:i.phone},
        {label:"Address", value:i.address}
      ], async (v) => {
        const r = await apiPost('edit', {
          id: i.id,
          full_name: v[0] || i.full_name,
          department: v[1] || i.department,
          gender: v[2] || i.gender,
          birthday: v[3] || i.birthday,
          citizenship: v[4] || i.citizenship,
          civil_status: v[5] || i.civil_status,
          employment: v[6] || i.employment,
          email: v[7] || i.email,
          phone: v[8] || i.phone,
          address: v[9] || i.address
        });
        if (r.success) {
          await loadInstructors();
          closeViewInstructorModal();
          showSuccessModal("Updated!", "Instructor information updated successfully.");
        } else alert(r.message);
      });
    }

    /* ============ Subject CRUD ============ */
    function addSubject() {
      showInputModal("Add Subject", [{label:"Code",value:""},{label:"Title",value:""},{label:"Units",value:"3"}], async (v) => {
        const r = await apiPost('add_subject', {
          instructor_id: currentInstructorId,
          code: v[0] || "", title: v[1] || "", units: v[2] || 0
        });
        if (r.success) {
          await viewInstructor(currentInstructorId);
          await loadInstructors();
          showSuccessModal("Added!", "Subject added successfully.");
        } else alert(r.message);
      });
    }
    function editSubject(subjectId) {
      const s = currentInstructorData.subjects.find(x => x.id == subjectId);
      if (!s) return;
      showInputModal("Edit Subject", [{label:"Code",value:s.code},{label:"Title",value:s.title},{label:"Units",value:s.units}], async (v) => {
        const r = await apiPost('edit_subject', {
          subject_id: subjectId,
          code: v[0] || s.code, title: v[1] || s.title, units: v[2] || s.units
        });
        if (r.success) {
          await viewInstructor(currentInstructorId);
          await loadInstructors();
          showSuccessModal("Updated!", "Subject updated successfully.");
        } else alert(r.message);
      });
    }
    function deleteSubject(subjectId) {
      showWarningModal("Delete Subject", "Are you sure you want to delete this subject?", "Delete", async () => {
        const r = await apiPost('delete_subject', { subject_id: subjectId });
        if (r.success) {
          await viewInstructor(currentInstructorId);
          await loadInstructors();
          showSuccessModal("Deleted!", "Subject deleted successfully.");
        } else alert(r.message);
      });
    }

    /* ============ Schedule CRUD ============ */
    function addSchedule() {
      showInputModal("Add Schedule", [
        {label:"Day (e.g. MON/WED)",value:""},
        {label:"Time (e.g. 07:30 AM - 09:00 AM)",value:""},
        {label:"Room",value:""},
        {label:"Subject Code",value:""}
      ], async (v) => {
        const r = await apiPost('add_schedule', {
          instructor_id: currentInstructorId,
          day: v[0] || "", time: v[1] || "", room: v[2] || "", subject: v[3] || ""
        });
        if (r.success) { await viewInstructor(currentInstructorId); showSuccessModal("Added!", "Schedule added successfully."); }
        else alert(r.message);
      });
    }
    function editSchedule(scheduleId) {
      const s = currentInstructorData.schedules.find(x => x.id == scheduleId);
      if (!s) return;
      showInputModal("Edit Schedule", [
        {label:"Day",value:s.day},{label:"Time",value:s.time},{label:"Room",value:s.room},{label:"Subject Code",value:s.subject}
      ], async (v) => {
        const r = await apiPost('edit_schedule', {
          schedule_id: scheduleId,
          day: v[0] || s.day, time: v[1] || s.time, room: v[2] || s.room, subject: v[3] || s.subject
        });
        if (r.success) { await viewInstructor(currentInstructorId); showSuccessModal("Updated!", "Schedule updated successfully."); }
        else alert(r.message);
      });
    }
    function deleteSchedule(scheduleId) {
      showWarningModal("Delete Schedule", "Are you sure you want to delete this schedule?", "Delete", async () => {
        const r = await apiPost('delete_schedule', { schedule_id: scheduleId });
        if (r.success) { await viewInstructor(currentInstructorId); showSuccessModal("Deleted!", "Schedule deleted successfully."); }
        else alert(r.message);
      });
    }

    /* ============ Archive / Restore / Delete ============ */
    function archiveInstructor(id) {
      showWarningModal("Archive Instructor", "Are you sure you want to archive this instructor? They will move to Former Instructors.", "Archive", async () => {
        const r = await apiPost('archive', { id });
        if (r.success) { await loadInstructors(); showSuccessModal("Archived!", "Instructor moved to Former Instructors."); }
        else alert(r.message);
      });
    }
    function restoreInstructor(id) {
      showWarningModal("Restore Instructor", "Are you sure you want to restore this instructor?", "Restore", async () => {
        const r = await apiPost('restore', { id });
        if (r.success) { await loadInstructors(); showSuccessModal("Restored!", "Instructor restored."); }
        else alert(r.message);
      });
    }
    function deleteInstructor(id) {
      showWarningModal("Delete Instructor", "Are you sure you want to permanently delete this instructor?", "Delete", async () => {
        const r = await apiPost('delete', { id });
        if (r.success) { await loadInstructors(); showSuccessModal("Deleted!", "Instructor deleted permanently."); }
        else alert(r.message);
      });
    }

    document.addEventListener('DOMContentLoaded', loadInstructors);
  </script>
</body>
</html>