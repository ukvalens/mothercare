<?php
include 'connection.php';

// Enable error reporting for better debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Drop tables first (for testing) in reverse order of dependencies
    $dropTables = [
        'audit_logs', 'notifications', 'ai_risk_predictions', 
        'appointments', 'deliveries', 'anc_visits', 
        'pregnancies', 'patients', 'users'
    ];
    
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
    foreach ($dropTables as $table) {
        if (mysqli_query($conn, "DROP TABLE IF EXISTS `$table`")) {
            echo "Dropped table: $table<br>";
        } else {
            echo "Error dropping $table: " . mysqli_error($conn) . "<br>";
        }
    }
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

    // Define all tables with proper error handling
    $tableQueries = [
        "users" => "
            CREATE TABLE `users` (
                `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('Admin','Doctor','Nurse','Mother') NOT NULL,
                `email` VARCHAR(100),
                `phone` VARCHAR(20),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "patients" => "
            CREATE TABLE `patients` (
                `patient_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `first_name` VARCHAR(50) NOT NULL,
                `last_name` VARCHAR(50) NOT NULL,
                `dob` DATE,
                `contact_number` VARCHAR(20),
                `address` TEXT,
                `medical_history` TEXT,
                `obstetric_history` TEXT,
                `registered_by` INT UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`patient_id`),
                FOREIGN KEY (`registered_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "pregnancies" => "
            CREATE TABLE `pregnancies` (
                `pregnancy_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `patient_id` INT UNSIGNED NOT NULL,
                `gestational_age` INT,
                `expected_delivery_date` DATE NOT NULL,
                `current_status` ENUM('Active','Completed','High-Risk') DEFAULT 'Active',
                `ai_risk_score` DECIMAL(5,2),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`pregnancy_id`),
                FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "anc_visits" => "
            CREATE TABLE `anc_visits` (
                `visit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `pregnancy_id` INT UNSIGNED NOT NULL,
                `visit_date` DATE NOT NULL,
                `blood_pressure` VARCHAR(20),
                `weight` DECIMAL(5,2),
                `pulse` INT,
                `temperature` DECIMAL(4,1),
                `lab_results` TEXT,
                `medications_given` TEXT,
                `notes` TEXT,
                `recorded_by` INT UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`visit_id`),
                FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancies`(`pregnancy_id`) ON DELETE CASCADE,
                FOREIGN KEY (`recorded_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "deliveries" => "
            CREATE TABLE `deliveries` (
                `delivery_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `pregnancy_id` INT UNSIGNED NOT NULL,
                `delivery_date` DATE NOT NULL,
                `mode_of_delivery` ENUM('Normal','C-Section','Assisted') NOT NULL,
                `baby_weight` DECIMAL(5,2),
                `baby_gender` ENUM('Male','Female') NOT NULL,
                `apgar_score` INT,
                `complications` TEXT,
                `recorded_by` INT UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`delivery_id`),
                FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancies`(`pregnancy_id`) ON DELETE CASCADE,
                FOREIGN KEY (`recorded_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "appointments" => "
            CREATE TABLE `appointments` (
                `appointment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `patient_id` INT UNSIGNED NOT NULL,
                `doctor_id` INT UNSIGNED DEFAULT NULL,
                `appointment_date` DATETIME NOT NULL,
                `status` ENUM('Scheduled','Completed','Missed') DEFAULT 'Scheduled',
                `type` ENUM('ANC Visit','Delivery Planning','Postnatal Check') NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`appointment_id`),
                FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE,
                FOREIGN KEY (`doctor_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "ai_risk_predictions" => "
            CREATE TABLE `ai_risk_predictions` (
                `prediction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `pregnancy_id` INT UNSIGNED NOT NULL,
                `risk_score` DECIMAL(5,2),
                `risk_level` ENUM('Low','Medium','High'),
                `recommended_action` TEXT,
                `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`prediction_id`),
                FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancies`(`pregnancy_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "notifications" => "
            CREATE TABLE `notifications` (
                `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `patient_id` INT UNSIGNED DEFAULT NULL,
                `message` TEXT NOT NULL,
                `type` ENUM('SMS','Email','System') NOT NULL,
                `status` ENUM('Sent','Pending','Failed') DEFAULT 'Pending',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`notification_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "audit_logs" => "
            CREATE TABLE `audit_logs` (
                `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `action_type` ENUM('Create','Update','Delete','Login') NOT NULL,
                `entity` VARCHAR(50),
                `entity_id` INT,
                `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`log_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];

    // Create tables
    foreach ($tableQueries as $tableName => $sql) {
        if (mysqli_query($conn, $sql)) {
            echo "$tableName table created successfully!<br>";
        } else {
            throw new Exception("Error creating $tableName table: " . mysqli_error($conn));
        }
    }

    echo "All tables created successfully!";

} catch (Exception $e) {
    echo "Database setup failed: " . $e->getMessage();
} finally {
    // Close connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>