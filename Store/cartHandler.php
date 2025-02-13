<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database configuration
require_once 'db.php';

// Error handler to catch any PHP errors
function errorHandler($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
}
set_error_handler("errorHandler");

try {
    // Retrieve JSON input
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received');
    }

    $response = ['success' => false];

    // Check if action parameter exists
    if (!isset($data['action'])) {
        throw new Exception('No action specified');
    }

    $action = $data['action'];

    switch ($action) {
        case 'add':
            if (!isset($data['image'], $data['name'], $data['price'], $data['quantity'], $data['category'])) {
                throw new Exception('Missing product information');
            }
            
            $stmt = $pdo->prepare("INSERT INTO cart (image, name, price, quantity, category) VALUES (:image, :name, :price, :quantity, :category)");
            $stmt->execute([
                ':image' => $data['image'],
                ':name' => $data['name'],
                ':price' => $data['price'],
                ':quantity' => $data['quantity'],
                ':category' => $data['category']
            ]);
            $response['success'] = true;
            break;

        case 'fetch':
            $stmt = $pdo->query("SELECT * FROM cart");
            $response['cartItems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'update':
            if (!isset($data['id'], $data['quantity'])) {
                throw new Exception('Missing item ID or quantity');
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $stmt->execute([
                ':quantity' => $data['quantity'],
                ':id' => $data['id']
            ]);
            $response['success'] = true;
            break;

        case 'remove':
            if (!isset($data['id'])) {
                throw new Exception('Missing item ID');
            }
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = :id");
            $stmt->execute([':id' => $data['id']]);
            $response['success'] = true;
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);
?>