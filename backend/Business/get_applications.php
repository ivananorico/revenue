<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "business";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->application_id) && !empty($data->tax_rate_id) && !empty($data->total_amount)) {
    
    // Update application status
    $updateApp = "UPDATE business_applications SET status = 'assessed' WHERE id = ?";
    $stmt = $conn->prepare($updateApp);
    $stmt->bind_param("i", $data->application_id);
    
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating application: ' . $conn->error
        ]);
        $conn->close();
        exit();
    }
    
    // First, check if assessments table exists, if not create it
    $checkTable = "CREATE TABLE IF NOT EXISTS business_assessments (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        application_id INT(11) NOT NULL,
        tax_rate_id INT(11) NOT NULL,
        tax_rate DECIMAL(5,2) NOT NULL,
        total_amount DECIMAL(15,2) NOT NULL,
        assessed_at DATETIME NOT NULL,
        FOREIGN KEY (application_id) REFERENCES business_applications(id)
    )";
    $conn->query($checkTable);
    
    // Insert assessment record
    $insertAssessment = "INSERT INTO business_assessments 
                        (application_id, tax_rate_id, tax_rate, total_amount, assessed_at) 
                        VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertAssessment);
    $stmt->bind_param("iisd", $data->application_id, $data->tax_rate_id, $data->tax_rate, $data->total_amount);
    
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating assessment: ' . $conn->error
        ]);
        $conn->close();
        exit();
    }
    
    $assessment_id = $conn->insert_id;
    
    // Insert quarterly breakdown if provided
    if (!empty($data->quarterly_breakdown)) {
        // Check if quarters table exists, if not create it
        $checkQuartersTable = "CREATE TABLE IF NOT EXISTS assessment_quarters (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            assessment_id INT(11) NOT NULL,
            quarter_name VARCHAR(50) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            due_date DATE NOT NULL,
            FOREIGN KEY (assessment_id) REFERENCES business_assessments(id)
        )";
        $conn->query($checkQuartersTable);
        
        foreach ($data->quarterly_breakdown as $quarter) {
            $insertQuarter = "INSERT INTO assessment_quarters 
                            (assessment_id, quarter_name, amount, due_date) 
                            VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuarter);
            $due_date = date('Y-m-d', strtotime($quarter->dueDate));
            $stmt->bind_param("isds", $assessment_id, $quarter->name, $quarter->amount, $due_date);
            $stmt->execute();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Application assessed successfully',
        'assessment_id' => $assessment_id
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data'
    ]);
}

$conn->close();
?>