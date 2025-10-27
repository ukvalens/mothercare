<?php
session_start();
include 'connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);

    // Check if user already exists for ALL roles
    $check_query = "SELECT * FROM users WHERE email = '$email' OR username = '$username'";
    $check_result = mysqli_query($conn, $check_query);
    $user_exists = mysqli_num_rows($check_result) > 0;
    
    if ($user_exists) {
        $error = "User with this email or username already exists!";
    } else {
        // Hash password for ALL roles
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle Mother role - create BOTH patient record AND user account
        if ($role == 'Mother') {
            // First create patient record
            $patient_query = "INSERT INTO patients (first_name, last_name, contact_number, registered_by) 
                            VALUES ('$full_name', '', '$phone', 1)";
            
            if (mysqli_query($conn, $patient_query)) {
                $patient_id = mysqli_insert_id($conn);
                
                // Then create user account for the mother
                $user_query = "INSERT INTO users (username, password, role, email, phone) 
                              VALUES ('$username', '$hashed_password', 'Mother', '$email', '$phone')";
                
                if (mysqli_query($conn, $user_query)) {
                    $user_id = mysqli_insert_id($conn);
                    
                    // Update patient record with user_id if you have that column
                    // If not, you can store the relationship in session or separate table
                    
                    $success = "Registration successful! Your account has been created. Redirecting to login...";
                    
                    // Redirect to login page
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 3000);
                    </script>";
                } else {
                    $error = "User account creation failed: " . mysqli_error($conn);
                }
            } else {
                $error = "Patient registration failed: " . mysqli_error($conn);
            }
        } else {
            // Insert user for staff roles (Doctor, Nurse, Admin)
            $insert_query = "INSERT INTO users (username, password, role, email, phone) 
                            VALUES ('$username', '$hashed_password', '$role', '$email', '$phone')";
            
            if (mysqli_query($conn, $insert_query)) {
                $user_id = mysqli_insert_id($conn);
                $success = "Registration successful! Redirecting to login...";
                
                // Redirect to login page after successful registration
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
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
    <title>MaternalCare  - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0077B6;
            --secondary: #023E8A;
            --accent: #00BFA6;
            --background: #E6F2F1;
            --text-primary: #2D2D2D;
            --text-secondary: #6C757D;
            --error: #E63946;
            --success: #2A9D8F;
            --card-bg: #FFFFFF;
            --border: #DEE2E6;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #E6F2F1 0%, #F8F9FA 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-link {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            padding: 8px 0;
            position: relative;
            cursor: pointer;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: width 0.3s;
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }

        /* Main Container */
        .main-container {
            margin-top: 80px;
            flex: 1;
            width: 100%;
        }

        /* Section Styles */
        .section {
            display: none;
            min-height: calc(100vh - 80px);
            padding: 40px 20px;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Home Section */
        .home-section {
            max-width: 1200px;
            margin: 0 auto;
        }

        .home-content-wrapper {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 50px;
            align-items: start;
        }

        /* Hero Section */
        .hero-section {
            text-align: left;
        }

        .hero-title {
            font-size: 2.8rem;
            color: var(--primary);
            margin-bottom: 25px;
            font-weight: 700;
            animation: slideInUp 1s ease-out;
        }

        @keyframes slideInUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 35px;
            line-height: 1.7;
            animation: slideInUp 1s ease-out 0.2s both;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            animation: slideInUp 1s ease-out 0.4s both;
        }

        .feature-item {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .feature-item p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Animation Elements */
        .floating-elements {
            position: relative;
            height: 150px;
            margin: 25px 0;
        }

        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }

        .element-1 {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .element-2 {
            top: 60%;
            left: 80%;
            animation-delay: 2s;
        }

        .element-3 {
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        .health-icon {
            font-size: 2.5rem;
            color: var(--primary);
            opacity: 0.7;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        /* Signup Form on Home Page */
        .home-signup-container {
            position: sticky;
            top: 100px;
        }

        .home-auth-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 35px;
            position: relative;
            overflow: hidden;
            animation: slideInRight 1s ease-out;
        }

        .home-auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .home-auth-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .home-auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .home-auth-logo i {
            font-size: 2.2rem;
        }

        .home-auth-logo h1 {
            font-size: 1.5rem;
        }

        .home-auth-header h2 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        .home-auth-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* About Section */
        .about-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .about-content {
            max-width: 1200px;
            text-align: center;
            padding: 50px 20px;
        }

        .about-title {
            font-size: 3rem;
            margin-bottom: 40px;
            animation: slideInUp 1s ease-out;
        }

        .animated-text {
            font-size: 1.5rem;
            margin-bottom: 40px;
            animation: slideInUp 1s ease-out 0.3s both;
        }

        .healthcare-images {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-top: 50px;
        }

        .health-image {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 1s ease-out;
            transition: transform 0.3s;
        }

        .health-image:hover {
            transform: translateY(-10px);
        }

        .health-image i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: white;
        }

        .health-image h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .health-image p {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Footer Styles */
        .footer {
            background-color: var(--secondary);
            color: white;
            padding: 60px 0 30px;
            width: 100%;
            margin-top: auto;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }

        .footer-section h3 {
            margin-bottom: 20px;
            color: white;
            font-size: 1.2rem;
        }

        .footer-section p {
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 191, 166, 0.1);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 119, 182, 0.3);
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            animation: slideIn 0.5s ease-out;
            font-size: 0.95rem;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .role-description {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 5px;
            padding: 8px;
            background-color: var(--background);
            border-radius: 4px;
            border-left: 3px solid var(--primary);
            animation: fadeIn 0.3s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .home-content-wrapper {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .home-signup-container {
                position: static;
                max-width: 600px;
                margin: 0 auto;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .healthcare-images {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .home-content-wrapper {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .hero-section {
                text-align: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
            
            .nav-menu {
                gap: 20px;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .about-title {
                font-size: 2.5rem;
            }
            
            .healthcare-images {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .floating-elements {
                height: 100px;
            }
        }

        @media (max-width: 576px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .home-auth-card {
                padding: 25px 20px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .about-title {
                font-size: 2rem;
            }
            
            .animated-text {
                font-size: 1.2rem;
            }
            
            .section {
                padding: 30px 15px;
            }
            
            .home-auth-logo h1 {
                font-size: 1.3rem;
            }
            
            .home-auth-header h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="#" class="logo" onclick="showSection('home')">
                <i class="fas fa-baby"></i>
                <span>Maternal Healthcare</span>
            </a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="#" class="nav-link active" onclick="showSection('home')">Home</a></li>
                    <li><a href="#" class="nav-link" onclick="showSection('about')">About</a></li>
                    <li><a href="login.php" class="nav-link">Sign In</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Home Section -->
        <section id="home" class="section home-section active">
            <div class="home-content-wrapper">
                <!-- Left Side - Hero Content -->
                <div class="hero-section">
                    <h1 class="hero-title">Empowering Maternal Health with AI</h1>
                    <p class="hero-subtitle">
                        Join our innovative platform that combines medical expertise with artificial intelligence 
                        to provide exceptional care for mothers and their babies throughout the pregnancy journey.
                    </p>
                    
                    <!-- Animated Elements -->
                    <div class="floating-elements">
                        <div class="floating-element element-1">
                            <i class="fas fa-heartbeat health-icon"></i>
                        </div>
                        <div class="floating-element element-2">
                            <i class="fas fa-baby health-icon"></i>
                        </div>
                        <div class="floating-element element-3">
                            <i class="fas fa-stethoscope health-icon"></i>
                        </div>
                    </div>

                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h3 class="feature-title">AI Risk Prediction</h3>
                            <p>Advanced algorithms to predict potential pregnancy complications</p>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3 class="feature-title">Smart Scheduling</h3>
                            <p>Automated appointment management and reminders</p>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="feature-title">Progress Tracking</h3>
                            <p>Monitor pregnancy milestones and health indicators</p>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="feature-title">Expert Care Team</h3>
                            <p>Connect with qualified doctors and healthcare professionals</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Signup Form -->
                <div class="home-signup-container">
                    <div class="home-auth-card">
                        <div class="home-auth-logo">
                            <i class="fas fa-baby"></i>
                            <h1>MaternalCare AI</h1>
                        </div>
                        
                        <div class="home-auth-header">
                            <h2>Create Your Account</h2>
                            <p>Join our maternal care platform</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label" for="home_full_name">Full Name</label>
                                <input type="text" class="form-control" id="home_full_name" name="full_name" 
                                       placeholder="Enter your full name" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="home_username">Username</label>
                                <input type="text" class="form-control" id="home_username" name="username" 
                                       placeholder="Choose a username" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="home_email">Email Address</label>
                                <input type="email" class="form-control" id="home_email" name="email" 
                                       placeholder="Enter your email" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="home_phone">Phone Number</label>
                                <input type="tel" class="form-control" id="home_phone" name="phone" 
                                       placeholder="Enter your phone number" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="home_password">Password</label>
                                <input type="password" class="form-control" id="home_password" name="password" 
                                       placeholder="Create a password" required minlength="6">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="home_role">I am a:</label>
                                <select class="form-control" id="home_role" name="role" required onchange="showHomeRoleDescription()">
                                    <option value="">Select your role</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Nurse">Nurse</option>
                                    <option value="Admin">Administrator</option>
                                    <option value="Mother">Pregnant Mother</option>
                                </select>
                                <div id="home-role-description" class="role-description" style="display: none;">
                                    Please select your role to see description
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </div>
                        </form>

                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Sign in here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="section about-section">
            <div class="about-content">
                <h1 class="about-title">About MaternalCare AI</h1>
                <p class="animated-text">Transforming Maternal Healthcare Through Innovation</p>
                
                <div class="healthcare-images">
                    <div class="health-image">
                        <i class="fas fa-heartbeat"></i>
                        <h3>Cardiac Monitoring</h3>
                        <p>Advanced heart health tracking for expectant mothers</p>
                    </div>
                    <div class="health-image">
                        <i class="fas fa-baby"></i>
                        <h3>Fetal Development</h3>
                        <p>Comprehensive fetal growth and development monitoring</p>
                    </div>
                    <div class="health-image">
                        <i class="fas fa-stethoscope"></i>
                        <h3>Expert Care</h3>
                        <p>24/7 access to maternal health specialists</p>
                    </div>
                    <div class="health-image">
                        <i class="fas fa-brain"></i>
                        <h3>AI Analytics</h3>
                        <p>Predictive analytics for better health outcomes</p>
                    </div>
                    <div class="health-image">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Smart Scheduling</h3>
                        <p>Automated appointment and medication reminders</p>
                    </div>
                    <div class="health-image">
                        <i class="fas fa-chart-line"></i>
                        <h3>Progress Tracking</h3>
                        <p>Real-time health metrics and progress monitoring</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Static Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>MaternalCare AI</h3>
                <p>Revolutionizing maternal healthcare through artificial intelligence and expert medical care.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a onclick="showSection('home')">Home</a></li>
                    <li><a onclick="showSection('about')">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Services</h3>
                <ul class="footer-links">
                    <li><a href="ai-risk.php">AI Risk Prediction</a></li>
                    <li><a href="appointments.php">Appointments</a></li>
                    <li><a href="visits.php">ANC Visits</a></li>
                    <li><a href="deliveries.php">Delivery Care</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact Info</h3>
                <ul class="footer-links">
                    <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                    <li><i class="fas fa-envelope"></i> info@maternalcare.ai</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Healthcare St, Medical City</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 MaternalCare AI. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #E63946;"></i> for better maternal healthcare</p>
        </div>
    </footer>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav link
            event.target.classList.add('active');
            
            // Scroll to top when switching sections
            window.scrollTo(0, 0);
        }

        function showHomeRoleDescription() {
            const role = document.getElementById('home_role').value;
            const description = document.getElementById('home-role-description');
            
            const descriptions = {
                'Doctor': 'Access to patient management, medical records, appointments, and AI risk predictions.',
                'Nurse': 'Access to patient visits, vital signs monitoring, and basic patient management.',
                'Admin': 'Full system access including user management and system configuration.',
                'Mother': 'Access to personal health records, appointment scheduling, and pregnancy tracking.'
            };
            
            if (role && descriptions[role]) {
                description.textContent = descriptions[role];
                description.style.display = 'block';
            } else {
                description.style.display = 'none';
            }
        }

        // Show description if role is pre-selected (e.g., form submission error)
        document.addEventListener('DOMContentLoaded', function() {
            showHomeRoleDescription();
        });
    </script>
</body>
</html>