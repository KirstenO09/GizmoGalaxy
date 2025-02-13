<?php
// config.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tables';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// cart_operations.php
header('Content-Type: application/json');

function getCart($userId) {
    global $conn;
    
    $sql = "SELECT p.product_id, p.name, p.price, p.image_url, ci.quantity 
            FROM cart_items ci 
            JOIN carts c ON ci.cart_id = c.cart_id 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE c.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartItems = [];
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
    
    return $cartItems;
}

function addToCart($userId, $productId, $quantity) {
    global $conn;
    
    // Check if user has an active cart
    $sql = "SELECT cart_id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new cart
        $sql = "INSERT INTO carts (user_id) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $cartId = $conn->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $cartId = $row['cart_id'];
    }
    
    // Check if product already exists in cart
    $sql = "SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cartId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $sql = "UPDATE cart_items SET quantity = quantity + ? WHERE cart_id = ? AND product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $quantity, $cartId, $productId);
    } else {
        // Insert new item
        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $cartId, $productId, $quantity);
    }
    
    return $stmt->execute();
}

function updateCartQuantity($userId, $productId, $quantity) {
    global $conn;
    
    $sql = "UPDATE cart_items ci 
            JOIN carts c ON ci.cart_id = c.cart_id 
            SET ci.quantity = ? 
            WHERE c.user_id = ? AND ci.product_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $quantity, $userId, $productId);
    return $stmt->execute();
}

function removeFromCart($userId, $productId) {
    global $conn;
    
    $sql = "DELETE ci FROM cart_items ci 
            JOIN carts c ON ci.cart_id = c.cart_id 
            WHERE c.user_id = ? AND ci.product_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $productId);
    return $stmt->execute();
}

// Handle API requests
$action = $_POST['action'] ?? '';
$userId = $_POST['userId'] ?? null; // You'll need to implement user authentication

switch ($action) {
    case 'getCart':
        echo json_encode(getCart($userId));
        break;
        
    case 'addToCart':
        $productId = $_POST['productId'] ?? null;
        $quantity = $_POST['quantity'] ?? 1;
        echo json_encode(['success' => addToCart($userId, $productId, $quantity)]);
        break;
        
    case 'updateQuantity':
        $productId = $_POST['productId'] ?? null;
        $quantity = $_POST['quantity'] ?? 1;
        echo json_encode(['success' => updateCartQuantity($userId, $productId, $quantity)]);
        break;
        
    case 'removeItem':
        $productId = $_POST['productId'] ?? null;
        echo json_encode(['success' => removeFromCart($userId, $productId)]);
        break;
}
?>