<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaternalCare AI - Complete System</title>
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
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            background-color: var(--primary);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .logo i {
            font-size: 1.8rem;
        }

        /* Navigation Styles */
        .nav-container {
            background-color: var(--secondary);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-menu {
            display: flex;
            justify-content: center;
            list-style: none;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-bottom: 3px solid var(--accent);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }

        /* Footer Styles */
        .footer {
            background-color: var(--secondary);
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .copyright {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Auth Pages */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            
        }

        .auth-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 30px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .auth-header p {
            color: var(--text-secondary);
        }

        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .auth-logo i {
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
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
        }

        .btn-accent {
            background-color: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background-color: #00a38c;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(0, 119, 182, 0.1);
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px var(--shadow);
            padding: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .card-icon.patients {
            background-color: var(--primary);
        }

        .card-icon.visits {
            background-color: var(--accent);
        }

        .card-icon.risk {
            background-color: var(--error);
        }

        .card-icon.appointments {
            background-color: var(--success);
        }

        .card-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
        }

        .card-footer {
            margin-top: 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Patient Dashboard Specific Styles */
        .patient-welcome {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }

        .patient-welcome h2 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .patient-welcome p {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .pregnancy-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px var(--shadow);
            padding: 20px;
        }

        .info-card h3 {
            color: var(--primary);
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--background);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-primary);
        }

        .info-value {
            color: var(--text-secondary);
        }

        /* Forms */
        .form-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        /* Tables */
        .table-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background-color: var(--background);
            color: var(--text-primary);
            font-weight: 600;
        }

        .table tr:hover {
            background-color: rgba(0, 119, 182, 0.05);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-low {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success);
        }

        .badge-medium {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .badge-high {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--error);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            border-bottom: 3px solid var(--accent);
            color: var(--accent);
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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

        /* Role-based navigation */
        .doctor-nav, .patient-nav, .admin-nav {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr 1fr;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .nav-menu {
                flex-wrap: wrap;
            }
            .footer-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .nav-menu {
                flex-direction: column;
            }
            .nav-item {
                width: 100%;
            }
            .nav-link {
                text-align: center;
            }
        }

        /* Hidden class for toggling views */
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Authentication Pages -->
    <div id="auth-pages" class="auth-container">
        <!-- Login Form -->
        <div id="login-form" class="auth-card">
            <div class="auth-logo">
                <i class="fas fa-baby"></i>
                <h1>MaternalCare AI</h1>
            </div>
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="login-email">Email</label>
                    <input type="email" class="form-control" id="login-email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="login-password">Password</label>
                    <input type="password" class="form-control" id="login-password" placeholder="Enter your password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </div>
            </form>
            <div class="auth-footer">
                <p>Don't have an account? <a href="#" id="show-register">Register here</a></p>
            </div>
        </div>

        <!-- Registration Form -->
        <div id="register-form" class="auth-card hidden">
            <div class="auth-logo">
                <i class="fas fa-baby"></i>
                <h1>MaternalCare AI</h1>
            </div>
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Register for a new account</p>
            </div>
            <form id="registerForm">
                <div class="form-group">
                    <label class="form-label" for="register-name">Full Name</label>
                    <input type="text" class="form-control" id="register-name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="register-email">Email</label>
                    <input type="email" class="form-control" id="register-email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="register-password">Password</label>
                    <input type="password" class="form-control" id="register-password" placeholder="Create a password" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="register-role">Role</label>
                    <select class="form-control" id="register-role" required>
                        <option value="">Select your role</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Admin">Administrator</option>
                        <option value="Patient">Patient (Mother)</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
            <div class="auth-footer">
                <p>Already have an account? <a href="#" id="show-login">Sign in here</a></p>
            </div>
        </div>
    </div>

    <!-- Main Application -->
    <div id="main-app" class="hidden">
        <!-- Header -->
        <header class="header">
            <div class="header-container">
                <div class="logo">
                    <i class="fas fa-baby"></i>
                    <span>MaternalCare AI</span>
                </div>
                <div class="user-info">
                    <span id="user-display-name">Dr. Sarah Johnson</span>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-container">
            <ul class="nav-menu">
                <!-- Doctor Navigation -->
                <div class="doctor-nav">
                    <li class="nav-item"><a href="#" class="nav-link active" data-page="dashboard">Dashboard</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="patients">Patients</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="appointments">Appointments</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="visits">ANC Visits</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="deliveries">Delivery Records</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="ai-risk">AI Risk Prediction</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="reports">Reports & Analytics</a></li>
                </div>
                
                <!-- Patient Navigation -->
                <div class="patient-nav">
                    <li class="nav-item"><a href="#" class="nav-link active" data-page="patient-dashboard">My Dashboard</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="patient-appointments">My Appointments</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="patient-health">Health Records</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="patient-messages">Messages</a></li>
                </div>
                
                <!-- Admin Navigation -->
                <div class="admin-nav">
                    <li class="nav-item"><a href="#" class="nav-link active" data-page="admin-dashboard">Dashboard</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="user-management">User Management</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="system-settings">System Settings</a></li>
                    <li class="nav-item"><a href="#" class="nav-link" data-page="reports">Reports</a></li>
                </div>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Alert Container -->
            <div id="alert-container"></div>

            <!-- Dashboard Page (Doctor) -->
            <div id="dashboard-page" class="page-content">
                <h1>Doctor Dashboard</h1>
                <!-- Dashboard content from previous implementation -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Active Patients</div>
                            <div class="card-icon patients">
                                <i class="fas fa-user-injured"></i>
                            </div>
                        </div>
                        <div class="card-value" id="active-patients-count">0</div>
                        <div class="card-footer">+0 this month</div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">ANC Visits</div>
                            <div class="card-icon visits">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="card-value" id="anc-visits-count">0</div>
                        <div class="card-footer">+0 this week</div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">High-Risk Cases</div>
                            <div class="card-icon risk">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="card-value" id="high-risk-count">0</div>
                        <div class="card-footer">Require attention</div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Upcoming Appointments</div>
                            <div class="card-icon appointments">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="card-value" id="upcoming-appointments-count">0</div>
                        <div class="card-footer">Next 7 days</div>
                    </div>
                </div>

                <!-- Recent Patients Table -->
                <div class="table-container">
                    <h2 class="table-title">Recent Patients</h2>
                    <table class="table" id="recent-patients-table">
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Name</th>
                                <th>Gestational Age</th>
                                <th>Last Visit</th>
                                <th>Risk Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Patient Dashboard -->
            <div id="patient-dashboard-page" class="page-content hidden">
                <div class="patient-welcome">
                    <h2>Welcome, <span id="patient-name">Mother</span>!</h2>
                    <p>Your health and your baby's wellbeing are our top priority. Here you can track your pregnancy progress, view appointments, and access your health records.</p>
                </div>

                <div class="pregnancy-info">
                    <div class="info-card">
                        <h3>Pregnancy Overview</h3>
                        <div class="info-item">
                            <span class="info-label">Gestational Age:</span>
                            <span class="info-value" id="patient-gestational-age">24 weeks</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Expected Delivery:</span>
                            <span class="info-value" id="patient-edd">March 15, 2024</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Next Appointment:</span>
                            <span class="info-value" id="patient-next-appointment">Feb 10, 2024</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Risk Level:</span>
                            <span class="info-value"><span class="badge badge-low">Low</span></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Recent Health Metrics</h3>
                        <div class="info-item">
                            <span class="info-label">Blood Pressure:</span>
                            <span class="info-value">120/80 mmHg</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Weight:</span>
                            <span class="info-value">68 kg</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Hemoglobin:</span>
                            <span class="info-value">12.5 g/dL</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Visit:</span>
                            <span class="info-value">Jan 15, 2024</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Upcoming Appointments</div>
                            <div class="card-icon appointments">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="card-value" id="patient-appointments-count">2</div>
                        <div class="card-footer">Next 30 days</div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Weeks to Delivery</div>
                            <div class="card-icon visits">
                                <i class="fas fa-baby-carriage"></i>
                            </div>
                        </div>
                        <div class="card-value" id="weeks-to-delivery">16</div>
                        <div class="card-footer">Approximately 112 days</div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">ANC Visits Completed</div>
                            <div class="card-icon patients">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                        </div>
                        <div class="card-value" id="patient-visits-count">5</div>
                        <div class="card-footer">Of 8 recommended</div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Unread Messages</div>
                            <div class="card-icon risk">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="card-value" id="patient-messages-count">3</div>
                        <div class="card-footer">From your care team</div>
                    </div>
                </div>

                <div class="table-container">
                    <h2 class="table-title">Recent Health Tips</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Topic</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Jan 20, 2024</td>
                                <td>Nutrition</td>
                                <td>Increase iron-rich foods in your diet to support baby's development</td>
                            </tr>
                            <tr>
                                <td>Jan 15, 2024</td>
                                <td>Exercise</td>
                                <td>Continue light walking for 30 minutes daily</td>
                            </tr>
                            <tr>
                                <td>Jan 10, 2024</td>
                                <td>Preparation</td>
                                <td>Begin planning for your maternity leave and baby essentials</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Other pages would be implemented similarly -->
            <!-- For brevity, I'm showing the structure for the main pages -->

            <!-- Patients Page -->
            <div id="patients-page" class="page-content hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Patient Management</h2>
                        <button class="btn btn-primary" id="add-patient-btn">Add New Patient</button>
                    </div>
                    
                    <table class="table" id="patients-list-table">
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Contact</th>
                                <th>Gestational Age</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Patient Appointments Page -->
            <div id="patient-appointments-page" class="page-content hidden">
                <div class="table-container">
                    <h2 class="table-title">My Appointments</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Feb 10, 2024 - 10:00 AM</td>
                                <td>Dr. Sarah Johnson</td>
                                <td>Routine Checkup</td>
                                <td><span class="badge badge-low">Scheduled</span></td>
                                <td>
                                    <button class="btn btn-outline btn-sm">Reschedule</button>
                                    <button class="btn btn-primary btn-sm">Details</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Mar 5, 2024 - 2:30 PM</td>
                                <td>Dr. Michael Brown</td>
                                <td>Ultrasound</td>
                                <td><span class="badge badge-low">Scheduled</span></td>
                                <td>
                                    <button class="btn btn-outline btn-sm">Reschedule</button>
                                    <button class="btn btn-primary btn-sm">Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Settings Page -->
            <div id="settings-page" class="page-content hidden">
                <div class="form-container">
                    <h2 class="form-title">User Profile</h2>
                    <form id="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="profile-name">Full Name</label>
                                <input type="text" class="form-control" id="profile-name">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="profile-email">Email</label>
                                <input type="email" class="form-control" id="profile-email">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="profile-role">Role</label>
                            <input type="text" class="form-control" id="profile-role" disabled>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

       <!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-left">
            <button class="logout-btn" id="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
            <div class="copyright">
                &copy; 2024 MaternalCare AI. All rights reserved.
            </div>
        </div>
        <div class="footer-right">
            <div class="user-role-display">
                Logged in as: <span id="footer-user-role">Doctor</span>
            </div>
        </div>
    </div>

    <script>
        // Simple logout functionality
        document.getElementById('logout-btn').addEventListener('click', function() {
            // Optionally clear session/local storage
            sessionStorage.clear();
            localStorage.clear();

            // Redirect to login page
            window.location.href = 'index.php';
        });
    </script>
</footer>

    </div>

    <script>
        // Data Models
        const DataModel = {
            // Initialize data in localStorage if not exists
            init: function() {
                if (!localStorage.getItem('users')) {
                    localStorage.setItem('users', JSON.stringify([]));
                }
                if (!localStorage.getItem('patients')) {
                    localStorage.setItem('patients', JSON.stringify([]));
                }
                if (!localStorage.getItem('appointments')) {
                    localStorage.setItem('appointments', JSON.stringify([]));
                }
                if (!localStorage.getItem('visits')) {
                    localStorage.setItem('visits', JSON.stringify([]));
                }
                if (!localStorage.getItem('riskPredictions')) {
                    localStorage.setItem('riskPredictions', JSON.stringify([]));
                }
                if (!localStorage.getItem('currentUser')) {
                    localStorage.setItem('currentUser', JSON.stringify(null));
                }
            },

            // User management
            getUsers: function() {
                return JSON.parse(localStorage.getItem('users')) || [];
            },

            saveUser: function(user) {
                const users = this.getUsers();
                users.push(user);
                localStorage.setItem('users', JSON.stringify(users));
            },

            findUserByEmail: function(email) {
                const users = this.getUsers();
                return users.find(user => user.email === email);
            },

            // Patient management
            getPatients: function() {
                return JSON.parse(localStorage.getItem('patients')) || [];
            },

            savePatient: function(patient) {
                const patients = this.getPatients();
                // Generate patient ID if new
                if (!patient.patient_id) {
                    patient.patient_id = 'PT-' + (patients.length + 1000);
                    patient.created_at = new Date().toISOString();
                    patients.push(patient);
                } else {
                    // Update existing patient
                    const index = patients.findIndex(p => p.patient_id === patient.patient_id);
                    if (index !== -1) {
                        patient.updated_at = new Date().toISOString();
                        patients[index] = patient;
                    }
                }
                localStorage.setItem('patients', JSON.stringify(patients));
            },

            deletePatient: function(patientId) {
                const patients = this.getPatients();
                const filtered = patients.filter(p => p.patient_id !== patientId);
                localStorage.setItem('patients', JSON.stringify(filtered));
            },

            // Current user management
            getCurrentUser: function() {
                return JSON.parse(localStorage.getItem('currentUser'));
            },

            setCurrentUser: function(user) {
                localStorage.setItem('currentUser', JSON.stringify(user));
            },

            clearCurrentUser: function() {
                localStorage.setItem('currentUser', JSON.stringify(null));
            }
        };

        // UI Controller
        const UIController = {
            // Show/hide elements
            showElement: function(id) {
                document.getElementById(id).classList.remove('hidden');
            },

            hideElement: function(id) {
                document.getElementById(id).classList.add('hidden');
            },

            // Navigation
            showPage: function(pageName) {
                // Hide all pages
                document.querySelectorAll('.page-content').forEach(page => {
                    page.classList.add('hidden');
                });

                // Remove active class from all menu items
                document.querySelectorAll('.nav-link').forEach(item => {
                    item.classList.remove('active');
                });

                // Show selected page and activate menu item
                this.showElement(pageName + '-page');
                document.querySelector(`[data-page="${pageName}"]`).classList.add('active');
            },

            // Role-based navigation
            showRoleBasedNavigation: function(role) {
                // Hide all navigation menus
                document.querySelectorAll('.doctor-nav, .patient-nav, .admin-nav').forEach(nav => {
                    nav.style.display = 'none';
                });

                // Show appropriate navigation based on role
                if (role === 'Doctor' || role === 'Nurse') {
                    document.querySelector('.doctor-nav').style.display = 'flex';
                } else if (role === 'Patient') {
                    document.querySelector('.patient-nav').style.display = 'flex';
                } else if (role === 'Admin') {
                    document.querySelector('.admin-nav').style.display = 'flex';
                }

                // Update footer with role
                document.getElementById('footer-user-role').textContent = role;
            },

            // Alert messages
            showAlert: function(message, type = 'success') {
                const alertContainer = document.getElementById('alert-container');
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `
                    <span>${message}</span>
                    <button style="float: right; background: none; border: none; cursor: pointer;" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                alertContainer.appendChild(alert);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.remove();
                    }
                }, 5000);
            },

            // Form handling
            clearForm: function(formId) {
                document.getElementById(formId).reset();
            },

            // Dashboard updates
            updateDashboardCounts: function() {
                const patients = DataModel.getPatients();
                const visits = DataModel.getVisits();
                const appointments = DataModel.getAppointments();
                const predictions = DataModel.getRiskPredictions();

                document.getElementById('active-patients-count').textContent = patients.length;
                document.getElementById('anc-visits-count').textContent = visits.length;
                document.getElementById('high-risk-count').textContent = 
                    predictions.filter(p => p.risk_level === 'High').length;
                document.getElementById('upcoming-appointments-count').textContent = 
                    appointments.filter(a => a.status === 'Scheduled').length;
            },

            // Populate tables
            populatePatientsTable: function() {
                const patients = DataModel.getPatients();
                const tableBody = document.querySelector('#patients-list-table tbody');
                tableBody.innerHTML = '';

                patients.forEach(patient => {
                    const row = document.createElement('tr');
                    const age = patient.dob ? new Date().getFullYear() - new Date(patient.dob).getFullYear() : 'N/A';
                    
                    row.innerHTML = `
                        <td>${patient.patient_id}</td>
                        <td>${patient.first_name} ${patient.last_name}</td>
                        <td>${age}</td>
                        <td>${patient.contact_number || 'N/A'}</td>
                        <td>${patient.gestational_age ? patient.gestational_age + ' weeks' : 'N/A'}</td>
                        <td><span class="badge badge-low">Active</span></td>
                        <td class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="AppController.editPatient('${patient.patient_id}')">Edit</button>
                            <button class="btn btn-outline btn-sm" onclick="AppController.viewPatient('${patient.patient_id}')">View</button>
                            <button class="btn btn-outline btn-sm" onclick="AppController.deletePatient('${patient.patient_id}')">Delete</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            },

            populateRecentPatientsTable: function() {
                const patients = DataModel.getPatients().slice(0, 5); // Show only 5 recent patients
                const tableBody = document.querySelector('#recent-patients-table tbody');
                tableBody.innerHTML = '';

                patients.forEach(patient => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${patient.patient_id}</td>
                        <td>${patient.first_name} ${patient.last_name}</td>
                        <td>${patient.gestational_age ? patient.gestational_age + ' weeks' : 'N/A'}</td>
                        <td>${patient.created_at ? new Date(patient.created_at).toLocaleDateString() : 'N/A'}</td>
                        <td><span class="badge badge-low">Low</span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="AppController.viewPatient('${patient.patient_id}')">View</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            },

            // User info update
            updateUserInfo: function() {
                const user = DataModel.getCurrentUser();
                if (user) {
                    document.getElementById('user-display-name').textContent = user.name;
                    document.getElementById('patient-name').textContent = user.name.split(' ')[0];
                    
                    // Update profile form
                    document.getElementById('profile-name').value = user.name;
                    document.getElementById('profile-email').value = user.email;
                    document.getElementById('profile-role').value = user.role;
                }
            }
        };

        // Main Application Controller
        const AppController = {
            init: function() {
                // Initialize data model
                DataModel.init();

                // Check if user is logged in
                const currentUser = DataModel.getCurrentUser();
                if (currentUser) {
                    this.showMainApp();
                } else {
                    this.showAuth();
                }

                // Set up event listeners
                this.setupEventListeners();
            },

            setupEventListeners: function() {
                // Auth event listeners
                document.getElementById('show-register').addEventListener('click', (e) => {
                    e.preventDefault();
                    UIController.hideElement('login-form');
                    UIController.showElement('register-form');
                });

                document.getElementById('show-login').addEventListener('click', (e) => {
                    e.preventDefault();
                    UIController.hideElement('register-form');
                    UIController.showElement('login-form');
                });

                document.getElementById('loginForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleLogin();
                });

                document.getElementById('registerForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleRegister();
                });

                // Navigation event listeners
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = e.currentTarget.getAttribute('data-page');
                        UIController.showPage(page);
                        
                        // Load page-specific data
                        if (page === 'dashboard' || page === 'admin-dashboard') {
                            this.loadDashboardData();
                        } else if (page === 'patients') {
                            this.loadPatientsData();
                        }
                    });
                });

                // Logout button
                document.getElementById('logout-btn').addEventListener('click', () => {
                    this.handleLogout();
                });

                // Patient management
                document.getElementById('add-patient-btn').addEventListener('click', () => {
                    this.showPatientForm();
                });

                // Profile form
                document.getElementById('profile-form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.updateProfile();
                });
            },

            // Auth handlers
            handleLogin: function() {
                const email = document.getElementById('login-email').value;
                const password = document.getElementById('login-password').value;

                const user = DataModel.findUserByEmail(email);
                
                if (user && user.password === password) {
                    DataModel.setCurrentUser(user);
                    this.showMainApp();
                    UIController.showAlert('Login successful!', 'success');
                } else {
                    UIController.showAlert('Invalid email or password!', 'error');
                }
            },

            handleRegister: function() {
                const name = document.getElementById('register-name').value;
                const email = document.getElementById('register-email').value;
                const password = document.getElementById('register-password').value;
                const role = document.getElementById('register-role').value;

                // Check if user already exists
                if (DataModel.findUserByEmail(email)) {
                    UIController.showAlert('User with this email already exists!', 'error');
                    return;
                }

                const user = {
                    id: 'USER-' + (DataModel.getUsers().length + 1000),
                    name: name,
                    email: email,
                    password: password,
                    role: role,
                    created_at: new Date().toISOString()
                };

                DataModel.saveUser(user);
                DataModel.setCurrentUser(user);
                this.showMainApp();
                UIController.showAlert('Registration successful!', 'success');
            },

            handleLogout: function() {
                DataModel.clearCurrentUser();
                this.showAuth();
                UIController.showAlert('You have been logged out successfully.', 'success');
            },

            // UI state handlers
            showAuth: function() {
                UIController.hideElement('main-app');
                UIController.showElement('auth-pages');
                UIController.showElement('login-form');
                UIController.hideElement('register-form');
                
                // Clear forms
                UIController.clearForm('loginForm');
                UIController.clearForm('registerForm');
            },

            showMainApp: function() {
                UIController.hideElement('auth-pages');
                UIController.showElement('main-app');
                
                // Update user info
                UIController.updateUserInfo();
                
                // Show role-based navigation
                const currentUser = DataModel.getCurrentUser();
                UIController.showRoleBasedNavigation(currentUser.role);
                
                // Show appropriate dashboard based on role
                if (currentUser.role === 'Patient') {
                    UIController.showPage('patient-dashboard');
                } else if (currentUser.role === 'Doctor' || currentUser.role === 'Nurse') {
                    UIController.showPage('dashboard');
                } else if (currentUser.role === 'Admin') {
                    UIController.showPage('admin-dashboard');
                }
            },

            // Data loading
            loadDashboardData: function() {
                UIController.updateDashboardCounts();
                UIController.populateRecentPatientsTable();
            },

            loadPatientsData: function() {
                UIController.populatePatientsTable();
            },

            // Patient management
            showPatientForm: function(patientId = null) {
                // Implementation for showing patient form
                UIController.showAlert('Patient form would open here in a complete implementation', 'success');
            },

            editPatient: function(patientId) {
                this.showPatientForm(patientId);
            },

            viewPatient: function(patientId) {
                UIController.showAlert(`Viewing patient ${patientId} - This would open a detailed view in a real application.`, 'success');
            },

            deletePatient: function(patientId) {
                if (confirm('Are you sure you want to delete this patient?')) {
                    DataModel.deletePatient(patientId);
                    UIController.populatePatientsTable();
                    UIController.updateDashboardCounts();
                    UIController.showAlert('Patient deleted successfully!', 'success');
                }
            },
            

            // Profile management
            updateProfile: function() {
                const currentUser = DataModel.getCurrentUser();
                const users = DataModel.getUsers();
                
                // Update user in users array
                const userIndex = users.findIndex(u => u.id === currentUser.id);
                if (userIndex !== -1) {
                    users[userIndex].name = document.getElementById('profile-name').value;
                    users[userIndex].email = document.getElementById('profile-email').value;
                    localStorage.setItem('users', JSON.stringify(users));
                    
                    // Update current user
                    currentUser.name = document.getElementById('profile-name').value;
                    currentUser.email = document.getElementById('profile-email').value;
                    DataModel.setCurrentUser(currentUser);
                    
                    // Update UI
                    UIController.updateUserInfo();
                    UIController.showAlert('Profile updated successfully!', 'success');
                }
            }
        };
        

        // Initialize the application when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            AppController.init();
        });

        
    </script>
</body>
</html>