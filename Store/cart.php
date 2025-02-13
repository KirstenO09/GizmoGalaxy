<?php
session_start(); 
include 'db.php';
$isSignedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$trade_in_value = 0;
$conn = mysqli_connect("localhost", "root", "", "tables");
$userName = $userEmail = $userDescription = '';
$response = array();

// Fetch trade-in value if user is signed in
if ($isSignedIn) {
    // If the user is signed in
    $user_id = $_SESSION['user_id'];

    // Prepare the SQL statement to fetch trade-in value (no status filter)
    $stmt = $conn->prepare("SELECT trade_in_value FROM trade_in WHERE user_id = ? LIMIT 1");
    
    // Check if the preparation was successful
    if (!$stmt) {
        die('SQL Error: ' . $conn->error); // Catch SQL preparation errors
    }

    $stmt->bind_param("i", $user_id); // Bind the user_id to the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any results are returned
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $trade_in_value = floatval($row['trade_in_value']); // Get the trade-in value
    } else {
        echo "";
    }

    $stmt->close();
} elseif (isset($_SESSION['trade_in_value'])) {
    // For guest users, use the session value
    $trade_in_value = floatval($_SESSION['trade_in_value']);
} else {
    // Default value if no trade-in information
    $trade_in_value = 0;
}

 

