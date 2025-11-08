<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle form submission for report options
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'] ?? 'health_summary';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // Generate report based on type
    switch ($report_type) {
        case 'health_summary':
            generateHealthSummaryReport($conn, $user_id, $role);
            break;
        case 'anc_visits':
            generateANCVisitsReport($conn, $user_id, $role, $start_date, $end_date);
            break;
        case 'appointments':
            generateAppointmentsReport($conn, $user_id, $role, $start_date, $end_date);
            break;
        default:
            generateHealthSummaryReport($conn, $user_id, $role);
    }
}

// Show report options form
showReportOptionsForm();

function showReportOptionsForm()
{
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Download Health Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background: #f5f5f5;
            }

            .report-container {
                max-width: 600px;
                margin: 50px auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .report-header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #007bff;
                padding-bottom: 15px;
            }

            .report-header h1 {
                color: #333;
                margin: 0;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: bold;
                color: #333;
            }

            .form-control {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }

            .date-range {
                display: flex;
                gap: 10px;
            }

            .btn-group {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 30px;
            }

            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .btn-primary {
                background: #007bff;
                color: white;
            }

            .btn-outline {
                background: white;
                color: #333;
                border: 1px solid #ddd;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                color: #007bff;
                text-decoration: none;
                margin-bottom: 20px;
            }
        </style>
    </head>

    <body>
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="report-container">
            <div class="report-header">
                <h1><i class="fas fa-download"></i> Download Health Report</h1>
                <p>Select report type and options to generate your health report</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="report_type"><i class="fas fa-chart-bar"></i> Report Type</label>
                    <select id="report_type" name="report_type" class="form-control" required>
                        <option value="health_summary">Health Summary Report</option>
                        <option value="anc_visits">ANC Visits Report</option>
                        <option value="appointments">Appointments Report</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date"><i class="fas fa-calendar-alt"></i> Date Range (Optional)</label>
                    <div class="date-range">
                        <input type="date" id="start_date" name="start_date" class="form-control">
                        <input type="date" id="end_date" name="end_date" class="form-control">
                    </div>
                </div>

                <div class="btn-group">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="generate_report" class="btn btn-primary">
                        <i class="fas fa-download"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </body>

    </html>
<?php
    exit;
}

function generateHealthSummaryReport($conn, $user_id, $role)
{
    // For HTML output that can be printed as PDF
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="health_summary_report_' . date('Y-m-d') . '.html"');

    echo generateHTMLHealthReport($conn, $user_id, $role);
    exit;
}

function generateANCVisitsReport($conn, $user_id, $role, $start_date = null, $end_date = null)
{
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="anc_visits_report_' . date('Y-m-d') . '.html"');

    echo generateHTMLANCVisitsReport($conn, $user_id, $role, $start_date, $end_date);
    exit;
}

function generateAppointmentsReport($conn, $user_id, $role, $start_date = null, $end_date = null)
{
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="appointments_report_' . date('Y-m-d') . '.html"');

    echo generateHTMLAppointmentsReport($conn, $user_id, $role, $start_date, $end_date);
    exit;
}

function generateHTMLHealthReport($conn, $user_id, $role)
{
    ob_start();
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Health Summary Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }

            .header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .section {
                margin-bottom: 20px;
            }

            .section-title {
                background: #f5f5f5;
                padding: 10px;
                font-weight: bold;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #f2f2f2;
            }

            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <h1>MaternalCare AI - Health Summary Report</h1>
            <p>Generated on: <?php echo date('F j, Y'); ?></p>
        </div>
        <?php
        if ($role == 'Mother') {
            generatePatientHealthReport($conn, $user_id);
        } elseif ($role == 'Doctor' || $role == 'Nurse') {
            generateStaffHealthReport($conn, $user_id);
        }
        ?>
        <div class="footer">
            <p>Confidential Health Information - For Medical Use Only</p>
            <p>Generated by MaternalCare AI System</p>
        </div>
    </body>

    </html>
<?php
    return ob_get_clean();
}

