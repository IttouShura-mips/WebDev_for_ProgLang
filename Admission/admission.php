<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admissions | College of Information and Computer Studies</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --icon-rotate: #fafafa;
            --bg-box-shadow: rgba(0, 0, 0, 0.4);
            --bg-deep-abyss: #142141;
            --bg-card: #141e36c5;
            --bg-card-hover: #1e293b;
            --primary-neon: #fee500;
            --primary-neon-hover: #00c6ff;
            --accent-green: #10b981;
            --text-high-contrast: #f8fafc;
            --text-muted-teal: #94a3b8;
            --border-teal: #1e293b;
            --border-glow: rgba(0, 242, 254, 0.25);
            --border-radius: 12px;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --neon-glow: 0 0 20px rgba(0, 242, 254, 0.35);
        }

        body.light-mode {
            --icon-rotate: #000000;
            --bg-box-shadow: rgba(0, 0, 0, 0.08);
            --bg-deep-abyss: #c2d4e6;
            --bg-card: #ffffff;
            --bg-card-hover: #e2e8f0;
            --primary-neon: #0284c7;
            --primary-neon-hover: #0369a1;
            --accent-green: #059669;
            --text-high-contrast: #0f172a;
            --text-muted-teal: #475569;
            --border-teal: #cbd5e1;
            --border-glow: rgba(2, 132, 199, 0.2);
        }

        body {
            background-color: var(--bg-deep-abyss);
            font-family: 'Inter', sans-serif;
            color: var(--text-high-contrast);
            line-height: 1.6;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .mono-text {
            font-family: 'JetBrains Mono', monospace;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Scroll Reveal Animation Styles */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Header & Nav */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            justify-content: center;
            width: 100%;
            height: 65px;
            transition: var(--transition-smooth);
            padding: 5px 20px;
            background: var(--bg-deep-abyss);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 80;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-container {
            display: flex;
            width: 100%;
            justify-content: space-between;
            align-items: center;
        }

        .header.is-scrolled {
            z-index: 90;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-glow);
            box-shadow: 0 4px 20px var(--bg-box-shadow);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo img {
            height: 50px;
            transition: var(--transition-smooth);
        }

        .logo h2 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--text-high-contrast);
            font-family: 'JetBrains Mono', monospace;
        }

        .logo span {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary-neon);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Inter', sans-serif;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.8rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-high-contrast);
            font-weight: 600;
            font-size: 0.88rem;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            position: relative;
            padding-bottom: 4px;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-neon);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            transform: translateX(-50%);
            box-shadow: 0 0 8px var(--primary-neon);
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary-neon);
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 5px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 4px;
            list-style: none;
            min-width: 220px;
            padding: 10px 0;
            box-shadow: 0 10px 25px var(--bg-box-shadow);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: var(--transition-smooth);
            z-index: 100;
        }

        .nav-links li:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-links .dropdown-menu li {
            width: 100%;
        }

        .nav-links .dropdown-menu a {
            display: block;
            padding: 8px 16px;
            color: var(--text-high-contrast);
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .nav-links .dropdown-menu a::after {
            display: none;
        }

        .nav-links .dropdown-menu a:hover {
            color: var(--primary-neon);
            background: rgba(0, 242, 254, 0.08);
            padding-left: 20px;
        }

        /* Hero Banner Section */
        .about-hero {
            position: relative;
            padding-top: 140px;
            padding-bottom: 60px;
            background: linear-gradient(180deg, rgba(55, 69, 104, 0.38) 0%, var(--bg-deep-abyss) 100%),
                url('/CICSweb/background/cover/cics_cover_photo.png') top/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-hero-content {
            text-align: center;
            z-index: 2;
        }

        .cics-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 242, 254, 0.1);
            border: 1px solid var(--primary-neon);
            color: var(--primary-neon);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .about-hero h1 {
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(2.5rem, 5vw, 4rem);
            color: var(--text-high-contrast);
            font-weight: 800;
            line-height: 1.1;
        }

        .about-hero h1 span {
            color: var(--primary-neon);
            background: linear-gradient(90deg, var(--primary-neon), var(--primary-neon-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* About Main Container */
        .about-container {
            padding: 60px 20px 100px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .intro-box {
            background: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: var(--border-radius);
            padding: 40px;
            text-align: center;
            max-width: 850px;
            margin: 0 auto 50px auto;
            box-shadow: 0 10px 30px var(--bg-box-shadow);
        }

        .intro-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(0, 242, 254, 0.1);
            border: 1px solid var(--border-glow);
            color: var(--primary-neon);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 20px auto;
        }

        .intro-box p {
            font-size: 1.05rem;
            color: var(--text-muted-teal);
            line-height: 1.8;
        }

        .intro-box strong {
            color: var(--text-high-contrast);
        }

        /* Cards Grid Layout */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: var(--border-radius);
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-neon);
            box-shadow: var(--neon-glow);
        }

        .card-icon {
            font-size: 2rem;
            color: var(--primary-neon);
            margin-bottom: 16px;
        }

        .card-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-high-contrast);
            margin-bottom: 24px;
        }

        .card-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 0;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary-neon);
            background: rgba(0, 242, 254, 0.05);
            border: 1px solid var(--border-glow);
            border-radius: 30px;
            transition: var(--transition-smooth);
        }

        .card-btn:hover {
            background-color: var(--primary-neon);
            color: #000;
            border-color: var(--primary-neon);
        }

        /* Menu Sidebar Toggle Button */
        .menu-toggle-btn {
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 15px;
            right: 40px;
            z-index: 200;
            background: transparent;
            border: none;
            outline: none;
            border-radius: 8px;
            padding: 8px 12px;
            color: var(--text-high-contrast);
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 1px;
            cursor: pointer;
            align-items: center;
            gap: 6px;
            transition: var(--transition-smooth);
        }

        .menu-toggle-btn:hover {
            border-color: var(--primary-neon);
        }

        .menu-icon {
            width: 20px;
            height: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .menu-icon span {
            display: block;
            width: 100%;
            height: 2px;
            background-color: var(--primary-neon);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .menu-toggle-btn.is-active .menu-icon span:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }

        .menu-toggle-btn.is-active .menu-icon span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle-btn.is-active .menu-icon span:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        /* Hidden Menu Sidebar */
        .menu-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 340px;
            max-width: 100%;
            height: 100vh;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border-left: 1px solid var(--border-glow);
            display: flex;
            flex-direction: column;
            padding: 24px;
            z-index: 100;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow-y: auto;
            box-shadow: -10px 0 30px var(--bg-box-shadow);
        }

        .menu-sidebar.is-open {
            transform: translateX(0);
        }

        .brand-section {
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-teal);
            margin-top: 35px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon img {
            width: 38px;
            height: 38px;
        }

        .logo-text {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary-neon);
            font-family: 'JetBrains Mono', monospace;
        }

        .logo-text span {
            color: var(--text-muted-teal);
            display: block;
            font-size: 10px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }

        .sidebar-toolbar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-input {
            width: 100%;
            height: 40px;
            padding: 0 12px 0 36px;
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 6px;
            color: var(--text-high-contrast);
            font-size: 12px;
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted-teal);
            font-size: 13px;
        }

        .theme-btn {
            width: 40px;
            height: 40px;
            background: var(--bg-deep-abyss);
            border: 1px solid var(--border-teal);
            border-radius: 6px;
            color: var(--primary-neon);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: var(--transition-smooth);
        }

        .theme-btn:hover {
            border-color: var(--primary-neon);
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 20px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-high-contrast);
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.25s ease;
            gap: 10px;
        }

        .nav-link i.nav-icon {
            color: var(--primary-neon);
            font-size: 14px;
            width: 18px;
        }

        .nav-link:hover {
            color: var(--primary-neon);
            background: rgba(0, 242, 254, 0.08);
        }

        .nav-item.open>.nav-link {
            background: var(--primary-neon);
            color: #000;
        }

        .nav-item.open>.nav-link i.nav-icon {
            color: #000;
        }

        .submenu-arrow {
            transition: transform 0.3s ease;
            margin-left: auto;
            font-size: 10px;
        }

        .nav-item.open>.nav-link .submenu-arrow {
            transform: rotate(180deg);
        }

        .submenu {
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
            background: rgba(0, 0, 0, 0.15);
            border-left: 2px solid var(--primary-neon);
            margin: 4px 0 4px 18px;
            border-radius: 0 6px 6px 0;
        }

        .nav-item.open>.submenu {
            max-height: 500px;
        }

        .submenu-link {
            display: block;
            padding: 8px 16px;
            color: var(--text-muted-teal);
            text-decoration: none;
            font-size: 12px;
            transition: color 0.2s ease;
        }

        .submenu-link:hover {
            color: var(--primary-neon);
        }

        /* Login Options Box & Footer */
        .login-options-box {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: var(--bg-deep-abyss);
            border-radius: 8px;
            transition: all 0.35s ease;
        }

        .login-options-box.is-open {
            max-height: 200px;
            opacity: 1;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border-teal);
        }

        .login-option-btn {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border-teal);
            border-radius: 6px;
            color: var(--text-high-contrast);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .login-option-btn:hover {
            border-color: var(--primary-neon);
            color: var(--primary-neon);
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-teal);
            padding-top: 15px;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-neon);
            color: #000;
            border: none;
            border-radius: 6px;
            font-weight: 800;
            font-size: 12px;
            letter-spacing: 1px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Footer */
        footer {
            background-color: var(--bg-card);
            border-top: 1px solid var(--border-teal);
            color: var(--text-muted-teal);
            padding: 6rem 20px 3rem 20px;
            font-size: 0.88rem;
        }

        .first-row-footer {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto 40px auto;
            flex-wrap: wrap;
        }

        .footer-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 280px;
        }

        .footer-info h4 {
            border-bottom: 2px solid var(--primary-neon);
            padding-bottom: 8px;
            color: var(--text-high-contrast);
            font-family: 'JetBrains Mono', monospace;
            display: inline-block;
        }

        .contact ul {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 10px;
            list-style: none;
            padding: 0;
        }

        .contact svg {
            fill: var(--primary-neon);
            flex-shrink: 0;
        }

        .contact p {
            margin: 0;
        }

        .office-hours p {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 4px;
        }

        .footer-hero {
            display: flex;
            flex-direction: column;
            gap: 30px;
            flex: 1;
        }

        .link-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .link-source h3 {
            color: var(--primary-neon);
            margin-bottom: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
        }

        .link-source a {
            text-decoration: none;
        }

        .link-source ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 0;
        }

        .link-source ul a {
            color: var(--text-muted-teal);
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .link-source ul a:hover {
            color: var(--primary-neon);
        }

        .school-name {
            max-width: 1000px;
            margin: 40px auto;
            border-top: 1px solid var(--border-teal);
            border-bottom: 1px solid var(--border-teal);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            gap: 20px;
            text-align: left;
        }

        .school-name img {
            height: 60px;
        }

        .school-name h1 {
            font-size: 1.2rem;
            color: var(--text-high-contrast);
            font-family: 'JetBrains Mono', monospace;
            margin: 0;
        }

        .school-name span {
            display: block;
            font-size: 0.75rem;
            color: var(--primary-neon);
            font-family: 'Inter', sans-serif;
        }

        footer .footer-container p {
            text-align: center;
            padding-top: 20px;
            font-size: 0.8rem;
        }

        /* Responsive Breakpoints */
        @media only screen and (max-width: 1024px) {
            .nav-links {
                display: none;
            }

            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media only screen and (max-width: 768px) {
            .header {
                height: 70px;
            }

            .logo img {
                height: 40px;
            }

            .logo h2 {
                font-size: 1rem;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .school-name {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- Header Navigation -->
    <header class="header">
        <div class="container nav-container">
            <div class="logo">
                <img src="/CICSweb/background/logo/CICSlogo.png" alt="CICS Logo">
                <div>
                    <h2>CICS</h2>
                    <span>College of Information & Computer Studies</span>
                </div>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="/CICSweb/index.php">Home</a></li>
                    <li class="dropdown">
                        <a href="/CICSweb/extension/AcademicsProgram/academic-program.php">Academics <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="/CICSweb/extension/AcademicsProgram/academic-program.php#bscs">BS Computer Science</a></li>
                            <li><a href="/CICSweb/extension/AcademicsProgram/academic-program.php#act">Associate in Computer Science</a></li>
                        </ul>
                    </li>

                    <li><a href="/CICSweb/extension/Contact/contact-us.php">Contact Us</a></li>

                    <!-- About Dropdown -->
                    <li class="dropdown">
                        <a href="/CICSweb/extension/about-info-list.php">About <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="/CICSweb/extension/About/about-info-history.php">History</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-vision&mission.php">Vision & Mission</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-goals.php">Goals</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-corevalues.php">Core Values</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-organizational-chart.php">Organizational Chart</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-recognitions.php">Recognitions & Accreditations</a></li>
                        </ul>
                    </li>

                    <!-- Admission Dropdown -->
                    <li class="dropdown">
                        <a href="/CICSweb/extension/Admission/admission.php" class="active">Admission <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="/CICSweb/extension/Admission/admission-procedures.php">Admission Procedures</a></li>
                            <li><a href="/CICSweb/extension/Admission/entrance-requirements.php">Entrance Requirements</a></li>
                            <li><a href="/CICSweb/extension/Admission/enrollment-schedule.php">Enrollment Schedule</a></li>
                            <li><a href="/CICSweb/extension/Admission/tuition-information.php">Tuition Information</a></li>
                            <li><a href="/CICSweb/extension/Admission/scholarship-opportunities.php">Scholarship Opportunities</a></li>
                            <li><a href="/CICSweb/extension/Admission/faqs.php">Frequently Asked Questions</a></li>
                        </ul>
                    </li>

                    <!-- Gallery Dropdown -->
                    <li class="dropdown">
                        <a href="/CICSweb/extension/Gallery/gallery.php">Gallery <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="/CICSweb/extension/Gallery/gallery.php?category=Photos">Photos</a></li>
                                <li><a href="/CICSweb/extension/Gallery/gallery.php?category=Videos">Videos</a></li>
                                <li><a href="/CICSweb/extension/Gallery/gallery.php?category=Activities">Activities</a></li>
                                <li><a href="/CICSweb/extension/Gallery/gallery.php?category=Competitions">Competitions</a></li>
                                <li><a href="/CICSweb/extension/Gallery/gallery.php?category=Graduation">Graduation</a></li>
                                <li><a href="/CICSweb/extension/Gallery/gallery.php?category=Seminars">Seminars</a></li>
                            </ul>
                    </li>

                    <!-- Downloads Dropdown -->
                    <li class="dropdown">
                        <a href="/CICSweb/extension/Downloads/download-center.php">Downloads <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="/CICSweb/extension/Downloads/forms.php">Forms</a></li>
                            <li><a href="/CICSweb/extension/Downloads/manuals.php">Manuals</a></li>
                            <li><a href="/CICSweb/extension/Downloads/templates.php">Templates</a></li>
                            <li><a href="/CICSweb/extension/Downloads/policies.php">Policies</a></li>
                            <li><a href="/CICSweb/extension/Downloads/curriculum.php">Curriculum</a></li>
                            <li><a href="/CICSweb/extension/Downloads/prospectus.php">Prospectus</a></li>
                            <li><a href="/CICSweb/extension/Downloads/research-documents.php">Research Documents</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Menu Sidebar Toggle Button -->
    <button class="menu-toggle-btn" id="menuToggle">
        <div class="menu-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <span id="btnText" class="mono-text">MENU</span>
    </button>

    <!-- Hidden Menu Sidebar -->
    <aside class="menu-sidebar" id="sidebar">
        <div>
            <div class="brand-section">
                <div class="logo-container">
                    <div class="logo-icon"><img src="/CICSweb/background/logo/CICSlogo.png" alt="Logo"></div>
                    <div class="logo-text">
                        CICS
                        <span>College of Information & Computer Studies</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="sidebar-toolbar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="search-input" id="sidebarSearchInput" placeholder="Search system..." />
                </div>
                <button class="theme-btn" id="themeToggle" title="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="/CICSweb/index.php" class="nav-link">
                            <i class="fa-solid fa-house nav-icon"></i>
                            <span>HOME</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/CICSweb/extension/AcademicsProgram/academic-program.php" class="nav-link">
                            <i class="fa-solid fa-laptop-code nav-icon"></i>
                            <span>ACADEMIC PROGRAMS</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/CICSweb/extension/Contact/contact-us.php" class="nav-link">
                            <i class="fa-solid fa-headset nav-icon"></i>
                            <span>CONTACT US</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/CICSweb/index.php#quick-links" class="nav-link">
                            <i class="fa-solid fa-bolt nav-icon"></i>
                            <span>QUICK LINKS</span>
                        </a>
                    </li>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link">
                            <i class="fa-solid fa-circle-info nav-icon"></i>
                            <span>ABOUT CICS</span>
                            <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="/CICSweb/extension/About/about-info-history.php" class="submenu-link">History</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-vision&mission.php" class="submenu-link">Vision & Mission</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-goals.php" class="submenu-link">Goals</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-corevalues.php" class="submenu-link">Core Values</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-organizational-chart.php" class="submenu-link">Organizational Chart</a></li>
                            <li><a href="/CICSweb/extension/About/about-info-recognitions.php" class="submenu-link">Recognitions & Accreditation</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-submenu">
                        <a href="/CICSweb/extension/Admission/admission.php" class="nav-link">
                            <i class="fa-solid fa-user-plus nav-icon"></i>
                            <span>ADMISSIONS</span>
                            <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="/CICSweb/extension/Admission/entrance-requirements.php" class="submenu-link">Requirements</a></li>
                            <li><a href="/CICSweb/extension/Admission/enrollment-schedule.php" class="submenu-link">Enrollment Schedule</a></li>
                            <li><a href="/CICSweb/extension/Admission/scholarship-opportunities.php" class="submenu-link">Scholarships</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-submenu">
                        <a href="/CICSweb/extension/Gallery/gallery.php" class="nav-link">
                            <i class="fa-solid fa-images nav-icon"></i>
                            <span>GALLERY</span>
                            <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="/CICSweb/extension/Gallery/photos.php" class="submenu-link">PHOTOS</a></li>
                            <li><a href="/CICSweb/extension/Gallery/videos.php" class="submenu-link">VIDEOS</a></li>
                            <li><a href="/CICSweb/extension/Gallery/activities.php" class="submenu-link">ACTIVITIES</a></li>
                            <li><a href="/CICSweb/extension/Gallery/competitions.php" class="submenu-link">COMPETITIONS</a></li>
                            <li><a href="/CICSweb/extension/Gallery/graduation.php" class="submenu-link">GRADUATION</a></li>
                            <li><a href="/CICSweb/extension/Gallery/seminars.php" class="submenu-link">SEMINARS</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-submenu">
                        <a href="/CICSweb/extension/Downloads/download-center.php" class="nav-link">
                            <i class="fa-solid fa-folder-open nav-icon"></i>
                            <span>DOWNLOAD CENTER</span>
                            <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="/CICSweb/extension/Downloads/forms.php" class="submenu-link">FORMS</a></li>
                            <li><a href="/CICSweb/extension/Downloads/manuals.php" class="submenu-link">MANUALS</a></li>
                            <li><a href="/CICSweb/extension/Downloads/templates.php" class="submenu-link">TEMPLATES</a></li>
                            <li><a href="/CICSweb/extension/Downloads/policies.php" class="submenu-link">POLICIES</a></li>
                            <li><a href="/CICSweb/extension/Downloads/curriculum.php" class="submenu-link">CURRICULUM</a></li>
                            <li><a href="/CICSweb/extension/Downloads/prospectus.php" class="submenu-link">PROSPECTUS</a></li>
                            <li><a href="/CICSweb/extension/Downloads/research-documents.php" class="submenu-link">RESEARCH DOCUMENTS</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="sidebar-footer">
            <button class="login-btn" id="loginBtn">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span id="loginBtnLabel">LOG IN PORTAL</span>
            </button>
        </div>
        <div class="login-options-box" id="loginOptionsBox">
            <button class="login-option-btn" data-auth="Student"><span>STUDENT PORTAL</span> <i class="fa-solid fa-arrow-right"></i></button>
            <button class="login-option-btn" data-auth="Faculty"><span>FACULTY PORTAL</span> <i class="fa-solid fa-arrow-right"></i></button>
            <a href="/CICSweb/admin/auth-system/index.php" style="text-decoration:none;"><button class="login-option-btn"><span>ADMIN SYSTEM</span> <i class="fa-solid fa-lock"></i></button></a>
        </div>
    </aside>

    <!-- Banner / Hero Section -->
    <header class="about-hero">
        <div class="about-hero-content reveal">
            <div class="cics-badge">
                <i class="fa-solid fa-user-plus"></i> Admissions Center
            </div>
            <h1>CICS <span>ADMISSION INFORMATION</span></h1>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="about-container">

        <!-- Intro Header Card -->
        <div class="intro-box reveal">
            <div class="intro-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <p>
                <strong>Welcome prospective students!</strong> Explore step-by-step application instructions, enrollment schedules, entrance requirements, tuition rates, and financial assistance options available at the College of Information and Computer Studies.
            </p>
        </div>

        <!-- Directory Grid -->
        <div class="cards-grid">

            <!-- Admission Procedures Card -->
            <div class="card reveal">
                <i class="fa-solid fa-clipboard-list card-icon"></i>
                <h3 class="card-title">Admission Procedures</h3>
                <a href="/CICSweb/extension/Admission/admission-procedures.php" class="card-btn">
                    Read More <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Entrance Requirements Card -->
            <div class="card reveal">
                <i class="fa-solid fa-file-signature card-icon"></i>
                <h3 class="card-title">Entrance Requirements</h3>
                <a href="/CICSweb/extension/Admission/entrance-requirements.php" class="card-btn">
                    Read More <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Enrollment Schedule Card -->
            <div class="card reveal">
                <i class="fa-solid fa-calendar-days card-icon"></i>
                <h3 class="card-title">Enrollment Schedule</h3>
                <a href="/CICSweb/extension/Admission/enrollment-schedule.php" class="card-btn">
                    Read More <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Tuition Information Card -->
            <div class="card reveal">
                <i class="fa-solid fa-receipt card-icon"></i>
                <h3 class="card-title">Tuition Information</h3>
                <a href="/CICSweb/extension/Admission/tuition-information.php" class="card-btn">
                    Read More <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Scholarship Opportunities Card -->
            <div class="card reveal">
                <i class="fa-solid fa-award card-icon"></i>
                <h3 class="card-title">Scholarship Opportunities</h3>
                <a href="/CICSweb/extension/Admission/scholarship-opportunities.php" class="card-btn">
                    Read More <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Frequently Asked Questions Card -->
            <div class="card reveal">
                <i class="fa-solid fa-circle-question card-icon"></i>
                <h3 class="card-title">Frequently Asked Questions</h3>
                <a href="/CICSweb/extension/Admission/faqs.php" class="card-btn">
                    Read More <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="reveal">
        <div class="first-row-footer reveal">
            <div class="footer-info">
                <div class="contact">
                    <h4>CONTACT DETAILS</h4>
                    <ul>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                            <path d="M536.5-503.5Q560-527 560-560t-23.5-56.5Q513-640 480-640t-56.5 23.5Q400-593 400-560t23.5 56.5Q447-480 480-480t56.5-23.5ZM480-186q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-800Zm0-480Z" />
                        </svg>
                        <p>Burgos Street, Paniqui, Tarlac, Philippines</p>
                    </ul>
                    <ul>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                            <path d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67 67ZM241-600Zm358 358Z" />
                        </svg>
                        <p>+63 930 536 3258 </p>
                    </ul>
                    <ul>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                            <path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200ZM160-640v-80 480-400Z" />
                        </svg>
                        <p>admin@icfpaniqui.edu.ph</p>
                    </ul>
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

            <div class="footer-hero reveal">
                <div class="link-section">
                    <div class="link-source">
                        <a href="#">
                            <h3>Thesis/Research Repository</h3>
                        </a>
                        <ul>
                            <li><a href="#">Student Capstone Projects</a></li>
                            <li><a href="#">Thesis Abstracts</a></li>
                            <li><a href="#">Publications</a></li>
                        </ul>
                    </div>
                    <div class="link-source">
                        <a href="#">
                            <h3>Student Resources</h3>
                        </a>
                        <ul>
                            <li><a href="#">Student Handbook </a></li>
                            <li><a href="#">OJT Manual </a></li>
                            <li><a href="#">Internship Forms </a></li>
                            <li><a href="#">Enrollment Forms </a></li>
                            <li><a href="#">Clearance Forms </a></li>
                            <li><a href="#">Research Templates </a></li>
                            <li><a href="#">Thesis Format </a></li>
                            <li><a href="#">Capstone Guidelines</a></li>
                        </ul>
                    </div>
                    <div class="link-source">
                        <a href="#">
                            <h3>Gallery</h3>
                        </a>
                        <ul>
                            <li><a href="#">Photos</a></li>
                            <li><a href="#">Videos </a></li>
                            <li><a href="#">Activities </a></li>
                            <li><a href="#">Competitions </a></li>
                            <li><a href="#">Graduation </a></li>
                            <li><a href="#">Seminars</a></li>
                        </ul>
                    </div>
                </div>
                <div class="link-section">
                    <div class="link-source">
                        <a href="#">
                            <h3>Download Center</h3>
                        </a>
                        <ul>
                            <li><a href="#">Forms </a></li>
                            <li><a href="#">Manuals </a></li>
                            <li><a href="#">Templates</a></li>
                            <li><a href="#">Policies </a></li>
                            <li><a href="#">Curriculum </a></li>
                            <li><a href="#">Prospectus </a></li>
                            <li><a href="#">Research Documents</a></li>
                        </ul>
                    </div>
                    <div class="link-source">
                        <a href="#">
                            <h3>Frequently Asked Questions</h3>
                        </a>
                        <ul>
                            <li><a href="#">Browse FAQs </a></li>
                            <li><a href="#">Search FAQs</a></li>
                        </ul>
                    </div>
                    <div class="link-source">
                        <a href="#">
                            <h3>Admission Information </h3>
                        </a>
                        <ul>
                            <li><a href="#">Admission Procedures </a></li>
                            <li><a href="#">Entrance Requirements </a></li>
                            <li><a href="#">Enrollment Schedule </a></li>
                            <li><a href="#">Tuition Information </a></li>
                            <li><a href="#">Scholarship Opportunities </a></li>
                            <li><a href="#">Frequently Asked Questions </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="school-name reveal">
            <img class="icflogo" src="/CICSweb/background/logo/CICSlogo.png" alt="Logo">
            <h1>CICS<span>COLLEGE OF</span> <span>INFORMATION AND</span> <span>COMPUTER STUDIES</span></h1>
            <img class="icflogo" src="/CICSweb/background/logo/ICFLogo.png" alt="Logo">
            <h1>ICF<span>INTERWORLD</span> <span>COLLEGES</span> <span>FOUNDATION INC.</span></h1>
        </div>

        <div class="container footer-container">
            <p>&copy; 2026 ICF Interworld Colleges Foundation Inc. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript Mechanics -->
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const themeToggle = document.getElementById('themeToggle');
        const sidebar = document.getElementById('sidebar');
        const btnText = document.getElementById('btnText');
        const loginBtn = document.getElementById('loginBtn');
        const loginBtnLabel = document.getElementById('loginBtnLabel');
        const loginOptionsBox = document.getElementById('loginOptionsBox');
        const expandableItems = document.querySelectorAll('.nav-item.has-submenu');

        // Helper to close login box
        function closeLoginBox() {
            loginOptionsBox.classList.remove('is-open');
            loginBtnLabel.textContent = 'LOG IN PORTAL';
        }

        // Sidebar Toggle
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menuToggle.classList.toggle('is-active');
            sidebar.classList.toggle('is-open');

            if (sidebar.classList.contains('is-open')) {
                btnText.textContent = 'CLOSE';
            } else {
                btnText.textContent = 'MENU';
                closeLoginBox();
            }
        });

        // Close sidebar on click outside
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('is-open') && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('is-open');
                menuToggle.classList.remove('is-active');
                btnText.textContent = 'MENU';
                closeLoginBox();
            }
        });

        // ESC Key listener
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                sidebar.classList.remove('is-open');
                menuToggle.classList.remove('is-active');
                btnText.textContent = 'MENU';
                closeLoginBox();
            }
        });

        // Dark / Light Theme Toggle & Persistence
        const savedTheme = localStorage.getItem('theme');
        const themeIcon = themeToggle.querySelector('i');

        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
            themeIcon.className = 'fa-solid fa-sun';
        }

        themeToggle.addEventListener('click', () => {
            const isLight = document.body.classList.toggle('light-mode');
            if (isLight) {
                localStorage.setItem('theme', 'light');
                themeIcon.className = 'fa-solid fa-sun';
            } else {
                localStorage.setItem('theme', 'dark');
                themeIcon.className = 'fa-solid fa-moon';
            }
        });

        // Header scroll effect
        window.addEventListener("scroll", () => {
            const header = document.querySelector(".header");
            if (window.scrollY > 50) {
                header.classList.add("is-scrolled");
            } else {
                header.classList.remove("is-scrolled");
            }
        });

        // Accordion Submenu System
        expandableItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            if (link) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    expandableItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('open');
                        }
                    });
                    item.classList.toggle('open');
                });
            }
        });

        // Login Portal Expand Toggle
        loginBtn.addEventListener('click', () => {
            loginOptionsBox.classList.toggle('is-open');
            if (loginOptionsBox.classList.contains('is-open')) {
                loginBtnLabel.textContent = 'CANCEL';
            } else {
                loginBtnLabel.textContent = 'LOG IN PORTAL';
            }
        });

        // Scroll Reveal Animation Mechanic (Continuous Up & Down Observer)
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                } else {
                    entry.target.classList.remove('active');
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        });

        const revealElements = document.querySelectorAll('.reveal');
        revealElements.forEach(el => revealObserver.observe(el));
    </script>
</body>

</html>