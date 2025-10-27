<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];

// Get user's full name for display
$user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Get dashboard data based on role
if ($role == 'Doctor' || $role == 'Nurse') {
    // Count active patients
    $patients_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
    $visits_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits")->fetch_assoc()['count'];
    $appointments_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Scheduled'")->fetch_assoc()['count'];
    $high_risk_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM pregnancies WHERE current_status = 'High-Risk'")->fetch_assoc()['count'];
} elseif ($role == 'Mother') {
    // Get patient data for mother
    $patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
    $patient_result = mysqli_query($conn, $patient_query);
    $patient_data = mysqli_fetch_assoc($patient_result);
    
    if ($patient_data) {
        $patient_id = $patient_data['patient_id'];
        // Get pregnancy data
        $pregnancy_query = "SELECT * FROM pregnancies WHERE patient_id = '$patient_id' ORDER BY created_at DESC LIMIT 1";
        $pregnancy_result = mysqli_query($conn, $pregnancy_query);
        $pregnancy_data = mysqli_fetch_assoc($pregnancy_result);
        
        // Get appointments count
        $appointments_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE patient_id = '$patient_id' AND status = 'Scheduled'")->fetch_assoc()['count'];
        $visits_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits WHERE pregnancy_id IN (SELECT pregnancy_id FROM pregnancies WHERE patient_id = '$patient_id')")->fetch_assoc()['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MaternalCare AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles from the previous HTML file */
        
        <?php include 'styles.css'; ?>
    </style>
</head>
<body>
    <!-- Main Application -->
    <div id="main-app">
        <!-- Header -->
        <header class="header">
            <div class="header-container">
                <div class="logo">
                    <i class="fas fa-baby"></i>
                    <span>MaternalCare AI</span>
                </div>
                <div class="user-info">
                    <span id="user-display-name">Welcome, <?php echo $user_data['username']; ?> (<?php echo $role; ?>)</span>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-container">
            <ul class="nav-menu">
                <!-- Doctor Navigation -->
                <div class="doctor-nav" style="display: <?php echo ($role == 'Doctor' || $role == 'Nurse') ? 'flex' : 'none'; ?>;">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo ($role == 'Doctor' || $role == 'Nurse') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li class="nav-item"><a href="patients.php" class="nav-link">Patients</a></li>
                    <li class="nav-item"><a href="appointments.php" class="nav-link">Appointments</a></li>
                    <li class="nav-item"><a href="visits.php" class="nav-link">ANC Visits</a></li>
                    <li class="nav-item"><a href="deliveries.php" class="nav-link">Delivery Records</a></li>
                    <li class="nav-item"><a href="ai-risk.php" class="nav-link">AI Risk Prediction</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">Reports & Analytics</a></li>
                </div>
                
                <!-- Patient Navigation -->
                <div class="patient-nav" style="display: <?php echo $role == 'Mother' ? 'flex' : 'none'; ?>;">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo $role == 'Mother' ? 'active' : ''; ?>">My Dashboard</a></li>
                    <li class="nav-item"><a href="my-appointments.php" class="nav-link">My Appointments</a></li>
                    <li class="nav-item"><a href="health-records.php" class="nav-link">Health Records</a></li>
                    <li class="nav-item"><a href="messages.php" class="nav-link">Messages</a></li>
                    <li class="nav-item"><a href="pregnancy-tracker.php" class="nav-link">Pregnancy Tracker</a></li>
                </div>
                
                <!-- Admin Navigation -->
                <div class="admin-nav" style="display: <?php echo $role == 'Admin' ? 'flex' : 'none'; ?>;">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo $role == 'Admin' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li class="nav-item"><a href="user-management.php" class="nav-link">User Management</a></li>
                    <li class="nav-item"><a href="system-settings.php" class="nav-link">System Settings</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
                </div>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Alert Container -->
            <div id="alert-container"></div>

            <?php if ($role == 'Doctor' || $role == 'Nurse'): ?>
                <!-- Doctor Dashboard -->
                <div id="dashboard-page" class="page-content">
                    <h1><?php echo $role; ?> Dashboard</h1>
                    
                    <div class="dashboard-cards">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Active Patients</div>
                                <div class="card-icon patients">
                                    <i class="fas fa-user-injured"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $patients_count; ?></div>
                            <div class="card-footer">Registered patients</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">ANC Visits</div>
                                <div class="card-icon visits">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $visits_count; ?></div>
                            <div class="card-footer">Total visits recorded</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">High-Risk Cases</div>
                                <div class="card-icon risk">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $high_risk_count; ?></div>
                            <div class="card-footer">Require attention</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Upcoming Appointments</div>
                                <div class="card-icon appointments">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $appointments_count; ?></div>
                            <div class="card-footer">Scheduled appointments</div>
                        </div>
                    </div>

                   <!-- Recent Activity Section -->
<div class="table-container">
    <h2 class="table-title">Recent Activity</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Activity</th>
                <th>Details</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Simplified query using only ANC visits
            $recent_activity_query = "
                SELECT 
                    av.visit_date as activity_date,
                    'ANC Visit' as activity_type,
                    CONCAT('ANC visit recorded for ', p.first_name, ' ', p.last_name, ' (', pr.gestational_age, ' weeks)') as activity_details,
                    'completed' as status,
                    av.visit_date as sort_date
                FROM anc_visits av
                LEFT JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
                LEFT JOIN patients p ON pr.patient_id = p.patient_id
                WHERE av.recorded_by = '$user_id'
                ORDER BY av.visit_date DESC
                LIMIT 10
            ";
            
            $recent_activity_result = mysqli_query($conn, $recent_activity_query);
            
            if (mysqli_num_rows($recent_activity_result) > 0) {
                while ($activity = mysqli_fetch_assoc($recent_activity_result)) {
                    $activity_date = date('M j, Y g:i A', strtotime($activity['activity_date']));
                    
                    echo "
                    <tr>
                        <td>{$activity_date}</td>
                        <td>
                            <span class='activity-type'>{$activity['activity_type']}</span>
                        </td>
                        <td>{$activity['activity_details']}</td>
                        <td>
                            <span class='status-badge status-completed'>Completed</span>
                        </td>
                    </tr>
                    ";
                }
            } else {
                echo "
                <tr>
                    <td colspan='4' style='text-align: center; padding: 20px;'>
                        <p>No recent activity found. Record your first ANC visit to see activity here.</p>
                    </td>
                </tr>
                ";
            }
            ?>
        </tbody>
    </table>
</div>
                </div>

            <?php elseif ($role == 'Mother'): ?>
                <!-- Patient Dashboard -->
                <div id="patient-dashboard-page" class="page-content">
                    <div class="patient-welcome">
                        <h2>Welcome, <?php echo $user_data['username']; ?>!</h2>
                        <p>Your health and your baby's wellbeing are our top priority. Here you can track your pregnancy progress, view appointments, and access your health records.</p>
                    </div>

                    <?php if (isset($pregnancy_data)): ?>
                    <div class="pregnancy-info">
                        <div class="info-card">
                            <h3>Pregnancy Overview</h3>
                            <div class="info-item">
                                <span class="info-label">Gestational Age:</span>
                                <span class="info-value"><?php echo $pregnancy_data['gestational_age'] ?? 'N/A'; ?> weeks</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Expected Delivery:</span>
                                <span class="info-value"><?php echo $pregnancy_data['expected_delivery_date'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <span class="badge <?php echo $pregnancy_data['current_status'] == 'High-Risk' ? 'badge-high' : 'badge-low'; ?>">
                                        <?php echo $pregnancy_data['current_status'] ?? 'Not Set'; ?>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3>Quick Actions</h3>
                            <div class="info-item">
                                <a href="my-appointments.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">Book Appointment</a>
                            </div>
                            <div class="info-item">
                                <a href="health-records.php" class="btn btn-outline" style="width: 100%; margin-bottom: 10px;">View Health Records</a>
                            </div>
                            <div class="info-item">
                                <a href="pregnancy-tracker.php" class="btn btn-outline" style="width: 100%;">Track Pregnancy</a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="info-card">
                        <h3>Get Started</h3>
                        <p>Welcome to MaternalCare AI! It looks like you haven't started tracking your pregnancy yet.</p>
                        <a href="pregnancy-tracker.php" class="btn btn-primary">Start Tracking Your Pregnancy</a>
                    </div>
                    <?php endif; ?>

                    <div class="dashboard-cards">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Upcoming Appointments</div>
                                <div class="card-icon appointments">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $appointments_count ?? 0; ?></div>
                            <div class="card-footer">Next 30 days</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">ANC Visits</div>
                                <div class="card-icon visits">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $visits_count ?? 0; ?></div>
                            <div class="card-footer">Completed visits</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Weeks Pregnant</div>
                                <div class="card-icon patients">
                                    <i class="fas fa-baby"></i>
                                </div>
                            </div>
                            <div class="card-value"><?php echo $pregnancy_data['gestational_age'] ?? '0'; ?></div>
                            <div class="card-footer">Gestational age</div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Health Tips</div>
                                <div class="card-icon risk">
                                    <i class="fas fa-heart"></i>
                                </div>
                            </div>
                            <div class="card-value">5</div>
                            <div class="card-footer">New recommendations</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($role == 'Admin'): ?>
                <!-- Admin Dashboard -->
                <div id="admin-dashboard-page" class="page-content">
                    <h1>Admin Dashboard</h1>
                    <p>System administration and management panel.</p>
                    <!-- Admin specific content -->
                </div>
            <?php endif; ?>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-container">
                <div class="footer-left">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <div class="copyright">
                        &copy; 2024 MaternalCare AI. All rights reserved.
                    </div>
                </div>
                <div class="footer-right">
                    <div class="user-role-display">
                        Logged in as: <span id="footer-user-role"><?php echo $role; ?></span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Navigation functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle navigation link clicks
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // If it's a hash link, prevent default and handle page switching
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        if (page) {
                            switchPage(page);
                        }
                    }
                });
            });
            
            function switchPage(page) {
                // Hide all page contents
                document.querySelectorAll('.page-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Show selected page
                const targetPage = document.getElementById(page + '-page');
                if (targetPage) {
                    targetPage.style.display = 'block';
                }
            }
            
            // Set initial active state based on current page
            const currentPage = window.location.pathname;
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>