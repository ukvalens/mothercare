<?php
include('../connection.php'); // your database connection file

$sql = "ALTER TABLE `appointments` ADD COLUMN `notes` TEXT AFTER `type`";

if (mysqli_query($conn, $sql)) {
    echo "✅ Column 'notes' added successfully.";
} else {
    echo "❌ Error adding column: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
