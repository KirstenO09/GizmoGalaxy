<?php
$servername = "localhost";
$username = "root";     // Default XAMPP MySQL username is 'root'
$password = "";         // Default XAMPP MySQL password is empty
$dbname = "tables"; // Replace with your database name

try {
    $pdo = new PDO("mysql:servername=localhost;dbname=tables", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Remove or comment out any echo/print statements
    // Don't echo "Connected successfully" here
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'error' => "Connection failed: " . $e->getMessage()
    ]));
}
?>
