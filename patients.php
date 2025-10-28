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
    if (isset($_POST['add_patient'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $medical_history = mysqli_real_escape_string($conn, $_POST['medical_history']);
        $obstetric_history = mysqli_real_escape_string($conn, $_POST['obstetric_history']);

        $insert_query = "INSERT INTO patients (first_name, last_name, dob, contact_number, address, medical_history, obstetric_history, registered_by) 
                        VALUES ('$first_name', '$last_name', '$dob', '$contact_number', '$address', '$medical_history', '$obstetric_history', '$user_id')";
        
        if (mysqli_query($conn, $insert_query)) {
            $success = "Patient added successfully!";
        } else {
            $error = "Error adding patient: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_patient'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $medical_history = mysqli_real_escape_string($conn, $_POST['medical_history']);
        $obstetric_history = mysqli_real_escape_string($conn, $_POST['obstetric_history']);

        $update_query = "UPDATE patients SET 
                        first_name = '$first_name',
                        last_name = '$last_name',
                        dob = '$dob',
                        contact_number = '$contact_number',
                        address = '$address',
                        medical_history = '$medical_history',
                        obstetric_history = '$obstetric_history'
                        WHERE patient_id = '$patient_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Patient updated successfully!";
        } else {
            $error = "Error updating patient: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_patient'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        
        $delete_query = "DELETE FROM patients WHERE patient_id = '$patient_id'";
        
        if (mysqli_query($conn, $delete_query)) {
            $success = "Patient deleted successfully!";
        } else {
            $error = "Error deleting patient: " . mysqli_error($conn);
        }
    }
}

// Get all patients with their latest pregnancy status
$patients_query = "
    SELECT p.*, 
           pr.current_status as pregnancy_status,
           pr.gestational_age,
           pr.expected_delivery_date,
           u.username as registered_by_name
    FROM patients p
    LEFT JOIN (
        SELECT pregnancy_id, patient_id, current_status, gestational_age, expected_delivery_date,
               ROW_NUMBER() OVER (PARTITION BY patient_id ORDER BY created_at DESC) as rn
        FROM pregnancies
    ) pr ON p.patient_id = pr.patient_id AND pr.rn = 1
    LEFT JOIN users u ON p.registered_by = u.user_id
    ORDER BY p.created_at DESC
";
$patients_result = mysqli_query($conn, $patients_query);

// Get patient count for stats
$total_patients = mysqli_query($conn, "SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
$active_pregnancies = mysqli_query($conn, "SELECT COUNT(*) as count FROM pregnancies WHERE current_status = 'Active'")->fetch_assoc()['count'];
$high_risk_patients = mysqli_query($conn, "SELECT COUNT(*) as count FROM pregnancies WHERE current_status = 'High-Risk'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - MaternalCare AI</title>
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

        /* Patient Management Styles */
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

        .stat-icon.patients { background-color: var(--primary); }
        .stat-icon.active { background-color: var(--success); }
        .stat-icon.risk { background-color: var(--error); }

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

        .badge-active {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success);
        }

        .badge-high-risk {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--error);
        }

        .badge-completed {
            background-color: rgba(108, 117, 125, 0.2);
            color: var(--text-secondary);
        }

        .badge-none {
            background-color: rgba(108, 117, 125, 0.2);
            color: var(--text-secondary);
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
                <li class="nav-item"><a href="patients.php" class="nav-link active">Patients</a></li>
                <li class="nav-item"><a href="appointments.php" class="nav-link">Appointments</a></li>
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
            <h1 class="page-title">Patient Management</h1>
            <button class="btn btn-primary" onclick="openAddPatientModal()">
                <i class="fas fa-plus"></i> Add New Patient
            </button>
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
                <div class="stat-icon active">
                    <i class="fas fa-baby"></i>
                </div>
                <div class="stat-value"><?php echo $active_pregnancies; ?></div>
                <div class="stat-label">Active Pregnancies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon risk">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $high_risk_patients; ?></div>
                <div class="stat-label">High-Risk Cases</div>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">All Patients</h2>
                <div>
                    <input type="text" class="form-control" placeholder="Search patients..." style="width: 250px;" onkeyup="searchPatients()" id="searchInput">
                </div>
            </div>

            <table class="table" id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Contact</th>
                        <th>Pregnancy Status</th>
                        <th>Gestational Age</th>
                        <th>Registered By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($patients_result) > 0): ?>
                        <?php while ($patient = mysqli_fetch_assoc($patients_result)): 
                            $age = $patient['dob'] ? date_diff(date_create($patient['dob']), date_create('today'))->y : 'N/A';
                            $pregnancy_status = $patient['pregnancy_status'] ?? 'None';
                            $gestational_age = $patient['gestational_age'] ? $patient['gestational_age'] . ' weeks' : 'N/A';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['first_name']); ?></strong>
                                </td>
                                <td><?php echo $age; ?></td>
                                <td><?php echo htmlspecialchars($patient['contact_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $pregnancy_status == 'Active' ? 'active' : 
                                             ($pregnancy_status == 'High-Risk' ? 'high-risk' : 
                                             ($pregnancy_status == 'Completed' ? 'completed' : 'none')); 
                                    ?>">
                                        <?php echo $pregnancy_status; ?>
                                    </span>
                                </td>
                                <td><?php echo $gestational_age; ?></td>
                                <td><?php echo htmlspecialchars($patient['registered_by_name'] ?? 'System'); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-outline btn-sm" onclick="viewPatient(<?php echo $patient['patient_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="editPatient(<?php echo $patient['patient_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this patient?')">
                                        <input type="hidden" name="patient_id" value="<?php echo $patient['patient_id']; ?>">
                                        <button type="submit" name="delete_patient" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                <p>No patients found. <a href="#" onclick="openAddPatientModal()">Add your first patient</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Patient Modal -->
        <div id="addPatientModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New Patient</h3>
                    <span class="close" onclick="closeAddPatientModal()">&times;</span>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="dob">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_number">Contact Number</label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="medical_history">Medical History</label>
                        <textarea class="form-control" id="medical_history" name="medical_history" rows="3" placeholder="Any pre-existing conditions, allergies, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="obstetric_history">Obstetric History</label>
                        <textarea class="form-control" id="obstetric_history" name="obstetric_history" rows="3" placeholder="Previous pregnancies, deliveries, complications, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_patient" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-save"></i> Save Patient
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
        function openAddPatientModal() {
            document.getElementById('addPatientModal').style.display = 'block';
        }

        function closeAddPatientModal() {
            document.getElementById('addPatientModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addPatientModal');
            if (event.target == modal) {
                closeAddPatientModal();
            }
        }

        // Search functionality
        function searchPatients() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('patientsTable');
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

        // View patient details (placeholder function)
        function viewPatient(patientId) {
            alert('View patient details for ID: ' + patientId + '\nThis would open a detailed view in a complete implementation.');
            // In a complete implementation, this would redirect to patient-details.php?id=patientId
        }

        // Edit patient (placeholder function)
        function editPatient(patientId) {
            alert('Edit patient with ID: ' + patientId + '\nThis would open an edit form in a complete implementation.');
            // In a complete implementation, this would open an edit modal or redirect to edit-patient.php?id=patientId
        }
    </script>
</body>
</html>