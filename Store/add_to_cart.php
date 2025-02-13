<?php
// add_to_cart.php
$conn = mysqli_connect("localhost", "root", "", "tables");

if(isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Check if item already exists in cart
    $result = mysqli_query($conn, "SELECT cart_id FROM cart_items WHERE product_id = $product_id");
    
    if(mysqli_num_rows($result) > 0) {
        // Update quantity if item exists
        mysqli_query($conn, "UPDATE cart_items SET quantity = quantity + 1 WHERE product_id = $product_id");
    } else {
        // Add new item to cart
        mysqli_query($conn, "INSERT INTO cart_items (product_id, quantity) VALUES ($product_id, 1)");
    }
    
    echo json_encode(['success' => true]);
}
?>