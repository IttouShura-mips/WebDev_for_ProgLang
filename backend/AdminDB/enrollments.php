<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

// ========== SEARCH ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM enrolled WHERE firstname LIKE ? OR lastname LIKE ? OR course LIKE ? OR email LIKE ? ORDER BY student_id DESC");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM enrolled ORDER BY student_id DESC");
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// ========== CHECK DYNAMIC COLUMNS ==========
$hasPayment = false;
$colCheck = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'payment_status'");
if ($colCheck && $colCheck->num_rows > 0) $hasPayment = true;

$hasEnrollmentStatus = false;
$colCheck2 = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'enrollment_status'");
if ($colCheck2 && $colCheck2->num_rows > 0) $hasEnrollmentStatus = true;

$hasStudentCode = false;
$colCheck3 = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'student_code'");
if ($colCheck3 && $colCheck3->num_rows > 0) $hasStudentCode = true;

$hasCreatedAt = false;
$colCheck4 = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'created_at'");
if ($colCheck4 && $colCheck4->num_rows > 0) $hasCreatedAt = true;

// ========== SUMMARY STATS ==========
$totalStudents = count($rows);

$paidCount = 0;
$partialCount = 0;
$pendingCount = 0;
$totalUnits = 0;

foreach ($rows as $r) {
    if ($hasPayment) {
        $ps = $r['payment_status'] ?? 'pending';
        if ($ps === 'paid') $paidCount++;
        elseif ($ps === 'partial') $partialCount++;
        else $pendingCount++;
    }
    // Units: if major exists, approximate units (placeholder logic)
    $totalUnits += 21; // default placeholder; replace when units column exists
}

