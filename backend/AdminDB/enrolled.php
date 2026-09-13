<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

$res = $conn->query("SELECT * FROM enrolled ORDER BY student_id DESC");
$students = [];
while ($row = $res->fetch_assoc()) { $students[] = $row; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Control Panel - Enrolled Students</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    .cards-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:40px; }
    .card { background: var(--text-high-contrast); padding:20px; border-radius:8px; box-shadow: 0 0px 5px var(--primary-neon); }
    .card h3 { font-size:0.85rem; color: var(--bg-deep-abyss); text-transform:uppercase; margin-bottom:8px; }
    .card-value { font-size:1.8rem; font-weight:bold; color: var(--bg-deep-abyss); }
    .badge.warning { background-color:#f59e0b; color:#623e0b; }

    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,12,27,0.95); backdrop-filter:blur(8px); z-index:3000; justify-content:center; align-items:center; padding:20px; }
    .modal-overlay.active { display:flex; }
    .modal-container { background:var(--bg-card); border:1px solid var(--border-teal); border-radius:16px; width:100%; max-width:1100px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.8); position:relative; animation:modalFadeIn 0.3s ease; padding:30px; color:var(--text-high-contrast); }
    @keyframes modalFadeIn { from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }
    .modal-close { position:absolute; top:15px; right:20px; background:transparent; border:none; color:var(--text-muted-teal); font-size:28px; cursor:pointer; z-index:10; }
    .modal-close:hover { color:#ef4444; }

    .student-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:25px; background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:8px; padding:20px; }
    .student-info-left, .student-info-right { display:flex; flex-direction:column; gap:12px; }
    .student-info-right { align-items:flex-end; text-align:right; }
    .info-line { font-size:14px; color:var(--text-muted-teal); }
    .info-line strong { color:var(--text-high-contrast); font-weight:600; }
    .info-line strong i { color:var(--primary-neon); margin-right:8px; }
    .info-line .highlight { color:var(--primary-neon); font-weight:700; }

    .course-table { width:100%; border-collapse:collapse; font-size:13px; background:var(--bg-deep-abyss); border-radius:8px; overflow:hidden; margin-bottom:20px; border:1px solid var(--border-teal); }
    .course-table th { background:#0e1f36; color:var(--primary-neon); font-weight:600; padding:12px 10px; text-align:left; border-bottom:2px solid var(--border-teal); }
    .course-table td { padding:12px 10px; border-bottom:1px solid var(--border-teal); color:var(--text-high-contrast); }
    .course-table .total-row td { background:#0e1f36; font-weight:700; color:var(--primary-neon); border-top:2px solid var(--border-teal); }

    .payment-assessment-wrap { display:grid; grid-template-columns:1.2fr 0.8fr; gap:20px; }
    .payment-assess-card { background:var(--bg-deep-abyss); border:1px solid var(--border-teal); border-radius:8px; overflow:hidden; }
    .card-header { background:#0e1f36; padding:10px 15px; font-size:14px; font-weight:700; color:var(--primary-neon); text-transform:uppercase; letter-spacing:1px; border-bottom:2px solid var(--primary-neon); display:flex; align-items:center; gap:8px; }
    .data-table { width:100%; border-collapse:collapse; font-size:13px; }
    .data-table th, .data-table td { padding:10px 15px; text-align:left; border-bottom:1px solid var(--border-teal); }
    .data-table td { color:var(--text-high-contrast); }
    .data-table .assessment-item td:last-child { text-align:right; font-weight:600; }
    .data-table .assessment-total td { background:#0e1f36; font-weight:800; color:var(--primary-neon); font-size:15px; border-top:2px solid var(--primary-neon); }
    .data-table .assessment-total td:last-child { text-align:right; font-size:18px; }
    .data-table .assessment-remaining td { background:var(--bg-card-hover); color:var(--text-high-contrast); font-weight:800; font-size:15px; border-top:2px solid var(--primary-neon); }
    .data-table .assessment-remaining td:last-child { text-align:right; font-size:18px; }
    .data-table .total-paid-row td { background:#0e1f36; font-weight:800; color:var(--primary-neon); font-size:15px; border-top:2px solid var(--primary-neon); }
    .data-table .total-paid-row td:last-child { text-align:right; font-size:18px; }
    @media (max-width: 768px) {
      .student-info-grid { grid-template-columns: 1fr; }
      .student-info-right { align-items:flex-start; text-align:left; }
      .payment-assessment-wrap { grid-template-columns: 1fr; }
    }
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
        <li class="active"><a href="enrolled.php">Enrolled</a></li>
        <li><a href="instructor.php">Instructors</a></li>
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
      <h1>Enrolled Students</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="userSearchInput" placeholder="Search students..." autocomplete="off"/>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <div class="cards-grid">
      <div class="card"><h3>Total Students</h3><div class="card-value" id="totalStudentsCard"><?php echo count($students); ?></div></div>
      <div class="card"><h3>Fully PAID</h3><div class="card-value">92</div></div>
      <div class="card"><h3>Partial Payments</h3><div class="card-value">36</div></div>
      <div class="card"><h3>Total Units Enrolled</h3><div class="card-value" id="totalUnitsCard">0</div></div>
    </div>

    <div class="table-card">
      <div class="table-header"><h2>List of Enrolled Students</h2></div>
      <div class="table-wrapper">
        <table id="enrolledTable">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable(0)">Student ID <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(1)">Full Name <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(2)">Year Level <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(3)">Courses <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(4)">Block <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(5)">Units <i class="fas fa-sort"></i></th>
              <th class="sortable" onclick="sortTable(6)">Balance <i class="fas fa-sort"></i></th>
              <th class="no-sort">Action</th>
            </tr>
          </thead>
          <tbody id="enrolledTableBody">
            <?php foreach ($students as $s):
              $fullName = trim(($s['lastname'] ?? '') . ', ' . ($s['firstname'] ?? ''));
            ?>
            <tr data-record='<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES, "UTF-8"); ?>'>
              <td><?php echo htmlspecialchars($s['student_code'] ?? $s['student_id']); ?></td>
              <td><?php echo htmlspecialchars($fullName); ?></td>
              <td>3rd Year</td>
              <td><?php echo htmlspecialchars($s['course']); ?></td>
              <td>Block A</td>
              <td>21.0</td>
              <td><span class="badge warning">₱ 7,640.00</span></td>
              <td><button class="btn-action" onclick="viewStudent(this)">View</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <div class="modal-overlay" id="viewStudentModal">
    <div class="modal-container">
      <button class="modal-close" onclick="closeViewStudentModal()">&times;</button>
      <div class="student-info-grid">
        <div class="student-info-left">
          <div class="info-line"><strong><i class="fas fa-id-card"></i> Student ID:</strong> <span class="highlight" id="modalStudentID">-</span></div>
          <div class="info-line"><strong><i class="fas fa-user"></i> Name:</strong> <span id="modalStudentName">-</span></div>
          <div class="info-line"><strong><i class="fas fa-book-open"></i> Course:</strong> <span id="modalStudentCourse">-</span></div>
        </div>
        <div class="student-info-right">
          <div class="info-line"><strong><i class="fas fa-graduation-cap"></i> Year Level:</strong> <span id="modalStudentYear">-</span></div>
          <div class="info-line"><strong><i class="fas fa-print"></i> Date Printed:</strong> <span id="modalStudentDate">-</span></div>
        </div>
      </div>

      <table class="course-table">
        <thead>
          <tr><th>Code</th><th>Description</th><th>Units</th><th>Day</th><th>Time</th><th>Room</th><th>Block</th></tr>
        </thead>
        <tbody id="modalCourseBody"></tbody>
      </table>

      <div class="payment-assessment-wrap">
        <div class="payment-assess-card">
          <div class="card-header"><i class="fas fa-receipt"></i> Payment Details</div>
          <table class="data-table">
            <thead><tr><th>#</th><th>Date</th><th>O.R. No.</th><th>Amount</th></tr></thead>
            <tbody id="modalPaymentBody">
              <tr><td>1</td><td>Jun 02, 2026</td><td>OR-001</td><td>₱ 5,000.00</td></tr>
              <tr><td>2</td><td>Jun 15, 2026</td><td>OR-002</td><td>₱ 3,000.00</td></tr>
              <tr><td>3</td><td>Jul 05, 2026</td><td>OR-003</td><td>₱ 2,000.00</td></tr>
              <tr><td>4</td><td>Aug 10, 2026</td><td>OR-004</td><td>₱ 2,000.00</td></tr>
              <tr class="total-paid-row"><td colspan="3" style="text-align:right">TOTAL PAID:</td><td>₱ 12,000.00</td></tr>
            </tbody>
          </table>
        </div>

        <div class="payment-assess-card">
          <div class="card-header"><i class="fas fa-calculator"></i> Assessment</div>
          <table class="data-table">
            <tbody id="modalAssessmentBody">
              <tr class="assessment-item"><td>Tuition Fee:</td><td>₱ 14,070.00</td></tr>
              <tr class="assessment-item"><td>Academic:</td><td>₱ 1,200.00</td></tr>
              <tr class="assessment-item"><td>Computer:</td><td>₱ 1,850.00</td></tr>
              <tr class="assessment-item"><td>Misc. Fee:</td><td>₱ 2,100.00</td></tr>
              <tr class="assessment-item"><td>NSTP:</td><td>₱ 0.00</td></tr>
              <tr class="assessment-item"><td>Others:</td><td>₱ 420.00</td></tr>
              <tr class="assessment-total"><td>TOTAL:</td><td>₱ 19,640.00</td></tr>
              <tr class="assessment-remaining"><td>Balance:</td><td>₱ 7,640.00</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    let sortDirection = 1;

    document.addEventListener("DOMContentLoaded", () => {
      const searchInput = document.getElementById("userSearchInput");
      const enrollmentTable = document.getElementById("enrolledTable");
      if (searchInput && enrollmentTable) {
        searchInput.addEventListener("input", (e) => {
          const searchTerm = e.target.value.toLowerCase();
          enrollmentTable.querySelectorAll("tbody tr").forEach((row) => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
          });
        });
      }
      updateUnitsCard();
    });

    function updateUnitsCard() {
      let totalUnits = 0;
      document.querySelectorAll("#enrolledTableBody tr").forEach((row) => {
        totalUnits += parseFloat(row.cells[5].textContent) || 0;
      });
      document.getElementById("totalUnitsCard").textContent = totalUnits.toFixed(1);
    }

    function sortTable(columnIndex) {
      const table = document.getElementById("enrolledTable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.rows);
      sortDirection = sortDirection === 1 ? -1 : 1;
      rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
        const bText = b.cells[columnIndex].textContent.trim().toLowerCase();
        if (aText < bText) return -1 * sortDirection;
        if (aText > bText) return 1 * sortDirection;
        return 0;
      });
      rows.forEach(row => tbody.appendChild(row));
      document.querySelectorAll('th.sortable i').forEach(i => i.className = 'fas fa-sort');
      const activeTh = table.querySelector(`th.sortable:nth-child(${columnIndex + 1}) i`);
      if (activeTh) activeTh.className = sortDirection === 1 ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }

    function viewStudent(button) {
      const row = button.closest("tr");
      document.getElementById("modalStudentID").textContent = row.cells[0].textContent;
      document.getElementById("modalStudentName").textContent = row.cells[1].textContent;
      document.getElementById("modalStudentYear").textContent = row.cells[2].textContent;
      document.getElementById("modalStudentCourse").textContent = row.cells[3].textContent;
      document.getElementById("modalStudentDate").textContent = new Date().toLocaleDateString('en-US', {year:'numeric',month:'long',day:'numeric'});

      document.getElementById("modalCourseBody").innerHTML = `
        <tr><td>PC7</td><td>Automata Theory & Formal Languages</td><td>3.0</td><td>MON / WED</td><td>07:30 AM - 09:00 AM</td><td>208 / CL1</td><td>Block A</td></tr>
        <tr><td>PC8</td><td>Architecture and Organization</td><td>3.0</td><td>MON / WED</td><td>09:00 AM - 10:30 AM</td><td>208 / CL1</td><td>Block A</td></tr>
        <tr><td>PC11</td><td>Programming Languages</td><td>3.0</td><td>MON / WED</td><td>01:00 PM - 02:30 PM</td><td>208 / CL1</td><td>Block A</td></tr>
        <tr><td>PC10</td><td>Information Assurance and Security</td><td>3.0</td><td>MON / WED</td><td>02:30 PM - 04:00 PM</td><td>208 / CL1</td><td>Block A</td></tr>
        <tr><td>PElective3</td><td>Intelligent Systems</td><td>3.0</td><td>MON / WED</td><td>04:00 PM - 05:30 PM</td><td>208 / CL1</td><td>Block A</td></tr>
        <tr><td>PElective2</td><td>Graphics and Visual Computing</td><td>3.0</td><td>S</td><td>09:00 AM - 12:00 PM</td><td>209 / CL2</td><td>Block A</td></tr>
        <tr><td>PC12</td><td>Software Engineering 2</td><td>3.0</td><td>S</td><td>01:00 PM - 04:00 PM</td><td>ILAB</td><td>Block A</td></tr>
        <tr class="total-row">
          <td colspan="2" style="text-align:right; padding-right:20px;">TOTAL UNITS</td>
          <td>21.0</td>
          <td colspan="4" style="text-align:left;">Bridging Subjects: 0 | NSTP: 0</td>
        </tr>`;
      document.getElementById("viewStudentModal").classList.add("active");
    }

    function closeViewStudentModal() {
      document.getElementById("viewStudentModal").classList.remove("active");
    }
  </script>
</body>
</html>