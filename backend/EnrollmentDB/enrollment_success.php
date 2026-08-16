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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-deep-abyss: #020c1b;
            --bg-card: #0a192f;
            --bg-card-hover: #112240;
            --primary-neon: #0df5e3;
            --primary-neon-hover: #00cbb9;
            --text-high-contrast: #e2e8f0;
            --text-muted-teal: #8892b0;
            --border-teal: #172a45;
            --success-green: #10b981;
            --danger-red: #ef4444;
            --border-radius: 12px;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --neon-glow: 0 0 15px rgba(13, 245, 227, 0.3);
            --success-glow: 0 0 25px rgba(16, 185, 129, 0.4);
            --danger-glow: 0 0 25px rgba(239, 68, 68, 0.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-high-contrast);
            background-color: var(--bg-deep-abyss);
            line-height: 1.6;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* --- Header / Navigation --- */
        header {
            background-color: rgba(2, 12, 27, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-teal);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo img {
            height: 40px;
        }

        .logo h2 {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-high-contrast);
        }

        .logo span {
            display: block;
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted-teal);
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover {
            color: var(--primary-neon);
            text-shadow: 0 0 40px var(--primary-neon);
        }

        /* --- Buttons --- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.6rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition-smooth);
            border: none;
            font-size: 0.95rem;
            height: 48px;
        }

        .btn-primary {
            background-color: var(--primary-neon);
            color: var(--bg-deep-abyss);
            box-shadow: var(--neon-glow);
        }

        .btn-primary:hover {
            background-color: var(--primary-neon-hover);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(13, 245, 227, 0.5);
        }

        .btn-secondary {
            background-color: var(--bg-card-hover);
            color: var(--primary-neon);
            border: 1px solid rgba(13, 245, 227, 0.3);
        }

        .btn-secondary:hover {
            background-color: var(--primary-neon);
            color: var(--bg-deep-abyss);
            box-shadow: var(--neon-glow);
        }

        /* --- Main Content --- */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 0;
            background: radial-gradient(circle at center, rgba(13, 245, 227, 0.08) 0%, rgba(10, 25, 47, 0.85) 60%, var(--bg-deep-abyss) 100%);
        }

        .status-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: var(--border-radius);
            padding: 3.5rem 2.5rem;
            max-width: 650px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
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

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success-green), var(--primary-neon));
        }

        .status-card.error::before {
            background: linear-gradient(90deg, var(--danger-red), #ff6b6b);
        }

        .icon-container {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.8rem;
        }

        .success .icon-container {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success-green);
            color: var(--success-green);
            box-shadow: var(--success-glow);
            animation: pulse 2s infinite ease-in-out;
        }

        .error .icon-container {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--danger-red);
            color: var(--danger-red);
            box-shadow: var(--danger-glow);
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(16, 185, 129, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .error .icon-container {
            animation: pulseDanger 2s infinite ease-in-out;
        }

        @keyframes pulseDanger {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .status-card h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-high-contrast);
            margin-bottom: 0.8rem;
        }

        .status-card .subtitle {
            color: var(--text-muted-teal);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        /* Info Box */
        .info-box {
            background-color: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2.5rem;
            text-align: left;
        }

        .info-box h3 {
            font-size: 0.95rem;
            color: var(--primary-neon);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-box ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .info-box li {
            font-size: 0.9rem;
            color: var(--text-muted-teal);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .info-box li i {
            color: var(--primary-neon);
            margin-top: 4px;
            font-size: 0.8rem;
        }

        /* Student Info */
        .student-info {
            background: rgba(2, 12, 27, 0.6);
            border: 1px solid var(--border-teal);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .student-info p {
            font-size: 0.95rem;
            color: var(--text-muted-teal);
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }

        .student-info strong {
            color: var(--primary-neon);
            font-weight: 600;
        }

        .student-info .info-divider {
            height: 1px;
            background: var(--border-teal);
            margin: 12px 0;
        }

        /* Error Box */
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-left: 4px solid var(--danger-red);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #ff6b6b;
            word-break: break-word;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 12px;
            color: var(--text-muted-teal);
        }

        /* --- Footer Styles --- */
        footer {
            background-color: #01060f;
            border-top: 1px solid var(--border-teal);
            color: var(--text-muted-teal);
            padding: 3rem 0 2rem;
            font-size: 0.9rem;
        }

        footer .footer-info {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 5%;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .footer-info h4 {
            width: 100%;
            border-bottom: 1px solid var(--primary-neon);
            margin-bottom: 20px;
            padding-bottom: 10px;
            color: var(--text-high-contrast);
            font-size: 1.1rem;
        }

        .contact p, .office-hours p {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .office-hours p {
            justify-content: space-between;
            gap: 40px;
        }

        .school-name {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .school-name img.icflogo {
            height: 80px;
        }

        .school-name h1 {
            text-align: left;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-high-contrast);
            line-height: 1.2;
        }

        .school-name span {
            display: inline-block;
            font-size: 0.9rem;
            font-weight: 400;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        footer .social-icon {
            display: flex;
            align-items: center;
            border-left: 1px solid var(--border-teal);
            margin-left: 20px;
            padding-left: 20px;
            gap: 12px;
        }

        footer .icon img {
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid var(--primary-neon);
            height: 42px;
            width: 42px;
            transition: var(--transition-smooth);
        }

        footer .icon img:hover {
            transform: scale(1.1);
            box-shadow: var(--neon-glow);
        }

        footer .footer-container {
            margin-top: 30px;
            text-align: center;
        }

        footer .footer-container p {
            padding-top: 20px;
            border-top: 1px solid var(--border-teal);
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .footer-info {
                flex-direction: column;
            }
            .school-name {
                flex-direction: column;
                text-align: center;
            }
            .school-name h1 {
                text-align: center;
            }
            footer .social-icon {
                border-left: none;
                margin-left: 0;
                padding-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header>
        <div class="container nav-container">
            <a href="../../index.html" class="logo">
                <img src="../../BackGroundimage/ICFLogo.png" alt="ICF Logo">
                <h2>ICF <span>Interworld Colleges Foundation Inc.</span></h2>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="../../index.html"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="../department.html"><i class="fa-solid fa-building-columns"></i> Department</a></li>
                    <li><a href="../about.html"><i class="fa-solid fa-circle-info"></i> About</a></li>
                    <li><a href="../contact.html"><i class="fa-solid fa-envelope"></i> Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container" style="display: flex; justify-content: center;">
            <div class="status-card <?php echo $status; ?>">

                <?php if ($status === 'success'): ?>
                    <div class="icon-container">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h1>Enrollment Successful!</h1>
                    <p class="subtitle">Your registration has been submitted and saved to the database.</p>

                    <div class="student-info">
                        <p><strong>Student Name:</strong> <span><?php echo $name; ?></span></p>
                        <?php if ($course): ?>
                        <p><strong>Course/Program:</strong> <span><?php echo $course; ?></span></p>
                        <?php endif; ?>
                        <div class="info-divider"></div>
                        <p><strong>Status:</strong> <span>Pending Review</span></p>
                        <p><strong>Date Submitted:</strong> <span><?php echo date('F j, Y g:i A'); ?></span></p>
                    </div>

                    <div class="info-box">
                        <h3><i class="fa-solid fa-circle-info"></i> What happens next?</h3>
                        <ul>
                            <li>
                                <i class="fa-solid fa-chevron-right"></i>
                                <span>Our Registrar's Office will evaluate your registration details and requirements.</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-chevron-right"></i>
                                <span>You will receive an official confirmation via SMS or Email regarding your admission status.</span>
                            </li>
                        </ul>
                    </div>

                <?php else: ?>
                    <div class="icon-container">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <h1>Enrollment Failed</h1>
                    <p class="subtitle">Something went wrong while processing your registration.</p>

                    <?php if ($message): ?>
                    <div class="error-box">
                        <strong>Error:</strong> <?php echo $message; ?>
                    </div>
                    <?php endif; ?>

                    <div class="info-box">
                        <h3><i class="fa-solid fa-triangle-exclamation"></i> Troubleshooting</h3>
                        <ul>
                            <li>
                                <i class="fa-solid fa-chevron-right"></i>
                                <span>Please check your information and try again.</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-chevron-right"></i>
                                <span>If the problem persists, contact the school administrator.</span>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <a href="../../Extension/enrollmentpage.html" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to Enrollment
                    </a>
                    <a href="../../index.html" class="btn btn-primary">
                        <i class="fa-solid fa-house"></i> Go to Home
                    </a>
                </div>

                <p class="footer-note">&copy; 2026 ICF Interworld Colleges Foundation Inc.</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-info">
            <div class="contact">
                <h4>CONTACT DETAILS</h4>
                <p><i class="fa-solid fa-location-dot"></i> Burgos Street, Paniqui, Tarlac, Philippines</p>
                <p><i class="fa-solid fa-phone"></i> +63 930 536 3258</p>
                <p><i class="fa-solid fa-envelope"></i> admin@icfpaniqui.edu.ph</p>
            </div>
            <div class="office-hours">
                <h4>OFFICE HOURS</h4>
                <p>Sunday <span>Closed</span></p>
                <p>Monday <span>8:00am - 4:00pm</span></p>
                <p>Tuesday <span>8:00am - 4:00pm</span></p>
                <p>Wednesday <span>8:00am - 4:00pm</span></p>
                <p>Thursday <span>8:00am - 4:00pm</span></p>
                <p>Friday <span>8:00am - 4:00pm</span></p>
                <p>Saturday <span>8:00am - 12:00pm</span></p>
            </div>
        </div>

        <div class="school-name">
            <img class="icflogo" src="../../BackGroundimage/ICFLogo.png" alt="ICF Logo">
            <h1>ICF <span>INTERWORLD</span> <span>COLLEGES</span> <span>INC.</span></h1>

            <div class="social-icon">
                <div class="icon">
                    <a href="https://web.facebook.com/ICFPaniquiOfficial"><img src="../../BackGroundimage/Icon/FacebookLogo.png" alt="Facebook"></a>
                </div>
                <div class="icon">
                    <a href="#"><img src="../../BackGroundimage/Icon/YoutubeLogo.jpg" alt="YouTube"></a>
                </div>
                <div class="icon">
                    <a href="#"><img src="../../BackGroundimage/Icon/TwitterLogo.jpg" alt="Twitter"></a>
                </div>
                <div class="icon">
                    <a href="#"><img src="../../BackGroundimage/Icon/tiktokLogo.jpg" alt="TikTok"></a>
                </div>
                <div class="icon">
                    <a href="#"><img src="../../BackGroundimage/Icon/Pinterestlogo.jpg" alt="Pinterest"></a>
                </div>
                <div class="icon">
                    <a href="#"><img src="../../BackGroundimage/Icon/InstagramLogo.png" alt="Instagram"></a>
                </div>
            </div>
        </div>

        <div class="container footer-container">
            <p>&copy; 2026 ICF Interworld Colleges Foundation Inc. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>