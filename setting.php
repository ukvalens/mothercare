<?php
// create_system_settings_table.php
include 'connection.php';

// SQL to create system_settings table
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(255) NOT NULL DEFAULT 'MaternalCare AI',
    language ENUM('english', 'kinyarwanda') NOT NULL DEFAULT 'english',
    theme ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    auto_backup TINYINT(1) NOT NULL DEFAULT 1,
    backup_frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    session_timeout INT(11) NOT NULL DEFAULT 30,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    sms_notifications TINYINT(1) NOT NULL DEFAULT 1,
    max_login_attempts INT(11) NOT NULL DEFAULT 5,
    password_expiry INT(11) NOT NULL DEFAULT 90,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'system_settings' created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
