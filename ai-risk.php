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
    if (isset($_POST['generate_prediction'])) {
        $pregnancy_id = mysqli_real_escape_string($conn, $_POST['pregnancy_id']);
        
        // Get pregnancy data for risk calculation
        $pregnancy_query = "SELECT * FROM pregnancies WHERE pregnancy_id = '$pregnancy_id'";
        $pregnancy_result = mysqli_query($conn, $pregnancy_query);
        $pregnancy_data = mysqli_fetch_assoc($pregnancy_result);
        
        if ($pregnancy_data) {
            // Get patient data
            $patient_query = "SELECT * FROM patients WHERE patient_id = '{$pregnancy_data['patient_id']}'";
            $patient_result = mysqli_query($conn, $patient_query);
            $patient_data = mysqli_fetch_assoc($patient_result);
            
            // Get latest ANC visit data
            $visit_query = "SELECT * FROM anc_visits WHERE pregnancy_id = '$pregnancy_id' ORDER BY visit_date DESC LIMIT 1";
            $visit_result = mysqli_query($conn, $visit_query);
            $visit_data = mysqli_fetch_assoc($visit_result);
            
            // AI Risk Prediction Algorithm (Simplified)
            $risk_score = calculateRiskScore($pregnancy_data, $patient_data, $visit_data);
            $risk_level = getRiskLevel($risk_score);
            $recommended_action = getRecommendedAction($risk_level, $pregnancy_data, $visit_data);
            
            // Save prediction to database
            $insert_query = "INSERT INTO ai_risk_predictions (pregnancy_id, risk_score, risk_level, recommended_action) 
                            VALUES ('$pregnancy_id', '$risk_score', '$risk_level', '$recommended_action')";
            
            if (mysqli_query($conn, $insert_query)) {
                $prediction_id = mysqli_insert_id($conn);
                $success = "AI Risk Prediction generated successfully!";
                
                // Update pregnancy with risk score
                $update_query = "UPDATE pregnancies SET ai_risk_score = '$risk_score' WHERE pregnancy_id = '$pregnancy_id'";
                mysqli_query($conn, $update_query);
            } else {
                $error = "Error generating prediction: " . mysqli_error($conn);
            }
        }
    }
}

// Risk calculation functions
function calculateRiskScore($pregnancy, $patient, $visit) {
    $score = 50; // Base score
    
    // Age factor
    if ($patient['dob']) {
        $age = date_diff(date_create($patient['dob']), date_create('today'))->y;
        if ($age < 18) $score += 15;
        if ($age > 35) $score += 10;
        if ($age > 40) $score += 15;
    }
    
    // Gestational age factor
    if ($pregnancy['gestational_age'] > 40) $score += 10;
    if ($pregnancy['gestational_age'] < 20) $score += 5;
    
    // Blood pressure factor
    if ($visit && $visit['blood_pressure']) {
        $bp_parts = explode('/', $visit['blood_pressure']);
        if (count($bp_parts) == 2) {
            $systolic = intval($bp_parts[0]);
            $diastolic = intval($bp_parts[1]);
            if ($systolic > 140 || $diastolic > 90) $score += 20;
            if ($systolic > 160 || $diastolic > 110) $score += 15;
        }
    }
    
    // Weight factor
    if ($visit && $visit['weight']) {
        if ($visit['weight'] < 50) $score += 5;
        if ($visit['weight'] > 100) $score += 10;
    }
    
    // Medical history factor
    if ($patient['medical_history']) {
        $history = strtolower($patient['medical_history']);
        if (strpos($history, 'diabetes') !== false) $score += 15;
        if (strpos($history, 'hypertension') !== false) $score += 15;
        if (strpos($history, 'heart') !== false) $score += 20;
        if (strpos($history, 'asthma') !== false) $score += 5;
    }
    
    // Obstetric history factor
    if ($patient['obstetric_history']) {
        $obstetric = strtolower($patient['obstetric_history']);
        if (strpos($obstetric, 'previous c-section') !== false) $score += 10;
        if (strpos($obstetric, 'miscarriage') !== false) $score += 10;
        if (strpos($obstetric, 'preterm') !== false) $score += 15;
        if (strpos($obstetric, 'stillbirth') !== false) $score += 20;
    }
    
    return min(100, max(0, $score));
}

