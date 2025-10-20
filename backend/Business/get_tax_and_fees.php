<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "business";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Initialize arrays
$taxRates = [];
$fees = [];

// Get tax rates
$taxSql = "SELECT * FROM business_tax";
$taxResult = $conn->query($taxSql);

if ($taxResult->num_rows > 0) {
    while($row = $taxResult->fetch_assoc()) {
        $taxRates[] = $row;
    }
}

// Get regulatory fees
$feeSql = "SELECT * FROM regulatory_fee";
$feeResult = $conn->query($feeSql);

if ($feeResult->num_rows > 0) {
    while($row = $feeResult->fetch_assoc()) {
        $fees[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'taxRates' => $taxRates,
    'fees' => $fees
]);

$conn->close();
?>