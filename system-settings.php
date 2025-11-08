<?php
session_start();
include 'connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
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

// Get current system settings
$settings_query = "SELECT * FROM system_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);

if (mysqli_num_rows($settings_result) > 0) {
    $current_settings = mysqli_fetch_assoc($settings_result);
} else {
    // Default settings
    $current_settings = [
        'system_name' => 'MaternalCare AI',
        'language' => 'english',
        'theme' => 'light',
        'auto_backup' => 1,
        'backup_frequency' => 'daily',
        'session_timeout' => 30,
        'email_notifications' => 1,
        'sms_notifications' => 1,
        'max_login_attempts' => 5,
        'password_expiry' => 90
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_general_settings'])) {
        $system_name = mysqli_real_escape_string($conn, $_POST['system_name']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        $theme = mysqli_real_escape_string($conn, $_POST['theme']);

        // Update or insert settings
        if (mysqli_num_rows($settings_result) > 0) {
            $update_query = "UPDATE system_settings SET 
                            system_name = '$system_name',
                            language = '$language',
                            theme = '$theme',
                            updated_at = NOW()
                            WHERE id = 1";
        } else {
            $update_query = "INSERT INTO system_settings 
                            (system_name, language, theme, created_at, updated_at) 
                            VALUES ('$system_name', '$language', '$theme', NOW(), NOW())";
        }

        if (mysqli_query($conn, $update_query)) {
            $alert_message = "General settings updated successfully!";
            $alert_type = "success";

            // Update current settings
            $current_settings['system_name'] = $system_name;
            $current_settings['language'] = $language;
            $current_settings['theme'] = $theme;
        } else {
            $alert_message = "Error updating settings: " . mysqli_error($conn);
            $alert_type = "error";
        }
    } elseif (isset($_POST['save_security_settings'])) {
        $session_timeout = intval($_POST['session_timeout']);
        $max_login_attempts = intval($_POST['max_login_attempts']);
        $password_expiry = intval($_POST['password_expiry']);
        $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
        $backup_frequency = mysqli_real_escape_string($conn, $_POST['backup_frequency']);

        $update_query = "UPDATE system_settings SET 
                        session_timeout = '$session_timeout',
                        max_login_attempts = '$max_login_attempts',
                        password_expiry = '$password_expiry',
                        auto_backup = '$auto_backup',
                        backup_frequency = '$backup_frequency',
                        updated_at = NOW()
                        WHERE id = 1";

        if (mysqli_query($conn, $update_query)) {
            $alert_message = "Security settings updated successfully!";
            $alert_type = "success";

            // Update current settings
            $current_settings['session_timeout'] = $session_timeout;
            $current_settings['max_login_attempts'] = $max_login_attempts;
            $current_settings['password_expiry'] = $password_expiry;
            $current_settings['auto_backup'] = $auto_backup;
            $current_settings['backup_frequency'] = $backup_frequency;
        } else {
            $alert_message = "Error updating security settings: " . mysqli_error($conn);
            $alert_type = "error";
        }
    } elseif (isset($_POST['save_notification_settings'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;

        $update_query = "UPDATE system_settings SET 
                        email_notifications = '$email_notifications',
                        sms_notifications = '$sms_notifications',
                        updated_at = NOW()
                        WHERE id = 1";

        if (mysqli_query($conn, $update_query)) {
            $alert_message = "Notification settings updated successfully!";
            $alert_type = "success";

            // Update current settings
            $current_settings['email_notifications'] = $email_notifications;
            $current_settings['sms_notifications'] = $sms_notifications;
        } else {
            $alert_message = "Error updating notification settings: " . mysqli_error($conn);
            $alert_type = "error";
        }
    } elseif (isset($_POST['backup_now'])) {
        // Simulate backup process
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        // In a real system, you would implement actual database backup here
        $alert_message = "System backup created successfully: " . $backup_file;
        $alert_type = "success";
    } elseif (isset($_POST['clear_cache'])) {
        // Simulate cache clearing
        $alert_message = "System cache cleared successfully!";
        $alert_type = "success";
    }
}

// Language translations
$translations = [
    'english' => [
        'title' => 'System Settings',
        'description' => 'Manage system configuration and preferences',
        'general_settings' => 'General Settings',
        'security_settings' => 'Security Settings',
        'notification_settings' => 'Notification Settings',
        'system_tools' => 'System Tools',
        'system_name' => 'System Name',
        'language' => 'Language',
        'theme' => 'Theme',
        'light_mode' => 'Light Mode',
        'dark_mode' => 'Dark Mode',
        'session_timeout' => 'Session Timeout (minutes)',
        'max_login_attempts' => 'Maximum Login Attempts',
        'password_expiry' => 'Password Expiry (days)',
        'auto_backup' => 'Automatic Backup',
        'backup_frequency' => 'Backup Frequency',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'email_notifications' => 'Email Notifications',
        'sms_notifications' => 'SMS Notifications',
        'backup_now' => 'Backup Now',
        'clear_cache' => 'Clear Cache',
        'save_changes' => 'Save Changes',
        'system_backup' => 'System Backup',
        'cache_management' => 'Cache Management',
        'backup_description' => 'Create a manual backup of the system database',
        'cache_description' => 'Clear system cache to free up memory and improve performance',
        'last_backup' => 'Last Backup',
        'never' => 'Never',
        'cache_size' => 'Cache Size',
        'unknown' => 'Unknown'
    ],
    'kinyarwanda' => [
        'title' => 'Ibyerekeye Sisitemu',
        'description' => 'Gucunga igena ry\'sisitemu n\'ibyifuzo',
        'general_settings' => 'Igena Rusange',
        'security_settings' => 'Igena z\'Umutekano',
        'notification_settings' => 'Igena z\'Itangazo',
        'system_tools' => 'Ibikoresho bya Sisitemu',
        'system_name' => 'Izina ry\'Sisitemu',
        'language' => 'Ururimi',
        'theme' => 'Ishusho',
        'light_mode' => 'Uburyo bwo Kumurika',
        'dark_mode' => 'Uburyo bwo Kwijima',
        'session_timeout' => 'Igihe cy\'isesura (iminota)',
        'max_login_attempts' => 'Ingano y\'Injira zo Gukoresha',
        'password_expiry' => 'Igihe cy\'Ijambobanga (iminsi)',
        'auto_backup' => 'Gukora Backup mu buryo Bwihariye',
        'backup_frequency' => 'Igipimo cyo Gukora Backup',
        'daily' => 'Buri munsi',
        'weekly' => 'Buri cyumweru',
        'monthly' => 'Buri kwezi',
        'email_notifications' => 'Amatangazo kuri Email',
        'sms_notifications' => 'Amatangazo kuri SMS',
        'backup_now' => 'Kora Backup Nonaha',
        'clear_cache' => 'Sukura Cache',
        'save_changes' => 'Bika Amahinduko',
        'system_backup' => 'Gukora Backup ya Sisitemu',
        'cache_management' => 'Gucunga Cache',
        'backup_description' => 'Kora backup y\'ububiko bw\'amakuru ya sisitemu',
        'cache_description' => 'Sukura cache ya sisitemu kugirango wongere umutekano no kunoza imikorere',
        'last_backup' => 'Backup ya Nyuma',
        'never' => 'Ntabwo byigeze',
        'cache_size' => 'Ingano ya Cache',
        'unknown' => 'Ntibizwi'
    ]
];

$current_language = $current_settings['language'] ?? 'english';
$t = $translations[$current_language];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - MaternalCare AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles from the previous HTML file */

        <?php include 'styles.css'; ?>

        /* Additional styles for system settings */
        .system-settings-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px;
        }

        .settings-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #34495e;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdfe6;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #219a52;
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d68910;
        }

        .theme-preview {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .theme-option {
            flex: 1;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .theme-option:hover {
            border-color: #3498db;
        }

        .theme-option.active {
            border-color: #3498db;
            background-color: #f8f9fa;
        }

        .theme-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .tool-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .tool-icon {
            font-size: 32px;
            color: #3498db;
            margin-bottom: 15px;
        }

        .tool-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .tool-description {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .system-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #34495e;
        }

        .info-value {
            color: #7f8c8d;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Dark mode styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        body.dark-mode .settings-section {
            background: #2d2d2d;
            color: #e0e0e0;
        }

        body.dark-mode .section-title {
            color: #e0e0e0;
        }

        body.dark-mode .form-control {
            background-color: #3d3d3d;
            border-color: #555;
            color: #e0e0e0;
        }

        body.dark-mode .form-control:focus {
            border-color: #3498db;
        }

        body.dark-mode .tool-card {
            background: #3d3d3d;
        }

        body.dark-mode .system-info {
            background: #3d3d3d;
        }

        body.dark-mode .info-label {
            color: #e0e0e0;
        }

        body.dark-mode .theme-option.active {
            background-color: #3d3d3d;
        }
    </style>
</head>

<body class="<?php echo $current_settings['theme'] === 'dark' ? 'dark-mode' : ''; ?>">
    <!-- Main Application -->
    <div id="main-app">
        <!-- Header -->
        <header class="header">
            <div class="header-container">
                <div class="logo">
                    <i class="fas fa-baby"></i>
                    <span><?php echo $current_settings['system_name']; ?></span>
                </div>
                <div class="user-info">
                    <span id="user-display-name"><?php echo $t['welcome'] ?? 'Welcome'; ?>, <?php echo $user_data['username']; ?> (<?php echo $role; ?>)</span>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-container">
            <ul class="nav-menu">
                <!-- Admin Navigation -->
                <div class="admin-nav" style="display: <?php echo $role == 'Admin' ? 'flex' : 'none'; ?>;">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="user-management.php" class="nav-link">User Management</a></li>
                    <li class="nav-item"><a href="system-settings.php" class="nav-link active"><?php echo $t['title']; ?></a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
                </div>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Alert Container -->
            <div id="alert-container">
                <?php if (isset($alert_message)): ?>
                    <div class="alert alert-<?php echo $alert_type; ?>">
                        <?php echo $alert_message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="page-content">
                <h1><?php echo $t['title']; ?></h1>
                <p><?php echo $t['description']; ?></p>

                <div class="system-settings-container">
                    <!-- General Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $t['general_settings']; ?></h2>
                        </div>

                        <form method="POST" action="">
                            <div class="settings-grid">
                                <div class="form-group">
                                    <label for="system_name"><?php echo $t['system_name']; ?></label>
                                    <input type="text" id="system_name" name="system_name" class="form-control"
                                        value="<?php echo $current_settings['system_name']; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="language"><?php echo $t['language']; ?></label>
                                    <select id="language" name="language" class="form-control" required>
                                        <option value="english" <?php echo $current_settings['language'] == 'english' ? 'selected' : ''; ?>>English</option>
                                        <option value="kinyarwanda" <?php echo $current_settings['language'] == 'kinyarwanda' ? 'selected' : ''; ?>>Kinyarwanda</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label><?php echo $t['theme']; ?></label>
                                    <div class="theme-preview">
                                        <div class="theme-option <?php echo $current_settings['theme'] == 'light' ? 'active' : ''; ?>"
                                            onclick="selectTheme('light')">
                                            <div class="theme-icon">
                                                <i class="fas fa-sun"></i>
                                            </div>
                                            <div><?php echo $t['light_mode']; ?></div>
                                            <input type="radio" name="theme" value="light"
                                                <?php echo $current_settings['theme'] == 'light' ? 'checked' : ''; ?> style="display: none;">
                                        </div>
                                        <div class="theme-option <?php echo $current_settings['theme'] == 'dark' ? 'active' : ''; ?>"
                                            onclick="selectTheme('dark')">
                                            <div class="theme-icon">
                                                <i class="fas fa-moon"></i>
                                            </div>
                                            <div><?php echo $t['dark_mode']; ?></div>
                                            <input type="radio" name="theme" value="dark"
                                                <?php echo $current_settings['theme'] == 'dark' ? 'checked' : ''; ?> style="display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_general_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $t['save_changes']; ?>
                            </button>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $t['security_settings']; ?></h2>
                        </div>

                        <form method="POST" action="">
                            <div class="settings-grid">
                                <div class="form-group">
                                    <label for="session_timeout"><?php echo $t['session_timeout']; ?></label>
                                    <input type="number" id="session_timeout" name="session_timeout" class="form-control"
                                        value="<?php echo $current_settings['session_timeout']; ?>" min="5" max="240">
                                </div>

                                <div class="form-group">
                                    <label for="max_login_attempts"><?php echo $t['max_login_attempts']; ?></label>
                                    <input type="number" id="max_login_attempts" name="max_login_attempts" class="form-control"
                                        value="<?php echo $current_settings['max_login_attempts']; ?>" min="3" max="10">
                                </div>

                                <div class="form-group">
                                    <label for="password_expiry"><?php echo $t['password_expiry']; ?></label>
                                    <input type="number" id="password_expiry" name="password_expiry" class="form-control"
                                        value="<?php echo $current_settings['password_expiry']; ?>" min="30" max="365">
                                </div>

                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="auto_backup" name="auto_backup"
                                            <?php echo $current_settings['auto_backup'] ? 'checked' : ''; ?>>
                                        <label for="auto_backup"><?php echo $t['auto_backup']; ?></label>
                                    </div>

                                    <select id="backup_frequency" name="backup_frequency" class="form-control" style="margin-top: 10px;">
                                        <option value="daily" <?php echo $current_settings['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>
                                            <?php echo $t['daily']; ?>
                                        </option>
                                        <option value="weekly" <?php echo $current_settings['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>
                                            <?php echo $t['weekly']; ?>
                                        </option>
                                        <option value="monthly" <?php echo $current_settings['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>
                                            <?php echo $t['monthly']; ?>
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" name="save_security_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $t['save_changes']; ?>
                            </button>
                        </form>
                    </div>

                    <!-- Notification Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $t['notification_settings']; ?></h2>
                        </div>

                        <form method="POST" action="">
                            <div class="settings-grid">
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="email_notifications" name="email_notifications"
                                            <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?>>
                                        <label for="email_notifications"><?php echo $t['email_notifications']; ?></label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="sms_notifications" name="sms_notifications"
                                            <?php echo $current_settings['sms_notifications'] ? 'checked' : ''; ?>>
                                        <label for="sms_notifications"><?php echo $t['sms_notifications']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_notification_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $t['save_changes']; ?>
                            </button>
                        </form>
                    </div>

                    <!-- System Tools -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $t['system_tools']; ?></h2>
                        </div>

                        <div class="tools-grid">
                            <div class="tool-card">
                                <div class="tool-icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="tool-title"><?php echo $t['system_backup']; ?></div>
                                <div class="tool-description">
                                    <?php echo $t['backup_description']; ?>
                                </div>
                                <form method="POST" action="" style="display: inline;">
                                    <button type="submit" name="backup_now" class="btn btn-success">
                                        <i class="fas fa-download"></i> <?php echo $t['backup_now']; ?>
                                    </button>
                                </form>
                            </div>

                            <div class="tool-card">
                                <div class="tool-icon">
                                    <i class="fas fa-broom"></i>
                                </div>
                                <div class="tool-title"><?php echo $t['cache_management']; ?></div>
                                <div class="tool-description">
                                    <?php echo $t['cache_description']; ?>
                                </div>
                                <form method="POST" action="" style="display: inline;">
                                    <button type="submit" name="clear_cache" class="btn btn-warning">
                                        <i class="fas fa-broom"></i> <?php echo $t['clear_cache']; ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="system-info">
                            <div class="info-item">
                                <span class="info-label"><?php echo $t['last_backup']; ?>:</span>
                                <span class="info-value"><?php echo $t['never']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php echo $t['cache_size']; ?>:</span>
                                <span class="info-value"><?php echo $t['unknown']; ?></span>
                            </div>
                        </div>
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
                        &copy; 2024 <?php echo $current_settings['system_name']; ?>. All rights reserved.
                    </div>
                </div>
                <div class="footer-right">
                    <div class="user-role-display">
                        <?php echo $t['logged_in_as'] ?? 'Logged in as'; ?>: <span id="footer-user-role"><?php echo $role; ?></span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        function selectTheme(theme) {
            // Update radio buttons
            document.querySelectorAll('input[name="theme"]').forEach(radio => {
                radio.checked = radio.value === theme;
            });

            // Update visual selection
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Preview theme change
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }

        // Apply theme immediately when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = '<?php echo $current_settings['theme']; ?>';
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</body>

</html>