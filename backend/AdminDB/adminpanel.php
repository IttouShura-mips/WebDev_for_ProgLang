<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

$count_result = $conn->query("SELECT COUNT(*) as total FROM enrolled");
$total_enrollments = $count_result->fetch_assoc()['total'] ?? 0;

$course_result = $conn->query("SELECT COUNT(DISTINCT course) as total FROM enrolled");
$total_courses = $course_result->fetch_assoc()['total'] ?? 0;

$logins = [];
$login_check = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'last_login'");
if ($login_check && $login_check->num_rows > 0) {
    $logins_result = $conn->query("SELECT student_id, firstname, lastname, email, ip_address, last_login, status FROM enrolled WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 5");
    while ($row = $logins_result->fetch_assoc()) { $logins[] = $row; }
}

$enrollments = [];
$enr_result = $conn->query("SELECT student_id, firstname, lastname, course, academic_year, mobile_number FROM enrolled ORDER BY student_id DESC LIMIT 5");
while ($row = $enr_result->fetch_assoc()) { $enrollments[] = $row; }

// Fetch full records for view modal
$full_records = [];
$all_res = $conn->query("SELECT * FROM enrolled ORDER BY student_id DESC LIMIT 5");
while ($row = $all_res->fetch_assoc()) { $full_records[$row['student_id']] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="adminpanelstyle.css">
  <style>
    .view-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .view-modal-grid.full { grid-template-columns: 1fr; }
    .view-modal-section { margin-bottom: 18px; }
    .view-modal-section h4 { color: var(--primary-neon); font-size: 0.95rem; margin-bottom: 10px; border-bottom: 1px solid var(--border-teal); padding-bottom: 6px; }
    .view-modal-row { display: flex; margin-bottom: 8px; font-size: 0.88rem; }
    .view-modal-label { width: 140px; color: var(--text-muted-teal); font-weight: 600; flex-shrink: 0; }
    .view-modal-value { color: var(--text-high-contrast); flex: 1; word-break: break-word; }
    @media (max-width: 600px) { .view-modal-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div>
      <div class="brand"><h2>Admin Panel</h2></div>
      <ul class="nav-links">
        <li class="active"><a href="adminpanel.php">Dashboard</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="enrollments.php">Enrollments</a></li>
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
      <h1>Dashboard Overview</h1>
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

    <section class="cards-grid">
      <div class="card">
        <h3>Total Students</h3>
        <p class="card-value"><?php echo $total_enrollments; ?></p>
      </div>
      <div class="card">
        <h3>Active Enrollments</h3>
        <p class="card-value"><?php echo $total_enrollments; ?></p>
      </div>
      <div class="card">
        <h3>Courses Offered</h3>
        <p class="card-value"><?php echo $total_courses; ?></p>
      </div>
    </section>

    <section class="tables-container">
      <div class="table-card">
        <div class="table-header">
          <h2>Recent User Logins</h2>
          <a href="users.php"><button class="btn">View All</button></a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>User ID</th><th>Username</th><th>Role</th><th>IP Address</th><th>Last Login</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if (count($logins) > 0): ?>
                <?php foreach ($logins as $login): ?>
                <tr>
                  <td>#USR-<?php echo str_pad($login['student_id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td><?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $login['firstname'] . '_' . $login['lastname']))); ?></td>
                  <td>Student</td>
                  <td><?php echo htmlspecialchars($login['ip_address'] ?? 'N/A'); ?></td>
                  <td><?php echo $login['last_login']; ?></td>
                  <td><span class="badge <?php echo ($login['status'] ?? 'offline') === 'online' ? 'success' : 'danger'; ?>"><?php echo ucfirst($login['status'] ?? 'offline'); ?></span></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" style="text-align:center; color:#666;">No recent login records found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h2>Enrollment Records</h2>
          <a href="create.php"><button class="btn primary">+ New Enrollment</button></a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Enrollment ID</th><th>Student Name</th><th>Course</th><th>Academic Year</th><th>Contact</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if (count($enrollments) > 0): ?>
                <?php foreach ($enrollments as $row): 
                  $full = $full_records[$row['student_id']] ?? [];
                ?>
                <tr data-record='<?php echo htmlspecialchars(json_encode($full), ENT_QUOTES, "UTF-8"); ?>'>
                  <td>#ENR-<?php echo $row['student_id']; ?></td>
                  <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                  <td><?php echo htmlspecialchars($row['course']); ?></td>
                  <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                  <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                  <td>
                    <div class="action-group">
                      <button class="btn-action" onclick="openViewModal(this)" style="border-color:#10b981; color:#10b981;"><i class="fa-solid fa-eye"></i> View</button>
                      <a href="edit.php?id=<?php echo $row['student_id']; ?>"><button class="btn-action">Edit</button></a>
                      <a href="delete.php?id=<?php echo $row['student_id']; ?>" onclick="return confirm('Delete this record?')"><button class="btn-action" style="color:red; border-color:red;">Delete</button></a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" style="text-align:center; color:#666;">No enrollments found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <!-- View Enrollment Modal -->
  <div class="modal-overlay" id="viewEnrollmentModal">
    <div class="modal-container" style="max-width: 700px; max-height: 85vh; overflow-y: auto;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-id-card"></i> Enrollment Details</h3>
        <button class="modal-close-btn" onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="viewModalBody">
        <!-- Populated by JS -->
      </div>
      <div class="modal-footer">
        <button class="btn primary" onclick="closeViewModal()">Close</button>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    function openViewModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;

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

      document.getElementById('viewModalBody').innerHTML = html;
      document.getElementById('viewEnrollmentModal').classList.add('active');
    }

    function closeViewModal() {
      document.getElementById('viewEnrollmentModal').classList.remove('active');
    }

    // Close on overlay click
    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewModal();
    });
  </script>
</body>
</html>