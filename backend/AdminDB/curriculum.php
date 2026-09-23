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
$conn->query("CREATE TABLE IF NOT EXISTS curriculums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    year_level VARCHAR(100) DEFAULT '',
    academic_year VARCHAR(50) DEFAULT '',
    total_units INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS curriculum_semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curriculum_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    FOREIGN KEY (curriculum_id) REFERENCES curriculums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS curriculum_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_id INT NOT NULL,
    prereq VARCHAR(100) DEFAULT 'None',
    code VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    lec DECIMAL(4,1) DEFAULT 0,
    lab DECIMAL(4,1) DEFAULT 0,
    units DECIMAL(4,1) DEFAULT 0,
    FOREIGN KEY (semester_id) REFERENCES curriculum_semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ============================================
   AJAX HANDLER
   ============================================ */
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_REQUEST['action'];

    if ($action === 'list') {
        $rows = [];
        $res = $conn->query("SELECT * FROM curriculums ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success'=>true, 'data'=>$rows]); exit;
    }

    if ($action === 'add_curriculum') {
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $ay = trim($_POST['academic_year'] ?? '');
        $units = intval($_POST['units'] ?? 0);
        if ($code === '' || $title === '') { echo json_encode(['success'=>false,'message'=>'Code and Title are required.']); exit; }
        $stmt = $conn->prepare("INSERT INTO curriculums (code,title,year_level,academic_year,total_units) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssssi", $code, $title, $year, $ay, $units);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        $cid = $stmt->insert_id;
        $conn->query("INSERT INTO curriculum_semesters (curriculum_id, name) VALUES ($cid, '1st Year - 1st Semester')");
        echo json_encode(['success'=>true, 'id'=>$cid]); exit;
    }

    if ($action === 'delete_curriculum') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("DELETE FROM curriculums WHERE id = $id");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $res = $conn->query("SELECT * FROM curriculums WHERE id = $id");
        $cur = $res->fetch_assoc();
        if (!$cur) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        $sems = [];
        $sr = $conn->query("SELECT * FROM curriculum_semesters WHERE curriculum_id = $id ORDER BY id ASC");
        while ($s = $sr->fetch_assoc()) {
            $subs = [];
            $subres = $conn->query("SELECT * FROM curriculum_subjects WHERE semester_id = {$s['id']} ORDER BY id ASC");
            while ($sub = $subres->fetch_assoc()) $subs[] = $sub;
            $s['subjects'] = $subs;
            $sems[] = $s;
        }
        $cur['semesters'] = $sems;
        echo json_encode(['success'=>true, 'data'=>$cur]); exit;
    }

    if ($action === 'add_semester') {
        $cid = intval($_POST['curriculum_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { echo json_encode(['success'=>false,'message'=>'Name required']); exit; }
        $stmt = $conn->prepare("INSERT INTO curriculum_semesters (curriculum_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $cid, $name);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'edit_semester') {
        $sid = intval($_POST['semester_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $stmt = $conn->prepare("UPDATE curriculum_semesters SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $sid);
        $stmt->execute();
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'delete_semester') {
        $sid = intval($_POST['semester_id'] ?? 0);
        $conn->query("DELETE FROM curriculum_semesters WHERE id = $sid");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'add_subject') {
        $sid = intval($_POST['semester_id'] ?? 0);
        $prereq = trim($_POST['prereq'] ?? 'None');
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $lec = floatval($_POST['lec'] ?? 0);
        $lab = floatval($_POST['lab'] ?? 0);
        $units = floatval($_POST['units'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO curriculum_subjects (semester_id, prereq, code, title, lec, lab, units) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("isssddd", $sid, $prereq, $code, $title, $lec, $lab, $units);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'edit_subject') {
        $subId = intval($_POST['subject_id'] ?? 0);
        $prereq = trim($_POST['prereq'] ?? 'None');
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $lec = floatval($_POST['lec'] ?? 0);
        $lab = floatval($_POST['lab'] ?? 0);
        $units = floatval($_POST['units'] ?? 0);
        $stmt = $conn->prepare("UPDATE curriculum_subjects SET prereq=?, code=?, title=?, lec=?, lab=?, units=? WHERE id=?");
        $stmt->bind_param("sssdddi", $prereq, $code, $title, $lec, $lab, $units, $subId);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>$stmt->error]); exit; }
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'delete_subject') {
        $subId = intval($_POST['subject_id'] ?? 0);
        $conn->query("DELETE FROM curriculum_subjects WHERE id = $subId");
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
  <title>Admin Control Panel - Curriculum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    ::-webkit-scrollbar { width:10px; height:10px; }
    ::-webkit-scrollbar-track { background:var(--bg-deep-abyss); border-radius:10px; }
    ::-webkit-scrollbar-thumb { background:var(--border-teal); border-radius:10px; border:2px solid var(--bg-deep-abyss); }
    ::-webkit-scrollbar-thumb:hover { background:var(--primary-neon); }

    .cards-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:40px; }
    .card { background:var(--text-high-contrast); padding:20px; border-radius:8px; box-shadow:0 0px 5px var(--primary-neon); }
    .card h3 { font-size:0.85rem; color:var(--bg-deep-abyss); text-transform:uppercase; margin-bottom:8px; }
    .card-value { font-size:1.8rem; font-weight:bold; color:var(--bg-deep-abyss); }

    th.sortable { cursor:pointer; user-select:none; }
    th.no-sort { cursor:default; }
    th.no-sort:hover { background-color:#a5bdd5; }
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
    .operation-form-group input { width:100%; padding:10px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; color:var(--text-high-contrast); outline:none; }
    .operation-modal-actions { display:flex; justify-content:center; gap:10px; }
    .op-btn { padding:10px 25px; border:none; border-radius:6px; cursor:pointer; font-weight:700; font-size:0.9rem; }
    .op-btn.cancel { background:var(--bg-card); color:var(--text-high-contrast); border:1px solid var(--border-teal); }
    .op-btn.cancel:hover { background:var(--bg-card-hover); }
    .op-btn.confirm-success { background:var(--success-green); color:white; }
    .op-btn.confirm-success:hover { background:#059669; }
    .op-btn.confirm-danger { background:#dc2626; color:white; }
    .op-btn.confirm-danger:hover { background:#b91c1c; }

    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:2000; justify-content:center; align-items:center; padding:20px; }
    .modal-overlay.active { display:flex; }
    .modal-container { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:30px; color:var(--text-high-contrast); position:relative; }
    .modal-close { position:absolute; top:15px; right:20px; background:transparent; border:none; color:var(--text-muted-teal); font-size:28px; cursor:pointer; }
    .modal-close:hover { color:#ef4444; }
    .modal-title { font-size:20px; font-weight:800; color:var(--primary-neon); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
    .form-group { margin-bottom:15px; }
    .form-group label { display:block; font-size:12px; color:var(--text-muted-teal); font-weight:600; margin-bottom:6px; text-transform:uppercase; }
    .form-group input { width:100%; padding:10px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; color:var(--text-high-contrast); outline:none; }
    .form-group input:focus { border-color:var(--primary-neon); }
    .modal-footer { display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--border-teal); padding-top:20px; }
    .btn-cancel { background:var(--bg-card); color:var(--text-high-contrast); border:1px solid var(--border-teal); padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; }
    .btn-cancel:hover { background:var(--bg-card-hover); }
    .btn-save { background:var(--success-green); color:white; border:none; padding:10px 25px; border-radius:6px; cursor:pointer; font-weight:700; }
    .btn-save:hover { background:#059669; }

    .view-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:3000; justify-content:center; align-items:center; padding:20px; }
    .view-modal-overlay.active { display:flex; }
    .view-modal-container { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:1000px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:0; color:var(--text-high-contrast); }
    .view-modal-header { background:var(--bg-deep-abyss); padding:20px; border-bottom:2px solid var(--primary-neon); display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; border-radius:16px 16px 0 0; }
    .view-modal-header h3 { font-size:20px; color:var(--primary-neon); display:flex; align-items:center; gap:10px; }
    .view-modal-close { background:transparent; border:none; color:var(--text-muted-teal); font-size:24px; cursor:pointer; }
    .view-modal-close:hover { color:#ef4444; }
    .view-modal-body { padding:20px; }
    .curriculum-info { text-align:center; margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid var(--border-teal); }
    .curriculum-info h2 { font-size:24px; color:var(--text-high-contrast); font-weight:800; margin-bottom:5px; }
    .curriculum-info h3 { font-size:16px; color:var(--primary-neon); font-weight:600; margin-bottom:10px; }
    .curriculum-info p { font-size:13px; color:var(--text-muted-teal); }
    .semester-section { margin-bottom:30px; }
    .semester-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
    .semester-title { font-size:16px; font-weight:700; color:var(--primary-neon); background:var(--bg-deep-abyss); padding:10px; border-radius:4px; flex:1; }
    .semester-actions { display:flex; gap:6px; margin-left:10px; }
    .btn-small { padding:5px 10px; border:1px solid var(--border-teal); background:transparent; color:var(--text-high-contrast); border-radius:4px; cursor:pointer; font-size:12px; }
    .btn-small:hover { background:var(--bg-card-hover); }
    .btn-small.add { border-color:var(--primary-neon); color:var(--primary-neon); }
    .btn-small.add:hover { background:var(--primary-neon); color:var(--bg-deep-abyss); }
    .curriculum-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
    .curriculum-table th, .curriculum-table td { padding:10px 8px; border:1px solid var(--border-teal); text-align:left; color:var(--text-high-contrast); }
    .curriculum-table th { background-color:var(--bg-deep-abyss); color:var(--primary-neon); font-weight:600; }
    .curriculum-table tr:nth-child(even) { background-color:var(--bg-deep-abyss); }
    .curriculum-table tr:hover { background-color:var(--bg-card-hover); }
    .curriculum-table .total-row td { background:var(--bg-deep-abyss); font-weight:700; color:var(--primary-neon); border-top:2px solid var(--primary-neon); }
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
        <li><a href="instructor.php">Instructors</a></li>
        <li class="active"><a href="curriculum.php">Curriculum</a></li>
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
      <h1>Curriculum Management</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="curriculumSearchInput" placeholder="Search curriculum..." autocomplete="off"/>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <div class="cards-grid">
      <div class="card"><h3>Total Curriculums</h3><div class="card-value" id="totalCurriculumsCard">0</div></div>
      <div class="card"><h3>Active Programs</h3><div class="card-value" id="activeProgramsCard">0</div></div>
      <div class="card"><h3>Total Courses Offered</h3><div class="card-value" id="totalCoursesCard">0</div></div>
      <div class="card"><h3>Total Units Enrolled</h3><div class="card-value" id="totalUnitsCard">0</div></div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h2>List of Courses</h2>
        <div class="table-actions">
          <button class="btn primary" onclick="openAddCurriculumModal()"><i class="fas fa-plus"></i> Add Course</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="curriculumTable">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable(0)">Course Code <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(1)">Course Title <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(2)">Academic Year <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(3)">Total Units <i class="fas fa-sort"></i></th>
              <th class="no-sort">Action</th>
            </tr>
          </thead>
          <tbody id="curriculumTableBody"></tbody>
        </table>
      </div>
    </div>
  </main>

  <div class="modal-overlay" id="addCurriculumModal">
    <div class="modal-container">
      <button class="modal-close" onclick="closeAddCurriculumModal()">&times;</button>
      <div class="modal-title"><i class="fas fa-plus-circle"></i> Add Course</div>
      <form id="addCurriculumForm" onsubmit="event.preventDefault(); saveCurriculum();">
        <div class="form-group"><label>Course Code</label><input type="text" id="curriculumCode" placeholder="e.g., BSCS-2024" required /></div>
        <div class="form-group"><label>Course Title</label><input type="text" id="curriculumTitle" placeholder="e.g., Bachelor of Science in Computer Science" required /></div>
        <div class="form-group"><label>Year Level Covered</label><input type="text" id="curriculumYear" placeholder="e.g., 1st - 4th Year" required /></div>
        <div class="form-group"><label>Academic Year</label><input type="text" id="curriculumAcademicYear" placeholder="e.g., S.Y. 2023-2024" required /></div>
        <div class="form-group"><label>Total Units</label><input type="number" id="curriculumUnits" placeholder="e.g., 210" required /></div>
      </form>
      <div class="modal-footer">
        <button class="btn-cancel" onclick="closeAddCurriculumModal()">Cancel</button>
        <button class="btn-save" onclick="saveCurriculum()"><i class="fas fa-save"></i> Save Course</button>
      </div>
    </div>
  </div>

  <div class="view-modal-overlay" id="viewCurriculumModal">
    <div class="view-modal-container">
      <div class="view-modal-header">
        <h3><i class="fas fa-book-open"></i> Full Curriculum Details</h3>
        <button class="view-modal-close" onclick="closeViewCurriculumModal()">&times;</button>
      </div>
      <div class="view-modal-body" id="viewCurriculumBody"></div>
    </div>
  </div>

  <div class="operation-modal-overlay" id="operationModal">
    <div class="operation-modal-box" id="operationBox">
      <div class="operation-icon" id="operationIcon"></div>
      <h3 id="operationTitle"></h3>
      <p id="operationMessage"></p>
      <div id="operationFormContainer" style="display:none;"></div>
      <div class="operation-modal-actions" id="operationActions"></div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    let sortDirection = 1;
    let operationCallback = null;
    let currentCurriculumId = null;
    let currentCurriculumData = null;

    function escapeHtml(s) {
      return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    async function apiPost(action, data = {}) {
      const fd = new FormData();
      fd.append('action', action);
      for (const k in data) fd.append(k, data[k]);
      const res = await fetch('curriculum.php', { method: 'POST', body: fd });
      return res.json();
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadCurriculumData();
      const searchInput = document.getElementById('curriculumSearchInput');
      const curriculumTable = document.getElementById('curriculumTable');
      searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        curriculumTable.querySelectorAll('tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
      });
    });

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
        formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" id="op_field_${i}" value="${escapeHtml(field.value || '')}" /></div>`;
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
      document.querySelectorAll('#operationFormContainer input').forEach(i => values.push(i.value));
      closeOperationModal();
      if (cb) cb(values);
    }
    function closeOperationModal() {
      document.getElementById('operationModal').classList.remove('active');
      operationCallback = null;
    }

    /* ============ Load list ============ */
    async function loadCurriculumData() {
      const tbody = document.getElementById('curriculumTableBody');
      const r = await apiPost('list');
      if (!r.success) return;
      tbody.innerHTML = '';
      let grandTotalUnits = 0;
      r.data.forEach(c => {
        grandTotalUnits += parseInt(c.total_units) || 0;
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escapeHtml(c.code)}</td><td>${escapeHtml(c.title)}</td><td>${escapeHtml(c.academic_year)}</td><td>${c.total_units}</td>
          <td><div class="action-group">
            <button class="btn-action view" onclick="viewCurriculum(${c.id})"><i class="fas fa-eye"></i> View</button>
            <button class="btn-action delete" onclick="deleteCurriculum(${c.id})"><i class="fas fa-trash"></i></button>
          </div></td>`;
        tbody.appendChild(tr);
      });
      document.getElementById('totalCurriculumsCard').textContent = r.data.length;
      document.getElementById('activeProgramsCard').textContent = r.data.length;
      document.getElementById('totalCoursesCard').textContent = r.data.length;
      document.getElementById('totalUnitsCard').textContent = grandTotalUnits;
    }

    /* ============ View curriculum ============ */
    async function viewCurriculum(id) {
      currentCurriculumId = id;
      const r = await apiPost('get', { id });
      if (!r.success) { alert(r.message); return; }
      currentCurriculumData = r.data;
      const c = r.data;
      const body = document.getElementById('viewCurriculumBody');
      let html = `<div class="curriculum-info">
        <h2>INTERWORLD COLLEGES FOUNDATION, INC</h2>
        <h3>Burgos St., Paniqui, Tarlac</h3>
        <p><strong>${escapeHtml(c.title)}</strong></p>
        <p>${escapeHtml(c.academic_year)}</p></div>`;
      c.semesters.forEach(sem => {
        html += `<div class="semester-section"><div class="semester-header">
          <div class="semester-title">${escapeHtml(sem.name)}</div>
          <div class="semester-actions">
            <button class="btn-small add" onclick="addSubject(${sem.id})">+ Add Subject</button>
            <button class="btn-small" onclick="editSemesterName(${sem.id})">Rename</button>
            <button class="btn-small" onclick="deleteSemester(${sem.id})">Delete</button>
          </div></div>
          <table class="curriculum-table"><thead><tr><th>Pre-req</th><th>Code</th><th>Title</th><th>Lec</th><th>Lab</th><th>Units</th><th>Actions</th></tr></thead><tbody>`;
        sem.subjects.forEach(s => {
          html += `<tr><td>${escapeHtml(s.prereq)}</td><td>${escapeHtml(s.code)}</td><td>${escapeHtml(s.title)}</td>
            <td>${s.lec}</td><td>${s.lab}</td><td>${s.units}</td>
            <td><button class="btn-small" onclick="editSubject(${s.id})">Edit</button>
            <button class="btn-small" onclick="deleteSubject(${s.id})">Delete</button></td></tr>`;
        });
        const tL = sem.subjects.reduce((sum, s) => sum + parseFloat(s.lec), 0);
        const tB = sem.subjects.reduce((sum, s) => sum + parseFloat(s.lab), 0);
        const tU = sem.subjects.reduce((sum, s) => sum + parseFloat(s.units), 0);
        html += `</tbody><tr class="total-row"><td colspan="2" style="text-align:right;">TOTAL</td><td></td><td>${tL}</td><td>${tB}</td><td>${tU}</td><td></td></tr></table></div>`;
      });
      html += `<div style="text-align:center; margin-top:20px;"><button class="btn-save" onclick="addSemester()"><i class="fas fa-plus"></i> Add Semester</button></div>`;
      body.innerHTML = html;
      document.getElementById('viewCurriculumModal').classList.add('active');
    }

    function findSubject(subjectId) {
      if (!currentCurriculumData) return null;
      for (const sem of currentCurriculumData.semesters)
        for (const sub of sem.subjects)
          if (sub.id == subjectId) return sub;
      return null;
    }

    /* ============ Semester CRUD ============ */
    function addSemester() {
      showInputModal("Add New Semester", [{label:"Semester Name", value:""}], async (values) => {
        if (!values[0]) return;
        const r = await apiPost('add_semester', { curriculum_id: currentCurriculumId, name: values[0] });
        if (r.success) { await viewCurriculum(currentCurriculumId); showSuccessModal("Success!", "Semester added successfully."); }
        else alert(r.message);
      });
    }
    function editSemesterName(semesterId) {
      const sem = currentCurriculumData.semesters.find(s => s.id == semesterId);
      if (!sem) return;
      showInputModal("Edit Semester Name", [{label:"Semester Name", value:sem.name}], async (values) => {
        if (!values[0]) return;
        const r = await apiPost('edit_semester', { semester_id: semesterId, name: values[0] });
        if (r.success) { await viewCurriculum(currentCurriculumId); showSuccessModal("Updated!", "Semester name updated."); }
        else alert(r.message);
      });
    }
    function deleteSemester(semesterId) {
      showWarningModal("Delete Semester", "Are you sure you want to delete this semester?", "Delete", async () => {
        const r = await apiPost('delete_semester', { semester_id: semesterId });
        if (r.success) { await viewCurriculum(currentCurriculumId); showSuccessModal("Deleted!", "Semester deleted successfully."); }
        else alert(r.message);
      });
    }

    /* ============ Subject CRUD ============ */
    function addSubject(semesterId) {
      showInputModal("Add New Subject", [
        {label:"Pre-requisite", value:"None"},
        {label:"Subject Code", value:""},
        {label:"Subject Title", value:""},
        {label:"Lecture Units", value:"3"},
        {label:"Lab Units", value:"0"},
        {label:"Total Units", value:"3"}
      ], async (values) => {
        const r = await apiPost('add_subject', {
          semester_id: semesterId,
          prereq: values[0] || "None",
          code: values[1] || "",
          title: values[2] || "",
          lec: values[3] || 0,
          lab: values[4] || 0,
          units: values[5] || 0
        });
        if (r.success) { await viewCurriculum(currentCurriculumId); showSuccessModal("Success!", "Subject added successfully."); }
        else alert(r.message);
      });
    }
    function editSubject(subjectId) {
      const sub = findSubject(subjectId);
      if (!sub) return;
      showInputModal("Edit Subject", [
        {label:"Pre-requisite", value:sub.prereq},
        {label:"Subject Code", value:sub.code},
        {label:"Subject Title", value:sub.title},
        {label:"Lecture Units", value:sub.lec},
        {label:"Lab Units", value:sub.lab},
        {label:"Total Units", value:sub.units}
      ], async (values) => {
        const r = await apiPost('edit_subject', {
          subject_id: subjectId,
          prereq: values[0] || sub.prereq,
          code: values[1] || sub.code,
          title: values[2] || sub.title,
          lec: values[3] || sub.lec,
          lab: values[4] || sub.lab,
          units: values[5] || sub.units
        });
        if (r.success) { await viewCurriculum(currentCurriculumId); showSuccessModal("Updated!", "Subject updated successfully."); }
        else alert(r.message);
      });
    }
    function deleteSubject(subjectId) {
      showWarningModal("Delete Subject", "Are you sure you want to delete this subject?", "Delete", async () => {
        const r = await apiPost('delete_subject', { subject_id: subjectId });
        if (r.success) { await viewCurriculum(currentCurriculumId); showSuccessModal("Deleted!", "Subject deleted successfully."); }
        else alert(r.message);
      });
    }

    function deleteCurriculum(id) {
      showWarningModal("Delete Curriculum", "Are you sure you want to delete this entire curriculum? All semesters and subjects will be removed.", "Delete", async () => {
        const r = await apiPost('delete_curriculum', { id });
        if (r.success) { await loadCurriculumData(); showSuccessModal("Deleted!", "Curriculum deleted successfully."); }
        else alert(r.message);
      });
    }

    function closeViewCurriculumModal() { document.getElementById('viewCurriculumModal').classList.remove('active'); }

    /* ============ Add Course modal ============ */
    function openAddCurriculumModal() {
      document.getElementById('addCurriculumForm').reset();
      document.getElementById('addCurriculumModal').classList.add('active');
    }
    function closeAddCurriculumModal() { document.getElementById('addCurriculumModal').classList.remove('active'); }

    async function saveCurriculum() {
      const code = document.getElementById('curriculumCode').value.trim();
      const title = document.getElementById('curriculumTitle').value.trim();
      const year = document.getElementById('curriculumYear').value.trim();
      const academicYear = document.getElementById('curriculumAcademicYear').value.trim();
      const units = document.getElementById('curriculumUnits').value.trim();
      if (!code || !title || !year || !academicYear || !units) {
        showWarningModal("Missing Information", "Please fill in all fields before saving.", "OK", null);
        return;
      }
      const r = await apiPost('add_curriculum', { code, title, year, academic_year: academicYear, units });
      if (r.success) {
        await loadCurriculumData();
        closeAddCurriculumModal();
        showSuccessModal("Course Added!", "Course has been added successfully! Click 'View' to add detailed subjects.");
      } else alert(r.message);
    }

    /* ============ Sorting ============ */
    function sortTable(columnIndex) {
      const table = document.getElementById('curriculumTable');
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.rows);
      sortDirection = sortDirection === 1 ? -1 : 1;
      rows.sort((a, b) => {
        const aT = a.cells[columnIndex].textContent.trim().toLowerCase();
        const bT = b.cells[columnIndex].textContent.trim().toLowerCase();
        if (aT < bT) return -1 * sortDirection;
        if (aT > bT) return 1 * sortDirection;
        return 0;
      });
      rows.forEach(r => tbody.appendChild(r));
      document.querySelectorAll('th.sortable i').forEach(i => i.className = 'fas fa-sort');
      const active = table.querySelector(`th.sortable:nth-child(${columnIndex + 1}) i`);
      if (active) active.className = sortDirection === 1 ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }
  </script>
</body>
</html>