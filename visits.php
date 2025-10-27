<?php
session_start();
include 'connection.php';

// Check if user is logged in and is a doctor/nurse
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Doctor' && $_SESSION['role'] != 'Nurse')) {
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_visit'])) {
        $pregnancy_id = mysqli_real_escape_string($conn, $_POST['pregnancy_id']);
        $visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
        $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
        $weight = mysqli_real_escape_string($conn, $_POST['weight']);
        $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
        $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
        $lab_results = mysqli_real_escape_string($conn, $_POST['lab_results']);
        $medications_given = mysqli_real_escape_string($conn, $_POST['medications_given']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        $insert_query = "INSERT INTO anc_visits (pregnancy_id, visit_date, blood_pressure, weight, pulse, temperature, lab_results, medications_given, notes, recorded_by) 
                        VALUES ('$pregnancy_id', '$visit_date', '$blood_pressure', '$weight', '$pulse', '$temperature', '$lab_results', '$medications_given', '$notes', '$user_id')";
        
        if (mysqli_query($conn, $insert_query)) {
            $success = "ANC visit recorded successfully!";
        } else {
            $error = "Error recording visit: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_visit'])) {
        $visit_id = mysqli_real_escape_string($conn, $_POST['visit_id']);
        $visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
        $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
        $weight = mysqli_real_escape_string($conn, $_POST['weight']);
        $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
        $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
        $lab_results = mysqli_real_escape_string($conn, $_POST['lab_results']);
        $medications_given = mysqli_real_escape_string($conn, $_POST['medications_given']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        $update_query = "UPDATE anc_visits SET 
                        visit_date = '$visit_date',
                        blood_pressure = '$blood_pressure',
                        weight = '$weight',
                        pulse = '$pulse',
                        temperature = '$temperature',
                        lab_results = '$lab_results',
                        medications_given = '$medications_given',
                        notes = '$notes'
                        WHERE visit_id = '$visit_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Visit updated successfully!";
        } else {
            $error = "Error updating visit: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_visit'])) {
        $visit_id = mysqli_real_escape_string($conn, $_POST['visit_id']);
        
        $delete_query = "DELETE FROM anc_visits WHERE visit_id = '$visit_id'";
        
        if (mysqli_query($conn, $delete_query)) {
            $success = "Visit deleted successfully!";
        } else {
            $error = "Error deleting visit: " . mysqli_error($conn);
        }
    }
}

// Get all ANC visits with patient and pregnancy details
$visits_query = "
    SELECT av.*, 
           p.first_name, p.last_name, p.patient_id,
           pr.pregnancy_id, pr.gestational_age, pr.expected_delivery_date,
           u.username as recorded_by_name
    FROM anc_visits av
    LEFT JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u ON av.recorded_by = u.user_id
    ORDER BY av.visit_date DESC
";
$visits_result = mysqli_query($conn, $visits_query);

// Get active pregnancies for dropdown
$pregnancies_query = "
    SELECT pr.pregnancy_id, p.first_name, p.last_name, pr.gestational_age
    FROM pregnancies pr
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Active'
    ORDER BY p.first_name, p.last_name
";
$pregnancies_result = mysqli_query($conn, $pregnancies_query);

// Get visit count for stats
$total_visits = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits")->fetch_assoc()['count'];
$today_visits = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits WHERE DATE(visit_date) = CURDATE()")->fetch_assoc()['count'];
$month_visits = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())")->fetch_assoc()['count'];
$high_bp_visits = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits WHERE blood_pressure LIKE '%/%' AND CAST(SUBSTRING_INDEX(blood_pressure, '/', 1) AS UNSIGNED) > 140")->fetch_assoc()['count'];

