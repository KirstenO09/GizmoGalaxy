<?php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "tables"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Account registered successfully!'); window.location.href='sign in.php';</script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
