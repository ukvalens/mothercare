<?php
session_start();
include 'connection.php';

// Check if user is logged in and is a mother
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Mother') {
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

// Get patient data
$patient_query = "SELECT * FROM patients WHERE registered_by = '$user_id'";
$patient_result = mysqli_query($conn, $patient_query);
$patient_data = mysqli_fetch_assoc($patient_result);

$patient_id = $patient_data['patient_id'] ?? null;

// Initialize pregnancy data to prevent null errors
$pregnancy_data = null;
$pregnancy_id = null;

// Get current pregnancy data only if patient exists
if ($patient_id) {
    $pregnancy_query = "SELECT * FROM pregnancies WHERE patient_id = '$patient_id' ORDER BY created_at DESC LIMIT 1";
    $pregnancy_result = mysqli_query($conn, $pregnancy_query);
    $pregnancy_data = mysqli_fetch_assoc($pregnancy_result);
    $pregnancy_id = $pregnancy_data['pregnancy_id'] ?? null;
}

// Get recent ANC visits only if pregnancy exists
$visits_result = null;
if ($pregnancy_id) {
    $visits_query = "SELECT * FROM anc_visits WHERE pregnancy_id = '$pregnancy_id' ORDER BY visit_date DESC LIMIT 5";
    $visits_result = mysqli_query($conn, $visits_query);
}

// Get next appointment only if patient exists
$appointment_result = null;
$next_appointment = null;
if ($patient_id) {
    $appointment_query = "SELECT a.*, u.username as doctor_name 
                         FROM appointments a 
                         LEFT JOIN users u ON a.doctor_id = u.user_id 
                         WHERE a.patient_id = '$patient_id' 
                         AND a.status = 'Scheduled' 
                         ORDER BY a.appointment_date ASC LIMIT 1";
    $appointment_result = mysqli_query($conn, $appointment_query);
    $next_appointment = mysqli_fetch_assoc($appointment_result);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['start_pregnancy'])) {
        $edd = mysqli_real_escape_string($conn, $_POST['expected_delivery_date']);
        $lmp = mysqli_real_escape_string($conn, $_POST['last_menstrual_period']);
        
        // Calculate gestational age from LMP
        $lmp_date = new DateTime($lmp);
        $today = new DateTime();
        $gestational_age = $lmp_date->diff($today)->days;
        $gestational_weeks = floor($gestational_age / 7);
        
        // Only insert if patient_id exists
        if ($patient_id) {
            $insert_query = "INSERT INTO pregnancies (patient_id, gestational_age, expected_delivery_date, current_status) 
                            VALUES ('$patient_id', '$gestational_weeks', '$edd', 'Active')";
            
            if (mysqli_query($conn, $insert_query)) {
                header("Location: pregnancy-tracker.php?success=pregnancy_started");
                exit();
            } else {
                $error = "Error starting pregnancy tracking: " . mysqli_error($conn);
            }
        } else {
            $error = "Patient record not found. Please contact support.";
        }
    }
    
    if (isset($_POST['add_symptom'])) {
        $symptom = mysqli_real_escape_string($conn, $_POST['symptom']);
        $intensity = mysqli_real_escape_string($conn, $_POST['intensity']);
        $notes = mysqli_real_escape_string($conn, $_POST['symptom_notes']);
        
        // Only add symptom if pregnancy exists
        if ($pregnancy_id) {
            // Store symptoms in anc_visits notes for now, since symptoms table doesn't exist
            $insert_query = "INSERT INTO anc_visits (pregnancy_id, visit_date, notes, recorded_by) 
                            VALUES ('$pregnancy_id', NOW(), 
                            CONCAT('Symptom: ', '$symptom', ', Intensity: ', '$intensity', ', Notes: ', '$notes'), '$user_id')";
            
            if (mysqli_query($conn, $insert_query)) {
                header("Location: pregnancy-tracker.php?success=symptom_added");
                exit();
            } else {
                $error = "Error recording symptom: " . mysqli_error($conn);
            }
        } else {
            $error = "No active pregnancy found. Please start pregnancy tracking first.";
        }
    }
    
    if (isset($_POST['add_kick_count'])) {
        $kick_count = mysqli_real_escape_string($conn, $_POST['kick_count']);
        $duration = mysqli_real_escape_string($conn, $_POST['duration']);
        $time_of_day = mysqli_real_escape_string($conn, $_POST['time_of_day']);
        
        // Only add kick count if pregnancy exists
        if ($pregnancy_id) {
            // Store kick counts in anc_visits notes for now, since kick_counts table doesn't exist
            $insert_query = "INSERT INTO anc_visits (pregnancy_id, visit_date, notes, recorded_by) 
                            VALUES ('$pregnancy_id', NOW(), 
                            CONCAT('Kick Count: ', '$kick_count', ', Duration: ', '$duration', ' mins, Time: ', '$time_of_day'), '$user_id')";
            
            if (mysqli_query($conn, $insert_query)) {
                header("Location: pregnancy-tracker.php?success=kick_count_added");
                exit();
            } else {
                $error = "Error recording kick count: " . mysqli_error($conn);
            }
        } else {
            $error = "No active pregnancy found. Please start pregnancy tracking first.";
        }
    }
}