// PHP function to get visit details
function getVisitDetails($visit_id, $conn) {
    $visit_id = mysqli_real_escape_string($conn, $visit_id);
    
    $query = "
        SELECT av.*, 
               p.first_name, p.last_name, p.patient_id,
               pr.pregnancy_id, pr.gestational_age, pr.expected_delivery_date,
               u.username as recorded_by_name
        FROM anc_visits av
        LEFT JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
        LEFT JOIN patients p ON pr.patient_id = p.patient_id
        LEFT JOIN users u ON av.recorded_by = u.user_id
        WHERE av.visit_id = '$visit_id'
    ";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $visit = mysqli_fetch_assoc($result);
        
        // Format dates
        $visit_date = date('F j, Y', strtotime($visit['visit_date']));
        $expected_delivery = $visit['expected_delivery_date'] ? date('F j, Y', strtotime($visit['expected_delivery_date'])) : 'Not specified';
        
        $content = '
        <div class="visit-details">
            <h4>Visit Information</h4>
            <div class="detail-item">
                <span class="detail-label">Visit ID:</span>
                <span class="detail-value">' . htmlspecialchars($visit['visit_id']) . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Visit Date:</span>
                <span class="detail-value">' . $visit_date . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Patient:</span>
                <span class="detail-value">' . htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Patient ID:</span>
                <span class="detail-value">' . htmlspecialchars($visit['patient_id']) . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Gestational Age:</span>
                <span class="detail-value">' . ($visit['gestational_age'] ? htmlspecialchars($visit['gestational_age']) . ' weeks' : 'Not recorded') . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Expected Delivery:</span>
                <span class="detail-value">' . $expected_delivery . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Recorded By:</span>
                <span class="detail-value">' . htmlspecialchars($visit['recorded_by_name']) . '</span>
            </div>
        </div>
        
        <div class="visit-details">
            <h4>Vital Signs</h4>
            <div class="detail-item">
                <span class="detail-label">Blood Pressure:</span>
                <span class="detail-value">' . ($visit['blood_pressure'] ? htmlspecialchars($visit['blood_pressure']) . ' mmHg' : 'Not recorded') . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Weight:</span>
                <span class="detail-value">' . ($visit['weight'] ? htmlspecialchars($visit['weight']) . ' kg' : 'Not recorded') . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Pulse:</span>
                <span class="detail-value">' . ($visit['pulse'] ? htmlspecialchars($visit['pulse']) . ' bpm' : 'Not recorded') . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Temperature:</span>
                <span class="detail-value">' . ($visit['temperature'] ? htmlspecialchars($visit['temperature']) . '°C' : 'Not recorded') . '</span>
            </div>
        </div>
        
        <div class="visit-details">
            <h4>Clinical Information</h4>
            <div class="detail-item">
                <span class="detail-label">Lab Results:</span>
                <span class="detail-value">' . ($visit['lab_results'] ? nl2br(htmlspecialchars($visit['lab_results'])) : 'No lab results recorded') . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Medications Given:</span>
                <span class="detail-value">' . ($visit['medications_given'] ? nl2br(htmlspecialchars($visit['medications_given'])) : 'No medications recorded') . '</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Clinical Notes:</span>
                <span class="detail-value">' . ($visit['notes'] ? nl2br(htmlspecialchars($visit['notes'])) : 'No notes recorded') . '</span>
            </div>
        </div>';
        
        return $content;
    } else {
        return '<div class="alert alert-error">Visit not found</div>';
    }
}

