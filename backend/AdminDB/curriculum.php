<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);
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
    .operation-modal-box.warning { border-color:var(--warning-yellow); box-shadow:0 20px 50px rgba(245,158,11,0.3); }
    .operation-modal-box.success .operation-icon { color:var(--success-green); }
    .operation-modal-box.warning .operation-icon { color:var(--warning-yellow); }
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
    .op-btn.confirm-danger { background:var(--danger-red); color:white; }
    .op-btn.confirm-danger:hover { background:#b91c1c; }

    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:2000; justify-content:center; align-items:center; padding:20px; }
    .modal-overlay.active { display:flex; }
    .modal-container { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); padding:30px; color:var(--text-high-contrast); position:relative; }
    @keyframes modalFadeIn { from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }
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
      <div class="card"><h3>Total Courses Offered</h3><div class="card-value">24</div></div>
      <div class="card"><h3>Total Units Enrolled</h3><div class="card-value">156</div></div>
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
      <form id="addCurriculumForm">
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

    const curriculumData = [
      { code:"BSCS-2024", title:"Bachelor of Science in Computer Science", year:"1st - 4th Year", academicYear:"S.Y. 2023-2024", totalUnits:210,
        details:{ school:"INTERWORLD COLLEGES FOUNDATION, INC", address:"Burgos St., Paniqui, Tarlac", courseTitle:"Bachelor of Science in Computer Science", yearLevel:"S.Y. 2023-2024",
          semesters:[
            { name:"1st Year - 1st Semester", subjects:[
              {prereq:"None", code:"GECCOED101YG1", title:"Understanding the Self", lec:3, lab:0, units:3},
              {prereq:"None", code:"GECPCOM101YG1", title:"Purposive Communication", lec:3, lab:0, units:3},
              {prereq:"None", code:"GECMATH101YG1", title:"Mathematics in the Modern World", lec:3, lab:0, units:3},
              {prereq:"None", code:"GECRPHIL101YG1", title:"Readings in Philippine History", lec:3, lab:0, units:3},
              {prereq:"None", code:"GECETHIC101YG1", title:"Ethics", lec:3, lab:0, units:3},
              {prereq:"None", code:"GECSCTS101YG1", title:"Science, Technology and Society", lec:3, lab:0, units:3},
              {prereq:"None", code:"CC1", title:"Introduction to Computing", lec:2, lab:3, units:3},
              {prereq:"None", code:"PC1", title:"Computer Programming 1 (Fund. of Programming)", lec:2, lab:3, units:3},
              {prereq:"None", code:"NSTP101YG1", title:"CWTS/ROTC 1", lec:3, lab:0, units:3},
              {prereq:"None", code:"PE1", title:"Physical Activities Towards Health and Fitness 1", lec:2, lab:0, units:2}
            ]},
            { name:"1st Year - 2nd Semester", subjects:[
              {prereq:"None", code:"GECRIZAL101YG1", title:"Rizal's Life and Works", lec:3, lab:0, units:3},
              {prereq:"None", code:"GECARTAP101YG2", title:"Art Appreciation", lec:3, lab:0, units:3},
              {prereq:"None", code:"CC2", title:"Computer Programming 2", lec:2, lab:3, units:3},
              {prereq:"None", code:"CC3", title:"Discrete Structures", lec:3, lab:0, units:3},
              {prereq:"None", code:"PC2", title:"Data Structures and Algorithms", lec:2, lab:3, units:3},
              {prereq:"None", code:"PCOMPEI101", title:"Web Systems and Technologies 1", lec:2, lab:3, units:3},
              {prereq:"None", code:"PC3", title:"Information Assurance and Security 1", lec:2, lab:3, units:3},
              {prereq:"None", code:"NSTP102YG1", title:"CWTS/ROTC 2", lec:3, lab:0, units:3},
              {prereq:"None", code:"PE2", title:"Physical Activities Towards Health and Fitness 2", lec:2, lab:0, units:2}
            ]}
          ]
        }
      },
      { code:"BSACT-2023", title:"2-Year Associate in Computer Technology", year:"1st - 2nd Year", academicYear:"S.Y. 2022-2023", totalUnits:90,
        details:{ school:"INTERWORLD COLLEGES FOUNDATION, INC", address:"Burgos St., Paniqui, Tarlac", courseTitle:"2-Year Associate in Computer Technology", yearLevel:"S.Y. 2022-2023",
          semesters:[
            { name:"1st Year - 1st Semester", subjects:[
              {prereq:"None", code:"BRIDGING101 Math 2", title:"Plane Trigonometry", lec:0, lab:0, units:3},
              {prereq:"None", code:"BRIDGING102 Math 3", title:"Probabilities and Statistics", lec:0, lab:0, units:3},
              {prereq:"None", code:"ACTGECOD101YG1", title:"Understanding the Self", lec:3, lab:0, units:3}
            ]},
            { name:"1st Year - 2nd Semester", subjects:[
              {prereq:"None", code:"BRIDGING103 Nat Sci 1", title:"College Physics 1", lec:0, lab:0, units:3},
              {prereq:"None", code:"ACTGECOT102YG1", title:"Mathematics in the Modern World", lec:3, lab:0, units:3}
            ]}
          ]
        }
      }
    ];

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
      updateSummaryCards();
    });

    function showSuccessModal(title, message) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      const icon = document.getElementById('operationIcon');
      box.className = 'operation-modal-box success';
      icon.innerHTML = '<i class="fas fa-check-circle"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = message;
      document.getElementById('operationFormContainer').style.display = 'none';
      document.getElementById('operationActions').innerHTML = `<button class="op-btn confirm-success" onclick="closeOperationModal()">OK</button>`;
      modal.classList.add('active');
    }

    function showWarningModal(title, message, confirmText, callback) {
      const modal = document.getElementById('operationModal');
      const box = document.getElementById('operationBox');
      const icon = document.getElementById('operationIcon');
      box.className = 'operation-modal-box warning';
      icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
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
      const icon = document.getElementById('operationIcon');
      box.className = 'operation-modal-box';
      icon.innerHTML = '<i class="fas fa-edit"></i>';
      document.getElementById('operationTitle').textContent = title;
      document.getElementById('operationMessage').textContent = '';
      let formHtml = '';
      fields.forEach((field, i) => {
        formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" id="op_field_${i}" value="${field.value || ''}" /></div>`;
      });
      document.getElementById('operationFormContainer').innerHTML = formHtml;
      document.getElementById('operationFormContainer').style.display = 'block';
      operationCallback = callback;
      document.getElementById('operationActions').innerHTML = `
        <button class="op-btn cancel" onclick="closeOperationModal()">Cancel</button>
        <button class="op-btn confirm-success" onclick="executeInputOperation()">Save</button>`;
      modal.classList.add('active');
    }

    function executeOperation() { if (operationCallback) operationCallback(); closeOperationModal(); }
    function executeInputOperation() {
      if (operationCallback) {
        const values = [];
        document.querySelectorAll('#operationFormContainer input').forEach(i => values.push(i.value));
        operationCallback(values);
      }
      closeOperationModal();
    }
    function closeOperationModal() {
      document.getElementById('operationModal').classList.remove('active');
      operationCallback = null;
    }

    function loadCurriculumData() {
      const tbody = document.getElementById('curriculumTableBody');
      tbody.innerHTML = '';
      curriculumData.forEach((c, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${c.code}</td><td>${c.title}</td><td>${c.academicYear}</td><td>${c.totalUnits}</td>
          <td><div class="action-group"><button class="btn-action view" onclick="viewCurriculum(${index})"><i class="fas fa-eye"></i> View</button></div></td>`;
        tbody.appendChild(row);
      });
    }

    function updateSummaryCards() {
      const rows = document.querySelectorAll('#curriculumTableBody tr');
      document.getElementById('totalCurriculumsCard').textContent = rows.length;
      document.getElementById('activeProgramsCard').textContent = curriculumData.length;
    }

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

    function viewCurriculum(index) {
      const curriculum = curriculumData[index];
      const body = document.getElementById('viewCurriculumBody');
      let html = `<div class="curriculum-info">
        <h2>${curriculum.details.school}</h2>
        <h3>${curriculum.details.address}</h3>
        <p><strong>${curriculum.details.courseTitle}</strong></p>
        <p>${curriculum.details.yearLevel}</p></div>`;
      curriculum.details.semesters.forEach((sem, sIdx) => {
        html += `<div class="semester-section"><div class="semester-header">
          <div class="semester-title">${sem.name}</div>
          <div class="semester-actions">
            <button class="btn-small add" onclick="addSubject(${index}, ${sIdx})">+ Add Subject</button>
            <button class="btn-small" onclick="editSemesterName(${index}, ${sIdx})">Rename</button>
            <button class="btn-small" onclick="deleteSemester(${index}, ${sIdx})">Delete</button>
          </div></div>
          <table class="curriculum-table"><thead><tr><th>Pre-req</th><th>Code</th><th>Title</th><th>Lec</th><th>Lab</th><th>Units</th><th>Actions</th></tr></thead><tbody>`;
        sem.subjects.forEach((s, subIdx) => {
          html += `<tr><td>${s.prereq}</td><td>${s.code}</td><td>${s.title}</td><td>${s.lec}</td><td>${s.lab}</td><td>${s.units}</td>
            <td><button class="btn-small" onclick="editSubject(${index}, ${sIdx}, ${subIdx})">Edit</button> <button class="btn-small" onclick="deleteSubject(${index}, ${sIdx}, ${subIdx})">Delete</button></td></tr>`;
        });
        const tL = sem.subjects.reduce((sum, s) => sum + parseFloat(s.lec), 0);
        const tB = sem.subjects.reduce((sum, s) => sum + parseFloat(s.lab), 0);
        const tU = sem.subjects.reduce((sum, s) => sum + parseFloat(s.units), 0);
        html += `</tbody><tr class="total-row"><td colspan="2" style="text-align:right;">TOTAL</td><td></td><td>${tL}</td><td>${tB}</td><td>${tU}</td><td></td></tr></table></div>`;
      });
      html += `<div style="text-align:center; margin-top:20px;"><button class="btn-save" onclick="addSemester(${index})"><i class="fas fa-plus"></i> Add Semester</button></div>`;
      body.innerHTML = html;
      document.getElementById('viewCurriculumModal').classList.add('active');
    }

    function addSemester(index) {
      showInputModal("Add New Semester", [{label:"Semester Name", value:""}], (values) => {
        if (values[0]) {
          curriculumData[index].details.semesters.push({ name: values[0], subjects: [] });
          viewCurriculum(index);
          showSuccessModal("Success!", "Semester added successfully.");
        }
      });
    }
    function deleteSemester(index, sIdx) {
      showWarningModal("Delete Semester", "Are you sure you want to delete this semester?", "Delete", () => {
        curriculumData[index].details.semesters.splice(sIdx, 1);
        viewCurriculum(index);
        showSuccessModal("Deleted!", "Semester deleted successfully.");
      });
    }
    function editSemesterName(index, sIdx) {
      const currentName = curriculumData[index].details.semesters[sIdx].name;
      showInputModal("Edit Semester Name", [{label:"Semester Name", value:currentName}], (values) => {
        if (values[0]) {
          curriculumData[index].details.semesters[sIdx].name = values[0];
          viewCurriculum(index);
          showSuccessModal("Updated!", "Semester name updated.");
        }
      });
    }
    function addSubject(index, sIdx) {
      showInputModal("Add New Subject", [
        {label:"Pre-requisite", value:"None"},
        {label:"Subject Code", value:""},
        {label:"Subject Title", value:""},
        {label:"Lecture Units", value:"3"},
        {label:"Lab Units", value:"0"},
        {label:"Total Units", value:"3"}
      ], (values) => {
        curriculumData[index].details.semesters[sIdx].subjects.push({
          prereq: values[0]||"None", code: values[1]||"", title: values[2]||"",
          lec: values[3]||0, lab: values[4]||0, units: values[5]||0
        });
        viewCurriculum(index);
        showSuccessModal("Success!", "Subject added successfully.");
      });
    }
    function editSubject(index, sIdx, subIdx) {
      const sub = curriculumData[index].details.semesters[sIdx].subjects[subIdx];
      showInputModal("Edit Subject", [
        {label:"Pre-requisite", value:sub.prereq},
        {label:"Subject Code", value:sub.code},
        {label:"Subject Title", value:sub.title},
        {label:"Lecture Units", value:sub.lec},
        {label:"Lab Units", value:sub.lab},
        {label:"Total Units", value:sub.units}
      ], (values) => {
        curriculumData[index].details.semesters[sIdx].subjects[subIdx] = {
          prereq: values[0]||sub.prereq, code: values[1]||sub.code, title: values[2]||sub.title,
          lec: values[3]||sub.lec, lab: values[4]||sub.lab, units: values[5]||sub.units
        };
        viewCurriculum(index);
        showSuccessModal("Updated!", "Subject updated successfully.");
      });
    }
    function deleteSubject(index, sIdx, subIdx) {
      showWarningModal("Delete Subject", "Are you sure you want to delete this subject?", "Delete", () => {
        curriculumData[index].details.semesters[sIdx].subjects.splice(subIdx, 1);
        viewCurriculum(index);
        showSuccessModal("Deleted!", "Subject deleted successfully.");
      });
    }
    function closeViewCurriculumModal() { document.getElementById('viewCurriculumModal').classList.remove('active'); }

    function openAddCurriculumModal() {
      document.getElementById('addCurriculumForm').reset();
      document.getElementById('addCurriculumModal').classList.add('active');
    }
    function closeAddCurriculumModal() { document.getElementById('addCurriculumModal').classList.remove('active'); }
    function saveCurriculum() {
      const code = document.getElementById('curriculumCode').value;
      const title = document.getElementById('curriculumTitle').value;
      const year = document.getElementById('curriculumYear').value;
      const academicYear = document.getElementById('curriculumAcademicYear').value;
      const units = document.getElementById('curriculumUnits').value;
      if (!code || !title || !year || !academicYear || !units) {
        showWarningModal("Missing Information", "Please fill in all fields before saving.", "OK", null);
        return;
      }
      curriculumData.push({
        code: code, title: title, year: year, academicYear: academicYear, totalUnits: parseInt(units),
        details: { school: "INTERWORLD COLLEGES FOUNDATION, INC", address: "Burgos St., Paniqui, Tarlac",
          courseTitle: title, yearLevel: academicYear,
          semesters: [{ name: "1st Year - 1st Semester", subjects: [] }]
        }
      });
      loadCurriculumData(); updateSummaryCards();
      closeAddCurriculumModal();
      showSuccessModal("Course Added!", "Course has been added successfully! Click 'View' to add detailed subjects.");
    }
  </script>
</body>
</html>