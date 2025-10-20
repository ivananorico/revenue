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
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

$application_id = 1; // Test with your application ID

// Check if application exists and is assessed
$sql = "SELECT * FROM business_applications WHERE id = ? AND status = 'assessed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No assessed application found with ID: ' . $application_id
    ]);
    exit();
}

$application = $result->fetch_assoc();

// Check assessment data
$assessment_sql = "SELECT * FROM business_assessments WHERE application_id = ?";
$stmt = $conn->prepare($assessment_sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$assessment_result = $stmt->get_result();
$assessment = $assessment_result->num_rows > 0 ? $assessment_result->fetch_assoc() : null;

echo json_encode([
    'success' => true,
    'application' => $application,
    'assessment' => $assessment,
    'message' => 'Data found for application ID: ' . $application_id
]);

$conn->close();
?>