function generateHTMLANCVisitsReport($conn, $user_id, $role, $start_date = null, $end_date = null)
{
    ob_start();
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>ANC Visits Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }

            .header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #f2f2f2;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <h1>ANC Visits Report</h1>
            <p>Generated on: <?php echo date('F j, Y'); ?></p>
            <?php if ($start_date && $end_date): ?>
                <p>Period: <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
            <?php endif; ?>
        </div>
        <?php
        // Get ANC visits data
        if ($role == 'Mother') {
            $patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
            $patient_result = mysqli_query($conn, $patient_query);
            $patient_data = mysqli_fetch_assoc($patient_result);

            if ($patient_data) {
                $patient_id = $patient_data['patient_id'];
                $visits_query = "
                SELECT av.*, u.username as recorded_by_name 
                FROM anc_visits av 
                LEFT JOIN users u ON av.recorded_by = u.user_id 
                WHERE av.pregnancy_id IN (SELECT pregnancy_id FROM pregnancies WHERE patient_id = '$patient_id')
                " . ($start_date && $end_date ? " AND av.visit_date BETWEEN '$start_date' AND '$end_date'" : "") . "
                ORDER BY av.visit_date DESC
            ";
            }
        } else {
            $visits_query = "
            SELECT av.*, p.first_name, p.last_name, u.username as recorded_by_name 
            FROM anc_visits av 
            LEFT JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
            LEFT JOIN patients p ON pr.patient_id = p.patient_id
            LEFT JOIN users u ON av.recorded_by = u.user_id
            " . ($start_date && $end_date ? " WHERE av.visit_date BETWEEN '$start_date' AND '$end_date'" : "") . "
            ORDER BY av.visit_date DESC
            LIMIT 50
        ";
        }

        $visits_result = mysqli_query($conn, $visits_query);

        if (mysqli_num_rows($visits_result) > 0) {
            echo '<table>';
            echo '<tr><th>Visit Date</th><th>Patient</th><th>Blood Pressure</th><th>Weight</th><th>Recorded By</th></tr>';
            while ($visit = mysqli_fetch_assoc($visits_result)) {
                $patient_name = isset($visit['first_name']) ? $visit['first_name'] . ' ' . $visit['last_name'] : 'N/A';
                echo "<tr>
                <td>{$visit['visit_date']}</td>
                <td>{$patient_name}</td>
                <td>{$visit['blood_pressure']}</td>
                <td>{$visit['weight']} kg</td>
                <td>{$visit['recorded_by_name']}</td>
            </tr>";
            }
            echo '</table>';
        } else {
            echo "<p>No ANC visits found for the selected period.</p>";
        }
        ?>
    </body>

    </html>
<?php
    return ob_get_clean();
}

function generateHTMLAppointmentsReport($conn, $user_id, $role, $start_date = null, $end_date = null)
{
    ob_start();
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Appointments Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }

            .header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #f2f2f2;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <h1>Appointments Report</h1>
            <p>Generated on: <?php echo date('F j, Y'); ?></p>
            <?php if ($start_date && $end_date): ?>
                <p>Period: <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
            <?php endif; ?>
        </div>
        <?php
        // Get appointments data
        if ($role == 'Mother') {
            $patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
            $patient_result = mysqli_query($conn, $patient_query);
            $patient_data = mysqli_fetch_assoc($patient_result);

            if ($patient_data) {
                $patient_id = $patient_data['patient_id'];
                $appointments_query = "
                SELECT a.*, u.username as doctor_name 
                FROM appointments a 
                LEFT JOIN users u ON a.doctor_id = u.user_id 
                WHERE a.patient_id = '$patient_id'
                " . ($start_date && $end_date ? " AND a.appointment_date BETWEEN '$start_date' AND '$end_date'" : "") . "
                ORDER BY a.appointment_date DESC
            ";
            }
        } else {
            $appointments_query = "
            SELECT a.*, p.first_name, p.last_name, u.username as doctor_name 
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.patient_id
            LEFT JOIN users u ON a.doctor_id = u.user_id
            " . ($start_date && $end_date ? " WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'" : "") . "
            ORDER BY a.appointment_date DESC
            LIMIT 50
        ";
        }

        $appointments_result = mysqli_query($conn, $appointments_query);

        if (mysqli_num_rows($appointments_result) > 0) {
            echo '<table>';
            echo '<tr><th>Appointment Date</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Purpose</th></tr>';
            while ($appointment = mysqli_fetch_assoc($appointments_result)) {
                $patient_name = isset($appointment['first_name']) ? $appointment['first_name'] . ' ' . $appointment['last_name'] : 'N/A';
                echo "<tr>
                <td>{$appointment['appointment_date']}</td>
                <td>{$patient_name}</td>
                <td>{$appointment['doctor_name']}</td>
                <td>{$appointment['status']}</td>
                
            </tr>";
            }
            echo '</table>';
        } else {
            echo "<p>No appointments found for the selected period.</p>";
        }
        ?>
    </body>

    </html>
    <?php
    return ob_get_clean();
}

