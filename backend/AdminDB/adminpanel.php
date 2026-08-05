<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../admin/login.html");
    exit();
}
require 'db.php';

$count_result = $conn->query("SELECT COUNT(*) as total FROM enrolled");
$total_enrollments = $count_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel</title>
  <link rel="stylesheet" href="../../admin/adminpanelstyle.css">
</head>
<body>

  <aside class="sidebar">
    <div class="brand">
      <h2>Admin Panel</h2>
    </div>
    <ul class="nav-links">
      <li class="active"><a href="adminpanel.php">Dashboard</a></li>
      <li><a href="enrollments.php">Enrollments</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Dashboard Overview</h1>
      <div class="header-right">
        <div class="user-profile">
          <span><?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
          <div class="avatar">A</div>
        </div>
      </div>
    </header>

    <section class="cards-grid">
      <div class="card">
        <h3>Active Enrollments</h3>
        <p class="card-value"><?php echo $total_enrollments; ?></p>
      </div>
    </section>

    <section class="tables-container">
      <div class="table-card">
        <div class="table-header">
          <h2>Enrollment Records</h2>
          <a href="create.php"><button class="btn primary">+ New Enrollment</button></a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Course</th>
                <th>Academic Year</th>
                <th>Contact</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT student_id, firstname, lastname, course, academic_year, mobile_number FROM enrolled ORDER BY student_id DESC";
              $result = $conn->query($sql);
              
              if ($result->num_rows > 0) {
                  while($row = $result->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td>#ENR-" . $row['student_id'] . "</td>";
                      echo "<td>" . htmlspecialchars($row['firstname'] . " " . $row['lastname']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['academic_year']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['mobile_number']) . "</td>";
                      echo "<td>
                              <a href='edit.php?id=" . $row['student_id'] . "'><button class='btn-action'>Edit</button></a> 
                              <a href='delete.php?id=" . $row['student_id'] . "' onclick='return confirm(\"Delete this record?\")'><button class='btn-action' style='color:red; border-color:red;'>Delete</button></a>
                            </td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='6'>No enrollments found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>
</body>
</html>