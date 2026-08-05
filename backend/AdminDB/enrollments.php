<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Enrollments</title>
  <link rel="stylesheet" href="../../admin/adminpanelstyle.css">
  <style>
    .search-bar { padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; width: 300px; }
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .badge.success { background: #d1fae5; color: #065f46; }
    .badge.warning { background: #fef3c7; color: #92400e; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="brand"><h2>Admin Panel</h2></div>
    <ul class="nav-links">
      <li><a href="adminpanel.php">Dashboard</a></li>
      <li class="active"><a href="enrollments.php">Enrollments</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Enrollment List</h1>
      <div class="header-right">
        <form method="GET" style="display:inline;">
          <input type="text" name="search" class="search-bar" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
        <div class="user-profile">
          <span><?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
          <div class="avatar">A</div>
        </div>
      </div>
    </header>

    <div class="table-card">
      <div class="table-header">
        <h2>Enrollment Records</h2>
        <a href="create.php"><button class="btn primary">+ New Enrollment</button></a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Enrollment ID</th>
              <th>Student Name</th>
              <th>Course</th>
              <th>Date Enrolled</th>
              <th>Payment</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td>#ENR-<?php echo $row['student_id']; ?></td>
                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo date('Y-m-d'); /* Replace with actual enrollment date field if you add one */ ?></td>
                <td><span class="badge success">Paid</span></td>
                <td>
                  <a href="edit.php?id=<?php echo $row['student_id']; ?>"><button class="btn-action">Edit</button></a>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6">No records found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>