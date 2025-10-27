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
    if (isset($_POST['add_delivery'])) {
        $pregnancy_id = mysqli_real_escape_string($conn, $_POST['pregnancy_id']);
        $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
        $mode_of_delivery = mysqli_real_escape_string($conn, $_POST['mode_of_delivery']);
        $baby_weight = mysqli_real_escape_string($conn, $_POST['baby_weight']);
        $baby_gender = mysqli_real_escape_string($conn, $_POST['baby_gender']);
        $apgar_score = mysqli_real_escape_string($conn, $_POST['apgar_score']);
        $complications = mysqli_real_escape_string($conn, $_POST['complications']);

        // Check if delivery already exists for this pregnancy
        $check_query = "SELECT * FROM deliveries WHERE pregnancy_id = '$pregnancy_id'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "A delivery record already exists for this pregnancy.";
        } else {
            $insert_query = "INSERT INTO deliveries (pregnancy_id, delivery_date, mode_of_delivery, baby_weight, baby_gender, apgar_score, complications, recorded_by) 
                            VALUES ('$pregnancy_id', '$delivery_date', '$mode_of_delivery', '$baby_weight', '$baby_gender', '$apgar_score', '$complications', '$user_id')";
            
            if (mysqli_query($conn, $insert_query)) {
                // Update pregnancy status to Completed
                $update_pregnancy_query = "UPDATE pregnancies SET current_status = 'Completed' WHERE pregnancy_id = '$pregnancy_id'";
                mysqli_query($conn, $update_pregnancy_query);
                
                // Redirect to prevent form resubmission
                header("Location: deliveries.php?success=delivery_added");
                exit();
            } else {
                $error = "Error recording delivery: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['update_delivery'])) {
        $delivery_id = mysqli_real_escape_string($conn, $_POST['delivery_id']);
        $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
        $mode_of_delivery = mysqli_real_escape_string($conn, $_POST['mode_of_delivery']);
        $baby_weight = mysqli_real_escape_string($conn, $_POST['baby_weight']);
        $baby_gender = mysqli_real_escape_string($conn, $_POST['baby_gender']);
        $apgar_score = mysqli_real_escape_string($conn, $_POST['apgar_score']);
        $complications = mysqli_real_escape_string($conn, $_POST['complications']);

        $update_query = "UPDATE deliveries SET 
                        delivery_date = '$delivery_date',
                        mode_of_delivery = '$mode_of_delivery',
                        baby_weight = '$baby_weight',
                        baby_gender = '$baby_gender',
                        apgar_score = '$apgar_score',
                        complications = '$complications'
                        WHERE delivery_id = '$delivery_id'";
        
        if (mysqli_query($conn, $update_query)) {
            // Redirect to prevent form resubmission
            header("Location: deliveries.php?success=delivery_updated");
            exit();
        } else {
            $error = "Error updating delivery: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_delivery'])) {
        $delivery_id = mysqli_real_escape_string($conn, $_POST['delivery_id']);
        
        // Get pregnancy_id before deleting to update pregnancy status
        $get_pregnancy_query = "SELECT pregnancy_id FROM deliveries WHERE delivery_id = '$delivery_id'";
        $pregnancy_result = mysqli_query($conn, $get_pregnancy_query);
        $pregnancy_data = mysqli_fetch_assoc($pregnancy_result);
        $pregnancy_id = $pregnancy_data['pregnancy_id'];
        
        $delete_query = "DELETE FROM deliveries WHERE delivery_id = '$delivery_id'";
        
        if (mysqli_query($conn, $delete_query)) {
            // Update pregnancy status back to Active
            $update_pregnancy_query = "UPDATE pregnancies SET current_status = 'Active' WHERE pregnancy_id = '$pregnancy_id'";
            mysqli_query($conn, $update_pregnancy_query);
            
            // Redirect to prevent form resubmission
            header("Location: deliveries.php?success=delivery_deleted");
            exit();
        } else {
            $error = "Error deleting delivery: " . mysqli_error($conn);
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'delivery_added':
            $success = "Delivery recorded successfully! Pregnancy status updated to Completed.";
            break;
        case 'delivery_updated':
            $success = "Delivery record updated successfully!";
            break;
        case 'delivery_deleted':
            $success = "Delivery record deleted successfully! Pregnancy status reverted to Active.";
            break;
    }
}

// Get all deliveries with patient and pregnancy details
$deliveries_query = "
    SELECT d.*, 
           p.first_name, p.last_name, p.patient_id,
           pr.pregnancy_id, pr.gestational_age, pr.expected_delivery_date,
           u.username as recorded_by_name
    FROM deliveries d
    LEFT JOIN pregnancies pr ON d.pregnancy_id = pr.pregnancy_id
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u ON d.recorded_by = u.user_id
    ORDER BY d.delivery_date DESC
";
$deliveries_result = mysqli_query($conn, $deliveries_query);

// Get active pregnancies for dropdown (for new deliveries)
$pregnancies_query = "
    SELECT pr.pregnancy_id, p.first_name, p.last_name, pr.gestational_age, pr.expected_delivery_date
    FROM pregnancies pr
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Active'
    ORDER BY p.first_name, p.last_name
";
$pregnancies_result = mysqli_query($conn, $pregnancies_query);

// Get delivery count for stats
$total_deliveries = mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries")->fetch_assoc()['count'];
$normal_deliveries = mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries WHERE mode_of_delivery = 'Normal'")->fetch_assoc()['count'];
$csection_deliveries = mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries WHERE mode_of_delivery = 'C-Section'")->fetch_assoc()['count'];
$this_month_deliveries = mysqli_query($conn, "SELECT COUNT(*) as count FROM deliveries WHERE MONTH(delivery_date) = MONTH(CURDATE()) AND YEAR(delivery_date) = YEAR(CURDATE())")->fetch_assoc()['count'];

// Handle AJAX request for delivery details
if (isset($_GET['action']) && $_GET['action'] == 'get_delivery_details') {
    $delivery_id = mysqli_real_escape_string($conn, $_GET['delivery_id']);
    
    $delivery_details_query = "
        SELECT d.*, 
               p.first_name, p.last_name, p.patient_id, p.dob, p.contact_number,
               pr.pregnancy_id, pr.gestational_age, pr.expected_delivery_date,
               u.username as recorded_by_name,
               DATE_FORMAT(d.created_at, '%M %e, %Y at %l:%i %p') as recorded_on
        FROM deliveries d
        LEFT JOIN pregnancies pr ON d.pregnancy_id = pr.pregnancy_id
        LEFT JOIN patients p ON pr.patient_id = p.patient_id
        LEFT JOIN users u ON d.recorded_by = u.user_id
        WHERE d.delivery_id = '$delivery_id'
    ";
    $delivery_details_result = mysqli_query($conn, $delivery_details_query);
    $delivery_details = mysqli_fetch_assoc($delivery_details_result);
    
    if ($delivery_details) {
        header('Content-Type: application/json');
        echo json_encode($delivery_details);
        exit();
    }
}

// Handle AJAX request for delivery edit form
if (isset($_GET['action']) && $_GET['action'] == 'get_delivery_edit') {
    $delivery_id = mysqli_real_escape_string($conn, $_GET['delivery_id']);
    
    $delivery_edit_query = "
        SELECT d.*, 
               p.first_name, p.last_name,
               pr.pregnancy_id
        FROM deliveries d
        LEFT JOIN pregnancies pr ON d.pregnancy_id = pr.pregnancy_id
        LEFT JOIN patients p ON pr.patient_id = p.patient_id
        WHERE d.delivery_id = '$delivery_id'
    ";
    $delivery_edit_result = mysqli_query($conn, $delivery_edit_query);
    $delivery_edit = mysqli_fetch_assoc($delivery_edit_result);
    
    if ($delivery_edit) {
        header('Content-Type: application/json');
        echo json_encode($delivery_edit);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Records - MaternalCare AI</title>
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

        /* Delivery Management Styles */
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
        .stat-icon.normal { background-color: var(--success); }
        .stat-icon.csection { background-color: #FF6B6B; }
        .stat-icon.month { background-color: var(--accent); }

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

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-normal {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success);
        }

        .badge-csection {
            background-color: rgba(255, 107, 107, 0.2);
            color: #FF6B6B;
        }

        .badge-assisted {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .baby-details {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .baby-badge {
            background-color: var(--background);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            border-left: 3px solid var(--accent);
        }

        .baby-badge.male {
            border-left-color: var(--primary);
            background-color: rgba(0, 119, 182, 0.1);
        }

        .baby-badge.female {
            border-left-color: #FF6B6B;
            background-color: rgba(255, 107, 107, 0.1);
        }

        .apgar-excellent { color: var(--success); font-weight: bold; }
        .apgar-good { color: var(--accent); font-weight: bold; }
        .apgar-fair { color: #ffc107; font-weight: bold; }
        .apgar-poor { color: var(--error); font-weight: bold; }

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

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .delivery-details {
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

        .complication-warning {
            background-color: rgba(230, 57, 70, 0.1);
            border-left: 4px solid var(--error);
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 0;
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
            
            .delivery-grid {
                grid-template-columns: 1fr;
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
                <li class="nav-item"><a href="deliveries.php" class="nav-link active">Delivery Records</a></li>
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
            <h1 class="page-title">Delivery Records</h1>
            <button class="btn btn-primary" onclick="openAddDeliveryModal()">
                <i class="fas fa-plus"></i> Record New Delivery
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-baby-carriage"></i>
                </div>
                <div class="stat-value"><?php echo $total_deliveries; ?></div>
                <div class="stat-label">Total Deliveries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon normal">
                    <i class="fas fa-baby"></i>
                </div>
                <div class="stat-value"><?php echo $normal_deliveries; ?></div>
                <div class="stat-label">Normal Deliveries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon csection">
                    <i class="fas fa-procedures"></i>
                </div>
                <div class="stat-value"><?php echo $csection_deliveries; ?></div>
                <div class="stat-label">C-Sections</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon month">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $this_month_deliveries; ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <!-- Recent Deliveries Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">All Delivery Records</h2>
                <div>
                    <input type="text" class="form-control" placeholder="Search deliveries..." style="width: 250px;" onkeyup="searchDeliveries()" id="searchInput">
                </div>
            </div>

            <table class="table" id="deliveriesTable">
                <thead>
                    <tr>
                        <th>Delivery ID</th>
                        <th>Patient</th>
                        <th>Delivery Date</th>
                        <th>Mode of Delivery</th>
                        <th>Baby Details</th>
                        <th>APGAR Score</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($deliveries_result) > 0): ?>
                        <?php while ($delivery = mysqli_fetch_assoc($deliveries_result)): 
                            $delivery_date = date('M j, Y', strtotime($delivery['delivery_date']));
                            $apgar_class = '';
                            if ($delivery['apgar_score'] >= 8) $apgar_class = 'apgar-excellent';
                            elseif ($delivery['apgar_score'] >= 7) $apgar_class = 'apgar-good';
                            elseif ($delivery['apgar_score'] >= 5) $apgar_class = 'apgar-fair';
                            else $apgar_class = 'apgar-poor';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($delivery['delivery_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($delivery['first_name'] . ' ' . $delivery['last_name']); ?></strong>
                                    <br><small>PID: <?php echo htmlspecialchars($delivery['patient_id']); ?></small>
                                </td>
                                <td><?php echo $delivery_date; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace('-', '', $delivery['mode_of_delivery'])); ?>">
                                        <?php echo $delivery['mode_of_delivery']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="baby-details">
                                        <?php if ($delivery['baby_weight']): ?>
                                            <span class="baby-badge">
                                                <?php echo htmlspecialchars($delivery['baby_weight']); ?> kg
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($delivery['baby_gender']): ?>
                                            <span class="baby-badge <?php echo strtolower($delivery['baby_gender']); ?>">
                                                <i class="fas fa-<?php echo $delivery['baby_gender'] == 'Male' ? 'mars' : 'venus'; ?>"></i>
                                                <?php echo $delivery['baby_gender']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($delivery['apgar_score']): ?>
                                        <span class="<?php echo $apgar_class; ?>">
                                            <?php echo htmlspecialchars($delivery['apgar_score']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span>N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($delivery['recorded_by_name']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-outline btn-sm" onclick="viewDeliveryDetails(<?php echo $delivery['delivery_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="editDelivery(<?php echo $delivery['delivery_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this delivery record?')">
                                        <input type="hidden" name="delivery_id" value="<?php echo $delivery['delivery_id']; ?>">
                                        <button type="submit" name="delete_delivery" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                <p>No delivery records found. <a href="#" onclick="openAddDeliveryModal()">Record your first delivery</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Delivery Modal -->
        <div id="addDeliveryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Record New Delivery</h3>
                    <span class="close" onclick="closeAddDeliveryModal()">&times;</span>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="pregnancy_id">Patient *</label>
                        <select class="form-control" id="pregnancy_id" name="pregnancy_id" required onchange="updatePatientInfo()">
                            <option value="">Select Patient</option>
                            <?php 
                            // Reset pointer and loop through pregnancies again
                            mysqli_data_seek($pregnancies_result, 0);
                            while ($pregnancy = mysqli_fetch_assoc($pregnancies_result)): ?>
                                <option value="<?php echo $pregnancy['pregnancy_id']; ?>" data-edd="<?php echo $pregnancy['expected_delivery_date']; ?>" data-ga="<?php echo $pregnancy['gestational_age']; ?>">
                                    <?php echo htmlspecialchars($pregnancy['first_name'] . ' ' . $pregnancy['last_name']); ?> 
                                    (EDD: <?php echo date('M j, Y', strtotime($pregnancy['expected_delivery_date'])); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small id="patientInfo" style="color: var(--text-secondary); margin-top: 5px; display: block;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="delivery_date">Delivery Date *</label>
                        <input type="date" class="form-control" id="delivery_date" name="delivery_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <h4 style="margin: 20px 0 15px 0; color: var(--primary);">Delivery Information</h4>
                    <div class="delivery-grid">
                        <div class="form-group">
                            <label class="form-label" for="mode_of_delivery">Mode of Delivery *</label>
                            <select class="form-control" id="mode_of_delivery" name="mode_of_delivery" required>
                                <option value="">Select Mode</option>
                                <option value="Normal">Normal Vaginal Delivery</option>
                                <option value="C-Section">Cesarean Section</option>
                                <option value="Assisted">Assisted Delivery (Forceps/Vacuum)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="baby_weight">Baby Weight (kg) *</label>
                            <input type="number" class="form-control" id="baby_weight" name="baby_weight" step="0.01" min="0.5" max="5.0" required placeholder="e.g., 3.2">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="baby_gender">Baby Gender *</label>
                            <select class="form-control" id="baby_gender" name="baby_gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="apgar_score">APGAR Score (1-10)</label>
                            <input type="number" class="form-control" id="apgar_score" name="apgar_score" min="1" max="10" placeholder="e.g., 8">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="complications">Complications & Notes</label>
                        <textarea class="form-control" id="complications" name="complications" rows="3" placeholder="Any delivery complications, maternal condition, special notes..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_delivery" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-save"></i> Save Delivery Record
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Delivery Details Modal -->
        <div id="viewDeliveryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Delivery Details</h3>
                    <span class="close" onclick="closeViewDeliveryModal()">&times;</span>
                </div>
                <div id="deliveryDetailsContent">
                    <!-- Delivery details will be loaded here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Edit Delivery Modal (will be created dynamically) -->
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
        function openAddDeliveryModal() {
            document.getElementById('addDeliveryModal').style.display = 'block';
        }

        function closeAddDeliveryModal() {
            document.getElementById('addDeliveryModal').style.display = 'none';
        }

        function openViewDeliveryModal() {
            document.getElementById('viewDeliveryModal').style.display = 'block';
        }

        function closeViewDeliveryModal() {
            document.getElementById('viewDeliveryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addDeliveryModal');
            const viewModal = document.getElementById('viewDeliveryModal');
            if (event.target == addModal) {
                closeAddDeliveryModal();
            }
            if (event.target == viewModal) {
                closeViewDeliveryModal();
            }
        }

        // Update patient information when selection changes
        function updatePatientInfo() {
            const select = document.getElementById('pregnancy_id');
            const selectedOption = select.options[select.selectedIndex];
            const infoDiv = document.getElementById('patientInfo');
            
            if (selectedOption.value) {
                const edd = selectedOption.getAttribute('data-edd');
                const ga = selectedOption.getAttribute('data-ga');
                infoDiv.innerHTML = `Expected Delivery: ${new Date(edd).toLocaleDateString()} | Gestational Age: ${ga} weeks`;
            } else {
                infoDiv.innerHTML = '';
            }
        }

        // Search functionality
        function searchDeliveries() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('deliveriesTable');
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

        // View delivery details - FETCH FROM DATABASE
        function viewDeliveryDetails(deliveryId) {
            // Show loading state
            document.getElementById('deliveryDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p>Loading delivery details...</p>
                </div>
            `;
            openViewDeliveryModal();

            // Fetch delivery details from database via AJAX
            fetch(`deliveries.php?action=get_delivery_details&delivery_id=${deliveryId}`)
                .then(response => response.json())
                .then(delivery => {
                    if (delivery) {
                        const deliveryDate = new Date(delivery.delivery_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        const expectedDeliveryDate = delivery.expected_delivery_date ? 
                            new Date(delivery.expected_delivery_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            }) : 'N/A';

                        // Determine APGAR score class
                        let apgarClass = '';
                        if (delivery.apgar_score >= 8) apgarClass = 'apgar-excellent';
                        else if (delivery.apgar_score >= 7) apgarClass = 'apgar-good';
                        else if (delivery.apgar_score >= 5) apgarClass = 'apgar-fair';
                        else apgarClass = 'apgar-poor';

                        const content = `
                            <div class="delivery-details">
                                <h4>Patient Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Patient Name:</span>
                                    <span class="detail-value">${delivery.first_name} ${delivery.last_name}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Patient ID:</span>
                                    <span class="detail-value">${delivery.patient_id || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Expected Delivery Date:</span>
                                    <span class="detail-value">${expectedDeliveryDate}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Gestational Age:</span>
                                    <span class="detail-value">${delivery.gestational_age || 'N/A'} weeks</span>
                                </div>
                            </div>
                            
                            <div class="delivery-details">
                                <h4>Delivery Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Delivery ID:</span>
                                    <span class="detail-value">${delivery.delivery_id}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Delivery Date:</span>
                                    <span class="detail-value">${deliveryDate}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Mode of Delivery:</span>
                                    <span class="detail-value">
                                        <span class="badge badge-${delivery.mode_of_delivery ? delivery.mode_of_delivery.toLowerCase().replace('-', '') : 'normal'}">
                                            ${delivery.mode_of_delivery || 'N/A'}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="delivery-details">
                                <h4>Baby Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Baby Weight:</span>
                                    <span class="detail-value">${delivery.baby_weight || 'N/A'} kg</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Baby Gender:</span>
                                    <span class="detail-value">
                                        ${delivery.baby_gender ? `
                                            <span class="baby-badge ${delivery.baby_gender.toLowerCase()}">
                                                <i class="fas fa-${delivery.baby_gender === 'Male' ? 'mars' : 'venus'}"></i>
                                                ${delivery.baby_gender}
                                            </span>
                                        ` : 'N/A'}
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">APGAR Score:</span>
                                    <span class="detail-value ${apgarClass}">${delivery.apgar_score || 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div class="delivery-details">
                                <h4>Clinical Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Complications:</span>
                                    <span class="detail-value">${delivery.complications || 'None reported'}</span>
                                </div>
                                ${delivery.complications ? `
                                    <div class="complication-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Complications Reported:</strong> ${delivery.complications}
                                    </div>
                                ` : ''}
                                <div class="detail-item">
                                    <span class="detail-label">Recorded By:</span>
                                    <span class="detail-value">${delivery.recorded_by_name || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Recorded On:</span>
                                    <span class="detail-value">${delivery.recorded_on || 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 20px;">
                                <button class="btn btn-primary" onclick="editDelivery(${delivery.delivery_id})">
                                    <i class="fas fa-edit"></i> Edit This Record
                                </button>
                            </div>
                        `;
                        
                        document.getElementById('deliveryDetailsContent').innerHTML = content;
                    } else {
                        document.getElementById('deliveryDetailsContent').innerHTML = `
                            <div style="text-align: center; padding: 40px; color: var(--error);">
                                <i class="fas fa-exclamation-circle" style="font-size: 2rem;"></i>
                                <p>Delivery record not found.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching delivery details:', error);
                    document.getElementById('deliveryDetailsContent').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: var(--error);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                            <p>Error loading delivery details. Please try again.</p>
                        </div>
                    `;
                });
        }

        // Edit delivery - FETCH FROM DATABASE
        function editDelivery(deliveryId) {
            // Close view modal first
            closeViewDeliveryModal();
            
            // Show loading state
            setTimeout(() => {
                // Fetch delivery data for editing
                fetch(`deliveries.php?action=get_delivery_edit&delivery_id=${deliveryId}`)
                    .then(response => response.json())
                    .then(delivery => {
                        if (delivery) {
                            // Create edit form
                            const editForm = `
                                <form method="POST">
                                    <input type="hidden" name="delivery_id" value="${delivery.delivery_id}">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Patient</label>
                                        <input type="text" class="form-control" value="${delivery.first_name} ${delivery.last_name}" disabled>
                                        <small style="color: var(--text-secondary);">Patient information cannot be changed</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="edit_delivery_date">Delivery Date *</label>
                                        <input type="date" class="form-control" id="edit_delivery_date" name="delivery_date" required 
                                               value="${delivery.delivery_date}">
                                    </div>
                                    
                                    <h4 style="margin: 20px 0 15px 0; color: var(--primary);">Delivery Information</h4>
                                    <div class="delivery-grid">
                                        <div class="form-group">
                                            <label class="form-label" for="edit_mode_of_delivery">Mode of Delivery *</label>
                                            <select class="form-control" id="edit_mode_of_delivery" name="mode_of_delivery" required>
                                                <option value="Normal" ${delivery.mode_of_delivery === 'Normal' ? 'selected' : ''}>Normal Vaginal Delivery</option>
                                                <option value="C-Section" ${delivery.mode_of_delivery === 'C-Section' ? 'selected' : ''}>Cesarean Section</option>
                                                <option value="Assisted" ${delivery.mode_of_delivery === 'Assisted' ? 'selected' : ''}>Assisted Delivery (Forceps/Vacuum)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="edit_baby_weight">Baby Weight (kg) *</label>
                                            <input type="number" class="form-control" id="edit_baby_weight" name="baby_weight" 
                                                   step="0.01" min="0.5" max="5.0" required value="${delivery.baby_weight || ''}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="edit_baby_gender">Baby Gender *</label>
                                            <select class="form-control" id="edit_baby_gender" name="baby_gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male" ${delivery.baby_gender === 'Male' ? 'selected' : ''}>Male</option>
                                                <option value="Female" ${delivery.baby_gender === 'Female' ? 'selected' : ''}>Female</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="edit_apgar_score">APGAR Score (1-10)</label>
                                            <input type="number" class="form-control" id="edit_apgar_score" name="apgar_score" 
                                                   min="1" max="10" value="${delivery.apgar_score || ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="edit_complications">Complications & Notes</label>
                                        <textarea class="form-control" id="edit_complications" name="complications" rows="3">${delivery.complications || ''}</textarea>
                                    </div>
                                    
                                    <div class="form-group" style="display: flex; gap: 10px;">
                                        <button type="submit" name="update_delivery" class="btn btn-success" style="flex: 1;">
                                            <i class="fas fa-save"></i> Update Delivery Record
                                        </button>
                                        <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeEditModal()">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            `;
                            
                            // Create edit modal
                            const editModal = document.createElement('div');
                            editModal.className = 'modal';
                            editModal.id = 'editDeliveryModal';
                            editModal.style.display = 'block';
                            editModal.innerHTML = `
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3 class="modal-title">Edit Delivery Record</h3>
                                        <span class="close" onclick="closeEditModal()">&times;</span>
                                    </div>
                                    ${editForm}
                                </div>
                            `;
                            
                            document.body.appendChild(editModal);
                            
                            // Close edit modal when clicking outside
                            editModal.onclick = function(event) {
                                if (event.target === editModal) {
                                    closeEditModal();
                                }
                            };
                        } else {
                            alert('Error: Delivery record not found.');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching delivery data:', error);
                        alert('Error loading delivery data. Please try again.');
                    });
            }, 300);
        }

        // Close edit modal
        function closeEditModal() {
            const editModal = document.getElementById('editDeliveryModal');
            if (editModal) {
                editModal.remove();
            }
        }

        // Auto-set delivery date to today for new records
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('delivery_date').value = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>