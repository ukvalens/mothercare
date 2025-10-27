<?php
session_start();
include 'connection.php';

// Check if user is logged in and is a patient (Mother)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Mother') {
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

// Get patient data
$patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
$patient_result = mysqli_query($conn, $patient_query);
$patient_data = mysqli_fetch_assoc($patient_result);

$patient_id = $patient_data['patient_id'] ?? null;

// Get pregnancy data
if ($patient_id) {
    $pregnancy_query = "SELECT * FROM pregnancies WHERE patient_id = '$patient_id' ORDER BY created_at DESC LIMIT 1";
    $pregnancy_result = mysqli_query($conn, $pregnancy_query);
    $pregnancy_data = mysqli_fetch_assoc($pregnancy_result);
}

// Get ANC visits
if ($patient_id && isset($pregnancy_data['pregnancy_id'])) {
    $visits_query = "
        SELECT av.*, u.username as recorded_by_name
        FROM anc_visits av
        LEFT JOIN users u ON av.recorded_by = u.user_id
        WHERE av.pregnancy_id = '{$pregnancy_data['pregnancy_id']}'
        ORDER BY av.visit_date DESC
    ";
    $visits_result = mysqli_query($conn, $visits_query);
}

// Get lab results and important metrics from visits
if ($patient_id && isset($pregnancy_data['pregnancy_id'])) {
    $metrics_query = "
        SELECT 
            visit_date,
            blood_pressure,
            weight,
            pulse,
            temperature,
            lab_results
        FROM anc_visits 
        WHERE pregnancy_id = '{$pregnancy_data['pregnancy_id']}'
        AND (blood_pressure IS NOT NULL OR weight IS NOT NULL OR lab_results IS NOT NULL)
        ORDER BY visit_date DESC
        LIMIT 10
    ";
    $metrics_result = mysqli_query($conn, $metrics_query);
}

// Get risk predictions
if ($patient_id && isset($pregnancy_data['pregnancy_id'])) {
    $risk_query = "
        SELECT *
        FROM ai_risk_predictions 
        WHERE pregnancy_id = '{$pregnancy_data['pregnancy_id']}'
        ORDER BY generated_at DESC
        LIMIT 5
    ";
    $risk_result = mysqli_query($conn, $risk_query);
}

// Get delivery records if any
if ($patient_id && isset($pregnancy_data['pregnancy_id'])) {
    $delivery_query = "
        SELECT *
        FROM deliveries 
        WHERE pregnancy_id = '{$pregnancy_data['pregnancy_id']}'
        ORDER BY delivery_date DESC
        LIMIT 1
    ";
    $delivery_result = mysqli_query($conn, $delivery_query);
    $delivery_data = mysqli_fetch_assoc($delivery_result);
}

