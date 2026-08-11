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
      <a href="../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
      <a href="logout.php" class="btn-homepage" style="margin-top:10px; border-color:#ef4444; color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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
              <tr data-status="<?php echo $status; ?>">
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
</body>
</html>