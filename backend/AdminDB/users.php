<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

// Fetch users from the users table
$users_list = [];
$res = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
while ($row = $res->fetch_assoc()) { $users_list[] = $row; }

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id = $id");
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
      <a href="../../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Log out</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Registered Users</h1>
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
          <h2>User Accounts</h2>
          <div class="table-controls">
            <input type="text" class="section-search" placeholder="Search username or name..." data-table="userTable">
          </div>
        </div>
        <div class="table-wrapper">
          <table id="userTable">
            <thead>
              <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($users_list) > 0): ?>
                <?php foreach ($users_list as $u): 
                  $full_name = trim($u['first_name'] . ' ' . $u['middle_name'] . ' ' . $u['last_name']);
                ?>
                <tr data-record='<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8"); ?>'>
                  <td>#USR-<?php echo str_pad($u['user_id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td class="username"><?php echo htmlspecialchars($u['username']); ?></td>
                  <td><?php echo htmlspecialchars($full_name); ?></td>
                  <td>
                    <div class="action-group">
                      <button class="btn-action" onclick="openViewModal(this)" style="border-color:#10b981; color:#10b981;"><i class="fa-solid fa-eye"></i> View</button>
                      <button class="btn-action delete" onclick="openDeleteModal(<?php echo $u['user_id']; ?>, '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>')">Delete</button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center; color:#666;">No users found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <!-- View User Modal -->
  <div class="modal-overlay" id="viewUserModal">
    <div class="modal-container" style="max-width: 700px; max-height: 85vh; overflow-y: auto;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-user"></i> User Details</h3>
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

  <div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Confirm Delete</h3>
        <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
      </div>
      <p style="color: var(--text-muted-teal); font-size: 0.9rem; margin-bottom: 20px;">
        Are you sure you want to delete user <strong id="deleteUserName" style="color:var(--primary-neon);"></strong>? This action cannot be undone.
      </p>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <a id="deleteLink" href="#"><button type="button" class="btn-danger">Delete</button></a>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    document.querySelectorAll('.section-search').forEach(el => {
      el.addEventListener('input', filterTables);
    });
    function filterTables() {
      document.querySelectorAll('.section-search').forEach(search => {
        const tableId = search.getAttribute('data-table');
        const val = search.value.toLowerCase().trim();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
          const uname = row.querySelector('.username') ? row.querySelector('.username').textContent.toLowerCase() : '';
          const fullName = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
          row.style.display = (uname.includes(val) || fullName.includes(val)) ? '' : 'none';
        });
      });
    }

    let deleteId = 0;
    function openDeleteModal(id, name) {
      deleteId = id;
      document.getElementById('deleteUserName').textContent = name;
      document.getElementById('deleteLink').href = 'users.php?delete=' + id;
      document.getElementById('deleteModal').classList.add('active');
    }
    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('active');
    }

    function openViewModal(btn) {
      const row = btn.closest('tr');
      const data = JSON.parse(row.getAttribute('data-record'));
      if (!data) return;

      const sections = [
        {
          title: 'Account Information',
          fields: [
            ['User ID', '#USR-' + String(data.user_id).padStart(3, '0')],
            ['Username', data.username],
            ['First Name', data.first_name],
            ['Middle Name', data.middle_name],
            ['Last Name', data.last_name]
          ]
        }
      ];

      let html = '';
      sections.forEach(sec => {
        html += `<div class="view-modal-section"><h4>${sec.title}</h4><div class="view-modal-grid">`;
        sec.fields.forEach(([label, value]) => {
          const displayValue = (value !== null && value !== undefined && String(value).trim() !== '') ? value : 'N/A';
          html += `<div class="view-modal-row"><span class="view-modal-label">${label}:</span><span class="view-modal-value">${displayValue}</span></div>`;
        });
        html += '</div></div>';
      });

      document.getElementById('viewModalBody').innerHTML = html;
      document.getElementById('viewUserModal').classList.add('active');
    }

    function closeViewModal() {
      document.getElementById('viewUserModal').classList.remove('active');
    }

    document.getElementById('viewUserModal').addEventListener('click', function(e) {
      if (e.target === this) closeViewModal();
    });
  </script>
</body>
</html>