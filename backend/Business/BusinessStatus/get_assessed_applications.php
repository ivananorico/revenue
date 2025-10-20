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

// Get assessed applications
$sql = "SELECT 
            ba.*, 
            COALESCE(ba_assess.total_amount, ba.amount) as display_amount,
            ba_assess.assessed_at
        FROM business_applications ba
        LEFT JOIN business_assessments ba_assess ON ba.id = ba_assess.application_id
        WHERE ba.status = 'assessed'
        ORDER BY ba_assess.assessed_at DESC";

$result = $conn->query($sql);

$applications = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'applications' => $applications,
    'count' => count($applications)
]);

$conn->close();
?>