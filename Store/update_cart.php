<?php
// update_cart.php

header('Content-Type: application/json');
include 'config.php'; // Include the config file

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tables";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['message' => 'Database connection failed']));
}

// Retrieve JSON data from the request body
$cartData = json_decode(file_get_contents("php://input"), true);

// Assuming user_id is 1 for this example
$user_id = 1;

// Ensure the user has a cart in the database
// If not, create one
$sql = "SELECT cart_id FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No cart exists for the user, create a new one
    $insertCart = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $insertCart->bind_param("i", $user_id);
    $insertCart->execute();
    $cart_id = $insertCart->insert_id;
    $insertCart->close();
} else {
    // Cart exists, get the cart_id
    $cartRow = $result->fetch_assoc();
    $cart_id = $cartRow['cart_id'];
}

$stmt->close();

// Clear existing items in cart_items for this user's cart
$clearCartItems = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
$clearCartItems->bind_param("i", $cart_id);
$clearCartItems->execute();
$clearCartItems->close();

// Insert updated cart items
$insertItem = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");

foreach ($cartData as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];

    $insertItem->bind_param("iii", $cart_id, $product_id, $quantity);
    $insertItem->execute();
}

$insertItem->close();

echo json_encode(['message' => 'Cart updated successfully']);

$conn->close();
?>
