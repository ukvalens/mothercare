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

// Get patient data - FIXED: Use registered_by instead of user_id
$patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
$patient_result = mysqli_query($conn, $patient_query);
$patient_data = mysqli_fetch_assoc($patient_result);

// AUTO-CREATE PATIENT RECORD IF NOT EXISTS
if (!$patient_data) {
    // Create a patient record automatically
    $create_patient_query = "INSERT INTO patients (first_name, last_name, contact_number, registered_by) 
                            VALUES ('$username', 'User', '{$user_data['phone']}', '$user_id')";
    
    if (mysqli_query($conn, $create_patient_query)) {
        $patient_id = mysqli_insert_id($conn);
        $patient_data = ['patient_id' => $patient_id];
        // Refresh the page to load the new patient data
        header("Location: my-appointments.php");
        exit();
    } else {
        $error = "Error creating patient profile: " . mysqli_error($conn);
    }
}

$patient_id = $patient_data['patient_id'] ?? null;

// Initialize variables to prevent undefined errors
$upcoming_result = null;
$appointments_result = null;
$total_appointments = 0;
$upcoming_count = 0;
$completed_count = 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['book_appointment'])) {
        $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
        $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        $appointment_datetime = $appointment_date . ' ' . $appointment_time . ':00';

        // Get a random doctor for the appointment
        $doctor_query = "SELECT user_id FROM users WHERE role = 'Doctor' ORDER BY RAND() LIMIT 1";
        $doctor_result = mysqli_query($conn, $doctor_query);
        
        if ($doctor_result && mysqli_num_rows($doctor_result) > 0) {
            $doctor_data = mysqli_fetch_assoc($doctor_result);
            $doctor_id = $doctor_data['user_id'];
        } else {
            $doctor_id = null;
        }

        if ($doctor_id && $patient_id) {
            $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, type, status, notes) 
                            VALUES ('$patient_id', '$doctor_id', '$appointment_datetime', '$type', 'Scheduled', '$notes')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = "Appointment booked successfully!";
            } else {
                $error = "Error booking appointment: " . mysqli_error($conn);
            }
        } else {
            // More specific error message
            if (!$patient_id) {
                $error = "Patient record not found. Please contact support to set up your patient profile.";
            } else {
                $error = "No doctors available at the moment. Please try again later or contact the clinic.";
            }
        }
    }
    
    if (isset($_POST['cancel_appointment'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        $reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']);

        $update_query = "UPDATE appointments SET status = 'Cancelled', notes = CONCAT(COALESCE(notes, ''), ' [Cancelled: $reason]') WHERE appointment_id = '$appointment_id' AND patient_id = '$patient_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Appointment cancelled successfully!";
        } else {
            $error = "Error cancelling appointment: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['reschedule_appointment'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        $new_date = mysqli_real_escape_string($conn, $_POST['new_appointment_date']);
        $new_time = mysqli_real_escape_string($conn, $_POST['new_appointment_time']);
        
        $new_datetime = $new_date . ' ' . $new_time . ':00';

        $update_query = "UPDATE appointments SET appointment_date = '$new_datetime', status = 'Scheduled', notes = CONCAT(COALESCE(notes, ''), ' [Rescheduled to $new_datetime]') WHERE appointment_id = '$appointment_id' AND patient_id = '$patient_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Appointment rescheduled successfully!";
        } else {
            $error = "Error rescheduling appointment: " . mysqli_error($conn);
        }
    }
}

// Get patient's appointments
if ($patient_id) {
    $appointments_query = "
        SELECT a.*, u.username as doctor_name, u.email as doctor_email
        FROM appointments a
        LEFT JOIN users u ON a.doctor_id = u.user_id
        WHERE a.patient_id = '$patient_id'
        ORDER BY a.appointment_date DESC
    ";
    $appointments_result = mysqli_query($conn, $appointments_query);
}

// Get upcoming appointments (next 30 days)
if ($patient_id) {
    $upcoming_query = "
        SELECT a.*, u.username as doctor_name
        FROM appointments a
        LEFT JOIN users u ON a.doctor_id = u.user_id
        WHERE a.patient_id = '$patient_id' 
        AND a.appointment_date >= CURDATE() 
        AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND a.status = 'Scheduled'
        ORDER BY a.appointment_date ASC
    ";
    $upcoming_result = mysqli_query($conn, $upcoming_query);
}