// Handle AJAX request for visit details
if (isset($_GET['action']) && $_GET['action'] == 'get_visit_details' && isset($_GET['visit_id'])) {
    echo getVisitDetails($_GET['visit_id'], $conn);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANC Visits - MaternalCare AI</title>
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

        /* Visit Management Styles */
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

        .stat-icon.total { background-color: var(--primary); }
        .stat-icon.today { background-color: var(--accent); }
        .stat-icon.month { background-color: var(--success); }
        .stat-icon.high-bp { background-color: var(--error); }

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

        .vital-signs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .vital-badge {
            background-color: var(--background);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            border-left: 3px solid var(--accent);
        }

        .vital-badge.high-bp {
            border-left-color: var(--error);
            background-color: rgba(230, 57, 70, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
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

        .btn-danger {
            background-color: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #248277;
        }

        .form-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .form-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
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

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: var(--card-bg);
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .close {
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .close:hover {
            color: var(--text-primary);
        }

        .vital-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .visit-details {
            background-color: var(--background);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-primary);
        }

        .detail-value {
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .vital-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }
            
            .vital-grid {
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
                <li class="nav-item"><a href="visits.php" class="nav-link active">ANC Visits</a></li>
                <li class="nav-item"><a href="deliveries.php" class="nav-link">Delivery Records</a></li>
                <li class="nav-item"><a href="ai-risk.php" class="nav-link">AI Risk Prediction</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link">Reports & Analytics</a></li>
            </div>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Alert Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">ANC Visit Management</h1>
            <button class="btn btn-primary" onclick="openAddVisitModal()">
                <i class="fas fa-plus"></i> Record New Visit
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="stat-value"><?php echo $total_visits; ?></div>
                <div class="stat-label">Total Visits</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value"><?php echo $today_visits; ?></div>
                <div class="stat-label">Today's Visits</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon month">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $month_visits; ?></div>
                <div class="stat-label">This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon high-bp">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="stat-value"><?php echo $high_bp_visits; ?></div>
                <div class="stat-label">High BP Cases</div>
            </div>
        </div>

        <!-- Visits Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">All ANC Visits</h2>
                <div>
                    <input type="text" class="form-control" placeholder="Search visits..." style="width: 250px;" onkeyup="searchVisits()" id="searchInput">
                </div>
            </div>

            <table class="table" id="visitsTable">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient</th>
                        <th>Visit Date</th>
                        <th>Vital Signs</th>
                        <th>Gestational Age</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($visits_result) > 0): ?>
                        <?php while ($visit = mysqli_fetch_assoc($visits_result)): 
                            $visit_date = date('M j, Y', strtotime($visit['visit_date']));
                            $gestational_age = $visit['gestational_age'] ? $visit['gestational_age'] . ' weeks' : 'N/A';
                            
                            // Check for high blood pressure
                            $is_high_bp = false;
                            if ($visit['blood_pressure'] && strpos($visit['blood_pressure'], '/') !== false) {
                                $bp_parts = explode('/', $visit['blood_pressure']);
                                if (count($bp_parts) == 2 && intval($bp_parts[0]) > 140) {
                                    $is_high_bp = true;
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visit['visit_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']); ?></strong>
                                    <br><small>PID: <?php echo htmlspecialchars($visit['patient_id']); ?></small>
                                </td>
                                <td><?php echo $visit_date; ?></td>
                                <td>
                                    <div class="vital-signs">
                                        <?php if ($visit['blood_pressure']): ?>
                                            <span class="vital-badge <?php echo $is_high_bp ? 'high-bp' : ''; ?>">
                                                BP: <?php echo htmlspecialchars($visit['blood_pressure']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($visit['weight']): ?>
                                            <span class="vital-badge">
                                                Wt: <?php echo htmlspecialchars($visit['weight']); ?> kg
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($visit['pulse']): ?>
                                            <span class="vital-badge">
                                                Pulse: <?php echo htmlspecialchars($visit['pulse']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $gestational_age; ?></td>
                                <td><?php echo htmlspecialchars($visit['recorded_by_name']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-outline btn-sm" onclick="viewVisitDetails(<?php echo $visit['visit_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="editVisit(<?php echo $visit['visit_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this visit record?')">
                                        <input type="hidden" name="visit_id" value="<?php echo $visit['visit_id']; ?>">
                                        <button type="submit" name="delete_visit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                <p>No ANC visits recorded. <a href="#" onclick="openAddVisitModal()">Record your first visit</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Visit Modal -->
        <div id="addVisitModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Record New ANC Visit</h3>
                    <span class="close" onclick="closeAddVisitModal()">&times;</span>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="pregnancy_id">Patient *</label>
                        <select class="form-control" id="pregnancy_id" name="pregnancy_id" required>
                            <option value="">Select Patient</option>
                            <?php 
                            // Reset pointer and loop through pregnancies again
                            mysqli_data_seek($pregnancies_result, 0);
                            while ($pregnancy = mysqli_fetch_assoc($pregnancies_result)): ?>
                                <option value="<?php echo $pregnancy['pregnancy_id']; ?>">
                                    <?php echo htmlspecialchars($pregnancy['first_name'] . ' ' . $pregnancy['last_name']); ?> 
                                    (<?php echo $pregnancy['gestational_age']; ?> weeks)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="visit_date">Visit Date *</label>
                        <input type="date" class="form-control" id="visit_date" name="visit_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <h4 style="margin: 20px 0 15px 0; color: var(--primary);">Vital Signs</h4>
                    <div class="vital-grid">
                        <div class="form-group">
                            <label class="form-label" for="blood_pressure">Blood Pressure</label>
                            <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="e.g., 120/80">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="weight">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" step="0.1" placeholder="e.g., 65.5">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pulse">Pulse (bpm)</label>
                            <input type="number" class="form-control" id="pulse" name="pulse" placeholder="e.g., 72">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="temperature">Temperature (°C)</label>
                            <input type="number" class="form-control" id="temperature" name="temperature" step="0.1" placeholder="e.g., 36.6">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="lab_results">Lab Results</label>
                        <textarea class="form-control" id="lab_results" name="lab_results" rows="3" placeholder="Hemoglobin, urine tests, ultrasound findings, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="medications_given">Medications Given</label>
                        <textarea class="form-control" id="medications_given" name="medications_given" rows="2" placeholder="Iron supplements, folic acid, vaccinations, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="notes">Clinical Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="General assessment, recommendations, follow-up instructions..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_visit" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-save"></i> Save Visit Record
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Visit Details Modal -->
        <div id="viewVisitModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Visit Details</h3>
                    <span class="close" onclick="closeViewVisitModal()">&times;</span>
                </div>
                <div id="visitDetailsContent">
                    <!-- Visit details will be loaded here by JavaScript -->
                </div>
            </div>
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
        // Modal functions
        function openAddVisitModal() {
            document.getElementById('addVisitModal').style.display = 'block';
        }

        function closeAddVisitModal() {
            document.getElementById('addVisitModal').style.display = 'none';
        }

        function openViewVisitModal() {
            document.getElementById('viewVisitModal').style.display = 'block';
        }

        function closeViewVisitModal() {
            document.getElementById('viewVisitModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addVisitModal');
            const viewModal = document.getElementById('viewVisitModal');
            if (event.target == addModal) {
                closeAddVisitModal();
            }
            if (event.target == viewModal) {
                closeViewVisitModal();
            }
        }

        // Search functionality
        function searchVisits() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('visitsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        if (td[j].textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }

        // View visit details - Updated to use PHP backend
        function viewVisitDetails(visitId) {
            // Create AJAX request to fetch visit details
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'visits.php?action=get_visit_details&visit_id=' + visitId, true);
            
            xhr.onload = function() {
                if (this.status === 200) {
                    document.getElementById('visitDetailsContent').innerHTML = this.responseText;
                    openViewVisitModal();
                } else {
                    alert('Error loading visit details');
                }
            };
            
            xhr.send();
        }

        // Edit visit (placeholder function)
        function editVisit(visitId) {
            alert('Edit visit with ID: ' + visitId + '\nThis would open an edit form in a complete implementation.');
            // In a complete implementation, this would open an edit modal with pre-filled data
        }

        // Auto-format blood pressure input
        document.getElementById('blood_pressure')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d/]/g, '');
            e.target.value = value;
        });
    </script>
</body>
</html>