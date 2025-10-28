<?php
session_start();
include 'connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Check in users table for ALL roles (including Mother)
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // If user is a Mother, also get their patient record
            if ($user['role'] == 'Mother') {
                $patient_query = "SELECT * FROM patients WHERE contact_number = '$user[phone]' OR contact_number = '$user[email]'";
                $patient_result = mysqli_query($conn, $patient_query);
                
                if (mysqli_num_rows($patient_result) == 1) {
                    $patient = mysqli_fetch_assoc($patient_result);
                    $_SESSION['patient_id'] = $patient['patient_id'];
                }
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        // Alternative: Check by phone number for patients who might have used phone as username
        $phone_query = "SELECT * FROM users WHERE phone = '$email'";
        $phone_result = mysqli_query($conn, $phone_query);
        
        if (mysqli_num_rows($phone_result) == 1) {
            $user = mysqli_fetch_assoc($phone_result);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // If user is a Mother, also get their patient record
                if ($user['role'] == 'Mother') {
                    $patient_query = "SELECT * FROM patients WHERE contact_number = '$user[phone]'";
                    $patient_result = mysqli_query($conn, $patient_query);
                    
                    if (mysqli_num_rows($patient_result) == 1) {
                        $patient = mysqli_fetch_assoc($patient_result);
                        $_SESSION['patient_id'] = $patient['patient_id'];
                    }
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "No user found with this email or phone number!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MaternalCare AI</title>
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
            padding: 20px 20px 40px;
        }

        .login-content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: start;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Hero Section */
        .hero-section {
            text-align: left;
        }

        .hero-title {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 20px;
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
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 25px;
            line-height: 1.7;
            animation: slideInUp 1s ease-out 0.2s both;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            animation: slideInUp 1s ease-out 0.4s both;
        }

        .feature-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: white;
            font-size: 1.3rem;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 1rem;
        }

        .feature-item p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Animation Elements */
        .floating-elements {
            position: relative;
            height: 120px;
            margin: 20px 0;
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
            font-size: 2rem;
            color: var(--primary);
            opacity: 0.7;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        /* Login Form */
        .login-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 30px;
            position: relative;
            overflow: hidden;
            animation: slideInRight 1s ease-out;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 12px;
            color: var(--primary);
        }

        .login-logo i {
            font-size: 2rem;
        }

        .login-logo h1 {
            font-size: 1.4rem;
        }

        .login-header h2 {
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 1.3rem;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
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
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
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
            margin-top: 18px;
            color: var(--text-secondary);
            font-size: 0.9rem;
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
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            animation: slideIn 0.5s ease-out;
            font-size: 0.9rem;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .alert-info {
            background-color: rgba(0, 119, 182, 0.2);
            color: var(--primary);
            border-left: 4px solid var(--primary);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .login-content-wrapper {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .hero-title {
                font-size: 2.3rem;
            }
        }

        @media (max-width: 768px) {
            .login-content-wrapper {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .hero-section {
                text-align: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
            
            .nav-menu {
                gap: 20px;
            }
            
            .hero-title {
                font-size: 2rem;
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
            
            .login-card {
                padding: 25px 20px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .main-container {
                padding: 15px 15px 30px;
            }
            
            .login-logo h1 {
                font-size: 1.3rem;
            }
            
            .login-header h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-baby"></i>
                <span>MaternalCare AI</span>
            </a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="signup.php" class="nav-link">Sign Up</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <div class="login-content-wrapper">
            <!-- Left Side - Hero Content -->
            <div class="hero-section">
                <h1 class="hero-title">Welcome Back to MaternalCare AI</h1>
                <p class="hero-subtitle">
                    Continue your journey with our innovative platform that combines medical expertise 
                    with artificial intelligence to provide exceptional care for mothers and their babies.
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

            <!-- Right Side - Login Form -->
            <div class="login-card">
                <div class="login-logo">
                    <i class="fas fa-baby"></i>
                    <h1>MaternalCare AI</h1>
                </div>
                
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address or Phone Number</label>
                        <input type="text" class="form-control" id="email" name="email" 
                               placeholder="Enter your email or phone number" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Sign In</button>
                    </div>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Register here</a></p>
                </div>

                <div class="alert alert-info">
                    <strong>Login Instructions:</strong><br>
                    • <strong>Staff</strong>: Use your registered email<br>
                    • <strong>Patients</strong>: Use your phone number (contact clinic for password setup)
                </div>
            </div>
        </div>
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
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
                    <li><i class="fas fa-phone"></i> +250780468216</li>
                    <li><i class="fas fa-envelope"></i> ukwitegetsev9@gmail.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Healthcare St, Medical City</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 MaternalCare AI. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #E63946;"></i> for better maternal healthcare</p>
        </div>
    </footer>
</body>
</html>