function getRiskLevel($score) {
    if ($score >= 80) return 'High';
    if ($score >= 60) return 'Medium';
    return 'Low';
}

function getRecommendedAction($risk_level, $pregnancy, $visit) {
    $actions = [];
    
    switch ($risk_level) {
        case 'High':
            $actions[] = "Immediate specialist consultation required";
            $actions[] = "Consider hospitalization for monitoring";
            $actions[] = "Frequent fetal monitoring (2-3 times weekly)";
            $actions[] = "Weekly ANC visits";
            $actions[] = "Prepare for possible early delivery";
            break;
            
        case 'Medium':
            $actions[] = "Schedule specialist consultation within 1 week";
            $actions[] = "Bi-weekly ANC visits";
            $actions[] = "Regular fetal movement monitoring";
            $actions[] = "Lifestyle modifications as advised";
            break;
            
        case 'Low':
            $actions[] = "Continue routine ANC schedule";
            $actions[] = "Maintain healthy lifestyle";
            $actions[] = "Regular self-monitoring of symptoms";
            $actions[] = "Report any unusual symptoms immediately";
            break;
    }
    
    // Add specific recommendations based on factors
    if ($visit && $visit['blood_pressure']) {
        $bp_parts = explode('/', $visit['blood_pressure']);
        if (count($bp_parts) == 2 && intval($bp_parts[0]) > 140) {
            $actions[] = "Blood pressure monitoring twice daily";
            $actions[] = "Low sodium diet recommended";
        }
    }
    
    if ($pregnancy['gestational_age'] > 40) {
        $actions[] = "Consider induction of labor";
        $actions[] = "Increased fetal surveillance";
    }
    
    return implode("\n", $actions);
}

// Get active pregnancies for prediction
$pregnancies_query = "
    SELECT pr.pregnancy_id, p.first_name, p.last_name, pr.gestational_age, 
           pr.expected_delivery_date, pr.ai_risk_score, pr.current_status
    FROM pregnancies pr
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Active'
    ORDER BY pr.ai_risk_score DESC, p.first_name, p.last_name
";
$pregnancies_result = mysqli_query($conn, $pregnancies_query);

// Get recent predictions
$predictions_query = "
    SELECT arp.*, 
           p.first_name, p.last_name, p.patient_id,
           pr.gestational_age
    FROM ai_risk_predictions arp
    LEFT JOIN pregnancies pr ON arp.pregnancy_id = pr.pregnancy_id
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    ORDER BY arp.generated_at DESC
    LIMIT 10
";
$predictions_result = mysqli_query($conn, $predictions_query);

