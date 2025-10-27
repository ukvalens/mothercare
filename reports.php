<?php
session_start();
include 'connection.php';

// Check if user is logged in and is a doctor/nurse/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Doctor' && $_SESSION['role'] != 'Nurse' && $_SESSION['role'] != 'Admin')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get user data
$user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Date range handling
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Get statistics for dashboard
$total_patients = mysqli_query($conn, "SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
$active_pregnancies = mysqli_query($conn, "SELECT COUNT(*) as count FROM pregnancies WHERE current_status = 'Active'")->fetch_assoc()['count'];
$total_visits = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits")->fetch_assoc()['count'];
$total_deliveries = mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries")->fetch_assoc()['count'];

// Monthly statistics
$monthly_visits = mysqli_query($conn, "
    SELECT MONTH(visit_date) as month, COUNT(*) as count 
    FROM anc_visits 
    WHERE YEAR(visit_date) = YEAR(CURDATE())
    GROUP BY MONTH(visit_date)
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

$monthly_deliveries = mysqli_query($conn, "
    SELECT MONTH(delivery_date) as month, COUNT(*) as count 
    FROM deliveries 
    WHERE YEAR(delivery_date) = YEAR(CURDATE())
    GROUP BY MONTH(delivery_date)
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

// Risk distribution
$risk_distribution = mysqli_query($conn, "
    SELECT risk_level, COUNT(*) as count 
    FROM ai_risk_predictions 
    GROUP BY risk_level
")->fetch_all(MYSQLI_ASSOC);

// Delivery mode distribution
$delivery_modes = mysqli_query($conn, "
    SELECT mode_of_delivery, COUNT(*) as count 
    FROM deliveries 
    GROUP BY mode_of_delivery
")->fetch_all(MYSQLI_ASSOC);

// High-risk patients
$high_risk_patients = mysqli_query($conn, "
    SELECT p.first_name, p.last_name, pr.gestational_age, pr.expected_delivery_date,
           arp.risk_score, arp.risk_level, arp.recommended_action
    FROM ai_risk_predictions arp
    LEFT JOIN pregnancies pr ON arp.pregnancy_id = pr.pregnancy_id
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    WHERE arp.risk_level = 'High'
    ORDER BY arp.risk_score DESC
    LIMIT 10
");

// Recent activities
$recent_activities = mysqli_query($conn, "
    (SELECT 'Visit' as type, visit_date as date, CONCAT('ANC Visit recorded') as description, visit_id as id
     FROM anc_visits 
     ORDER BY visit_date DESC LIMIT 5)
    UNION ALL
    (SELECT 'Delivery' as type, delivery_date as date, CONCAT('Delivery recorded') as description, delivery_id as id
     FROM deliveries 
     ORDER BY delivery_date DESC LIMIT 5)
    UNION ALL
    (SELECT 'Prediction' as type, generated_at as date, CONCAT('Risk prediction generated') as description, prediction_id as id
     FROM ai_risk_predictions 
     ORDER BY generated_at DESC LIMIT 5)
    ORDER BY date DESC
    LIMIT 15
");

// Staff performance (for admin)
if ($role == 'Admin') {
    $staff_performance = mysqli_query($conn, "
        SELECT u.username, u.role,
               COUNT(DISTINCT p.patient_id) as patients_registered,
               COUNT(DISTINCT av.visit_id) as visits_recorded,
               COUNT(DISTINCT d.delivery_id) as deliveries_recorded
        FROM users u
        LEFT JOIN patients p ON u.user_id = p.registered_by
        LEFT JOIN anc_visits av ON u.user_id = av.recorded_by
        LEFT JOIN deliveries d ON u.user_id = d.recorded_by
        WHERE u.role IN ('Doctor', 'Nurse')
        GROUP BY u.user_id, u.username, u.role
        ORDER BY patients_registered DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - MaternalCare AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-top: 40px;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Reports Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            color: var(--primary);
        }

        .date-filter {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            padding: 10px 15px;
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
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #248277;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(0, 119, 182, 0.1);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px var(--shadow);
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        .stat-icon.patients { background-color: var(--primary); }
        .stat-icon.pregnancies { background-color: var(--accent); }
        .stat-icon.visits { background-color: var(--success); }
        .stat-icon.deliveries { background-color: #FF6B6B; }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 968px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .chart-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--primary);
            text-align: center;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .table-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px var(--shadow);
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

        .activity-timeline {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .activity-icon.visit { background-color: var(--success); }
        .activity-icon.delivery { background-color: #FF6B6B; }
        .activity-icon.prediction { background-color: var(--primary); }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .export-options {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-info {
            background-color: rgba(0, 119, 182, 0.1);
            color: var(--primary);
            border-left: 4px solid var(--primary);
        }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .export-options {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-baby"></i>
                <span>MaternalCare AI</span>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</span>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav-container">
        <ul class="nav-menu">
            <!-- Doctor Navigation -->
            <div class="doctor-nav" style="display: flex;">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="patients.php" class="nav-link">Patients</a></li>
                <li class="nav-item"><a href="appointments.php" class="nav-link">Appointments</a></li>
                <li class="nav-item"><a href="visits.php" class="nav-link">ANC Visits</a></li>
                <li class="nav-item"><a href="deliveries.php" class="nav-link">Delivery Records</a></li>
                <li class="nav-item"><a href="ai-risk.php" class="nav-link">AI Risk Prediction</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link active">Reports & Analytics</a></li>
            </div>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Reports & Analytics</h1>
            <div class="export-options">
                <button class="btn btn-success" onclick="exportPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-outline" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon patients">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div class="stat-value"><?php echo $total_patients; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pregnancies">
                    <i class="fas fa-baby"></i>
                </div>
                <div class="stat-value"><?php echo $active_pregnancies; ?></div>
                <div class="stat-label">Active Pregnancies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon visits">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="stat-value"><?php echo $total_visits; ?></div>
                <div class="stat-label">ANC Visits</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon deliveries">
                    <i class="fas fa-baby-carriage"></i>
                </div>
                <div class="stat-value"><?php echo $total_deliveries; ?></div>
                <div class="stat-label">Deliveries</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Monthly Visits Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Monthly ANC Visits</h3>
                <div class="chart-wrapper">
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>

            <!-- Monthly Deliveries Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Monthly Deliveries</h3>
                <div class="chart-wrapper">
                    <canvas id="deliveriesChart"></canvas>
                </div>
            </div>

            <!-- Risk Distribution Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Risk Level Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="riskChart"></canvas>
                </div>
            </div>

            <!-- Delivery Modes Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Delivery Mode Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="deliveryModeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- High-Risk Patients Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">High-Risk Patients Requiring Attention</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Gestational Age</th>
                        <th>Expected Delivery</th>
                        <th>Risk Score</th>
                        <th>Risk Level</th>
                        <th>Recommended Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($high_risk_patients) > 0): ?>
                        <?php while ($patient = mysqli_fetch_assoc($high_risk_patients)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($patient['gestational_age']); ?> weeks</td>
                                <td><?php echo date('M j, Y', strtotime($patient['expected_delivery_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($patient['risk_score']); ?>%</strong></td>
                                <td>
                                    <span class="badge badge-high">High Risk</span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($patient['recommended_action'], 0, 100) . '...'); ?></small>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <p>No high-risk patients found. Great job!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Staff Performance (Admin Only) -->
        <?php if ($role == 'Admin'): ?>
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Staff Performance Overview</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Patients Registered</th>
                        <th>Visits Recorded</th>
                        <th>Deliveries Recorded</th>
                        <th>Total Activities</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($staff_performance) > 0): ?>
                        <?php while ($staff = mysqli_fetch_assoc($staff_performance)): 
                            $total_activities = $staff['patients_registered'] + $staff['visits_recorded'] + $staff['deliveries_recorded'];
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($staff['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($staff['role']); ?></td>
                                <td><?php echo $staff['patients_registered']; ?></td>
                                <td><?php echo $staff['visits_recorded']; ?></td>
                                <td><?php echo $staff['deliveries_recorded']; ?></td>
                                <td><strong><?php echo $total_activities; ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <p>No staff performance data available.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Recent Activity Timeline -->
        <div class="activity-timeline">
            <h2 class="table-title" style="margin-bottom: 20px;">Recent Activity Timeline</h2>
            <?php if (mysqli_num_rows($recent_activities) > 0): ?>
                <?php while ($activity = mysqli_fetch_assoc($recent_activities)): 
                    $activity_date = date('M j, Y H:i', strtotime($activity['date']));
                    $icon_class = '';
                    $icon = '';
                    switch ($activity['type']) {
                        case 'Visit':
                            $icon_class = 'visit';
                            $icon = 'fas fa-stethoscope';
                            break;
                        case 'Delivery':
                            $icon_class = 'delivery';
                            $icon = 'fas fa-baby-carriage';
                            break;
                        case 'Prediction':
                            $icon_class = 'prediction';
                            $icon = 'fas fa-brain';
                            break;
                    }
                ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $icon_class; ?>">
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                            <div class="activity-date"><?php echo $activity_date; ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: var(--text-secondary);">
                    No recent activities found.
                </p>
            <?php endif; ?>
        </div>
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
                    Logged in as: <span><?php echo $role; ?></span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Monthly Visits Chart
            const visitsCtx = document.getElementById('visitsChart').getContext('2d');
            new Chart(visitsCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'ANC Visits',
                        data: [<?php
                            $visitsData = array_fill(0, 12, 0);
                            foreach ($monthly_visits as $visit) {
                                $visitsData[$visit['month'] - 1] = $visit['count'];
                            }
                            echo implode(', ', $visitsData);
                        ?>],
                        borderColor: '#00BFA6',
                        backgroundColor: 'rgba(0, 191, 166, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Visits'
                            }
                        }
                    }
                }
            });

            // Monthly Deliveries Chart
            const deliveriesCtx = document.getElementById('deliveriesChart').getContext('2d');
            new Chart(deliveriesCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Deliveries',
                        data: [<?php
                            $deliveriesData = array_fill(0, 12, 0);
                            foreach ($monthly_deliveries as $delivery) {
                                $deliveriesData[$delivery['month'] - 1] = $delivery['count'];
                            }
                            echo implode(', ', $deliveriesData);
                        ?>],
                        backgroundColor: '#FF6B6B',
                        borderColor: '#E63946',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Deliveries'
                            }
                        }
                    }
                }
            });

            // Risk Distribution Chart
            const riskCtx = document.getElementById('riskChart').getContext('2d');
            new Chart(riskCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                    datasets: [{
                        data: [<?php
                            $riskData = ['Low' => 0, 'Medium' => 0, 'High' => 0];
                            foreach ($risk_distribution as $risk) {
                                $riskData[$risk['risk_level']] = $risk['count'];
                            }
                            echo $riskData['Low'] . ', ' . $riskData['Medium'] . ', ' . $riskData['High'];
                        ?>],
                        backgroundColor: [
                            '#2A9D8F',
                            '#FFC107',
                            '#E63946'
                        ],
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Delivery Mode Chart
            const deliveryModeCtx = document.getElementById('deliveryModeChart').getContext('2d');
            new Chart(deliveryModeCtx, {
                type: 'pie',
                data: {
                    labels: ['Normal', 'C-Section', 'Assisted'],
                    datasets: [{
                        data: [<?php
                            $modeData = ['Normal' => 0, 'C-Section' => 0, 'Assisted' => 0];
                            foreach ($delivery_modes as $mode) {
                                $modeData[$mode['mode_of_delivery']] = $mode['count'];
                            }
                            echo $modeData['Normal'] . ', ' . $modeData['C-Section'] . ', ' . $modeData['Assisted'];
                        ?>],
                        backgroundColor: [
                            '#2A9D8F',
                            '#FF6B6B',
                            '#0077B6'
                        ],
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });

        // Export Functions
        function exportPDF() {
            alert('Generating PDF report...\nThis would generate a comprehensive PDF report in a complete implementation.');
            // In a complete implementation, this would generate a PDF using libraries like jsPDF
        }

        function exportExcel() {
            alert('Exporting to Excel...\nThis would generate an Excel file in a complete implementation.');
            // In a complete implementation, this would generate an Excel file using libraries like SheetJS
        }

        // Auto-refresh data every 5 minutes
        setInterval(() => {
            console.log('Auto-refreshing report data...');
            // In a complete implementation, this would refresh the data via AJAX
        }, 300000);
    </script>
</body>
</html>