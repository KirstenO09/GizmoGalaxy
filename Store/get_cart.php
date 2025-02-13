<?php
// get_cart.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Add if you're making cross-origin requests

try {
    // Include config file
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }
    include 'config.php';

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tables";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Assuming user_id is 1 for this example
    $user_id = 1;

    // Fetch cart items for the user
    $sql = "
        SELECT p.product_id, p.product_name AS name, p.price, p.image_url AS image, 
               ci.quantity, p.category
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE c.user_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }

    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = [
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'image' => $row['image'],
            'quantity' => (int)$row['quantity'],
            'category' => $row['category']
        ];
    }

    // Check if we got any results
    if (empty($cart)) {
        echo json_encode([
            'cart' => [],
            'message' => 'No items found in cart'
        ]);
    } else {
        echo json_encode([
            'cart' => $cart,
            'message' => 'Cart retrieved successfully'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>