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

$application_id = $_GET['id'] ?? 0;

if (!$application_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No application ID provided'
    ]);
    exit();
}

// Get application details
$application_sql = "SELECT * FROM business_applications WHERE id = ?";
$stmt = $conn->prepare($application_sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$application_result = $stmt->get_result();

if ($application_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Application not found'
    ]);
    exit();
}

$application = $application_result->fetch_assoc();

// Get assessment details
$assessment_sql = "SELECT * FROM business_assessments WHERE application_id = ?";
$stmt = $conn->prepare($assessment_sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$assessment_result = $stmt->get_result();
$assessment = $assessment_result->num_rows > 0 ? $assessment_result->fetch_assoc() : null;

// Get regulatory fees
$fees_sql = "SELECT * FROM assessment_fees WHERE assessment_id = ?";
$stmt = $conn->prepare($fees_sql);
$assessment_id = $assessment ? $assessment['id'] : 0;
$regulatory_fees = [];
if ($assessment_id) {
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $fees_result = $stmt->get_result();
    while ($row = $fees_result->fetch_assoc()) {
        $regulatory_fees[] = $row;
    }
}

// Get quarterly breakdown
$quarters_sql = "SELECT * FROM assessment_quarters WHERE assessment_id = ? ORDER BY quarter_name";
$stmt = $conn->prepare($quarters_sql);
$quarterly_breakdown = [];
if ($assessment_id) {
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $quarters_result = $stmt->get_result();
    while ($row = $quarters_result->fetch_assoc()) {
        $quarterly_breakdown[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'application' => $application,
    'assessment' => $assessment,
    'regulatory_fees' => $regulatory_fees,
    'quarterly_breakdown' => $quarterly_breakdown
]);

$conn->close();
?>