if(isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    switch($action) {
        case 'increase':
            mysqli_query($conn, "UPDATE cart_items SET quantity = quantity + 1 WHERE product_id = $product_id");
            break;
        case 'decrease':
            mysqli_query($conn, "UPDATE cart_items SET quantity = GREATEST(quantity - 1, 1) WHERE product_id = $product_id");
            break;
        case 'remove':
            mysqli_query($conn, "DELETE FROM cart_items WHERE product_id = $product_id");
            break;
    }
    
    // Get updated totals
    $result = mysqli_query($conn, "SELECT SUM(p.price * c.quantity) as total 
                                  FROM cart_items c 
                                  JOIN products p ON c.product_id = p.product_id");
    $row = mysqli_fetch_assoc($result);
    
    // Get updated quantity for the specific item
    $qty_result = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE product_id = $product_id");
    $qty_row = mysqli_fetch_assoc($qty_result);
    
    $response = array(
        'subtotal' => number_format($row['total'], 2),
        'total' => number_format($row['total'] + 10, 2), // Adding $10 shipping
        'quantity' => $qty_row ? $qty_row['quantity'] : 0
    );
    
    echo json_encode($response);
    exit;

}

// Handle order submission
if(isset($_POST['checkout'])) {
    // Calculate final total from cart
    $result = mysqli_query($conn, "SELECT SUM(p.price * c.quantity) as subtotal 
                                  FROM cart_items c 
                                  JOIN products p ON c.product_id = p.product_id");
    $row = mysqli_fetch_assoc($result);
    $subtotal = $row['subtotal'] ?? 0;
    $total_amount = $subtotal + 10; // Adding $10 shipping

    // Generate random order ID between 100000 and 999999
    $order_id = mt_rand(100000, 999999);

    // Make sure order_id is unique
    $check_query = "SELECT order_id FROM orders WHERE order_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $order_id);
    
    // Keep generating new order_id until we find a unique one
    while(true) {
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if($result->num_rows == 0) break;
        $order_id = mt_rand(100000, 999999);
    }
    
    // Keep your existing order insertion
$query = "INSERT INTO orders (order_id, total_amount, order_date) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("id", $order_id, $total_amount);

if($stmt->execute()) {
    // Add new code here to insert order items
    $cart_query = "SELECT c.product_id, c.quantity, p.price 
                   FROM cart_items c 
                   JOIN products p ON c.product_id = p.product_id";
    $cart_result = mysqli_query($conn, $cart_query);
    
    // Prepare order items insertion statement
    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    
    $success = true;
    
    // Insert each cart item into order_items
    while($cart_result && $cart_item = mysqli_fetch_assoc($cart_result)) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $price = $cart_item['price'];
        
        if(!$item_stmt->execute()) {
            $success = false;
            error_log("Failed to insert order item: " . $conn->error);
            break;
        }
    }
    
    if ($success) {
        // Reset the trade-in value to NULL or 0 after the order is completed
        if ($isSignedIn) {
            // Update trade-in value to 0 or NULL
            $reset_trade_in_query = "UPDATE trade_in SET trade_in_value = 0 WHERE user_id = ?";
            $reset_trade_in_stmt = $conn->prepare($reset_trade_in_query);
            $reset_trade_in_stmt->bind_param("i", $user_id);
            $reset_trade_in_stmt->execute();
        }
        // Keep your existing cart clearing and redirect
        mysqli_query($conn, "DELETE FROM cart_items");
        header("Location: order-complete.php?orderID=" . $order_id);
        exit();
    } else {
        // If order items insertion failed, delete the main order
        mysqli_query($conn, "DELETE FROM orders WHERE order_id = " . $order_id);
        echo "Error creating order: Failed to insert order items";
    }
} else {
    echo "Error creating order: " . $conn->error;
}
}

// Get cart total for display
$result = mysqli_query($conn, "SELECT SUM(p.price * c.quantity) as subtotal 
                              FROM cart_items c 
                              JOIN products p ON c.product_id = p.product_id");
$row = mysqli_fetch_assoc($result);
$subtotal = $row['subtotal'] ?? 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.72.0">

    <title>Gizmo Galaxy</title>
    <link rel="icon" href="img/logo.png">

    <link rel="canonical" href="https://v5.getbootstrap.com/docs/5.0/examples/product/">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css"
        integrity="sha384-r4NyP46KrjDleawBgD5tp8Y7UzmLA05oM1iAEQ17CSuDqnUK2+k9luXQOfXJCJ4I" crossorigin="anonymous">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.min.js"
        integrity="sha384-oesi62hOLfzrys4LxRF63OJCXdXDipiYWBnvTl9Y9/TRlw5xlKIEHpNyvvDShgf/"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
        crossorigin="anonymous"></script>
    <!-- Link to external stylesheet -->
     <link rel="stylesheet" href="styles.css"> <!--Adjust the path if necessary -->

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
   <!-- Navbar -->
   <nav class="site-header sticky-top py-1">
        <div class="container d-flex flex-column flex-md-row justify-content-between">
            <a class="py-2" href="#" aria-label="Product">
                <img src="img/logo.png" alt="Logo" class="navbar-logo">
            </a>
            <a class="py-2 d-none d-md-inline-block" href="index.php">Home</a>
            <a class="py-2 d-none d-md-inline-block" href="shop.php">Shop</a>
            <a class="py-2 d-none d-md-inline-block" href="about.php">About Us</a>
            <a class="py-2 d-none d-md-inline-block" href="trade in.php">Trade-in</a>
            <?php if ($isSignedIn): ?>
                <a class="py-2 d-none d-md-inline-block" href="profile.php">Profile</a>
            <?php else: ?>
                <a class="py-2 d-none d-md-inline-block" href="sign in.php">Sign In</a>
            <?php endif; ?>
            <a class="py-2 d-none d-md-inline-block" href="cart.php">Cart</a>
        </div>
    </nav>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Cart Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="cart-items">
                            <?php
                            $result = mysqli_query($conn, "SELECT c.*, p.* 
                                                         FROM cart_items c 
                                                         JOIN products p ON c.product_id = p.product_id");
                            $subtotal = 0;
                            
                            while($row = mysqli_fetch_assoc($result)) {
                                $item_total = $row['price'] * $row['quantity'];
                                $subtotal += $item_total;
                                ?>
                                <div class="cart-item mb-3 pb-3 border-bottom" data-product-id="<?php echo $row['product_id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?php echo $row['image_url']; ?>" class="img-fluid" alt="<?php echo $row['name']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <h6><?php echo $row['name']; ?></h6>
                                            <small class="text-muted"><?php echo $row['category']; ?></small>
                                        </div>
                                        <div class="col-md-2">
                                            $<?php echo number_format($row['price'], 2); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-sm btn-outline-secondary quantity-btn decrease">-</button>
                                            <span class="quantity"><?php echo $row['quantity']; ?></span>
                                            <button class="btn btn-sm btn-outline-secondary quantity-btn increase">+</button>
                                        </div>
                                        <div class="col-md-2 text-right">
                                            <button class="btn btn-sm btn-danger remove">Remove</button>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span id="subtotal">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span id="shipping">$10.00</span>
                        </div>

                          <!-- Display Trade-in Value if available -->
            <?php if ($trade_in_value > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Estimated Trade-in Credit</span>
                    <span id="trade-in-value">-$<?php echo number_format($trade_in_value, 2); ?></span>
                </div>
            <?php endif; ?>


                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong id="total">$<?php echo number_format($subtotal + 10, 2); ?></strong>
                        </div>

                        <form method="POST" action="cart.php">
                <input type="hidden" name="checkout" value="1">
                <button type="submit" id="checkout-button" class="btn btn-primary w-100">Complete Order</button>
            </form>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Your existing shipping and payment information sections here -->
         <!-- Shipping Information -->
         <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="state" class="form-label">State</label>
                                    <select class="form-select" id="state" required>
                                        <option value="">Choose...</option>
                                        <option>...</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="zip" class="form-label">Zip</label>
                                    <input type="text" class="form-control" id="zip" required>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <!-- Payment Information Section -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="cardName" class="form-label">Name on Card</label>
                        <input type="text" class="form-control" id="cardName" required>
                    </div>
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" 
                               placeholder="1234 5678 9012 3456" 
                               maxlength="19" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="expiryDate" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiryDate" 
                                   placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" 
                                   placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="saveCard">
                            <label class="form-check-label" for="saveCard">
                                Save this card for future purchases
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



    <footer class="container py-5">
        <div class="row justify-content-between align-items-center">
            <!-- Copyright Section -->
            <div class="col-md-6 text-center text-md-left">
                <small class="text-muted">&copy; Copyright Gizmo Galaxy 2024</small>
            </div>
            
            <!-- Contact Section -->
            <div class="col-md-6 text-center text-md-right">
                <h5 class="contact-title">Contact Us</h5>
                <div class="contact-icons">
                    <a href="mailto:contact@gismosgalaxy.com" target="_blank">
                        <img src="img/mail.png" alt="Email" class="contact-icon">
                    </a>
                    <a href="https://www.instagram.com/gismosgalaxy" target="_blank" rel="noopener noreferrer">
                        <img src="img/ig.png" alt="Instagram" class="contact-icon">
                    </a>
                    <a href="https://www.facebook.com/gismosgalaxy" target="_blank" rel="noopener noreferrer">
                        <img src="img/fb.png" alt="Facebook" class="contact-icon">
                    </a>
                </div>
            </div>
        </div>
    </footer>


    <script>
    $(document).ready(function() {
    $('.quantity-btn').click(function() {
        const button = $(this);
        const cartItem = button.closest('.cart-item');
        const productId = cartItem.data('product-id');
        const quantitySpan = cartItem.find('.quantity');
        const currentQuantity = parseInt(quantitySpan.text());
        const action = button.hasClass('increase') ? 'increase' : 'decrease';
        
        // Don't decrease below 1
        if (action === 'decrease' && currentQuantity <= 1) {
            return;
        }
        
        // Update quantity display immediately
        if (action === 'increase') {
            quantitySpan.text(currentQuantity + 1);
        } else {
            quantitySpan.text(currentQuantity - 1);
        }
        
        updateCart(productId, action);
    });

    $('.remove').click(function() {
        const cartItem = $(this).closest('.cart-item');
        const productId = cartItem.data('product-id');
        
        updateCart(productId, 'remove');
        cartItem.remove();
    });

    function updateCart(productId, action) {
        $.ajax({
            url: 'cart.php',
            method: 'POST',
            data: {
                action: action,
                product_id: productId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    $('#subtotal').text('$' + data.subtotal);
                    $('#total').text('$' + data.total);
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                // Optionally revert the quantity display if the server update failed
                location.reload();
            }
        });
    }
});
    </script>

    
</body>
</html>