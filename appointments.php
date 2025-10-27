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
    if (isset($_POST['add_appointment'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
        $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
        $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        $appointment_datetime = $appointment_date . ' ' . $appointment_time . ':00';

        $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, type, status) 
                        VALUES ('$patient_id', '$doctor_id', '$appointment_datetime', '$type', 'Scheduled')";
        
        if (mysqli_query($conn, $insert_query)) {
            $success = "Appointment scheduled successfully!";
        } else {
            $error = "Error scheduling appointment: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_appointment_status'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $update_query = "UPDATE appointments SET status = '$status' WHERE appointment_id = '$appointment_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Appointment status updated successfully!";
        } else {
            $error = "Error updating appointment: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_appointment'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        
        $delete_query = "DELETE FROM appointments WHERE appointment_id = '$appointment_id'";
        
        if (mysqli_query($conn, $delete_query)) {
            $success = "Appointment deleted successfully!";
        } else {
            $error = "Error deleting appointment: " . mysqli_error($conn);
        }
    }
}

// Get all appointments with patient and doctor details
$appointments_query = "
    SELECT a.*, 
           p.first_name, p.last_name, p.contact_number,
           pr.gestational_age,
           u.username as doctor_name,
           u2.username as patient_username
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN pregnancies pr ON p.patient_id = pr.patient_id AND pr.current_status = 'Active'
    LEFT JOIN users u ON a.doctor_id = u.user_id
    LEFT JOIN users u2 ON p.registered_by = u2.user_id
    ORDER BY a.appointment_date DESC
";
$appointments_result = mysqli_query($conn, $appointments_query);

// Get doctors for dropdown
$doctors_query = "SELECT user_id, username FROM users WHERE role = 'Doctor'";
$doctors_result = mysqli_query($conn, $doctors_query);

// Get patients for dropdown
$patients_query = "SELECT patient_id, first_name, last_name FROM patients";
$patients_result = mysqli_query($conn, $patients_query);

// Get appointment count for stats
$total_appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$scheduled_appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Scheduled'")->fetch_assoc()['count'];
$completed_appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Completed'")->fetch_assoc()['count'];
$today_appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - MaternalCare AI</title>
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
        .stat-icon.scheduled { background-color: var(--accent); }
        .stat-icon.completed { background-color: var(--success); }
        .stat-icon.today { background-color: #FF6B6B; }

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

        .badge-scheduled {
            background-color: rgba(0, 191, 166, 0.2);
            color: var(--accent);
        }

        .badge-completed {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success);
        }

        .badge-missed {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--error);
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
            max-width: 600px;
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

        .status-update-form {
            display: inline;
        }

        .upcoming-appointments {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .upcoming-appointments h3 {
            margin-bottom: 15px;
            color: white;
        }

        .appointment-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            border-left: 4px solid var(--accent);
        }

        .appointment-time {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .appointment-patient {
            margin: 5px 0;
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
                <li class="nav-item"><a href="appointments.php" class="nav-link active">Appointments</a></li>
                <li class="nav-item"><a href="visits.php" class="nav-link">ANC Visits</a></li>
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
            <h1 class="page-title">Appointment Management</h1>
            <button class="btn btn-primary" onclick="openAddAppointmentModal()">
                <i class="fas fa-plus"></i> Schedule Appointment
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
                <div class="stat-icon scheduled">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $scheduled_appointments; ?></div>
                <div class="stat-label">Scheduled</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $completed_appointments; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
        </div>

        <!-- Today's Appointments -->
        <?php
        $today_query = "
            SELECT a.*, p.first_name, p.last_name, u.username as doctor_name
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.patient_id
            LEFT JOIN users u ON a.doctor_id = u.user_id
            WHERE DATE(a.appointment_date) = CURDATE() AND a.status = 'Scheduled'
            ORDER BY a.appointment_date ASC
        ";
        $today_result = mysqli_query($conn, $today_query);
        if (mysqli_num_rows($today_result) > 0): ?>
        <div class="upcoming-appointments">
            <h3><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
            <?php while ($appointment = mysqli_fetch_assoc($today_result)): ?>
                <div class="appointment-item">
                    <div class="appointment-time">
                        <i class="fas fa-clock"></i> 
                        <?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?>
                    </div>
                    <div class="appointment-patient">
                        <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                        with Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                    </div>
                    <div class="appointment-type">
                        <?php echo $appointment['type']; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- Appointments Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">All Appointments</h2>
                <div>
                    <input type="text" class="form-control" placeholder="Search appointments..." style="width: 250px;" onkeyup="searchAppointments()" id="searchInput">
                </div>
            </div>

            <table class="table" id="appointmentsTable">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Gestational Age</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                        <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): 
                            $appointment_time = date('M j, Y h:i A', strtotime($appointment['appointment_date']));
                            $gestational_age = $appointment['gestational_age'] ? $appointment['gestational_age'] . ' weeks' : 'N/A';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                    <?php if ($appointment['contact_number']): ?>
                                        <br><small><?php echo htmlspecialchars($appointment['contact_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not Assigned'); ?></td>
                                <td><?php echo $appointment_time; ?></td>
                                <td><?php echo htmlspecialchars($appointment['type']); ?></td>
                                <td><?php echo $gestational_age; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo $appointment['status']; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <form method="POST" class="status-update-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <select name="status" class="form-control" onchange="this.form.submit()" style="width: 120px; margin-bottom: 5px;">
                                            <option value="Scheduled" <?php echo $appointment['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="Completed" <?php echo $appointment['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Missed" <?php echo $appointment['status'] == 'Missed' ? 'selected' : ''; ?>>Missed</option>
                                        </select>
                                        <input type="hidden" name="update_appointment_status">
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this appointment?')">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="delete_appointment" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                <p>No appointments found. <a href="#" onclick="openAddAppointmentModal()">Schedule your first appointment</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Appointment Modal -->
        <div id="addAppointmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Schedule New Appointment</h3>
                    <span class="close" onclick="closeAddAppointmentModal()">&times;</span>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="patient_id">Patient *</label>
                            <select class="form-control" id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                    <option value="<?php echo $patient['patient_id']; ?>">
                                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="doctor_id">Doctor *</label>
                            <select class="form-control" id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                                    <option value="<?php echo $doctor['user_id']; ?>">
                                        <?php echo htmlspecialchars($doctor['username']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
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
                            <option value="ANC Visit">ANC Visit</option>
                            <option value="Delivery Planning">Delivery Planning</option>
                            <option value="Postnatal Check">Postnatal Check</option>
                            <option value="Ultrasound">Ultrasound</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="notes">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_appointment" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-calendar-plus"></i> Schedule Appointment
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
                    Logged in as: <span><?php echo $role; ?></span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Modal functions
        function openAddAppointmentModal() {
            document.getElementById('addAppointmentModal').style.display = 'block';
            // Set minimum date to today
            document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
        }

        function closeAddAppointmentModal() {
            document.getElementById('addAppointmentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addAppointmentModal');
            if (event.target == modal) {
                closeAddAppointmentModal();
            }
        }

        // Search functionality
        function searchAppointments() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('appointmentsTable');
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

        // Set default time to next available hour
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const nextHour = now.getHours() + 1;
            const timeString = nextHour.toString().padStart(2, '0') + ':00';
            document.getElementById('appointment_time').value = timeString;
        });
    </script>
</body>
</html>