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
    .operation-modal-box.warning { border-color:var(--warning-yellow); box-shadow:0 20px 50px rgba(245,158,11,0.3); }
    .operation-modal-box.success .operation-icon { color:var(--success-green); }
    .operation-modal-box.warning .operation-icon { color:var(--warning-yellow); }
    .operation-modal-box h3 { font-size:20px; font-weight:800; margin-bottom:10px; }
    .operation-modal-box p { font-size:14px; color:var(--text-muted-teal); margin-bottom:20px; line-height:1.6; }
    .operation-form-group { margin-bottom:15px; text-align:left; }
    .operation-form-group label { display:block; font-size:12px; color:var(--text-muted-teal); font-weight:600; margin-bottom:6px; text-transform:uppercase; }
    .operation-form-group input, .operation-form-group select, .operation-form-group textarea { width:100%; padding:10px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:4px; font-size:0.9rem; color:var(--text-high-contrast); outline:none; font-family:inherit; }
    .operation-form-group input[type="radio"] { width:auto; margin-right:5px; }
    .operation-form-group .radio-label { display:inline-block; margin-right:15px; }
    .operation-modal-actions { display:flex; justify-content:center; gap:10px; flex-wrap:wrap; }
    .op-btn { padding:10px 25px; border:none; border-radius:6px; cursor:pointer; font-weight:700; font-size:0.9rem; }
    .op-btn.cancel { background:var(--bg-card); color:var(--text-high-contrast); border:1px solid var(--border-teal); }
    .op-btn.cancel:hover { background:var(--bg-card-hover); }
    .op-btn.confirm-success { background:var(--success-green); color:white; }
    .op-btn.confirm-success:hover { background:#059669; }
    .op-btn.confirm-danger { background:var(--danger-red); color:white; }
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
      <div class="card"><h3>Total Departments</h3><div class="card-value">2</div></div>
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
            <thead><tr><th class="sortable">Full Name <i class="fas fa-sort"></i></th><th class="sortable">Department <i class="fas fa-sort"></i></th><th class="sortable">Units Handling <i class="fas fa-sort"></i></th><th class="no-sort">Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header"><h2>EDUC - College of Education</h2></div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="EDUC" data-status="active">
            <thead><tr><th class="sortable">Full Name <i class="fas fa-sort"></i></th><th class="sortable">Department <i class="fas fa-sort"></i></th><th class="sortable">Units Handling <i class="fas fa-sort"></i></th><th class="no-sort">Action</th></tr></thead>
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
            <thead><tr><th class="sortable">Full Name <i class="fas fa-sort"></i></th><th class="sortable">Department <i class="fas fa-sort"></i></th><th class="sortable">Units Handling <i class="fas fa-sort"></i></th><th class="no-sort">Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header"><h2>EDUC - Former Instructors</h2></div>
        <div class="table-wrapper">
          <table class="instructor-table" data-department="EDUC" data-status="former">
            <thead><tr><th class="sortable">Full Name <i class="fas fa-sort"></i></th><th class="sortable">Department <i class="fas fa-sort"></i></th><th class="sortable">Units Handling <i class="fas fa-sort"></i></th><th class="no-sort">Action</th></tr></thead>
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
    let currentViewIndex = null;

    function formatPhoneInput(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length > 11) value = value.substring(0, 11);
      let f = '';
      if (value.length > 0) f += value.substring(0, 4);
      if (value.length >= 5) f += '-' + value.substring(4, 7);
      if (value.length >= 8) f += '-' + value.substring(7, 11);
      input.value = f;
    }

    const instructors = [
      { id:1, fullName:"Engr. Cesar C. Gaspar", profilePic:"", department:"CICS", status:"active",
        bioData:{gender:"Male", birthday:"1980-01-15", citizenship:"Filipino", civilStatus:"Married", employment:"Full-Time"},
        contactInfo:{email:"cesar.gaspar@icf.edu.ph", phone:"0917-123-4567", address:"Burgos St., Paniqui, Tarlac"},
        subjectHandling:[
          {code:"PC7", title:"Automata Theory & Formal Languages", units:3},
          {code:"PC8", title:"Architecture and Organization", units:3},
          {code:"PC11", title:"Programming Languages", units:3},
          {code:"PC10", title:"Information Assurance and Security", units:3},
          {code:"PC12", title:"Software Engineering 2", units:3},
          {code:"PC13", title:"Intelligent Systems", units:3}
        ],
        schedule:[
          {day:"MON/WED", time:"07:30 AM - 09:00 AM", room:"208 / CL1", subject:"PC7"},
          {day:"MON/WED", time:"09:00 AM - 10:30 AM", room:"208 / CL1", subject:"PC8"},
          {day:"MON/WED", time:"01:00 PM - 02:30 PM", room:"208 / CL1", subject:"PC11"},
          {day:"MON/WED", time:"02:30 PM - 04:00 PM", room:"208 / CL1", subject:"PC10"},
          {day:"S", time:"01:00 PM - 04:00 PM", room:"ILAB", subject:"PC12"}
        ]
      },
      { id:2, fullName:"Prof. Alma Reyes", profilePic:"", department:"CICS", status:"active",
        bioData:{gender:"Female", birthday:"1988-03-10", citizenship:"Filipino", civilStatus:"Single", employment:"Full-Time"},
        contactInfo:{email:"alma.reyes@icf.edu.ph", phone:"0918-456-7890", address:"Tarlac City"},
        subjectHandling:[
          {code:"PC1", title:"Computer Programming 1", units:3},
          {code:"PC2", title:"Data Structures & Algorithms", units:3},
          {code:"PC4", title:"Networks and Communication", units:3},
          {code:"PC5", title:"Object-Oriented Programming", units:3}
        ],
        schedule:[
          {day:"TUE/THU", time:"07:30 AM - 09:00 AM", room:"202 / CL2", subject:"PC1"},
          {day:"TUE/THU", time:"09:00 AM - 10:30 AM", room:"202 / CL2", subject:"PC2"},
          {day:"S", time:"09:00 AM - 12:00 PM", room:"202 / CL2", subject:"PC4"}
        ]
      },
      { id:3, fullName:"Dr. Maria Santos", profilePic:"", department:"EDUC", status:"active",
        bioData:{gender:"Female", birthday:"1985-05-22", citizenship:"Filipino", civilStatus:"Single", employment:"Full-Time"},
        contactInfo:{email:"maria.santos@icf.edu.ph", phone:"0928-987-6543", address:"Tarlac City"},
        subjectHandling:[
          {code:"EDUC101", title:"Child and Adolescent Development", units:3},
          {code:"EDUC102", title:"The Teaching Profession", units:3},
          {code:"EDUC103", title:"Facilitating Learner-Centered Teaching", units:3}
        ],
        schedule:[
          {day:"TUE/THU", time:"08:00 AM - 09:30 AM", room:"101 / EDUC", subject:"EDUC101"},
          {day:"TUE/THU", time:"10:00 AM - 11:30 AM", room:"101 / EDUC", subject:"EDUC102"},
          {day:"S", time:"09:00 AM - 12:00 PM", room:"102 / EDUC", subject:"EDUC103"}
        ]
      },
      { id:4, fullName:"Prof. Dante Cruz", profilePic:"", department:"CICS", status:"former",
        bioData:{gender:"Male", birthday:"1975-11-30", citizenship:"Filipino", civilStatus:"Married", employment:"Part-Time"},
        contactInfo:{email:"dante.cruz@icf.edu.ph", phone:"0917-222-3344", address:"Paniqui, Tarlac"},
        subjectHandling:[
          {code:"PC6", title:"Information Assurance 2", units:3},
          {code:"PC9", title:"Software Engineering 1", units:3}
        ],
        schedule:[
          {day:"MON/WED", time:"04:00 PM - 05:30 PM", room:"208 / CL1", subject:"PC6"},
          {day:"S", time:"09:00 AM - 12:00 PM", room:"209 / CL2", subject:"PC9"}
        ]
      }
    ];

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
        if (field.type === 'radio') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label>${field.options.map(opt => `<label class="radio-label"><input type="radio" name="op_radio_${i}" value="${opt}" ${field.value === opt ? 'checked' : ''}> ${opt}</label>`).join('')}</div>`;
        } else if (field.type === 'date') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="date" id="op_field_${i}" value="${field.value || ''}" /></div>`;
        } else if (field.type === 'phone') {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" inputmode="numeric" id="op_field_${i}" value="${field.value || ''}" oninput="formatPhoneInput(this)" placeholder="0912-345-6789" /></div>`;
        } else {
          formHtml += `<div class="operation-form-group"><label>${field.label}</label><input type="text" id="op_field_${i}" value="${field.value || ''}" /></div>`;
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

    function executeOperation() { if (operationCallback) operationCallback(); closeOperationModal(); }
    function executeInputOperation() {
      if (operationCallback) {
        const values = [];
        document.querySelectorAll('#operationFormContainer input').forEach(input => {
          if (input.type === 'radio') { if (input.checked) values.push(input.value); }
          else values.push(input.value);
        });
        operationCallback(values);
      }
      closeOperationModal();
    }
    function closeOperationModal() {
      document.getElementById('operationModal').classList.remove('active');
      operationCallback = null;
    }

    function loadInstructors() {
      document.querySelectorAll('.instructor-table').forEach(table => {
        const department = table.getAttribute('data-department');
        const status = table.getAttribute('data-status');
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        instructors.forEach((instructor, index) => {
          if (instructor.department === department && instructor.status === status) {
            const totalUnits = instructor.subjectHandling.reduce((sum, s) => sum + parseFloat(s.units), 0);
            let actions = status === 'active'
              ? `<button class="btn-action view" onclick="viewInstructor(${index})"><i class="fas fa-eye"></i> View</button>
                 <button class="btn-action archive" onclick="archiveInstructor(${index})"><i class="fas fa-archive"></i> Archive</button>`
              : `<button class="btn-action view" onclick="viewInstructor(${index})"><i class="fas fa-eye"></i> View</button>
                 <button class="btn-action restore" onclick="restoreInstructor(${index})"><i class="fas fa-undo"></i> Restore</button>
                 <button class="btn-action delete" onclick="deleteInstructor(${index})"><i class="fas fa-trash"></i> Delete</button>`;
            const row = document.createElement('tr');
            row.innerHTML = `<td>${instructor.fullName}</td><td>${instructor.department}</td><td>${totalUnits}</td><td><div class="action-group">${actions}</div></td>`;
            tbody.appendChild(row);
          }
        });
      });
      updateSummaryCards();
    }

    function updateSummaryCards() {
      document.getElementById('totalInstructorsCard').textContent = instructors.length;
      document.getElementById('activeInstructorsCard').textContent = instructors.filter(i => i.status === 'active').length;
      document.getElementById('formerInstructorsCard').textContent = instructors.filter(i => i.status === 'former').length;
    }

    function viewInstructor(index) {
      const instructor = instructors[index];
      const body = document.getElementById('viewInstructorBody');
      const canEdit = instructor.status === 'active';
      const initials = instructor.fullName.split(' ').filter(w => w[0] && w[0] === w[0].toUpperCase()).map(w => w[0]).slice(0,2).join('');
      let subjectHtml = '';
      instructor.subjectHandling.forEach((subject, subIdx) => {
        subjectHtml += `<tr><td>${subject.code}</td><td>${subject.title}</td><td>${subject.units}</td>${canEdit ? `<td><button class="btn-small" onclick="editSubject(${index}, ${subIdx})">Edit</button> <button class="btn-small" onclick="deleteSubject(${index}, ${subIdx})">Delete</button></td>` : ''}</tr>`;
      });
      let scheduleHtml = '';
      instructor.schedule.forEach((sched, schIdx) => {
        scheduleHtml += `<tr><td>${sched.day}</td><td>${sched.time}</td><td>${sched.room}</td><td>${sched.subject}</td>${canEdit ? `<td><button class="btn-small" onclick="editSchedule(${index}, ${schIdx})">Edit</button> <button class="btn-small" onclick="deleteSchedule(${index}, ${schIdx})">Delete</button></td>` : ''}</tr>`;
      });
      const totalUnits = instructor.subjectHandling.reduce((sum, s) => sum + parseFloat(s.units), 0);
      body.innerHTML = `
        <div class="profile-pic-container">
          <div class="profile-pic">${instructor.profilePic ? `<img src="${instructor.profilePic}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : initials}</div>
          <h2>${instructor.fullName}</h2>
          <p>${instructor.department} Department</p>
          <p style="color:var(--text-muted-teal); margin-top:5px;">Total Units Handling: <strong style="color:var(--primary-neon);">${totalUnits}</strong></p>
        </div>
        <div class="info-section"><div class="info-section-title">Bio Data</div>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Gender:</span><span class="info-value">${instructor.bioData.gender}</span></div>
            <div class="info-item"><span class="info-label">Birthday:</span><span class="info-value">${instructor.bioData.birthday}</span></div>
            <div class="info-item"><span class="info-label">Citizenship:</span><span class="info-value">${instructor.bioData.citizenship}</span></div>
            <div class="info-item"><span class="info-label">Civil Status:</span><span class="info-value">${instructor.bioData.civilStatus}</span></div>
            <div class="info-item"><span class="info-label">Employment:</span><span class="info-value">${instructor.bioData.employment}</span></div>
          </div>
        </div>
        <div class="info-section"><div class="info-section-title">Contact Info</div>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Email:</span><span class="info-value">${instructor.contactInfo.email}</span></div>
            <div class="info-item"><span class="info-label">Phone:</span><span class="info-value">${instructor.contactInfo.phone}</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">${instructor.contactInfo.address}</span></div>
          </div>
        </div>
        <div class="info-section"><div class="info-section-title">Subject Handling</div>
          ${canEdit ? `<div style="margin-bottom:10px;"><button class="btn-small add" onclick="addSubject(${index})">+ Add Subject</button></div>` : ''}
          <table class="subject-table"><thead><tr><th>Code</th><th>Title</th><th>Units</th>${canEdit ? '<th>Actions</th>' : ''}</tr></thead><tbody>${subjectHtml}</tbody></table>
        </div>
        <div class="info-section"><div class="info-section-title">Schedule List</div>
          ${canEdit ? `<div style="margin-bottom:10px;"><button class="btn-small add" onclick="addSchedule(${index})">+ Add Schedule</button></div>` : ''}
          <table class="schedule-table"><thead><tr><th>Day</th><th>Time</th><th>Room</th><th>Subject</th>${canEdit ? '<th>Actions</th>' : ''}</tr></thead><tbody>${scheduleHtml}</tbody></table>
        </div>
        ${canEdit ? `<div style="text-align:center; margin-top:20px;"><button class="btn primary" onclick="openEditInstructorModal(${index})"><i class="fas fa-edit"></i> Edit Instructor</button></div>` : ''}`;
      document.getElementById('viewInstructorModal').classList.add('active');
    }

    function closeViewInstructorModal() { document.getElementById('viewInstructorModal').classList.remove('active'); }

    function openEditInstructorModal(index) {
      const i = instructors[index];
      showInputModal("Edit Instructor Information", [
        {label:"Full Name", value:i.fullName},
        {label:"Department", value:i.department},
        {label:"Gender", type:"radio", options:["Male","Female"], value:i.bioData.gender},
        {label:"Birthday", type:"date", value:i.bioData.birthday},
        {label:"Citizenship", value:i.bioData.citizenship},
        {label:"Civil Status", type:"radio", options:["Single","Married"], value:i.bioData.civilStatus},
        {label:"Employment", type:"radio", options:["Full-Time","Part-Time"], value:i.bioData.employment},
        {label:"Email", value:i.contactInfo.email},
        {label:"Phone Number", type:"phone", value:i.contactInfo.phone},
        {label:"Address", value:i.contactInfo.address}
      ], (values) => {
        const inst = instructors[index];
        inst.fullName = values[0] || inst.fullName;
        inst.department = values[1] || inst.department;
        inst.bioData.gender = values[2] || inst.bioData.gender;
        inst.bioData.birthday = values[3] || inst.bioData.birthday;
        inst.bioData.citizenship = values[4] || inst.bioData.citizenship;
        inst.bioData.civilStatus = values[5] || inst.bioData.civilStatus;
        inst.bioData.employment = values[6] || inst.bioData.employment;
        inst.contactInfo.email = values[7] || inst.contactInfo.email;
        inst.contactInfo.phone = values[8] || inst.contactInfo.phone;
        inst.contactInfo.address = values[9] || inst.contactInfo.address;
        loadInstructors(); closeViewInstructorModal();
        showSuccessModal("Updated!", "Instructor information updated successfully.");
      });
    }

    function addSubject(index) {
      showInputModal("Add Subject", [{label:"Code",value:""},{label:"Title",value:""},{label:"Units",value:"3"}], (v) => {
        instructors[index].subjectHandling.push({code:v[0]||"", title:v[1]||"", units:v[2]||0});
        loadInstructors(); viewInstructor(index);
        showSuccessModal("Added!", "Subject added successfully.");
      });
    }
    function editSubject(index, subIdx) {
      const s = instructors[index].subjectHandling[subIdx];
      showInputModal("Edit Subject", [{label:"Code",value:s.code},{label:"Title",value:s.title},{label:"Units",value:s.units}], (v) => {
        instructors[index].subjectHandling[subIdx] = {code:v[0]||s.code, title:v[1]||s.title, units:v[2]||s.units};
        loadInstructors(); viewInstructor(index);
        showSuccessModal("Updated!", "Subject updated successfully.");
      });
    }
    function deleteSubject(index, subIdx) {
      showWarningModal("Delete Subject", "Are you sure you want to delete this subject?", "Delete", () => {
        instructors[index].subjectHandling.splice(subIdx, 1);
        loadInstructors(); viewInstructor(index);
        showSuccessModal("Deleted!", "Subject deleted successfully.");
      });
    }
    function addSchedule(index) {
      showInputModal("Add Schedule", [{label:"Day (e.g. MON/WED)",value:""},{label:"Time (e.g. 07:30 AM - 09:00 AM)",value:""},{label:"Room",value:""},{label:"Subject Code",value:""}], (v) => {
        instructors[index].schedule.push({day:v[0]||"", time:v[1]||"", room:v[2]||"", subject:v[3]||""});
        loadInstructors(); viewInstructor(index);
        showSuccessModal("Added!", "Schedule added successfully.");
      });
    }
    function editSchedule(index, schIdx) {
      const s = instructors[index].schedule[schIdx];
      showInputModal("Edit Schedule", [{label:"Day",value:s.day},{label:"Time",value:s.time},{label:"Room",value:s.room},{label:"Subject Code",value:s.subject}], (v) => {
        instructors[index].schedule[schIdx] = {day:v[0]||s.day, time:v[1]||s.time, room:v[2]||s.room, subject:v[3]||s.subject};
        loadInstructors(); viewInstructor(index);
        showSuccessModal("Updated!", "Schedule updated successfully.");
      });
    }
    function deleteSchedule(index, schIdx) {
      showWarningModal("Delete Schedule", "Are you sure you want to delete this schedule?", "Delete", () => {
        instructors[index].schedule.splice(schIdx, 1);
        loadInstructors(); viewInstructor(index);
        showSuccessModal("Deleted!", "Schedule deleted successfully.");
      });
    }
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
      ], (v) => {
        if (!v[0]) { showWarningModal("Missing Information","Please enter the Instructor's Full Name.","OK",null); return; }
        instructors.push({
          id: instructors.length + 1, fullName: v[0], department: v[1] || "CICS", status: "active", profilePic: "",
          bioData: { gender: v[2]||"Male", birthday: v[3]||"", citizenship: v[4]||"Filipino", civilStatus: v[5]||"Single", employment: v[6]||"Full-Time" },
          contactInfo: { email: v[7]||"", phone: v[8]||"", address: v[9]||"" },
          subjectHandling: [], schedule: []
        });
        loadInstructors();
        showSuccessModal("Added!", "New instructor added successfully.");
      });
    }
    function archiveInstructor(index) {
      showWarningModal("Archive Instructor","Are you sure you want to archive this instructor? They will move to Former Instructors.","Archive",() => {
        instructors[index].status = 'former'; loadInstructors();
        showSuccessModal("Archived!", "Instructor moved to Former Instructors.");
      });
    }
    function restoreInstructor(index) {
      showWarningModal("Restore Instructor","Are you sure you want to restore this instructor?","Restore",() => {
        instructors[index].status = 'active'; loadInstructors();
        showSuccessModal("Restored!", "Instructor restored.");
      });
    }
    function deleteInstructor(index) {
      showWarningModal("Delete Instructor","Are you sure you want to permanently delete this instructor?","Delete",() => {
        instructors.splice(index, 1); loadInstructors();
        showSuccessModal("Deleted!", "Instructor deleted permanently.");
      });
    }

    document.addEventListener('DOMContentLoaded', loadInstructors);
  </script>
</body>
</html>