// Calculate pregnancy progress
if (isset($pregnancy_data)) {
    $edd = new DateTime($pregnancy_data['expected_delivery_date']);
    $today = new DateTime();
    $total_days = 280; // 40 weeks
    $days_passed = $today->diff(new DateTime($pregnancy_data['expected_delivery_date']))->days;
    $progress = min(100, max(0, (($total_days - $days_passed) / $total_days) * 100));
    $weeks_remaining = floor($days_passed / 7);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - MaternalCare AI</title>
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

        /* Health Records Styles */
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

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-banner h2 {
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .welcome-banner p {
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .pregnancy-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .overview-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px var(--shadow);
            text-align: center;
        }

        .overview-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        .overview-icon.weeks { background-color: var(--primary); }
        .overview-icon.progress { background-color: var(--accent); }
        .overview-icon.remaining { background-color: var(--success); }
        .overview-icon.risk { background-color: var(--error); }

        .overview-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .overview-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .progress-bar {
            background-color: var(--background);
            border-radius: 20px;
            height: 10px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, var(--accent), var(--primary));
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s ease;
        }

        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 968px) {
            .records-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vital-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .vital-card {
            background-color: var(--background);
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid var(--accent);
        }

        .vital-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .vital-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
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

        .visit-item {
            background-color: var(--background);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent);
        }

        .visit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .visit-date {
            font-weight: bold;
            color: var(--primary);
        }

        .visit-doctor {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .visit-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }

        .metric {
            text-align: center;
        }

        .metric-value {
            font-weight: bold;
            color: var(--text-primary);
        }

        .metric-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .risk-item {
            background-color: var(--background);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            border-left: 4px solid;
        }

        .risk-item.low { border-left-color: var(--success); }
        .risk-item.medium { border-left-color: #ffc107; }
        .risk-item.high { border-left-color: var(--error); }

        .risk-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .risk-score {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .risk-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .recommendations {
            background-color: rgba(0, 191, 166, 0.1);
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .recommendations h4 {
            color: var(--accent);
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .recommendations ul {
            list-style-type: none;
            padding: 0;
        }

        .recommendations li {
            padding: 5px 0;
            font-size: 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .recommendations li i {
            color: var(--accent);
            margin-top: 2px;
            font-size: 0.7rem;
        }

        .chart-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .chart-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            text-align: center;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .delivery-info {
            background: linear-gradient(135deg, var(--success), #248277);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .delivery-info h3 {
            margin-bottom: 15px;
            color: white;
        }

        .delivery-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .delivery-item {
            text-align: center;
        }

        .delivery-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .delivery-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
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

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(0, 119, 182, 0.1);
        }

        @media (max-width: 768px) {
            .pregnancy-overview {
                grid-template-columns: 1fr 1fr;
            }
            
            .vital-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .visit-metrics {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .pregnancy-overview {
                grid-template-columns: 1fr;
            }
            
            .vital-stats {
                grid-template-columns: 1fr;
            }
            
            .visit-metrics {
                grid-template-columns: 1fr;
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
                <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav-container">
        <ul class="nav-menu">
            <!-- Patient Navigation -->
            <div class="patient-nav" style="display: flex;">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">My Dashboard</a></li>
                <li class="nav-item"><a href="my-appointments.php" class="nav-link">My Appointments</a></li>
                <li class="nav-item"><a href="health-records.php" class="nav-link active">Health Records</a></li>
                <li class="nav-item"><a href="messages.php" class="nav-link">Messages</a></li>
                <li class="nav-item"><a href="pregnancy-tracker.php" class="nav-link">Pregnancy Tracker</a></li>
            </div>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h2>My Health Records</h2>
            <p>Track your pregnancy journey, view medical records, and monitor your health progress</p>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Health Records & Medical History</h1>
            <button class="btn btn-outline" onclick="downloadHealthReport()">
                <i class="fas fa-download"></i> Download Report
            </button>
        </div>

        <?php if (!$patient_id || !isset($pregnancy_data)): ?>
            <!-- No Pregnancy Data -->
            <div class="empty-state">
                <i class="fas fa-file-medical"></i>
                <h3>No Health Records Found</h3>
                <p>It looks like you haven't started tracking your pregnancy yet. Start your pregnancy journey to begin recording health data.</p>
                <a href="pregnancy-tracker.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-baby"></i> Start Pregnancy Tracking
                </a>
            </div>
        <?php else: ?>
            <!-- Pregnancy Overview -->
            <div class="pregnancy-overview">
                <div class="overview-card">
                    <div class="overview-icon weeks">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="overview-value"><?php echo $pregnancy_data['gestational_age'] ?? '0'; ?></div>
                    <div class="overview-label">Weeks Pregnant</div>
                </div>
                <div class="overview-card">
                    <div class="overview-icon progress">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="overview-value"><?php echo round($progress); ?>%</div>
                    <div class="overview-label">Pregnancy Progress</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="overview-icon remaining">
                        <i class="fas fa-baby-carriage"></i>
                    </div>
                    <div class="overview-value"><?php echo $weeks_remaining; ?></div>
                    <div class="overview-label">Weeks to Go</div>
                </div>
                <div class="overview-card">
                    <div class="overview-icon risk">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="overview-value">
                        <?php 
                        if (isset($pregnancy_data['ai_risk_score'])) {
                            echo $pregnancy_data['ai_risk_score'] . '%';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="overview-label">Current Risk Score</div>
                </div>
            </div>

            <!-- Records Grid -->
            <div class="records-grid">
                <!-- Vital Signs & Metrics -->
                <div class="card">
                    <h3 class="card-title">
                        <i class="fas fa-heartbeat"></i>
                        Vital Signs Overview
                    </h3>
                    <?php if ($metrics_result && mysqli_num_rows($metrics_result) > 0): ?>
                        <?php 
                        $latest_visit = mysqli_fetch_assoc($metrics_result);
                        mysqli_data_seek($metrics_result, 0);
                        ?>
                        <div class="vital-stats">
                            <?php if ($latest_visit['blood_pressure']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?php echo htmlspecialchars($latest_visit['blood_pressure']); ?></div>
                                    <div class="vital-label">Blood Pressure</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($latest_visit['weight']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?php echo htmlspecialchars($latest_visit['weight']); ?> kg</div>
                                    <div class="vital-label">Weight</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($latest_visit['pulse']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?php echo htmlspecialchars($latest_visit['pulse']); ?> bpm</div>
                                    <div class="vital-label">Pulse Rate</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($latest_visit['temperature']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?php echo htmlspecialchars($latest_visit['temperature']); ?>°C</div>
                                    <div class="vital-label">Temperature</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
                            Last updated: <?php echo date('M j, Y', strtotime($latest_visit['visit_date'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 20px;">
                            <i class="fas fa-clipboard-list"></i>
                            <p>No vital signs recorded yet</p>
                            <small>Your vital signs will appear here after your first ANC visit</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Risk Assessment -->
                <div class="card">
                    <h3 class="card-title">
                        <i class="fas fa-robot"></i>
                        AI Risk Assessment
                    </h3>
                    <?php if ($risk_result && mysqli_num_rows($risk_result) > 0): ?>
                        <?php while ($risk = mysqli_fetch_assoc($risk_result)): 
                            $risk_date = date('M j, Y', strtotime($risk['generated_at']));
                        ?>
                            <div class="risk-item <?php echo strtolower($risk['risk_level']); ?>">
                                <div class="risk-header">
                                    <div class="risk-score"><?php echo $risk['risk_score']; ?>% Risk</div>
                                    <span class="badge badge-<?php echo strtolower($risk['risk_level']); ?>">
                                        <?php echo $risk['risk_level']; ?> Risk
                                    </span>
                                </div>
                                <div class="risk-date">Assessed on <?php echo $risk_date; ?></div>
                                <?php if ($risk['recommended_action']): ?>
                                    <div class="recommendations">
                                        <h4>Recommended Actions:</h4>
                                        <ul>
                                            <?php foreach (explode("\n", $risk['recommended_action']) as $action): ?>
                                                <?php if (trim($action)): ?>
                                                    <li><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars(trim($action)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 20px;">
                            <i class="fas fa-brain"></i>
                            <p>No risk assessments yet</p>
                            <small>AI risk predictions will appear here after assessment</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ANC Visits History -->
            <div class="card">
                <h3 class="card-title">
                    <i class="fas fa-stethoscope"></i>
                    ANC Visit History
                </h3>
                <?php if ($visits_result && mysqli_num_rows($visits_result) > 0): ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php while ($visit = mysqli_fetch_assoc($visits_result)): 
                            $visit_date = date('M j, Y', strtotime($visit['visit_date']));
                        ?>
                            <div class="visit-item">
                                <div class="visit-header">
                                    <div class="visit-date"><?php echo $visit_date; ?></div>
                                    <div class="visit-doctor">Recorded by: <?php echo htmlspecialchars($visit['recorded_by_name']); ?></div>
                                </div>
                                <div class="visit-metrics">
                                    <?php if ($visit['blood_pressure']): ?>
                                        <div class="metric">
                                            <div class="metric-value"><?php echo htmlspecialchars($visit['blood_pressure']); ?></div>
                                            <div class="metric-label">Blood Pressure</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($visit['weight']): ?>
                                        <div class="metric">
                                            <div class="metric-value"><?php echo htmlspecialchars($visit['weight']); ?> kg</div>
                                            <div class="metric-label">Weight</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($visit['pulse']): ?>
                                        <div class="metric">
                                            <div class="metric-value"><?php echo htmlspecialchars($visit['pulse']); ?> bpm</div>
                                            <div class="metric-label">Pulse</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($visit['temperature']): ?>
                                        <div class="metric">
                                            <div class="metric-value"><?php echo htmlspecialchars($visit['temperature']); ?>°C</div>
                                            <div class="metric-label">Temperature</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($visit['lab_results']): ?>
                                    <div style="margin-top: 10px;">
                                        <strong>Lab Results:</strong>
                                        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 5px;">
                                            <?php echo htmlspecialchars($visit['lab_results']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['notes']): ?>
                                    <div style="margin-top: 10px;">
                                        <strong>Clinical Notes:</strong>
                                        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 5px;">
                                            <?php echo htmlspecialchars($visit['notes']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 20px;">
                        <i class="fas fa-calendar-times"></i>
                        <p>No ANC visits recorded</p>
                        <small>Your ANC visit history will appear here after your first appointment</small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Delivery Information -->
            <?php if (isset($delivery_data)): ?>
                <div class="delivery-info">
                    <h3><i class="fas fa-baby-carriage"></i> Delivery Information</h3>
                    <div class="delivery-details">
                        <div class="delivery-item">
                            <div class="delivery-value"><?php echo date('M j, Y', strtotime($delivery_data['delivery_date'])); ?></div>
                            <div class="delivery-label">Delivery Date</div>
                        </div>
                        <div class="delivery-item">
                            <div class="delivery-value"><?php echo htmlspecialchars($delivery_data['mode_of_delivery']); ?></div>
                            <div class="delivery-label">Delivery Mode</div>
                        </div>
                        <div class="delivery-item">
                            <div class="delivery-value"><?php echo htmlspecialchars($delivery_data['baby_weight']); ?> kg</div>
                            <div class="delivery-label">Baby Weight</div>
                        </div>
                        <div class="delivery-item">
                            <div class="delivery-value"><?php echo htmlspecialchars($delivery_data['baby_gender']); ?></div>
                            <div class="delivery-label">Baby Gender</div>
                        </div>
                        <?php if ($delivery_data['apgar_score']): ?>
                            <div class="delivery-item">
                                <div class="delivery-value"><?php echo htmlspecialchars($delivery_data['apgar_score']); ?>/10</div>
                                <div class="delivery-label">APGAR Score</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Health Trends Chart -->
            <div class="chart-container">
                <h3 class="chart-title">Weight Progress</h3>
                <div class="chart-wrapper">
                    <canvas id="weightChart"></canvas>
                </div>
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
                    Logged in as: <span>Mother</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Download health report
        function downloadHealthReport() {
            alert('Generating health report...\nThis would generate a comprehensive PDF health report in a complete implementation.');
            // In a complete implementation, this would generate a PDF health report
        }

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Weight Progress Chart
            const weightCtx = document.getElementById('weightChart');
            if (weightCtx) {
                new Chart(weightCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Week 12', 'Week 16', 'Week 20', 'Week 24', 'Week 28', 'Week 32', 'Week 36'],
                        datasets: [{
                            label: 'Your Weight (kg)',
                            data: [58, 60, 62, 65, 68, 71, 73],
                            borderColor: '#00BFA6',
                            backgroundColor: 'rgba(0, 191, 166, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Recommended Range',
                            data: [57, 60, 63, 66, 69, 72, 74],
                            borderColor: '#0077B6',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            fill: false,
                            pointRadius: 0
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
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Weight (kg)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Gestational Week'
                                }
                            }
                        }
                    }
                });
            }

            // Animate progress bars
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                setTimeout(() => {
                    progressFill.style.transition = 'width 1.5s ease-in-out';
                }, 500);
            }
        });

        // Print health records
        function printHealthRecords() {
            window.print();
        }

        // Add to home screen prompt for mobile
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                console.log('Health records page loaded - ready for offline access');
            });
        }
    </script>
</body>
</html>