function generatePatientHealthReport($conn, $user_id = null)
{
    if ($user_id) {
        $patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
        $patient_result = mysqli_query($conn, $patient_query);
        $patient_data = mysqli_fetch_assoc($patient_result);
    }

    if ($patient_data) {
        $patient_id = $patient_data['patient_id'];
    ?>
        <div class="section">
            <div class="section-title">Patient Information</div>
            <table>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td><?php echo $patient_data['first_name'] . ' ' . $patient_data['last_name']; ?></td>
                </tr>
                <tr>
                    <td><strong>Age:</strong></td>
                    <td><?php echo $patient_data['age']; ?> years</td>
                </tr>
                <tr>
                    <td><strong>Contact:</strong></td>
                    <td><?php echo $patient_data['phone']; ?></td>
                </tr>
                <tr>
                    <td><strong>Address:</strong></td>
                    <td><?php echo $patient_data['address']; ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Current Pregnancy Information</div>
            <?php
            $pregnancy_query = "SELECT * FROM pregnancies WHERE patient_id = '$patient_id' ORDER BY created_at DESC LIMIT 1";
            $pregnancy_result = mysqli_query($conn, $pregnancy_query);

            if ($pregnancy_data = mysqli_fetch_assoc($pregnancy_result)) {
            ?>
                <table>
                    <tr>
                        <td><strong>Gestational Age:</strong></td>
                        <td><?php echo $pregnancy_data['gestational_age']; ?> weeks</td>
                    </tr>
                    <tr>
                        <td><strong>Expected Delivery:</strong></td>
                        <td><?php echo $pregnancy_data['expected_delivery_date']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><?php echo $pregnancy_data['current_status']; ?></td>
                    </tr>
                </table>
            <?php
            } else {
                echo "<p>No pregnancy data available.</p>";
            }
            ?>
        </div>

        <div class="section">
            <div class="section-title">Recent ANC Visits</div>
            <?php
            $visits_query = "
                SELECT av.*, u.username as recorded_by_name 
                FROM anc_visits av 
                LEFT JOIN users u ON av.recorded_by = u.user_id 
                WHERE av.pregnancy_id IN (SELECT pregnancy_id FROM pregnancies WHERE patient_id = '$patient_id')
                ORDER BY av.visit_date DESC 
                LIMIT 5
            ";
            $visits_result = mysqli_query($conn, $visits_query);

            if (mysqli_num_rows($visits_result) > 0) {
            ?>
                <table>
                    <tr>
                        <th>Visit Date</th>
                        <th>Blood Pressure</th>
                        <th>Weight</th>
                        <th>Recorded By</th>
                    </tr>
                    <?php while ($visit = mysqli_fetch_assoc($visits_result)): ?>
                        <tr>
                            <td><?php echo $visit['visit_date']; ?></td>
                            <td><?php echo $visit['blood_pressure']; ?></td>
                            <td><?php echo $visit['weight']; ?> kg</td>
                            <td><?php echo $visit['recorded_by_name']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php
            } else {
                echo "<p>No ANC visits recorded.</p>";
            }
            ?>
        </div>
    <?php
    }
}

function generateStaffHealthReport($conn, $user_id)
{
    $patients_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
    $visits_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM anc_visits WHERE recorded_by = '$user_id'")->fetch_assoc()['count'];
    $high_risk_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM pregnancies WHERE current_status = 'High-Risk'")->fetch_assoc()['count'];
    ?>
    <div class="section">
        <div class="section-title">Medical Staff Summary Report</div>
        <table>
            <tr>
                <td><strong>Total Patients:</strong></td>
                <td><?php echo $patients_count; ?></td>
            </tr>
            <tr>
                <td><strong>ANC Visits Recorded:</strong></td>
                <td><?php echo $visits_count; ?></td>
            </tr>
            <tr>
                <td><strong>High-Risk Cases:</strong></td>
                <td><?php echo $high_risk_count; ?></td>
            </tr>
            <tr>
                <td><strong>Report Period:</strong></td>
                <td><?php echo date('F Y'); ?></td>
            </tr>
        </table>
    </div>
<?php
}
?>