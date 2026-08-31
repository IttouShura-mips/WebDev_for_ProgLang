<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.html");
    exit();
}
require 'db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_user']);

$receivedTable = $conn->query("SHOW TABLES LIKE 'received_mail'") && $conn->query("SHOW TABLES LIKE 'received_mail'")->num_rows > 0;
$composedTable = $conn->query("SHOW TABLES LIKE 'composed_mail'") && $conn->query("SHOW TABLES LIKE 'composed_mail'")->num_rows > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compose_submit'])) {
    $sender = 'ICFAdmin';
    $email = trim($_POST['recipientEmail'] ?? '');
    $receiver = trim($_POST['recipientName'] ?? '');
    $subject = trim($_POST['emailSubject'] ?? '');
    $message = trim($_POST['emailMessage'] ?? '');
    $date = date('Y-m-d');
    $editingId = intval($_POST['editingId'] ?? 0);

    if ($composedTable) {
        if ($editingId > 0) {
            $stmt = $conn->prepare("UPDATE composed_mail SET email=?, receiver=?, subject=?, message=?, date_sent=? WHERE id=?");
            $stmt->bind_param("sssssi", $email, $receiver, $subject, $message, $date, $editingId);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO composed_mail (sender, email, receiver, subject, message, date_sent, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending Send')");
            $stmt->bind_param("ssssss", $sender, $email, $receiver, $subject, $message, $date);
            $stmt->execute();
        }
    }
    header("Location: received-mail.php?success=1");
    exit();
}

if (isset($_GET['send'])) {
    $id = intval($_GET['send']);
    if ($composedTable) {
        $conn->query("UPDATE composed_mail SET status='Sent' WHERE id=$id");
    }
    header("Location: received-mail.php?sent=1");
    exit();
}

if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    if ($composedTable) {
        $conn->query("UPDATE composed_mail SET status='Cancelled' WHERE id=$id");
    }
    header("Location: received-mail.php?cancelled=1");
    exit();
}

$received = [];
if ($receivedTable) {
    $res = $conn->query("SELECT * FROM received_mail ORDER BY date_received DESC, id DESC");
    while ($row = $res->fetch_assoc()) { $received[] = $row; }
}

$composed = [];
if ($composedTable) {
    $res = $conn->query("SELECT * FROM composed_mail ORDER BY date_sent DESC, id DESC");
    while ($row = $res->fetch_assoc()) { $composed[] = $row; }
}

if (!$receivedTable) {
    $received = [
        ['id'=>1, 'email'=>'clarissa.villamor@gmail.com', 'sender_name'=>'Clarissa Villamor', 'subject'=>'Inquiry Regarding BS Computer Science Tuition Fees', 'date_received'=>'2026-08-05', 'status'=>'unread', 'message'=>'Hello, I would like to inquire about the tuition fees for BS Computer Science.'],
        ['id'=>2, 'email'=>'mark.reyes@yahoo.com', 'sender_name'=>'Mark Anthony Reyes', 'subject'=>'Request for Transcript of Records (TOR)', 'date_received'=>'2026-08-04', 'status'=>'urgent', 'message'=>'I need my TOR as soon as possible for my job application.'],
        ['id'=>3, 'email'=>'jasmine.cruz@gmail.com', 'sender_name'=>'Jasmine Cruz', 'subject'=>'Status of Academic Scholarship Application', 'date_received'=>'2026-08-03', 'status'=>'read', 'message'=>'May I know the status of my scholarship application?'],
    ];
}
if (!$composedTable) {
    $composed = [
        ['id'=>1, 'sender'=>'ICFAdmin', 'email'=>'clarissa.villamor@gmail.com', 'receiver'=>'Clarissa Villamor', 'date_sent'=>'2026-08-06', 'status'=>'Sent', 'subject'=>'Tuition Fee Response', 'message'=>'Hello Clarissa, here are the details regarding the BS Computer Science tuition fees...'],
        ['id'=>2, 'sender'=>'ICFAdmin', 'email'=>'mark.reyes@yahoo.com', 'receiver'=>'Mark Anthony Reyes', 'date_sent'=>'2026-08-06', 'status'=>'Pending Send', 'subject'=>'TOR Request Status', 'message'=>'Dear Mark, we have received your TOR request and it is currently being processed.'],
    ];
}

