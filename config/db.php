<?php
// Local database configuration for development
$conn = new mysqli("localhost", "root", "", "cosmic_inventory");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>