<?php
// Prevent any HTML output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Ensure we're sending JSON response headers first
header('Content-Type: application/json');

try {
    // Get the JSON data
    $json = file_get_contents('php://input');
    if (!$json) {
        throw new Exception('No data received');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    if (!isset($data['orderID']) || !isset($data['orderDate']) || !isset($data['total'])) {
        throw new Exception('Missing required order data');
    }

    // Database connection with error handling
    $conn = new mysqli("localhost", "root", "", "tables");
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get customer email from database based on order ID
    $query = "SELECT c.email 
              FROM customers c 
              JOIN orders o ON c.customer_id = o.customer_id 
              WHERE o.order_id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $data['orderID']);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    if (!$customer) {
        throw new Exception('Customer not found for order ID: ' . $data['orderID']);
    }

    // Email content
    $to = $customer['email'];
    $subject = "Your Order Receipt #" . $data['orderID'];
    $message = "
        <html>
        <head>
            <title>Order Receipt</title>
        </head>
        <body>
            <h2>Thank you for your order!</h2>
            <p>Order Details:</p>
            <ul>
                <li>Order ID: {$data['orderID']}</li>
                <li>Order Date: {$data['orderDate']}</li>
                <li>Total Amount: {$data['total']}</li>
            </ul>
        </body>
        </html>
    ";

    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@gizmogalaxy.com" . "\r\n";

    // Send email with error checking
    if (!mail($to, $subject, $message, $headers)) {
        throw new Exception('Failed to send email');
    }

    // Success response
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);

} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Close database connection if it exists
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>