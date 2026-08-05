<?php
$status = $_GET['status'] ?? 'success';
$name = isset($_GET['name']) ? htmlspecialchars(urldecode($_GET['name'])) : 'Student';
$course = isset($_GET['course']) ? htmlspecialchars(urldecode($_GET['course'])) : '';
$message = isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICF Enrollment Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #071427 0%, #003249 50%, #001f27 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .status-card {
            background: #001d2b;
            border: 2px solid cyan;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 247, 255, 0.3);
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .icon-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 45px;
        }
        .success .icon-circle {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .error .icon-circle {
            background: #ffebee;
            color: #c62828;
        }
        .status-card h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1a237e;
        }
        .status-card .subtitle {
            font-size: 16px;
            color: #555;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .status-card .student-info {
            background: #0b1421;
            border-radius: 12px;
            padding: 18px;
            margin: 22px 0;
            text-align: left;
        }
        .student-info p {
            font-size: 14px;
            color: #444;
            margin: 6px 0;
        }
        .student-info strong {
            color: #1a7e6f;
        }
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 25px 0;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-block;
        }
        .btn-primary {
            background: #081220;
            color: #00eaff;
        }
        .btn-primary:hover {
            background: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3);
        }
        .btn-secondary {
            background: #0a1422;
            color: #61d2ff;
            border: 2px solid #00bbff;
        }
        .btn-secondary:hover {
            background: #1a237e;
            color: #fff;
            transform: translateY(-2px);
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #c62828;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
            font-family: monospace;
            font-size: 13px;
            color: #c62828;
            word-break: break-word;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="status-card <?php echo $status; ?>">

        <?php if ($status === 'success'): ?>
            <div class="icon-circle">&#10003;</div>
            <h1>Enrollment Successful!</h1>
            <p class="subtitle">Your registration has been submitted and saved to the database.</p>

            <div class="student-info">
                <p><strong>Student Name:</strong> <?php echo $name; ?></p>
                <?php if ($course): ?>
                <p><strong>Course/Program:</strong> <?php echo $course; ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong> Pending Review</p>
                <p><strong>Date Submitted:</strong> <?php echo date('F j, Y g:i A'); ?></p>
            </div>

            <p class="subtitle">Please wait for the admin to review your application. You will be notified via email or SMS once your enrollment is confirmed.</p>

        <?php else: ?>
            <div class="icon-circle">&#10007;</div>
            <h1>Enrollment Failed</h1>
            <p class="subtitle">Something went wrong while processing your registration.</p>

            <?php if ($message): ?>
            <div class="error-box">
                <strong>Error:</strong> <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <p class="subtitle">Please check your information and try again. If the problem persists, contact the school administrator.</p>
        <?php endif; ?>

        <div class="divider"></div>

        <div class="btn-group">
            <a href="../../Extension/enrollmentpage.html" class="btn btn-primary">
                &#8592; Back to Enrollment Page
            </a>
            <a href="../../index.html" class="btn btn-secondary">
                Go to Home
            </a>
        </div>

        <p class="footer-note">&copy; 2026 ICF Interworld Colleges Foundation Inc.</p>
    </div>
</body>
</html>