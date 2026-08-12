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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Control Panel - Received Mail</title>
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
        <li><a href="enrollments.php">Enrollments</a></li>
        <li class="active"><a href="received-mail.php">Received Mail</a></li>
      </ul>
    </div>
    <div class="sidebar-footer">
      <a href="../../index.html" class="btn-homepage"><i class="fa-solid fa-arrow-left"></i> Back to Homepage</a>
      <a href="logout.php" class="btn-homepage" style="margin-top:10px; border-color:#ef4444; color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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
            <tr data-status="<?php echo $mail['status']; ?>" data-subject="<?php echo htmlspecialchars($mail['subject']); ?>" data-message="<?php echo htmlspecialchars($mail['message']); ?>" data-sender="<?php echo htmlspecialchars($mail['sender_name']); ?>" data-email="<?php echo htmlspecialchars($mail['email']); ?>" data-date="<?php echo $mail['date_received']; ?>">
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

    <?php if (isset($_GET['success'])): ?>toggleSuccessModal(true, 'Message Saved!', 'Your composed message has been added to the queue.');<?php endif; ?>
    <?php if (isset($_GET['sent'])): ?>toggleSuccessModal(true, 'Message Sent!', 'The message has been successfully sent to the recipient.');<?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>toggleSuccessModal(true, 'Message Cancelled', 'The message has been cancelled.');<?php endif; ?>
  </script>
</body>
</html>