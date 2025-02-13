<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    $conn = new mysqli("localhost", "root", "", "gizmogalaxy");
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $tests = [
        "Database connection" => true,
        "Tables exist" => [],
        "Sample data" => []
    ];
    
    // Check tables
    $required_tables = ['cart', 'cart_items', 'products'];
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $tests["Tables exist"][$table] = $result->num_rows > 0;
    }
    
    // Check sample data
    $tests["Sample data"]["products"] = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $tests["Sample data"]["cart"] = $conn->query("SELECT COUNT(*) as count FROM cart")->fetch_assoc()['count'];
    
    echo json_encode([
        "success" => true,
        "tests" => $tests
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>