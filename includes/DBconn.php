<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ClothingStore');

// Create connection
function getDBConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database if it does not exist
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);

    // Select database
    $conn->select_db(DB_NAME);

    // Set charset
    $conn->set_charset("utf8mb4");

    return $conn;
}
?>