$statusFilter = $_GET['status'] ?? 'all';

// AJAX endpoint: mark inbox message as read
if (isset($_GET['mark_read'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_GET['mark_read']);
    if ($receivedTable) {
        $stmt = $conn->prepare("UPDATE received_mail SET status = 'read' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit();
}

// ============================================
// ENROLLMENT APPROVAL / DECLINE SYSTEM
// ============================================

// NOTE: Run `composer require phpmailer/phpmailer` in your project root first.
// Then update the SMTP credentials inside sendEnrollmentApprovalEmail() below.

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateStudentCode($conn) {
    $year = date('Y');
    $prefix = $year . '-';
    $stmt = $conn->prepare("SELECT username FROM users WHERE username LIKE ? ORDER BY username DESC LIMIT 1");
    $like = $prefix . '%';
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $nextNum = 1;
    if ($result->num_rows > 0) {
        $last = $result->fetch_assoc()['username'];
        $parts = explode('-', $last);
        if (isset($parts[1]) && is_numeric($parts[1])) {
            $nextNum = intval($parts[1]) + 1;
        }
    }
    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

function sendEnrollmentApprovalEmail($toEmail, $studentName, $studentId, $password) {
    $mail = new PHPMailer(true);
    try {
        // TODO: Replace with your actual SMTP credentials
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
     $mail->Username   = 'Shanrayeguzman0@gmail.com'; 
        $mail->Password   = 'beto pzbx zgqk kjcv';  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@icfpaniqui.edu.ph', 'ICF Enrollment System');
        $mail->addAddress($toEmail, $studentName);

        $mail->isHTML(true);
        $mail->Subject = 'ICF Enrollment Approved - Student Portal Credentials';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; color: #0a192f; max-width: 600px; margin: 0 auto; border: 1px solid #172a45; border-radius: 12px; overflow: hidden;'>
                <div style='background: #020c1b; padding: 20px; text-align: center;'>
                    <h2 style='color: #0df5e3; margin: 0;'>Welcome to ICF!</h2>
                </div>
                <div style='padding: 25px; background: #fff;'>
                    <p style='font-size: 16px;'>Dear <strong>{$studentName}</strong>,</p>
                    <p>Your enrollment application has been <strong style='color: #10b981;'>APPROVED</strong>.</p>
                    <p>You may now access the <strong>Student Portal</strong> using the credentials below:</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8fafc; border-radius: 8px;'>
                        <tr>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #0a192f; width: 40%;'>Student ID:</td>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #0a192f; font-family: monospace; font-size: 15px;'>{$studentId}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 15px; font-weight: bold; color: #0a192f;'>Password:</td>
                            <td style='padding: 12px 15px; color: #0a192f; font-family: monospace; font-size: 15px;'>{$password}</td>
                        </tr>
                    </table>
                    <p style='text-align: center; margin: 25px 0;'>
                        <a href='../../student/login.html' style='display: inline-block; padding: 12px 24px; background: #0df5e3; color: #020c1b; text-decoration: none; border-radius: 8px; font-weight: bold;'>Go to Student Portal</a>
                    </p>
                    <p style='font-size: 13px; color: #666; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px;'>
                        Please change your password immediately after your first login for security purposes.<br>
                        If you have any questions, contact the Registrar's Office.
                    </p>
                </div>
            </div>
        ";
        $mail->AltBody = "Welcome to ICF, {$studentName}!\n\nYour enrollment has been APPROVED.\n\nStudent ID: {$studentId}\nPassword: {$password}\n\nLogin at: ../../student/login.html\n\nPlease change your password after first login.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Approve enrollment
if (isset($_GET['approve_enrollment'])) {
    $id = intval($_GET['approve_enrollment']);

    // Auto-create student_code column if it doesn't exist
    $conn->query("ALTER TABLE enrolled ADD COLUMN IF NOT EXISTS student_code VARCHAR(20) DEFAULT NULL AFTER student_id");

    $stmt = $conn->prepare("SELECT * FROM enrolled WHERE student_id = ? AND enrollment_status = 'pending'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $enr = $result->fetch_assoc();

        $studentCode = generateStudentCode($conn);
        $plainPassword = generatePassword();
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt2 = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssss", $enr['firstname'], $enr['middlename'], $enr['lastname'], $studentCode, $hashedPassword);

        if ($stmt2->execute()) {
            $stmt3 = $conn->prepare("UPDATE enrolled SET enrollment_status = 'approved', student_code = ? WHERE student_id = ?");
            $stmt3->bind_param("si", $studentCode, $id);
            $stmt3->execute();

            $emailSent = sendEnrollmentApprovalEmail($enr['email'], $enr['firstname'] . ' ' . $enr['lastname'], $studentCode, $plainPassword);

            if ($emailSent) {
                header("Location: received-mail.php?enrollment_approved=1");
            } else {
                header("Location: received-mail.php?enrollment_approved=1&email_error=1");
            }
            exit();
        }
    }
    header("Location: received-mail.php?approval_error=1");
    exit();
}

// Decline enrollment
if (isset($_GET['decline_enrollment'])) {
    $id = intval($_GET['decline_enrollment']);
    $stmt = $conn->prepare("UPDATE enrolled SET enrollment_status = 'declined' WHERE student_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: received-mail.php?enrollment_declined=1");
    exit();
}

// Fetch pending enrollments
$pendingEnrollments = [];
$penRes = $conn->query("SELECT * FROM enrolled WHERE enrollment_status = 'pending' ORDER BY created_at DESC");
if ($penRes) {
    while ($row = $penRes->fetch_assoc()) {
        $pendingEnrollments[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel - Received Mail</title>
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
        <li><a href="enrollments.php">Enrollments</a></li>
        <li class="active"><a href="received-mail.php">Received Mail</a></li>
      </ul>
    </div>
    <div class="sidebar-footer">
      <a href="../../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Log out</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <h1>Received Mail</h1>
      <div class="header-right">
        <div class="search-container">
          <input type="text" id="mailSearchInput" placeholder="Search mail..." autocomplete="off"/>
          <div id="searchResults" class="search-results-dropdown"></div>
        </div>
        <div class="user-profile">
          <span><?php echo $admin_name; ?></span>
          <div class="avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
        </div>
      </div>
    </header>

    <?php if (!$receivedTable || !$composedTable): ?>
    <div style="background:#fef3c7; color:#92400e; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:0.9rem;">
      <i class="fa-solid fa-triangle-exclamation"></i> <strong>Note:</strong> Mail tables not found in database. Showing demo data. Run <code>database.sql</code> to enable full mail functionality.
    </div>
    <?php endif; ?>

    <div class="cards-grid">
      <div class="card"><h3>Total Messages</h3><div class="card-value"><?php echo count($received); ?></div></div>
      <div class="card"><h3>Unread Mail</h3><div class="card-value"><?php echo count(array_filter($received, function($r){return $r['status']==='unread';})); ?></div></div>
      <div class="card"><h3>Urgent Inquiries</h3><div class="card-value"><?php echo count(array_filter($received, function($r){return $r['status']==='urgent';})); ?></div></div>
      <div class="card"><h3>Composed Messages</h3><div class="card-value"><?php echo count($composed); ?></div></div>
    </div>

    <!-- Pending Enrollment Requests -->
    <div class="table-card">
      <div class="table-header">
        <h2><i class="fa-solid fa-user-graduate"></i> Pending Enrollment Requests</h2>
      </div>
      <div class="table-wrapper">
        <table id="pendingEnrollmentTable">
          <thead>
            <tr>
              <th>Student Name</th>
              <th>Course</th>
              <th>Email</th>
              <th>Date Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($pendingEnrollments) > 0): ?>
              <?php foreach ($pendingEnrollments as $pen): ?>
              <tr data-record='<?php echo htmlspecialchars(json_encode($pen), ENT_QUOTES, "UTF-8"); ?>'>
                <td><?php echo htmlspecialchars($pen['firstname'] . ' ' . $pen['lastname']); ?></td>
                <td><?php echo htmlspecialchars($pen['course']); ?></td>
                <td><?php echo htmlspecialchars($pen['email']); ?></td>
                <td><?php echo $pen['created_at']; ?></td>
                <td>
                  <div class="action-group">
                    <button class="btn-action" onclick="openEnrollmentViewModal(this)" style="border-color:#10b981; color:#10b981;"><i class="fa-solid fa-eye"></i> View</button>
                    <a href="received-mail.php?approve_enrollment=<?php echo $pen['student_id']; ?>" onclick="return confirm('Approve this enrollment? A student account (ID & password) will be created and emailed to the applicant.')">
                      <button class="btn-action btn-send"><i class="fa-solid fa-check"></i> Approve</button>
                    </a>
                    <a href="received-mail.php?decline_enrollment=<?php echo $pen['student_id']; ?>" onclick="return confirm('Decline this enrollment request?')">
                      <button class="btn-action decline"><i class="fa-solid fa-xmark"></i> Decline</button>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" style="text-align:center; color:#666;">No pending enrollment requests</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h2>Inbox Messages</h2>
        <div class="table-actions">
          <select id="statusFilter" class="filter-select" onchange="filterMail()">
            <option value="all" <?php echo $statusFilter==='all'?'selected':''; ?>>All Statuses</option>
            <option value="read" <?php echo $statusFilter==='read'?'selected':''; ?>>Read</option>
            <option value="unread" <?php echo $statusFilter==='unread'?'selected':''; ?>>Unread</option>
            <option value="urgent" <?php echo $statusFilter==='urgent'?'selected':''; ?>>Urgent</option>
          </select>
          <button class="btn primary" id="openComposeModal"><i class="fa-solid fa-paper-plane"></i> Compose</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="mailTable">
          <thead>
            <tr><th>Mail Address</th><th>Sender Name</th><th>Subject</th><th>Date Received</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($received as $mail): 
              $badgeClass = $mail['status'] === 'read' ? 'success' : ($mail['status'] === 'urgent' ? 'danger' : 'warning');
            ?>
            <tr data-id="<?php echo $mail['id']; ?>" data-status="<?php echo $mail['status']; ?>" data-subject="<?php echo htmlspecialchars($mail['subject']); ?>" data-message="<?php echo htmlspecialchars($mail['message']); ?>" data-sender="<?php echo htmlspecialchars($mail['sender_name']); ?>" data-email="<?php echo htmlspecialchars($mail['email']); ?>" data-date="<?php echo $mail['date_received']; ?>">
              <td><?php echo htmlspecialchars($mail['email']); ?></td>
              <td><?php echo htmlspecialchars($mail['sender_name']); ?></td>
              <td><?php echo htmlspecialchars($mail['subject']); ?></td>
              <td><?php echo $mail['date_received']; ?></td>
              <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($mail['status']); ?></span></td>
              <td><button class="btn-action btn-view" onclick="viewMessage(this, 'inbox')">View</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header"><h2>Composed Message List</h2></div>
      <div class="table-wrapper">
        <table id="composedMailTable">
          <thead>
            <tr><th>Sender</th><th>Mail Address</th><th>Receiver</th><th>Date Sent</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($composed as $mail): 
              $badgeClass = $mail['status'] === 'Sent' ? 'success' : ($mail['status'] === 'Pending Send' ? 'info' : 'danger');
            ?>
            <tr data-subject="<?php echo htmlspecialchars($mail['subject']); ?>" data-message="<?php echo htmlspecialchars($mail['message']); ?>">
              <td><?php echo htmlspecialchars($mail['sender']); ?></td>
              <td><?php echo htmlspecialchars($mail['email']); ?></td>
              <td><?php echo htmlspecialchars($mail['receiver']); ?></td>
              <td><?php echo $mail['date_sent']; ?></td>
              <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $mail['status']; ?></span></td>
              <td>
                <div class="action-group">
                  <button class="btn-action btn-view" onclick="viewMessage(this, 'composed')">View</button>
                  <?php if ($mail['status'] === 'Pending Send'): ?>
                  <button class="btn-action btn-edit" onclick="editComposedMessage(<?php echo $mail['id']; ?>, '<?php echo htmlspecialchars($mail['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mail['receiver'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mail['subject'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mail['message'], ENT_QUOTES); ?>')">Edit</button>
                  <a href="received-mail.php?send=<?php echo $mail['id']; ?>"><button class="btn-action btn-send">Send</button></a>
                  <a href="received-mail.php?cancel=<?php echo $mail['id']; ?>"><button class="btn-action btn-cancel">Cancel</button></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <div class="modal-overlay" id="viewMessageModal">
    <div class="modal-container">
      <div class="modal-header">
        <h3><i class="fa-solid fa-envelope-open-text"></i> View Message Details</h3>
        <button class="modal-close-btn" onclick="toggleViewModal(false)"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="view-detail-row"><span class="view-detail-label" id="viewLabelContact">From/To:</span><span class="view-detail-value" id="viewContactVal">-</span></div>
        <div class="view-detail-row"><span class="view-detail-label">Mail Address:</span><span class="view-detail-value" id="viewEmailVal">-</span></div>
        <div class="view-detail-row"><span class="view-detail-label">Date:</span><span class="view-detail-value" id="viewDateVal">-</span></div>
        <div class="view-detail-row"><span class="view-detail-label">Status:</span><span class="view-detail-value" id="viewStatusVal">-</span></div>
        <div class="view-detail-row" id="viewSubjectRow"><span class="view-detail-label">Subject:</span><span class="view-detail-value" id="viewSubjectVal">-</span></div>
        <div style="margin-top: 15px;"><span class="view-detail-label">Message:</span><div class="view-message-content" id="viewBodyVal">No message content available.</div></div>
      </div>
      <div class="modal-footer"><button class="btn primary" onclick="toggleViewModal(false)">Close</button></div>
    </div>
  </div>

  <div class="modal-overlay" id="composeModal">
    <div class="modal-container">
      <div class="modal-header">
        <h3 id="composeModalTitle"><i class="fa-solid fa-paper-plane"></i> New Message</h3>
        <button class="modal-close-btn" onclick="toggleComposeModal(false)"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="received-mail.php" id="composeForm">
          <input type="hidden" name="compose_submit" value="1">
          <input type="hidden" id="editingId" name="editingId" value="" />
          <div class="form-group"><label>From</label><input type="text" value="ICFAdmin" readonly /></div>
          <div class="form-group"><label>Recipient Mail Address</label><input type="email" name="recipientEmail" id="recipientEmail" placeholder="example@gmail.com" required /></div>
          <div class="form-group"><label>Receiver Name</label><input type="text" name="recipientName" id="recipientName" placeholder="Enter receiver name..." required /></div>
          <div class="form-group"><label>Subject</label><input type="text" name="emailSubject" id="emailSubject" placeholder="Enter subject..." required /></div>
          <div class="form-group"><label>Message</label><textarea name="emailMessage" id="emailMessage" placeholder="Type your message here..." required></textarea></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="toggleComposeModal(false)">Cancel</button>
        <button class="btn primary" type="submit" form="composeForm">Save Message</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="successModal">
    <div class="modal-container" style="max-width: 400px;">
      <div class="modal-header"><h3>Success</h3><button class="modal-close-btn" onclick="toggleSuccessModal(false)"><i class="fa-solid fa-xmark"></i></button></div>
      <div class="modal-body success-modal-body">
        <i class="fa-solid fa-circle-check success-icon"></i>
        <h4 id="successModalTitle">Message Saved!</h4>
        <p id="successModalBody">Your composed message has been added to the queue.</p>
      </div>
      <div class="modal-footer"><button class="btn primary" onclick="toggleSuccessModal(false)">OK</button></div>
    </div>
  </div>

  <!-- View Enrollment Application Modal -->
  <div class="modal-overlay" id="viewEnrollmentModal">
    <div class="modal-container" style="max-width: 700px; max-height: 85vh; overflow-y: auto;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-id-card"></i> Enrollment Application Details</h3>
        <button class="modal-close-btn" onclick="closeEnrollmentViewModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="enrollmentViewModalBody">
        <!-- Populated by JS -->
      </div>
      <div class="modal-footer">
        <button class="btn primary" onclick="closeEnrollmentViewModal()">Close</button>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script>
    function toggleViewModal(show) {
      document.getElementById('viewMessageModal').classList.toggle('active', show);
    }
    function toggleComposeModal(show) {
      document.getElementById('composeModal').classList.toggle('active', show);
      if (!show) {
        document.getElementById('composeForm').reset();
        document.getElementById('editingId').value = '';
        document.getElementById('composeModalTitle').innerHTML = '<i class="fa-solid fa-paper-plane"></i> New Message';
      }
    }
    function toggleSuccessModal(show, title, body) {
      if (title) document.getElementById('successModalTitle').textContent = title;
      if (body) document.getElementById('successModalBody').textContent = body;
      document.getElementById('successModal').classList.toggle('active', show);
    }
    document.getElementById('openComposeModal').addEventListener('click', () => toggleComposeModal(true));

    function viewMessage(btn, type) {
      const row = btn.closest('tr');
      if (type === 'inbox') {
        // Mark as read if currently unread
        if (row.dataset.status === 'unread') {
          const msgId = row.dataset.id;
          if (msgId) {
            fetch('received-mail.php?mark_read=' + msgId)
              .then(r => r.json())
              .then(data => {
                if (data.success) {
                  row.dataset.status = 'read';
                  const badge = row.querySelector('.badge');
                  if (badge) {
                    badge.classList.remove('warning');
                    badge.classList.add('success');
                    badge.textContent = 'Read';
                  }
                }
              })
              .catch(() => {});
          }
        }
        document.getElementById('viewLabelContact').textContent = 'Sender Name:';
        document.getElementById('viewEmailVal').textContent = row.dataset.email || row.cells[0].textContent;
        document.getElementById('viewContactVal').textContent = row.dataset.sender || row.cells[1].textContent;
        document.getElementById('viewSubjectVal').textContent = row.dataset.subject || row.cells[2].textContent;
        document.getElementById('viewDateVal').textContent = row.cells[3].textContent;
        document.getElementById('viewStatusVal').innerHTML = row.cells[4].innerHTML;
        document.getElementById('viewBodyVal').textContent = row.dataset.message || 'No message content available.';
      } else {
        document.getElementById('viewLabelContact').textContent = 'Receiver Name:';
        document.getElementById('viewEmailVal').textContent = row.cells[1].textContent;
        document.getElementById('viewContactVal').textContent = row.cells[2].textContent;
        document.getElementById('viewSubjectVal').textContent = row.dataset.subject || 'N/A';
        document.getElementById('viewDateVal').textContent = row.cells[3].textContent;
        document.getElementById('viewStatusVal').innerHTML = row.cells[4].innerHTML;
        document.getElementById('viewBodyVal').textContent = row.dataset.message || 'No message content available.';
      }
      toggleViewModal(true);
    }

    function editComposedMessage(id, email, receiver, subject, message) {
      document.getElementById('recipientEmail').value = email;
      document.getElementById('recipientName').value = receiver;
      document.getElementById('emailSubject').value = subject;
      document.getElementById('emailMessage').value = message;
      document.getElementById('editingId').value = id;
      document.getElementById('composeModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Message';
      toggleComposeModal(true);
    }

    function filterMail() {
      const val = document.getElementById('statusFilter').value.toLowerCase();
      document.querySelectorAll('#mailTable tbody tr').forEach(row => {
        row.style.display = (val === 'all' || row.getAttribute('data-status') === val) ? '' : 'none';
      });
    }


    // Enrollment Application View Modal
    function openEnrollmentViewModal(btn) {
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

      document.getElementById('enrollmentViewModalBody').innerHTML = html;
      document.getElementById('viewEnrollmentModal').classList.add('active');
    }

    function closeEnrollmentViewModal() {
      document.getElementById('viewEnrollmentModal').classList.remove('active');
    }

    document.getElementById('viewEnrollmentModal').addEventListener('click', function(e) {
      if (e.target === this) closeEnrollmentViewModal();
    });

    <?php if (isset($_GET['success'])): ?>toggleSuccessModal(true, 'Message Saved!', 'Your composed message has been added to the queue.');<?php endif; ?>
    <?php if (isset($_GET['sent'])): ?>toggleSuccessModal(true, 'Message Sent!', 'The message has been successfully sent to the recipient.');<?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>toggleSuccessModal(true, 'Message Cancelled', 'The message has been cancelled.');<?php endif; ?>
    <?php if (isset($_GET['enrollment_approved'])): ?>toggleSuccessModal(true, 'Enrollment Approved!', 'The student account has been created and credentials have been sent to their email.<?php echo isset($_GET["email_error"]) ? " (Warning: Email failed to send. Check SMTP settings.)" : ""; ?>');<?php endif; ?>
    <?php if (isset($_GET['enrollment_declined'])): ?>toggleSuccessModal(true, 'Enrollment Declined', 'The enrollment request has been declined.');<?php endif; ?>
    <?php if (isset($_GET['approval_error'])): ?>toggleSuccessModal(true, 'Approval Failed', 'Something went wrong while approving the enrollment. Please try again.');<?php endif; ?>
  </script>
</body>
</html>