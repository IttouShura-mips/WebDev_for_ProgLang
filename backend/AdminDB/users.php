<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

// Check if extra columns exist in enrolled
$hasExtraCols = false;
$colCheck = $conn->query("SHOW COLUMNS FROM enrolled LIKE 'status'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasExtraCols = true;
}

// Fetch students
$students = [];
if ($hasExtraCols) {
    $res = $conn->query("SELECT student_id, firstname, lastname, email, ip_address, last_login, status FROM enrolled ORDER BY student_id DESC");
} else {
    $res = $conn->query("SELECT student_id, firstname, lastname, email FROM enrolled ORDER BY student_id DESC");
}
while ($row = $res->fetch_assoc()) { $students[] = $row; }

// Fetch staff
$staff = [];
$staffTableExists = $conn->query("SHOW TABLES LIKE 'staff_users'") && $conn->query("SHOW TABLES LIKE 'staff_users'")->num_rows > 0;
if ($staffTableExists) {
    $res2 = $conn->query("SELECT id, user_id, username, role, ip_address, last_login, status FROM staff_users ORDER BY id DESC");
    while ($row = $res2->fetch_assoc()) { $staff[] = $row; }
}

// Handle AJAX/POST for adding staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $username = trim($_POST['username'] ?? '');
    $password = password_hash($_POST['password'] ?? 'staff123', PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'Instructor';
    $randomId = '#USR-' . rand(100, 999);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    if ($staffTableExists && !empty($username)) {
        $stmt = $conn->prepare("INSERT INTO staff_users (user_id, username, password, role, ip_address, status) VALUES (?, ?, ?, ?, ?, 'online')");
        $stmt->bind_param("sssss", $randomId, $username, $password, $role, $ip);
        $stmt->execute();
    }
    header("Location: users.php");
    exit();
}

