<?php 
session_start();
include 'db.php';
$isSignedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['trade_in_value']) && isset($data['device_name'])) {
        $trade_in_value = floatval($data['trade_in_value']);
        $device_name = $data['device_name'];

        if ($isSignedIn) {
            // Ensure $pdo is initialized for database operations
            try {
                $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $user_id = $_SESSION['user_id'];

                // Insert trade-in data
                $query = "INSERT INTO trade_in (user_id, device_name, trade_in_value) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$user_id, $device_name, $trade_in_value]);

                if ($stmt->rowCount() > 0) {
                    // Successfully inserted
                    echo json_encode([
                        'success' => true,
                        'message' => "Trade-in value of $$trade_in_value for $device_name successfully recorded."
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Failed to record your trade-in. Please try again."
                    ]);
                }
            } catch (PDOException $e) {
                // Log error for debugging and return a user-friendly message
                error_log($e->getMessage(), 3, 'errors.log');
                echo json_encode([
                    'success' => false,
                    'message' => "A database error occurred. Please try again later."
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => "You must be signed in to accept the trade-in."
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Invalid trade-in submission."
        ]);
    }
    exit;
}
?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gizmo Galaxy - Trade In</title>
    <link rel="icon" href="img/logo.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css" 
          integrity="sha384-r4NyP46KrjDleawBgD5tp8Y7UzmLA05oM1iAEQ17CSuDqnUK2+k9luXQOfXJCJ4I" crossorigin="anonymous">
    <link rel="stylesheet" href="trade in.css">
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

    <br> <br>

    <!-- Trade-In Form Section -->
    <section class="trade-in-form-container">
        <h2>Get Estimate</h2>
        <form id="tradeInForm">
            <label for="deviceName">Select Your Device:</label>
            <select id="deviceName" name="deviceName" required>
                <option value="iphone14">iPhone 14</option>
                <option value="oneplus9">OnePlus 9 5G</option>
                <option value="pixel9">Pixel 9</option>
                <option value="galaxyS22">Galaxy S22</option>
            </select>

            <label for="storageSize">Storage Size:</label>
            <select id="storageSize" name="storageSize" required>
                <option value="128">128 GB</option>
                <option value="256">256 GB</option>
            </select>

            <label for="deviceCondition">Is your device in good condition?</label>
            <small style="display:block; margin-bottom:5px;">
                Answer yes if your device turns on, is free of cracks, and the screen works properly.
            </small>
            <select id="deviceCondition" name="deviceCondition" required>
                <option value="yes">Yes</option>
                <option value="no">No</option>
            </select>

            <button type="submit">Get Trade-In Estimate</button>
            <p class="note">NOTE: The value of your device could change upon inspection</p>
        </form>

        <div id="tradeInEstimate"></div>
        <div id="tradeInConfirmation" style="display:none;">
            <center>
                <button id="acceptTradeIn">Accept</button>
                <button id="cancelTradeIn">Cancel</button>
            </center>
        </div>
    </section>

    <!-- Footer -->
    <footer class="container py-5">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6 text-center text-md-left">
                <small class="text-muted">&copy; Copyright Gizmo Galaxy 2024</small>
            </div>
            <div class="col-md-6 text-center text-md-right">
                <h5 class="contact-title">Contact Us</h5>
                <div class="contact-icons">
                    <a href="mailto:contact@gizmogalaxy.com" target="_blank">
                        <img src="img/mail.png" alt="Email" class="contact-icon">
                    </a>
                    <a href="https://www.instagram.com/gizmogalaxy" target="_blank">
                        <img src="img/ig.png" alt="Instagram" class="contact-icon">
                    </a>
                    <a href="https://www.facebook.com/gizmogalaxy" target="_blank">
                        <img src="img/fb.png" alt="Facebook" class="contact-icon">
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="trade in.js"></script>
</body>
</html>
