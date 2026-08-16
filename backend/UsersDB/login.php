<?php
session_start();

// Database Connection
$host     = '127.0.0.1'; 
$db_name  = 'enrollmentdb';
$dbuser   = 'root';
$dbpass   = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Flag for the UI
$login_success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    try {
        // Check for Student Login in the 'users' table
        $stmt = $conn->prepare("SELECT first_name, last_name, password FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the user exists and the hashed password matches
        if ($student && password_verify($password, $student['password'])) {

            // Set session variables
            $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
            $_SESSION['username']     = $username;

            // Set flag to show Success UI
            $login_success = true;

        } else {
            // Redirect back to login with error flag instead of alert
            header("Location: login.html?error=1");
            exit();
        }
    } catch (PDOException $e) {
        echo "<script>alert('An error occurred during login.'); window.history.back();</script>";
        exit();
    }
}
?>

<?php if ($login_success): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Successful</title>
    <style>
        :root {
            --bg-deep-abyss: #020c1b;
            --bg-card: #0a192f;
            --primary-neon: #0df5e3;
            --primary-neon-hover: #00cbb9;
            --neon-glow: 0 0 15px rgba(13, 245, 227, 0.3);
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: var(--bg-deep-abyss);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background-color: var(--bg-card);
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            color: #e2e8f0;
        }

        .card h2 {
            margin-bottom: 15px;
            color: #0df5e3;
        }

        .card p {
            color: #8892b0;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .action-group {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-home {
            background-color: var(--primary-neon);
            color: #020c1b;
            border: none;
            padding: 12px;
            border-radius: 25px; 
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-home:hover {
            background-color: var(--primary-neon-hover);
            box-shadow: var(--neon-glow);
            transform: translateY(-2px);
        }

        .success-icon {
            font-size: 60px;
            color: #10b981;
            margin-bottom: 15px;
            text-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>

<div class="card">
    <div class="success-icon">&#10004;</div>
    <h2>Login Successful!</h2>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['student_name']); ?>. Your credentials have been verified.</p>

    <div class="action-group">
        <a href="profile.php" class="btn-home">Go to Dashboard</a>
    </div>
</div>

</body>
</html>
<?php endif; ?>