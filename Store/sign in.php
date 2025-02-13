<?php
session_start(); // Start session to track user login status
$isSignedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '';

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tables";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the database connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);  // Sanitize email input
    $password = $_POST['password'];

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['full_name'];  // Store the full name in session
            header("Location: profile.php"); // Redirect to profile page
            exit();
        } else {
            echo "<script>alert('Invalid password. Please try again.'); window.location.href='sign in.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('No account found with that email.'); window.location.href='sign in.php';</script>";
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to Gizmo Galaxy">
    <meta name="author" content="Gizmo Galaxy Team">
    <title>Sign In | Gizmo Galaxy</title>
    <link rel="icon" href="img/logo.png">
    <link rel="stylesheet" href="sign in.css"> 

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css"
        integrity="sha384-r4NyP46KrjDleawBgD5tp8Y7UzmLA05oM1iAEQ17CSuDqnUK2+k9luXQOfXJCJ4I" crossorigin="anonymous">
</head>

<body>
    <!-- Navbar -->
    <nav class="site-header sticky-top py-1">
        <div class="container d-flex flex-column flex-md-row justify-content-between">
            <!-- Logo -->
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

    <!-- Form Section (Sign In / Sign Up) -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <!-- Sign In Form -->
            <div class="col-md-5 form-box border p-4">
                <h2>Sign In</h2>
                <form action="sign in.php" method="POST">
                    <label for="signin-email">Email:</label>
                    <input type="email" id="signin-email" name="email" placeholder="Enter your email" required class="form-control mb-3">
                    
                    <label for="signin-password">Password:</label>
                    <input type="password" id="signin-password" name="password" placeholder="Enter your password" required class="form-control mb-3">
                    
                    <button type="submit" class="btn btn-primary w-100">Sign In</button>
                </form>
            </div>

            <!-- Separator (vertical line) -->
            <div class="col-md-1 d-flex align-items-center justify-content-center">
                <span class="separator">OR</span>
            </div>

            <!-- Sign Up Form -->
            <div class="col-md-5 form-box border p-4">
                <h2>Sign Up</h2>
                <form action="sign up.php" method="POST">
                    <label for="signup-fullname">Full Name:</label>
                    <input type="text" id="signup-fullname" name="fullname" placeholder="Enter your full name" required class="form-control mb-3">
                    
                    <label for="signup-email">Email:</label>
                    <input type="email" id="signup-email" name="email" placeholder="Enter your email" required class="form-control mb-3">
                    
                    <label for="signup-password">Password:</label>
                    <input type="password" id="signup-password" name="password" placeholder="Create a password" required class="form-control mb-3">
                    
                    <p class="terms-text mt-3">
                        By creating an account, you agree to our 
                        <a href="terms.pdf" target="_blank">Terms & Conditions</a>.
                    </p>
                    
                    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
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

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.min.js"
        integrity="sha384-oesi62hOLfzrys4LxRF63OJCXdXDipiYWBnvTl9Y9/TRlw5xlKIEHpNyvvDShgf/" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
        crossorigin="anonymous"></script>
</body>

</html>
