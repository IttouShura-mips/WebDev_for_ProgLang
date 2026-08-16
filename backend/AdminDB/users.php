<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

// Check which columns actually exist in the enrolled table
$existingCols = [];
$colRes = $conn->query("SHOW COLUMNS FROM enrolled");
while ($c = $colRes->fetch_assoc()) {
    $existingCols[] = $c['Field'];
}

// Fetch full records for view modal
$students = [];
$res = $conn->query("SELECT * FROM enrolled ORDER BY student_id DESC");
while ($row = $res->fetch_assoc()) { $students[] = $row; }

// Handle delete/terminate
if (isset($_GET['terminate'])) {
    $id = intval($_GET['terminate']);
    $conn->query("DELETE FROM enrolled WHERE student_id = $id");
    header("Location: users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel - Users</title>
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
        <li><a href="adminpanel.php">Dashboard</a></li>
        <li class="active"><a href="users.php">Users</a></li>
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
      <h1>User Table List</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="userSearchInput" placeholder="Search users..." autocomplete="off"/>
          <div id="searchResults" class="search-results-dropdown"></div>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <section class="tables-container">
      <div class="table-card">
        <div class="table-header">
          <h2>Students</h2>
          <div class="table-controls">
            <input type="text" class="section-search" placeholder="Search username..." data-table="studentTable">
            <?php if (in_array('status', $existingCols)): ?>
            <select class="filter-select" data-table="studentTable">
              <option value="all">All Status</option>
              <option value="online">Online</option>
              <option value="offline">Offline</option>
            </select>
            <?php endif; ?>
            <a href="create.php"><button class="btn primary">+ Add Student</button></a>
          </div>
        </div>
        <div class="table-wrapper">
          <table id="studentTable">
            <thead>
              <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Role</th>
                <?php if (in_array('ip_address', $existingCols)): ?><th>IP Address</th><?php endif; ?>
                <?php if (in_array('last_login', $existingCols)): ?><th>Last Login</th><?php endif; ?>
                <?php if (in_array('status', $existingCols)): ?><th>Status</th><?php endif; ?>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($students) > 0): ?>
                <?php foreach ($students as $s): 
                  $username = strtolower(str_replace(' ', '_', $s['firstname'] . '_' . $s['lastname']));
                  $status = $s['status'] ?? 'offline';
                ?>
                <tr data-status="<?php echo strtolower($status); ?>" data-record='<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES, "UTF-8"); ?>'>
                  <td>#USR-<?php echo str_pad($s['student_id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td class="username"><?php echo htmlspecialchars($username); ?></td>
                  <td>Student</td>
                  <?php if (in_array('ip_address', $existingCols)): ?><td><?php echo htmlspecialchars($s['ip_address'] ?? 'N/A'); ?></td><?php endif; ?>
                  <?php if (in_array('last_login', $existingCols)): ?><td><?php echo $s['last_login'] ?? 'Never'; ?></td><?php endif; ?>
                  <?php if (in_array('status', $existingCols)): ?><td><span class="badge <?php echo $status === 'online' ? 'success' : 'danger'; ?>"><?php echo ucfirst($status); ?></span></td><?php endif; ?>
                  <td>
                    <div class="action-group">
                      <button class="btn-action" onclick="openViewModal(this)" style="border-color:#10b981; color:#10b981;"><i class="fa-solid fa-eye"></i> View</button>
                      <a href="edit.php?id=<?php echo $s['student_id']; ?>"><button class="btn-action">Edit</button></a>
                      <button class="btn-action delete" onclick="openTerminateModal(<?php echo $s['student_id']; ?>, '<?php echo htmlspecialchars($username); ?>')">Terminate</button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="<?php echo 4 + count(array_intersect(['ip_address','last_login','status'], $existingCols)); ?>" style="text-align:center; color:#666;">No students found</td></tr>
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

  <div id="terminateModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Confirm Termination</h3>
        <button class="modal-close" onclick="closeTerminateModal()">&times;</button>
      </div>
      <p style="color: var(--text-muted-teal); font-size: 0.9rem; margin-bottom: 20px;">
        Are you sure you want to terminate <strong id="terminateUserName" style="color:var(--primary-neon);"></strong>? This action cannot be undone.
      </p>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeTerminateModal()">Cancel</button>
        <a id="terminateLink" href="#"><button type="button" class="btn-danger">Terminate</button></a>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    document.querySelectorAll('.section-search, .filter-select').forEach(el => {
      el.addEventListener('input', filterTables);
      el.addEventListener('change', filterTables);
    });
    function filterTables() {
      document.querySelectorAll('.section-search').forEach(search => {
        const tableId = search.getAttribute('data-table');
        const filter = document.querySelector('.filter-select[data-table="' + tableId + '"]');
        const val = search.value.toLowerCase().trim();
        const fval = filter ? filter.value.toLowerCase() : 'all';
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
          const uname = row.querySelector('.username') ? row.querySelector('.username').textContent.toLowerCase() : '';
          const st = row.getAttribute('data-status') || 'offline';
          row.style.display = (uname.includes(val) && (fval === 'all' || st === fval)) ? '' : 'none';
        });
      });
    }

    let terminateId = 0;
    function openTerminateModal(id, name) {
      terminateId = id;
      document.getElementById('terminateUserName').textContent = name;
      document.getElementById('terminateLink').href = 'users.php?terminate=' + id;
      document.getElementById('terminateModal').classList.add('active');
    }
    function closeTerminateModal() {
      document.getElementById('terminateModal').classList.remove('active');
    }

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
          html += `<div class="view-modal-row"><span class="view-modal-label">${label}:</span><span class="view-modal-value">${value || 'N/A'}</span></div>`;
        });
        html += '</div></div>';
      });

      document.getElementById('viewModalBody').innerHTML = html;
      document.getElementById('viewEnrollmentModal').classList.add('active');
    }

    function closeViewModal() {
      document.getElementById('viewEnrollmentModal').classList.remove('active');
    }

    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewModal();
    });
  </script>
</body>
</html>