// Get appointment statistics
if ($patient_id) {
    $total_appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE patient_id = '$patient_id'")->fetch_assoc()['count'];
    $upcoming_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE patient_id = '$patient_id' AND status = 'Scheduled' AND appointment_date >= CURDATE()")->fetch_assoc()['count'];
    $completed_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE patient_id = '$patient_id' AND status = 'Completed'")->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - MaternalCare AI</title>
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

        /* Appointment Management Styles */
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
        .stat-icon.upcoming { background-color: var(--accent); }
        .stat-icon.completed { background-color: var(--success); }

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

        .upcoming-appointments {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .appointment-list {
            display: grid;
            gap: 15px;
        }

        .appointment-card {
            background-color: var(--background);
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid var(--accent);
            transition: all 0.3s;
        }

        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow);
        }

        .appointment-card.upcoming {
            border-left-color: var(--accent);
        }

        .appointment-card.completed {
            border-left-color: var(--success);
        }

        .appointment-card.cancelled {
            border-left-color: var(--error);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .appointment-date {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary);
        }

        .appointment-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-scheduled { background-color: rgba(0, 191, 166, 0.2); color: var(--accent); }
        .status-completed { background-color: rgba(42, 157, 143, 0.2); color: var(--success); }
        .status-cancelled { background-color: rgba(230, 57, 70, 0.2); color: var(--error); }
        .status-missed { background-color: rgba(108, 117, 125, 0.2); color: var(--text-secondary); }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }

        .detail-value {
            font-weight: 500;
        }

        .appointment-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            max-width: 500px;
            max-height: 80vh;
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

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .appointment-actions {
                flex-direction: column;
            }
            
            .appointment-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .appointment-details {
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
                <li class="nav-item"><a href="my-appointments.php" class="nav-link active">My Appointments</a></li>
                <li class="nav-item"><a href="health-records.php" class="nav-link">Health Records</a></li>
                <li class="nav-item"><a href="messages.php" class="nav-link">Messages</a></li>
                <li class="nav-item"><a href="pregnancy-tracker.php" class="nav-link">Pregnancy Tracker</a></li>
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

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h2>My Appointments</h2>
            <p>Manage your prenatal appointments and track your healthcare schedule</p>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Appointment Management</h1>
            <button class="btn btn-primary" onclick="openBookAppointmentModal()">
                <i class="fas fa-plus"></i> Book New Appointment
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon upcoming">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $upcoming_count; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $completed_count; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="upcoming-appointments">
            <h3 class="section-title">Upcoming Appointments</h3>
            <?php if ($upcoming_result && mysqli_num_rows($upcoming_result) > 0): ?>
                <div class="appointment-list">
                    <?php while ($appointment = mysqli_fetch_assoc($upcoming_result)): 
                        $appointment_time = date('M j, Y \a\t h:i A', strtotime($appointment['appointment_date']));
                        $is_today = date('Y-m-d') == date('Y-m-d', strtotime($appointment['appointment_date']));
                    ?>
                        <div class="appointment-card upcoming <?php echo $is_today ? 'today' : ''; ?>">
                            <div class="appointment-header">
                                <div class="appointment-date">
                                    <?php if ($is_today): ?>
                                        <i class="fas fa-calendar-day" style="color: var(--accent);"></i>
                                    <?php endif; ?>
                                    <?php echo $appointment_time; ?>
                                    <?php if ($is_today): ?>
                                        <span style="color: var(--accent); font-size: 0.9rem;">(Today)</span>
                                    <?php endif; ?>
                                </div>
                                <span class="appointment-status status-scheduled">Scheduled</span>
                            </div>
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <span class="detail-label">Doctor</span>
                                    <span class="detail-value">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Appointment Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appointment['type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value">Confirmed</span>
                                </div>
                            </div>
                            <div class="appointment-actions">
                                <button class="btn btn-outline btn-sm" onclick="viewAppointmentDetails(<?php echo $appointment['appointment_id']; ?>)">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                                <button class="btn btn-outline btn-sm" onclick="openRescheduleModal(<?php echo $appointment['appointment_id']; ?>)">
                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="openCancelModal(<?php echo $appointment['appointment_id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <h4>No Upcoming Appointments</h4>
                    <p>You don't have any scheduled appointments. Book your first appointment to get started.</p>
                    <button class="btn btn-primary" onclick="openBookAppointmentModal()" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Book Appointment
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Appointments -->
        <div class="form-container">
            <h3 class="form-title">Appointment History</h3>
            <?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
                <div class="appointment-list">
                    <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): 
                        $appointment_time = date('M j, Y \a\t h:i A', strtotime($appointment['appointment_date']));
                        $status_class = 'status-' . strtolower($appointment['status']);
                        $card_class = strtolower($appointment['status']);
                    ?>
                        <div class="appointment-card <?php echo $card_class; ?>">
                            <div class="appointment-header">
                                <div class="appointment-date"><?php echo $appointment_time; ?></div>
                                <span class="appointment-status <?php echo $status_class; ?>"><?php echo $appointment['status']; ?></span>
                            </div>
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <span class="detail-label">Doctor</span>
                                    <span class="detail-value">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appointment['type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value"><?php echo $appointment['status']; ?></span>
                                </div>
                            </div>
                            <?php if ($appointment['status'] == 'Scheduled'): ?>
                                <div class="appointment-actions">
                                    <button class="btn btn-outline btn-sm" onclick="viewAppointmentDetails(<?php echo $appointment['appointment_id']; ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="openRescheduleModal(<?php echo $appointment['appointment_id']; ?>)">
                                        <i class="fas fa-calendar-alt"></i> Reschedule
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="openCancelModal(<?php echo $appointment['appointment_id']; ?>)">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h4>No Appointment History</h4>
                    <p>Your appointment history will appear here once you book appointments.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Book Appointment Modal -->
        <div id="bookAppointmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Book New Appointment</h3>
                    <span class="close" onclick="closeBookAppointmentModal()">&times;</span>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="appointment_date">Date *</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="appointment_time">Time *</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="type">Appointment Type *</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="ANC Visit">Routine ANC Visit</option>
                            <option value="Ultrasound">Ultrasound Scan</option>
                            <option value="Consultation">Doctor Consultation</option>
                            <option value="Blood Test">Blood Tests</option>
                            <option value="Vaccination">Vaccination</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="notes">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any specific concerns or questions for the doctor..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="book_appointment" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-calendar-plus"></i> Book Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cancel Appointment Modal -->
        <div id="cancelAppointmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Cancel Appointment</h3>
                    <span class="close" onclick="closeCancelModal()">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" id="cancel_appointment_id" name="appointment_id">
                    <div class="form-group">
                        <label class="form-label" for="cancel_reason">Reason for Cancellation *</label>
                        <select class="form-control" id="cancel_reason" name="cancel_reason" required>
                            <option value="">Select Reason</option>
                            <option value="Not feeling well">Not feeling well</option>
                            <option value="Transportation issues">Transportation issues</option>
                            <option value="Family emergency">Family emergency</option>
                            <option value="Schedule conflict">Schedule conflict</option>
                            <option value="Other">Other reason</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cancel_notes">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="cancel_notes" name="cancel_notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="cancel_appointment" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-times"></i> Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reschedule Appointment Modal -->
        <div id="rescheduleAppointmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Reschedule Appointment</h3>
                    <span class="close" onclick="closeRescheduleModal()">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" id="reschedule_appointment_id" name="appointment_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="new_appointment_date">New Date *</label>
                            <input type="date" class="form-control" id="new_appointment_date" name="new_appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new_appointment_time">New Time *</label>
                            <input type="time" class="form-control" id="new_appointment_time" name="new_appointment_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="reschedule_appointment" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-calendar-alt"></i> Confirm Reschedule
                        </button>
                    </div>
                </form>
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
                    Logged in as: <span>Mother</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Modal functions
        function openBookAppointmentModal() {
            document.getElementById('bookAppointmentModal').style.display = 'block';
            // Set default time to next available hour
            const now = new Date();
            const nextHour = now.getHours() + 1;
            const timeString = nextHour.toString().padStart(2, '0') + ':00';
            document.getElementById('appointment_time').value = timeString;
        }

        function closeBookAppointmentModal() {
            document.getElementById('bookAppointmentModal').style.display = 'none';
        }

        function openCancelModal(appointmentId) {
            document.getElementById('cancel_appointment_id').value = appointmentId;
            document.getElementById('cancelAppointmentModal').style.display = 'block';
        }

        function closeCancelModal() {
            document.getElementById('cancelAppointmentModal').style.display = 'none';
        }

        function openRescheduleModal(appointmentId) {
            document.getElementById('reschedule_appointment_id').value = appointmentId;
            document.getElementById('rescheduleAppointmentModal').style.display = 'block';
        }

        function closeRescheduleModal() {
            document.getElementById('rescheduleAppointmentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['bookAppointmentModal', 'cancelAppointmentModal', 'rescheduleAppointmentModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // View appointment details
        function viewAppointmentDetails(appointmentId) {
            alert('Appointment details for ID: ' + appointmentId + '\nThis would show detailed appointment information in a complete implementation.');
            // In a complete implementation, this would open a modal with appointment details
        }

        // Set default appointment time to next available hour
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const nextHour = now.getHours() + 1;
            const timeString = nextHour.toString().padStart(2, '0') + ':00';
            
            // Set for book appointment modal
            const appointmentTime = document.getElementById('appointment_time');
            if (appointmentTime) {
                appointmentTime.value = timeString;
            }
            
            // Set for reschedule modal
            const newAppointmentTime = document.getElementById('new_appointment_time');
            if (newAppointmentTime) {
                newAppointmentTime.value = timeString;
            }
        });

        // Add reminder for today's appointments
        document.addEventListener('DOMContentLoaded', function() {
            const todayAppointments = document.querySelectorAll('.appointment-card.today');
            if (todayAppointments.length > 0) {
                console.log('You have ' + todayAppointments.length + ' appointment(s) today!');
                // In a complete implementation, this could show a browser notification
            }
        });
    </script>
</body>
</html>