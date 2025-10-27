<?php
// Database configuration
$host = "localhost";  // or "localhost"
$user = "root";       // your MySQL username
$password = "";       // your MySQL password
$database = "maternalcare"; // your database name

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//echo "Connected successfully";
?>
