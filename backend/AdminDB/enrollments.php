<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

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

$hasPayment = false;
$colCheck = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'payment_status'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasPayment = true;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
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
        <li><a href="users.php">Users</a></li>
        <li class="active"><a href="enrollments.php">Enrollments</a></li>
        <li><a href="received-mail.php">Received Mail</a></li>
      </ul>
    </div>
    <div class="sidebar-footer">
      <a href="../../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Log out</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Enrollment List</h1>
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
              <th>Date Enrolled</th>
              <?php if ($hasPayment): ?><th>Payment</th><?php endif; ?>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($rows) > 0): ?>
              <?php foreach ($rows as $row): 
                $status = $row['payment_status'] ?? 'paid';
                $badgeClass = $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger');
                $date = $row['created_at'] ?? date('Y-m-d');
              ?>
              <tr data-status="<?php echo $status; ?>" data-record='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>'>
                <td>#ENR-<?php echo $row['student_id']; ?></td>
                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo $date; ?></td>
                <?php if ($hasPayment): ?>
                <td>
                  <div class="payment-cell">
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                  </div>
                </td>
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
              <tr><td colspan="<?php echo $hasPayment ? 6 : 5; ?>" style="text-align:center; color:#666;">No records found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
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
  <?php if ($hasPayment): ?>
  <script>
    document.getElementById('statusFilter').addEventListener('change', function(e) {
      const val = e.target.value.toLowerCase();
      document.querySelectorAll('#enrollmentTable tbody tr').forEach(row => {
        row.style.display = (val === 'all' || row.getAttribute('data-status') === val) ? '' : 'none';
      });
    });
  </script>
  <?php endif; ?>
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

    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewModal();
    });
  </script>
</body>
</html>