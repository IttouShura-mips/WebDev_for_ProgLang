<?php
session_start();

$host     = 'localhost';
$db_name  = 'enrollmentdb'; 
$username = 'root'; 
$password = '';    

$registration_success = false;
$error_message = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    $first_name     = trim($_POST['first_name'] ?? '');
    $middle_name    = trim($_POST['middle_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $username       = trim($_POST['username'] ?? '');
    $plain_password = trim($_POST['password'] ?? '');

    if (empty($first_name) || empty($middle_name) || empty($last_name) || empty($username) || empty($plain_password)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (first_name, middle_name, last_name, username, password) 
                    VALUES (:first_name, :middle_name, :last_name, :username, :password)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name'  => $first_name,
                ':middle_name' => $middle_name,
                ':last_name'   => $last_name,
                ':username'    => $username,
                ':password'    => $hashed_password
            ]);

            // Set flag to show Success UI
            $registration_success = true;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Username is already registered. Please choose another.";
            } else {
                $error_message = "Something went wrong: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
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
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #7ae2ff;
        }
        .login-card { margin-bottom: 15px; }
        .login-card input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .action-group {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .login-button, .btn-home {
            background-color: var(--primary-neon);
            color: #020c1b;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .login-button:hover, .btn-home:hover {
            background-color: var(--primary-neon-hover);
            box-shadow: var(--neon-glow);
        }
        .back-login {
            color: #0df5e3;
            text-decoration: none;
            font-size: 14px;
        }
        .back-login:hover { text-decoration: underline; }
        .error-box {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .success-icon {
            font-size: 50px;
            color: #28a745;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="card">
    <?php if ($registration_success): ?>

        <!-- SUCCESS UI SCREEN -->
        <div class="success-icon">&#10004;</div>
        <h2>Signup Successful!</h2>
        <p>Your account has been created successfully. You can now log in.</p>

        <div class="action-group">
            <!-- FIXED: Redirects to login.html in same directory -->
            <a href="../../student/login.html" class="btn-home">Go to Login</a>
        </div>

    <?php else: ?>

        <!-- FORM SCREEN -->
        <h2>Create a Student Account</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-box"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off"> 
            <div class="login-card">
                <input type="text" id="first_name" name="first_name" autocomplete="off" required placeholder="First Name">
            </div>

            <div class="login-card">
                <input type="text" id="middle_name" name="middle_name" autocomplete="off" required placeholder="Middle Name">
            </div>

            <div class="login-card">
                <input type="text" id="last_name" name="last_name" autocomplete="off" required placeholder="Last Name">
            </div>

            <div class="login-card">
                <input type="text" id="username" name="username" autocomplete="off" required placeholder="Username / Student ID">
            </div>

            <div class="login-card">
                <input type="password" id="password" name="password" autocomplete="off" required placeholder="Password">
            </div>

            <div class="action-group">
                <button type="submit" name="submit" class="login-button">Sign Up</button>
                <!-- FIXED: Same-directory path -->
                <a href="../../student/login.html" class="back-login">Back to Login</a>
            </div>
        </form>

    <?php endif; ?>
</div>

</body>
</html>