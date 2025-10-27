<?php
include('../connection.php'); // your database connection file

$sql = "ALTER TABLE `patients` ADD COLUMN `user_id` TEXT AFTER `patient_id`";

if (mysqli_query($conn, $sql)) {
    echo "✅ Column 'notes' added successfully.";
} else {
    echo "❌ Error adding column: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