// Get risk statistics
$total_predictions = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_risk_predictions")->fetch_assoc()['count'];
$high_risk_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_risk_predictions WHERE risk_level = 'High'")->fetch_assoc()['count'];
$medium_risk_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_risk_predictions WHERE risk_level = 'Medium'")->fetch_assoc()['count'];
$low_risk_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_risk_predictions WHERE risk_level = 'Low'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Risk Prediction - MaternalCare AI</title>
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

        /* AI Risk Prediction Styles */
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

        .ai-banner {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .ai-banner h2 {
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .ai-banner p {
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
        .stat-icon.high { background-color: var(--error); }
        .stat-icon.medium { background-color: #ffc107; }
        .stat-icon.low { background-color: var(--success); }

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

        .prediction-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 968px) {
            .prediction-container {
                grid-template-columns: 1fr;
            }
        }

        .form-container, .results-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
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

        .btn {
            padding: 12px 20px;
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

        .risk-result {
            text-align: center;
            padding: 30px;
        }

        .risk-meter {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            position: relative;
        }

        .risk-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            transition: all 0.5s ease;
        }

        .risk-circle.low { background: conic-gradient(var(--success) 0% 33%, var(--background) 33% 100%); }
        .risk-circle.medium { background: conic-gradient(var(--success) 0% 33%, #ffc107 33% 66%, var(--background) 66% 100%); }
        .risk-circle.high { background: conic-gradient(var(--success) 0% 33%, #ffc107 33% 66%, var(--error) 66% 100%); }

        .risk-score {
            font-size: 3rem;
            font-weight: bold;
        }

        .risk-level {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .risk-level.low { color: var(--success); }
        .risk-level.medium { color: #ffc107; }
        .risk-level.high { color: var(--error); }

        .recommendations {
            background-color: var(--background);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
        }

        .recommendations h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .recommendations ul {
            list-style-type: none;
            padding: 0;
        }

        .recommendations li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .recommendations li:last-child {
            border-bottom: none;
        }

        .recommendations li i {
            color: var(--accent);
            margin-top: 2px;
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

        .factor-analysis {
            background-color: var(--background);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .factor-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .factor-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .factor-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .factor-impact {
            color: var(--text-secondary);
        }

        .factor-impact.high { color: var(--error); font-weight: bold; }
        .factor-impact.medium { color: #ffc107; font-weight: bold; }
        .factor-impact.low { color: var(--success); }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .risk-meter {
                width: 150px;
                height: 150px;
            }
            
            .risk-score {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .stats-cards {
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
                <li class="nav-item"><a href="ai-risk.php" class="nav-link active">AI Risk Prediction</a></li>
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
            <h1 class="page-title">AI Risk Prediction</h1>
        </div>

        <!-- AI Banner -->
        <div class="ai-banner">
            <h2><i class="fas fa-robot"></i> Smart Maternal Risk Assessment</h2>
            <p>Our AI system analyzes multiple factors to predict pregnancy risks and provide personalized recommendations for optimal maternal care.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo $total_predictions; ?></div>
                <div class="stat-label">Total Predictions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon high">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $high_risk_count; ?></div>
                <div class="stat-label">High Risk Cases</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon medium">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="stat-value"><?php echo $medium_risk_count; ?></div>
                <div class="stat-label">Medium Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon low">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $low_risk_count; ?></div>
                <div class="stat-label">Low Risk</div>
            </div>
        </div>

        <!-- Prediction Interface -->
        <div class="prediction-container">
            <!-- Prediction Form -->
            <div class="form-container">
                <h3 class="form-title">Generate Risk Prediction</h3>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="pregnancy_id">Select Patient *</label>
                        <select class="form-control" id="pregnancy_id" name="pregnancy_id" required>
                            <option value="">Select Patient</option>
                            <?php while ($pregnancy = mysqli_fetch_assoc($pregnancies_result)): 
                                $current_risk = $pregnancy['ai_risk_score'] ? $pregnancy['ai_risk_score'] . '%' : 'Not assessed';
                            ?>
                                <option value="<?php echo $pregnancy['pregnancy_id']; ?>">
                                    <?php echo htmlspecialchars($pregnancy['first_name'] . ' ' . $pregnancy['last_name']); ?> 
                                    (<?php echo $pregnancy['gestational_age']; ?> weeks) - Current: <?php echo $current_risk; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="generate_prediction" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-brain"></i> Generate AI Prediction
                        </button>
                    </div>
                </form>

                <!-- Risk Factors Information -->
                <div class="factor-analysis">
                    <h4>Risk Factors Analyzed</h4>
                    <div class="factor-item">
                        <span class="factor-name">Maternal Age</span>
                        <span class="factor-impact high">High Impact</span>
                    </div>
                    <div class="factor-item">
                        <span class="factor-name">Blood Pressure</span>
                        <span class="factor-impact high">High Impact</span>
                    </div>
                    <div class="factor-item">
                        <span class="factor-name">Medical History</span>
                        <span class="factor-impact high">High Impact</span>
                    </div>
                    <div class="factor-item">
                        <span class="factor-name">Gestational Age</span>
                        <span class="factor-impact medium">Medium Impact</span>
                    </div>
                    <div class="factor-item">
                        <span class="factor-name">Weight & BMI</span>
                        <span class="factor-impact medium">Medium Impact</span>
                    </div>
                    <div class="factor-item">
                        <span class="factor-name">Obstetric History</span>
                        <span class="factor-impact high">High Impact</span>
                    </div>
                </div>
            </div>

            <!-- Results Display -->
            <div class="results-container">
                <h3 class="form-title">Prediction Results</h3>
                
                <?php if (isset($risk_score) && isset($risk_level) && isset($recommended_action)): ?>
                    <div class="risk-result">
                        <div class="risk-meter">
                            <div class="risk-circle <?php echo strtolower($risk_level); ?>">
                                <div class="risk-score"><?php echo $risk_score; ?>%</div>
                            </div>
                        </div>
                        
                        <div class="risk-level <?php echo strtolower($risk_level); ?>">
                            <?php echo $risk_level; ?> Risk
                        </div>
                        
                        <p>Based on comprehensive analysis of patient data</p>
                    </div>

                    <div class="recommendations">
                        <h4><i class="fas fa-list-check"></i> Recommended Actions</h4>
                        <ul>
                            <?php foreach (explode("\n", $recommended_action) as $action): ?>
                                <?php if (trim($action)): ?>
                                    <li><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars(trim($action)); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <button class="btn btn-success" style="width: 100%;" onclick="downloadReport()">
                            <i class="fas fa-download"></i> Download Risk Report
                        </button>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <h4>No Prediction Generated</h4>
                        <p>Select a patient and generate an AI risk prediction to see results here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Predictions Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Recent Predictions</h2>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Prediction ID</th>
                        <th>Patient</th>
                        <th>Gestational Age</th>
                        <th>Risk Score</th>
                        <th>Risk Level</th>
                        <th>Generated On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($predictions_result) > 0): ?>
                        <?php while ($prediction = mysqli_fetch_assoc($predictions_result)): 
                            $generated_date = date('M j, Y H:i', strtotime($prediction['generated_at']));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prediction['prediction_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prediction['first_name'] . ' ' . $prediction['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($prediction['gestational_age']); ?> weeks</td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prediction['risk_score']); ?>%</strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($prediction['risk_level']); ?>">
                                        <?php echo $prediction['risk_level']; ?>
                                    </span>
                                </td>
                                <td><?php echo $generated_date; ?></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="viewPredictionDetails(<?php echo $prediction['prediction_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                <p>No predictions generated yet. Generate your first AI risk prediction above.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
        // View prediction details
        function viewPredictionDetails(predictionId) {
            alert('View prediction details for ID: ' + predictionId + '\nThis would show detailed analysis in a complete implementation.');
            // In a complete implementation, this would open a modal with detailed analysis
        }

        // Download report
        function downloadReport() {
            alert('Downloading risk assessment report...\nThis would generate a PDF report in a complete implementation.');
            // In a complete implementation, this would generate and download a PDF report
        }

        // Animate risk meter
        document.addEventListener('DOMContentLoaded', function() {
            const riskCircle = document.querySelector('.risk-circle');
            if (riskCircle) {
                // Add animation delay
                setTimeout(() => {
                    riskCircle.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        riskCircle.style.transform = 'scale(1)';
                    }, 300);
                }, 500);
            }
        });

        // Real-time risk estimation as user selects patient
        document.getElementById('pregnancy_id')?.addEventListener('change', function() {
            // In a complete implementation, this would show estimated risk based on patient data
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                // Show loading state or estimated risk
                console.log('Patient selected for risk assessment');
            }
        });
    </script>
</body>
</html>