// Check for student_schedules table
$hasSchedules = $conn->query("SHOW TABLES LIKE 'student_schedules'") && $conn->query("SHOW TABLES LIKE 'student_schedules'")->num_rows > 0;
// Check for student_payments table
$hasPaymentsTable = $conn->query("SHOW TABLES LIKE 'student_payments'") && $conn->query("SHOW TABLES LIKE 'student_payments'")->num_rows > 0;
// Check for student_assessments table
$hasAssessmentsTable = $conn->query("SHOW TABLES LIKE 'student_assessments'") && $conn->query("SHOW TABLES LIKE 'student_assessments'")->num_rows > 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel - Enrollments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    /* ===== SUMMARY CARDS ===== */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .card {
      background: var(--text-high-contrast);
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0px 5px var(--primary-neon);
    }
    .card h3 {
      font-size: 0.85rem;
      color: var(--bg-deep-abyss);
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .card-value {
      font-size: 1.8rem;
      font-weight: bold;
      color: var(--bg-deep-abyss);
    }

    /* ===== VIEW MODAL GRID ===== */
    .view-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .view-modal-grid.full { grid-template-columns: 1fr; }
    .view-modal-section { margin-bottom: 18px; }
    .view-modal-section h4 { color: var(--primary-neon); font-size: 0.95rem; margin-bottom: 10px; border-bottom: 1px solid var(--border-teal); padding-bottom: 6px; }
    .view-modal-row { display: flex; margin-bottom: 8px; font-size: 0.88rem; }
    .view-modal-label { width: 140px; color: var(--text-muted-teal); font-weight: 600; flex-shrink: 0; }
    .view-modal-value { color: var(--text-high-contrast); flex: 1; word-break: break-word; }
    @media (max-width: 600px) { .view-modal-grid { grid-template-columns: 1fr; } }

    /* ===== TABS ===== */
    .modal-tabs {
      display: flex;
      gap: 0;
      border-bottom: 1px solid var(--border-teal);
      margin-bottom: 20px;
    }
    .modal-tab {
      padding: 10px 20px;
      background: transparent;
      border: none;
      color: var(--text-muted-teal);
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 600;
      border-bottom: 2px solid transparent;
      transition: all 0.2s ease;
    }
    .modal-tab:hover { color: var(--text-high-contrast); }
    .modal-tab.active {
      color: var(--primary-neon);
      border-bottom-color: var(--primary-neon);
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ===== COR STYLES ===== */
    .cor-header {
      text-align: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--border-teal);
    }
    .cor-school-name {
      font-size: 20px;
      font-weight: 800;
      color: var(--text-high-contrast);
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .cor-address {
      font-size: 13px;
      color: var(--text-muted-teal);
      margin-top: 4px;
    }
    .cor-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--primary-neon);
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-top: 10px;
    }
    .student-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 20px;
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 8px;
      padding: 15px;
    }
    .student-info-right { align-items: flex-end; text-align: right; }
    .info-line {
      font-size: 14px;
      color: var(--text-muted-teal);
      margin-bottom: 8px;
    }
    .info-line strong { color: var(--text-high-contrast); font-weight: 600; }
    .info-line .highlight { color: var(--primary-neon); font-weight: 700; }
    .course-table, .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      background: var(--bg-deep-abyss);
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 15px;
      border: 1px solid var(--border-teal);
    }
    .course-table th, .data-table th {
      background: #0e1f36;
      color: var(--primary-neon);
      font-weight: 600;
      padding: 10px;
      text-align: left;
      border-bottom: 2px solid var(--border-teal);
    }
    .course-table td, .data-table td {
      padding: 10px;
      border-bottom: 1px solid var(--border-teal);
      color: var(--text-high-contrast);
    }
    .course-table .total-row td, .data-table .assessment-total td {
      background: #0e1f36;
      font-weight: 700;
      color: var(--primary-neon);
      border-top: 2px solid var(--primary-neon);
    }
    .data-table .assessment-item td:last-child { text-align: right; font-weight: 600; }
    .data-table .assessment-total td:last-child { text-align: right; font-size: 16px; }
    .data-table .assessment-remaining td {
      background: var(--bg-card-hover);
      font-weight: 800;
      font-size: 15px;
      border-top: 2px solid var(--primary-neon);
    }
    .data-table .assessment-remaining td:last-child { text-align: right; font-size: 16px; }
    .payment-assessment-wrap {
      display: grid;
      grid-template-columns: 1.2fr 0.8fr;
      gap: 15px;
    }
    .payment-assess-card {
      background: var(--bg-deep-abyss);
      border: 1px solid var(--border-teal);
      border-radius: 8px;
      overflow: hidden;
    }
    .card-header {
      background: #0e1f36;
      padding: 10px 15px;
      font-size: 14px;
      font-weight: 700;
      color: var(--primary-neon);
      text-transform: uppercase;
      letter-spacing: 1px;
      border-bottom: 2px solid var(--primary-neon);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .empty-state {
      text-align: center;
      padding: 30px;
      color: var(--text-muted-teal);
      font-size: 0.9rem;
    }
    .empty-state i { font-size: 2rem; margin-bottom: 10px; display: block; }
    @media (max-width: 768px) {
      .student-info-grid { grid-template-columns: 1fr; }
      .student-info-right { align-items: flex-start; text-align: left; }
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
        <li class="active"><a href="enrollments.php">Enrollments</a></li>
        <li><a href="enrolled.php">Enrolled</a></li>
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
      <h1>Enrollment Management</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="userSearchInput" placeholder="Search..." autocomplete="off"/>
          <div id="searchResults" class="search-results-dropdown"></div>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <!-- Summary Cards -->
    <div class="cards-grid">
      <div class="card">
        <h3>Total Students</h3>
        <div class="card-value"><?php echo $totalStudents; ?></div>
      </div>
      <div class="card">
        <h3>Fully Paid</h3>
        <div class="card-value"><?php echo $paidCount; ?></div>
      </div>
      <div class="card">
        <h3>Partial / Pending</h3>
        <div class="card-value"><?php echo $partialCount + $pendingCount; ?></div>
      </div>
      <div class="card">
        <h3>Total Courses</h3>
        <div class="card-value"><?php
          $courseRes = $conn->query("SELECT COUNT(DISTINCT course) as total FROM enrolled");
          echo $courseRes ? $courseRes->fetch_assoc()['total'] : 0;
        ?></div>
      </div>
    </div>

    <!-- Enrollment Table -->
    <div class="table-card">
      <div class="table-header">
        <h2>Enrollment Records</h2>
        <div class="table-actions">
          <form method="GET" style="display:inline;">
            <input type="text" name="search" class="section-search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>" style="width:220px;">
          </form>
          <?php if ($hasPayment): ?>
          <select id="statusFilter" class="filter-select">
            <option value="all">All Statuses</option>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="declined">Declined</option>
          </select>
          <?php endif; ?>
          <a href="create.php"><button class="btn primary">+ New Enrollment</button></a>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="enrollmentTable">
          <thead>
            <tr>
              <th>Enrollment ID</th>
              <th>Student Name</th>
              <th>Course</th>
              <th>Academic Year</th>
              <?php if ($hasPayment): ?><th>Payment</th><?php endif; ?>
              <?php if ($hasEnrollmentStatus): ?><th>Status</th><?php endif; ?>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($rows) > 0): ?>
              <?php foreach ($rows as $row):
                $status = $row['payment_status'] ?? 'pending';
                $badgeClass = $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger');
                $date = $row['created_at'] ?? date('Y-m-d');
                $enrollStatus = $row['enrollment_status'] ?? 'approved';
                $enrollBadge = $enrollStatus === 'approved' ? 'success' : ($enrollStatus === 'pending' ? 'warning' : 'danger');
              ?>
              <tr data-status="<?php echo $status; ?>" data-enroll="<?php echo $enrollStatus; ?>" data-record='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>'>
                <td>#ENR-<?php echo $row['student_id']; ?></td>
                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                <?php if ($hasPayment): ?>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                <?php endif; ?>
                <?php if ($hasEnrollmentStatus): ?>
                <td><span class="badge <?php echo $enrollBadge; ?>"><?php echo ucfirst($enrollStatus); ?></span></td>
                <?php endif; ?>
                <td>
                  <div class="action-group">
                    <button class="btn-action" onclick="openViewModal(this)" style="border-color:#10b981; color:#10b981;"><i class="fa-solid fa-eye"></i> View</button>
                    <a href="edit.php?id=<?php echo $row['student_id']; ?>"><button class="btn-action">Edit</button></a>
                    <a href="delete.php?id=<?php echo $row['student_id']; ?>" onclick="return confirm('Delete this record?')"><button class="btn-action decline">Delete</button></a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="<?php echo ($hasPayment ? 1 : 0) + ($hasEnrollmentStatus ? 1 : 0) + 5; ?>" style="text-align:center; color:#666;">No records found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- View Enrollment Modal -->
  <div class="modal-overlay" id="viewEnrollmentModal">
    <div class="modal-container" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-id-card"></i> Student Record</h3>
        <button class="modal-close-btn" onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <!-- Tabs -->
        <div class="modal-tabs">
          <button class="modal-tab active" onclick="switchTab('details')" id="tab-details">Enrollment Details</button>
          <button class="modal-tab" onclick="switchTab('cor')" id="tab-cor">Student COR</button>
        </div>

        <!-- Tab 1: Full Details -->
        <div class="tab-panel active" id="panel-details">
          <div id="detailsBody"><!-- Populated by JS --></div>
        </div>

        <!-- Tab 2: COR View -->
        <div class="tab-panel" id="panel-cor">
          <div class="cor-header">
            <div class="cor-school-name">INTERWORLD COLLEGES FOUNDATION, INC</div>
            <div class="cor-address">Burgos St., Paniqui, Tarlac</div>
            <div class="cor-title">Certificate of Registration</div>
          </div>

          <div class="student-info-grid">
            <div>
              <div class="info-line"><strong>Student ID:</strong> <span class="highlight" id="corStudentID">-</span></div>
              <div class="info-line"><strong>Name:</strong> <span id="corStudentName">-</span></div>
              <div class="info-line"><strong>Course:</strong> <span id="corStudentCourse">-</span></div>
            </div>
            <div class="student-info-right">
              <div class="info-line"><strong>Academic Year:</strong> <span id="corStudentYear">-</span></div>
              <div class="info-line"><strong>Date:</strong> <span id="corStudentDate">-</span></div>
            </div>
          </div>

          <!-- Course Schedule -->
          <div class="card-header"><i class="fas fa-book"></i> Enrolled Courses</div>
          <div id="corCoursesContainer">
            <div class="empty-state">
              <i class="fas fa-info-circle"></i>
              Course schedule data requires a <code>student_schedules</code> table.<br>
              <small>Contact your developer to add course enrollment functionality.</small>
            </div>
          </div>

          <!-- Payment & Assessment -->
          <div class="payment-assessment-wrap" style="margin-top: 15px;">
            <div class="payment-assess-card">
              <div class="card-header"><i class="fas fa-receipt"></i> Payment History</div>
              <div id="corPaymentsContainer">
                <div class="empty-state">
                  <i class="fas fa-info-circle"></i>
                  Payment records require a <code>student_payments</code> table.
                </div>
              </div>
            </div>
            <div class="payment-assess-card">
              <div class="card-header"><i class="fas fa-calculator"></i> Assessment</div>
              <div id="corAssessmentContainer">
                <div class="empty-state">
                  <i class="fas fa-info-circle"></i>
                  Assessment data requires a <code>student_assessments</code> table.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn primary" onclick="closeViewModal()">Close</button>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    function switchTab(tab) {
      document.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.getElementById('tab-' + tab).classList.add('active');
      document.getElementById('panel-' + tab).classList.add('active');
    }

    function openViewModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;

      // --- Tab 1: Full Details ---
      const sections = [
        {
          title: 'Personal Information',
          fields: [
            ['First Name', data.firstname],
            ['Middle Name', data.middlename],
            ['Last Name', data.lastname],
            ['Suffix', data.suffix || 'N/A'],
            ['Gender', data.gender],
            ['Birthday', data.birthday],
            ['Birthplace', data.birthplace],
            ['Citizenship', data.citizenship],
            ['Civil Status', data.civilstatus],
            ['Employment', data.employment]
          ]
        },
        {
          title: 'Family / Guardian',
          fields: [
            ["Mother's Name", data.mother],
            ["Mother's Phone", data.mphone_number],
            ["Father's Name", data.father],
            ["Father's Phone", data.fphone_number],
            ["Guardian's Name", data.guardian],
            ["Guardian's Phone", data.gphone_number]
          ]
        },
        {
          title: 'Academic Information',
          fields: [
            ['Course', data.course],
            ['Major', data.major],
            ['School Address', data.school_address],
            ['Academic Year', data.academic_year],
            ['Scholarship', data.scholarship]
          ]
        },
        {
          title: 'Contact & Address',
          fields: [
            ['Full Address', data.full_address],
            ['Mobile Number', data.mobile_number],
            ['Email', data.email]
          ]
        }
      ];

      let html = '';
      sections.forEach(sec => {
        html += `<div class="view-modal-section"><h4>${sec.title}</h4><div class="view-modal-grid">`;
        sec.fields.forEach(([label, value]) => {
          html += `<div class="view-modal-row"><span class="view-modal-label">${label}:</span><span class="view-modal-value">${(value !== null && value !== undefined && String(value).trim() !== '') ? value : 'N/A'}</span></div>`;
        });
        html += '</div></div>';
      });
      document.getElementById('detailsBody').innerHTML = html;

      // --- Tab 2: COR ---
      document.getElementById('corStudentID').textContent = data.student_code || ('#ENR-' + data.student_id);
      document.getElementById('corStudentName').textContent = (data.firstname + ' ' + data.lastname).trim();
      document.getElementById('corStudentCourse').textContent = data.course + (data.major ? ' - ' + data.major : '');
      document.getElementById('corStudentYear').textContent = data.academic_year || 'N/A';
      document.getElementById('corStudentDate').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

      document.getElementById('viewEnrollmentModal').classList.add('active');
      switchTab('details');
    }

    function closeViewModal() {
      document.getElementById('viewEnrollmentModal').classList.remove('active');
    }

    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewModal();
    });

    <?php if ($hasPayment): ?>
    document.getElementById('statusFilter').addEventListener('change', function(e) {
      const val = e.target.value.toLowerCase();
      document.querySelectorAll('#enrollmentTable tbody tr').forEach(row => {
        row.style.display = (val === 'all' || row.getAttribute('data-status') === val) ? '' : 'none';
      });
    });
    <?php endif; ?>
  </script>
</body>
</html>