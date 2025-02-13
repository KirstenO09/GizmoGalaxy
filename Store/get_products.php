<?php
header('Content-Type: application/json');
$conn = mysqli_connect("localhost", "root", "", "tables");

if (!$conn) {
    die(json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]));
}

$result = mysqli_query($conn, "SELECT * FROM products");
$products = [];

while($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

echo json_encode($products);
mysqli_close($conn);
?>