// Calculate pregnancy progress only if pregnancy exists
$progress = 0;
$weeks_remaining = 0;
$recent_entries_result = null;

if ($pregnancy_data) {
    $edd = new DateTime($pregnancy_data['expected_delivery_date']);
    $today = new DateTime();
    $total_days = 280; // 40 weeks
    $days_passed = $today->diff(new DateTime($pregnancy_data['expected_delivery_date']))->days;
    $progress = min(100, max(0, (($total_days - $days_passed) / $total_days) * 100));
    $weeks_remaining = floor($days_passed / 7);
    
    // Get recent symptoms and kick counts from anc_visits notes
    $recent_entries_query = "SELECT visit_date, notes FROM anc_visits 
                            WHERE pregnancy_id = '$pregnancy_id' 
                            AND notes IS NOT NULL 
                            ORDER BY visit_date DESC LIMIT 10";
    $recent_entries_result = mysqli_query($conn, $recent_entries_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pregnancy Tracker - MaternalCare AI</title>
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

        /* Pregnancy Tracker Specific Styles */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-banner h1 {
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .welcome-banner p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .progress-section {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .progress-title {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .progress-bar-container {
            background-color: var(--background);
            border-radius: 20px;
            height: 20px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--accent), var(--primary));
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s ease;
            position: relative;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background-color: var(--background);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .tracker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .tracker-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .tracker-card h3 {
            color: var(--primary);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--background);
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

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
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

        .symptoms-list, .kick-history {
            max-height: 300px;
            overflow-y: auto;
        }

        .symptom-item, .kick-item {
            background-color: var(--background);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid var(--accent);
        }

        .symptom-header, .kick-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .symptom-name {
            font-weight: 600;
            color: var(--primary);
        }

        .symptom-intensity {
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 12px;
            background-color: var(--accent);
            color: white;
        }

        .baby-development {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .baby-development h3 {
            color: white;
            margin-bottom: 15px;
        }

        .development-milestone {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .milestone-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
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

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .badge-high {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--error);
        }

        .badge-low {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success);
        }

        .hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .tracker-grid {
                grid-template-columns: 1fr;
            }
            
            .progress-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 576px) {
            .progress-stats {
                grid-template-columns: 1fr;
            }
            
            .welcome-banner h1 {
                font-size: 2rem;
            }
        }
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
                <!-- Patient Navigation -->
                <div class="patient-nav" style="display: <?php echo $role == 'Mother' ? 'flex' : 'none'; ?>;">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">My Dashboard</a></li>
                    <li class="nav-item"><a href="my-appointments.php" class="nav-link">My Appointments</a></li>
                    <li class="nav-item"><a href="pregnancy-tracker.php" class="nav-link active">Pregnancy Tracker</a></li>
                    <li class="nav-item"><a href="health-records.php" class="nav-link">Health Records</a></li>
                    <li class="nav-item"><a href="messages.php" class="nav-link">Messages</a></li>
                </div>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Alert Container -->
            <div id="alert-container"></div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    switch ($_GET['success']) {
                        case 'pregnancy_started':
                            echo "Pregnancy tracking started successfully!";
                            break;
                        case 'symptom_added':
                            echo "Symptom recorded successfully!";
                            break;
                        case 'kick_count_added':
                            echo "Kick count recorded successfully!";
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="welcome-banner">
                <h1>Pregnancy Tracker</h1>
                <p>Track your pregnancy journey week by week</p>
            </div>

            <?php if (!$pregnancy_data): ?>
                <!-- Start Pregnancy Tracking Section -->
                <div class="progress-section">
                    <h2>Start Tracking Your Pregnancy</h2>
                    <p>Let's get started by setting up your pregnancy details.</p>
                    
                    <form method="POST" style="max-width: 500px; margin-top: 20px;">
                        <div class="form-group">
                            <label class="form-label" for="last_menstrual_period">Last Menstrual Period (LMP)</label>
                            <input type="date" class="form-control" id="last_menstrual_period" name="last_menstrual_period" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="expected_delivery_date">Expected Delivery Date</label>
                            <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" required>
                        </div>
                        
                        <button type="submit" name="start_pregnancy" class="btn btn-primary">Start Tracking</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Pregnancy Progress Section -->
                <div class="progress-section">
                    <div class="progress-header">
                        <h2 class="progress-title">Your Pregnancy Progress</h2>
                        <div class="progress-percentage"><?php echo round($progress); ?>%</div>
                    </div>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    
                    <div class="progress-stats">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $pregnancy_data['gestational_age']; ?></div>
                            <div class="stat-label">Weeks Pregnant</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $weeks_remaining; ?></div>
                            <div class="stat-label">Weeks to Go</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo date('M j, Y', strtotime($pregnancy_data['expected_delivery_date'])); ?></div>
                            <div class="stat-label">Due Date</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <span class="badge <?php echo $pregnancy_data['current_status'] == 'High-Risk' ? 'badge-high' : 'badge-low'; ?>">
                                    <?php echo $pregnancy_data['current_status']; ?>
                                </span>
                            </div>
                            <div class="stat-label">Pregnancy Status</div>
                        </div>
                    </div>
                </div>

                <!-- Baby Development Section -->
                <div class="baby-development">
                    <h3>Your Baby's Development This Week</h3>
                    <?php
                    $week = $pregnancy_data['gestational_age'];
                    $milestones = [
                        4 => "Baby's neural tube forms, which will become brain and spinal cord",
                        8 => "All major organs have begun to form, baby is now called a fetus",
                        12 => "Baby's nerves and muscles begin to work together",
                        16 => "Baby can make sucking motions with mouth",
                        20 => "You might feel baby's first movements (quickening)",
                        24 => "Baby's fingerprints and footprints are forming",
                        28 => "Baby can blink and has eyelashes",
                        32 => "Baby's bones are fully formed but still soft",
                        36 => "Baby is gaining about half a pound per week",
                        40 => "Baby is full term and ready for birth!"
                    ];
                    
                    $current_milestone = $milestones[min($week, 40)] ?? "Your baby is growing and developing every day!";
                    ?>
                    <div class="development-milestone">
                        <div class="milestone-icon">
                            <i class="fas fa-baby"></i>
                        </div>
                        <div class="milestone-text">
                            <p><?php echo $current_milestone; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Tracker Grid -->
                <div class="tracker-grid">
                    <!-- Symptoms Tracker -->
                    <div class="tracker-card">
                        <h3><i class="fas fa-notes-medical"></i> Symptom Tracker</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label" for="symptom">Symptom</label>
                                <select class="form-control" id="symptom" name="symptom" required>
                                    <option value="">Select a symptom</option>
                                    <option value="Nausea">Nausea</option>
                                    <option value="Fatigue">Fatigue</option>
                                    <option value="Back Pain">Back Pain</option>
                                    <option value="Heartburn">Heartburn</option>
                                    <option value="Swelling">Swelling</option>
                                    <option value="Braxton Hicks">Braxton Hicks</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="intensity">Intensity</label>
                                <select class="form-control" id="intensity" name="intensity" required>
                                    <option value="Mild">Mild</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="Severe">Severe</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="symptom_notes">Notes</label>
                                <textarea class="form-control" id="symptom_notes" name="symptom_notes" rows="3" placeholder="Any additional details..."></textarea>
                            </div>
                            
                            <button type="submit" name="add_symptom" class="btn btn-primary">Record Symptom</button>
                        </form>
                        
                        <div class="symptoms-list" style="margin-top: 20px;">
                            <h4>Recent Entries</h4>
                            <?php if (mysqli_num_rows($recent_entries_result) > 0): ?>
                                <?php while ($entry = mysqli_fetch_assoc($recent_entries_result)): ?>
                                    <?php if (strpos($entry['notes'], 'Symptom:') !== false): ?>
                                        <div class="symptom-item">
                                            <div class="symptom-header">
                                                <span class="symptom-name"><?php echo explode(',', $entry['notes'])[0]; ?></span>
                                                <span class="symptom-intensity"><?php echo explode(',', $entry['notes'])[1] ?? ''; ?></span>
                                            </div>
                                            <div class="symptom-date"><?php echo $entry['visit_date']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No symptoms recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kick Counter -->
                    <div class="tracker-card">
                        <h3><i class="fas fa-heartbeat"></i> Kick Counter</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label" for="kick_count">Number of Kicks</label>
                                <input type="number" class="form-control" id="kick_count" name="kick_count" min="1" max="100" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="duration">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1" max="120" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="time_of_day">Time of Day</label>
                                <select class="form-control" id="time_of_day" name="time_of_day" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="add_kick_count" class="btn btn-accent">Record Kick Count</button>
                        </form>
                        
                        <div class="kick-history" style="margin-top: 20px;">
                            <h4>Recent Kick Counts</h4>
                            <?php 
                            // Reset pointer for recent entries
                            mysqli_data_seek($recent_entries_result, 0);
                            ?>
                            <?php if (mysqli_num_rows($recent_entries_result) > 0): ?>
                                <?php while ($entry = mysqli_fetch_assoc($recent_entries_result)): ?>
                                    <?php if (strpos($entry['notes'], 'Kick Count:') !== false): ?>
                                        <div class="kick-item">
                                            <div class="kick-header">
                                                <span class="kick-count"><?php echo explode(',', $entry['notes'])[0]; ?></span>
                                                <span class="kick-duration"><?php echo explode(',', $entry['notes'])[1] ?? ''; ?></span>
                                            </div>
                                            <div class="kick-time"><?php echo $entry['visit_date']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No kick counts recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Weight & Health Tracking -->
                    <div class="tracker-card">
                        <h3><i class="fas fa-weight"></i> Health Metrics</h3>
                        <?php
                        // Get latest health metrics
                        $health_query = "SELECT * FROM anc_visits WHERE pregnancy_id = '{$pregnancy_data['pregnancy_id']}' ORDER BY visit_date DESC LIMIT 1";
                        $health_result = mysqli_query($conn, $health_query);
                        $health_data = mysqli_fetch_assoc($health_result);
                        ?>
                        <div class="form-group">
                            <label class="form-label">Current Weight</label>
                            <input type="text" class="form-control" value="<?php echo $health_data['weight'] ?? 'Not recorded'; ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Blood Pressure</label>
                            <input type="text" class="form-control" value="<?php echo $health_data['blood_pressure'] ?? 'Not recorded'; ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Last ANC Visit</label>
                            <input type="text" class="form-control" value="<?php echo $health_data ? date('M j, Y', strtotime($health_data['visit_date'])) : 'No visits recorded'; ?>" disabled>
                        </div>
                        
                        <a href="health-records.php" class="btn btn-outline" style="width: 100%;">
                            View Detailed Metrics
                        </a>
                    </div>

                    <!-- Next Appointment -->
                    <div class="tracker-card">
                        <h3><i class="fas fa-calendar-alt"></i> Next Appointment</h3>
                        <div class="appointment-info">
                            <div style="text-align: center; padding: 20px;">
                                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 10px;">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <?php if ($next_appointment): ?>
                                    <h4><?php echo date('F j, Y', strtotime($next_appointment['appointment_date'])); ?></h4>
                                    <p><?php echo date('g:i A', strtotime($next_appointment['appointment_date'])); ?> with Dr. <?php echo $next_appointment['doctor_name']; ?></p>
                                    <p style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo $next_appointment['type']; ?></p>
                                <?php else: ?>
                                    <h4>No upcoming appointments</h4>
                                    <p>Schedule your next checkup</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <a href="my-appointments.php" class="btn btn-primary" style="flex: 1; text-decoration: none; text-align: center;">
                                <?php echo $next_appointment ? 'Reschedule' : 'Schedule'; ?>
                            </a>
                            <?php if ($next_appointment): ?>
                                <a href="my-appointments.php" class="btn btn-outline" style="flex: 1; text-decoration: none; text-align: center;">
                                    View Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Visits Section -->
                <div class="tracker-card">
                    <h3><i class="fas fa-history"></i> Recent ANC Visits</h3>
                    <?php if (mysqli_num_rows($visits_result) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: var(--background);">
                                        <th style="padding: 12px; text-align: left;">Date</th>
                                        <th style="padding: 12px; text-align: left;">Blood Pressure</th>
                                        <th style="padding: 12px; text-align: left;">Weight</th>
                                        <th style="padding: 12px; text-align: left;">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($visit = mysqli_fetch_assoc($visits_result)): ?>
                                        <tr style="border-bottom: 1px solid var(--border);">
                                            <td style="padding: 12px;"><?php echo $visit['visit_date']; ?></td>
                                            <td style="padding: 12px;"><?php echo $visit['blood_pressure'] ?? 'N/A'; ?></td>
                                            <td style="padding: 12px;"><?php echo $visit['weight'] ?? 'N/A'; ?> kg</td>
                                            <td style="padding: 12px;"><?php echo substr($visit['notes'] ?? 'No notes', 0, 50) . '...'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No ANC visits recorded yet.</p>
                    <?php endif; ?>
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
        // Auto-calculate EDD from LMP
        document.getElementById('last_menstrual_period')?.addEventListener('change', function() {
            const lmp = new Date(this.value);
            if (!isNaN(lmp.getTime())) {
                const edd = new Date(lmp);
                edd.setDate(edd.getDate() + 280); // 40 weeks
                document.getElementById('expected_delivery_date').value = edd.toISOString().split('T')[0];
            }
        });

        // Progress bar animation
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                // Animate progress bar
                setTimeout(() => {
                    progressBar.style.transition = 'width 2s ease-in-out';
                }, 500);
            }
        });
    </script>
</body>
</html>