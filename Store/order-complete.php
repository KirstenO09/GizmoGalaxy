<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tables");      
session_start();

$orderID = null;
$total = 0.00;
$order_date = '';
$error = '';
$order_items = array();
$shipping = 10.00; 

// Check if orderID is provided
if (isset($_GET['orderID'])) {
    $orderID = intval($_GET['orderID']);
    
    // Updated query to calculate price * quantity for each item
    $query = "SELECT o.order_id, o.total_amount, o.order_date, 
              p.name as product_name, oi.quantity, 
              (oi.quantity * oi.price) as total_price, 
              oi.price as unit_price
              FROM orders o
              LEFT JOIN order_items oi ON o.order_id = oi.order_id
              LEFT JOIN products p ON oi.product_id = p.product_id
              WHERE o.order_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $orderID);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $first_row = true;
        $subtotal = 0;
        
        while ($row = $result->fetch_assoc()) {
            if ($first_row) {
                $total_amount = $row['total_amount'];
                $order_date = date('F j, Y', strtotime($row['order_date']));
                $first_row = false;
            }
            if ($row['product_name']) {
                $order_items[] = array(
                    'name' => $row['product_name'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total_price' => $row['total_price']
                );
                $subtotal += $row['total_price'];
            }
        }
        
        $grand_total = $subtotal + $shipping;
        
        if (empty($order_items)) {
            $error = "No items found for this order.";
        }
    } else {
        $error = "Error retrieving order details: " . $conn->error;
    }
} else {
    $error = "No order ID provided.";
}

// Retrieve trade-in value for the logged-in user
$trade_in_value = 0.00; 
if (isset($_SESSION['user_id'])) {
    $query = "SELECT SUM(trade_in_value) AS trade_in_value 
              FROM trade_in 
              WHERE user_id = ? AND status = 'approved'";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
            if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $trade_in_value = isset($row['trade_in_value']) ? floatval($row['trade_in_value']) : 0.00;
            } else {
                error_log("Failed to fetch trade-in result: " . $conn->error);
            }
        } else {
            error_log("Trade-in query execution failed: " . $stmt->error);
        }
    } else {
        error_log("Trade-in query preparation failed: " . $conn->error);
    }
}

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
    
    
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <!-- Success Icon -->
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php else: ?>
                            <h2 class="mb-4">Order Complete!</h2>
                            <p class="text-muted mb-4">Thank you for your purchase. We've received your order.</p>
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col text-start">Order ID:</div>
                                        <div class="col text-end" id="orderID"><?php echo htmlspecialchars($orderID); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col text-start">Order Date:</div>
                                        <div class="col text-end" id="orderDate"><?php echo htmlspecialchars($order_date); ?></div>
                                    </div>
                                    
                                      <!-- Product Details -->
                                    <div class="order-items text-start mb-3">
                                        <h6 class="mb-3">Order Items:</h6>
                                        <?php foreach ($order_items as $item): ?>
                                            <div class="row mb-2 product-item">
                                                <div class="col-6 product-name">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                    <small class="d-block text-muted">
                                                        $<?php echo number_format($item['unit_price'], 2); ?> each
                                                    </small>
                                                </div>
                                                <div class="col-3 text-center product-quantity">
                                                    x<?php echo htmlspecialchars($item['quantity']); ?>
                                                </div>
                                                <div class="col-3 text-end product-price">
                                                    $<?php echo number_format($item['total_price'], 2); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <hr>
                                    <!-- Order Summary -->
                                    <div class="row mb-2">
                                        <div class="col text-start">Subtotal:</div>
                                        <div class="col text-end" id="subtotal">
                                            $<?php echo number_format($subtotal, 2); ?>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col text-start">Shipping:</div>
                                        <div class="col text-end" id="shipping">
                                            $<?php echo number_format($shipping, 2); ?>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                    <div class="col text-start">Trade-In Value:</div>
                                    <div class="col text-end">-$40<?php echo number_format($trade_in_value, 2); ?>
                                    </div>
                                </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col text-start"><strong>Total:</strong></div>
                                        <div class="col text-end">
                                            <strong id="total">$<?php echo number_format($grand_total, 2); ?></strong>
                                        </div>
                                    </div>
                        <?php endif; ?>
                        <!-- Action Buttons -->
                        <button class="btn btn-outline-secondary me-2" onclick="emailReceipt()">
                            Email Receipt
                        </button>
                        <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                            Print Receipt
                        </button>
                        <a href="shop.php" class="btn btn-primary">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <!-- Update the email receipt JavaScript -->
   <script>
function emailReceipt() {
    // Get the order details
    const orderID = document.getElementById('orderID').textContent.trim();
    const orderDate = document.getElementById('orderDate').textContent.trim();
    const subtotal = document.getElementById('subtotal').textContent.trim();
    const shipping = document.getElementById('shipping').textContent.trim();
    const trade_in_value = document.getElementById('trade_in_value').textContent.trim();
    const total = document.getElementById('total').textContent.trim();
    
    // Get all product items with clean formatting
    let productsText = '';
    const productItems = document.querySelectorAll('.product-item');
    
    productItems.forEach(item => {
        const name = item.querySelector('.product-name').childNodes[0].textContent.trim();
        const quantity = item.querySelector('.product-quantity').textContent.trim();
        const price = item.querySelector('.product-price').textContent.trim();
        productsText += `${name} ${quantity} - ${price}\n`;
    });
    
    // Create email content with clean formatting
    const subject = `Order Receipt #${orderID}`;
    const body = `Thank you for your order!

Order Details:
Order ID: ${orderID}
Order Date: ${orderDate}

Order Items:
${productsText}
Subtotal: ${subtotal}
Shipping: ${shipping}
Trade-In Credit: ${trade_in_value}
Total Amount: ${total}

Best regards,
Gizmo Galaxy`;

    const mailtoUrl = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  
    window.location.href = mailtoUrl;
}
</script>
</body>
</html>