// Handle delete/terminate
if (isset($_GET['terminate']) && isset($_GET['type'])) {
    if ($_GET['type'] === 'staff' && $staffTableExists) {
        $id = intval($_GET['terminate']);
        $conn->query("DELETE FROM staff_users WHERE id = $id");
    } elseif ($_GET['type'] === 'student') {
        $id = intval($_GET['terminate']);
        $conn->query("DELETE FROM enrolled WHERE student_id = $id");
    }
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
      <a href="../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
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
      <!-- Staff Section -->
      <div class="table-card">
        <div class="table-header">
          <h2>Staff Members</h2>
          <div class="table-controls">
            <input type="text" class="section-search" placeholder="Search username..." data-table="staffTable">
            <select class="filter-select" data-table="staffTable">
              <option value="all">All Status</option>
              <option value="online">Online</option>
              <option value="offline">Offline</option>
            </select>
            <button class="btn primary" onclick="openAddModal('staff')">+ Add Account</button>
          </div>
        </div>
        <div class="table-wrapper">
          <table id="staffTable">
            <thead>
              <tr><th>User ID</th><th>Username</th><th>Role</th><th>IP Address</th><th>Last Login</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($staffTableExists && count($staff) > 0): ?>
                <?php foreach ($staff as $s): ?>
                <tr data-status="<?php echo strtolower($s['status'] ?? 'offline'); ?>">
                  <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                  <td class="username"><?php echo htmlspecialchars($s['username']); ?></td>
                  <td><?php echo htmlspecialchars($s['role']); ?></td>
                  <td><?php echo htmlspecialchars($s['ip_address'] ?? 'N/A'); ?></td>
                  <td><?php echo $s['last_login'] ?? 'Never'; ?></td>
                  <td><span class="badge <?php echo ($s['status'] ?? 'offline') === 'online' ? 'success' : 'danger'; ?>"><?php echo ucfirst($s['status'] ?? 'offline'); ?></span></td>
                  <td><button class="btn-action delete" onclick="openTerminateModal('staff', <?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['username']); ?>')">Terminate</button></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" style="text-align:center; color:#666;">
                  <?php echo $staffTableExists ? 'No staff members found' : 'Staff table not set up. Run database.sql to create it.'; ?>
                </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Student Section -->
      <div class="table-card">
        <div class="table-header">
          <h2>Students</h2>
          <div class="table-controls">
            <input type="text" class="section-search" placeholder="Search username..." data-table="studentTable">
            <select class="filter-select" data-table="studentTable">
              <option value="all">All Status</option>
              <option value="online">Online</option>
              <option value="offline">Offline</option>
            </select>
            <a href="create.php"><button class="btn primary">+ Add Student</button></a>
          </div>
        </div>
        <div class="table-wrapper">
          <table id="studentTable">
            <thead>
              <tr><th>User ID</th><th>Username</th><th>Role</th><th>IP Address</th><th>Last Login</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if (count($students) > 0): ?>
                <?php foreach ($students as $s): 
                  $username = strtolower(str_replace(' ', '_', $s['firstname'] . '_' . $s['lastname']));
                  $status = $s['status'] ?? 'offline';
                ?>
                <tr data-status="<?php echo strtolower($status); ?>">
                  <td>#USR-<?php echo str_pad($s['student_id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td class="username"><?php echo htmlspecialchars($username); ?></td>
                  <td>Student</td>
                  <td><?php echo htmlspecialchars($s['ip_address'] ?? 'N/A'); ?></td>
                  <td><?php echo $s['last_login'] ?? 'Never'; ?></td>
                  <td><span class="badge <?php echo $status === 'online' ? 'success' : 'danger'; ?>"><?php echo ucfirst($status); ?></span></td>
                  <td><button class="btn-action delete" onclick="openTerminateModal('student', <?php echo $s['student_id']; ?>, '<?php echo htmlspecialchars($username); ?>')">Terminate</button></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" style="text-align:center; color:#666;">No students found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <!-- Add Account Modal -->
  <div id="addAccountModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="modalTitle">Add New Staff Account</h3>
        <button class="modal-close" onclick="closeAddModal()">&times;</button>
      </div>
      <form method="POST" action="users.php">
        <input type="hidden" name="add_staff" value="1">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required placeholder="Enter username">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required placeholder="Enter password" minlength="4">
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" style="width:100%; padding:10px; background:var(--bg-deep-abyss); color:var(--text-high-contrast); border:1px solid var(--border-teal); border-radius:6px;">
            <option value="Instructor">Instructor</option>
            <option value="Registrar">Registrar</option>
            <option value="Admin">Admin</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn primary">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Terminate Modal -->
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
    // Table filtering
    document.querySelectorAll('.section-search, .filter-select').forEach(el => {
      el.addEventListener('input', filterTables);
      el.addEventListener('change', filterTables);
    });
    function filterTables() {
      document.querySelectorAll('.section-search').forEach(search => {
        const tableId = search.getAttribute('data-table');
        const filter = document.querySelector(`.filter-select[data-table="${tableId}"]`);
        const val = search.value.toLowerCase().trim();
        const fval = filter ? filter.value.toLowerCase() : 'all';
        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
          const uname = row.querySelector('.username')?.textContent.toLowerCase() || '';
          const st = row.getAttribute('data-status') || 'offline';
          row.style.display = (uname.includes(val) && (fval === 'all' || st === fval)) ? '' : 'none';
        });
      });
    }

    function openAddModal(type) {
      document.getElementById('modalTitle').textContent = type === 'staff' ? 'Add New Staff Account' : 'Add New Student';
      document.getElementById('addAccountModal').classList.add('active');
    }
    function closeAddModal() {
      document.getElementById('addAccountModal').classList.remove('active');
    }

    let terminateType = '', terminateId = 0;
    function openTerminateModal(type, id, name) {
      terminateType = type; terminateId = id;
      document.getElementById('terminateUserName').textContent = name;
      document.getElementById('terminateLink').href = `users.php?terminate=${id}&type=${type}`;
      document.getElementById('terminateModal').classList.add('active');
    }
    function closeTerminateModal() {
      document.getElementById('terminateModal').classList.remove('active');
    }
  </script>
</body>
</html>