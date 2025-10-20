<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input);

// Log for debugging
error_log("Received data: " . print_r($data, true));

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No data received or invalid JSON'
    ]);
    exit();
}

if (empty($data->application_id) || empty($data->tax_rate_id) || empty($data->total_amount)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data: application_id, tax_rate_id, or total_amount'
    ]);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Update application status
    $updateApp = "UPDATE business_applications SET status = 'assessed' WHERE id = ?";
    $stmt = $conn->prepare($updateApp);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $data->application_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error updating application: ' . $stmt->error);
    }
    
    // Check if business_assessments table has tax_amount column, if not alter it
    $checkColumn = $conn->query("SHOW COLUMNS FROM business_assessments LIKE 'tax_amount'");
    if ($checkColumn->num_rows == 0) {
        $alterTable = "ALTER TABLE business_assessments ADD COLUMN tax_amount DECIMAL(15,2) AFTER total_amount";
        if (!$conn->query($alterTable)) {
            throw new Exception('Error altering table: ' . $conn->error);
        }
    }
    
    // Insert assessment record
    $insertAssessment = "INSERT INTO business_assessments 
                        (application_id, tax_rate_id, tax_rate, tax_name, total_amount, tax_amount, assessed_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertAssessment);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    // Use tax_amount from data or calculate it
    $tax_amount = isset($data->tax_amount) ? $data->tax_amount : 0;
    
    $stmt->bind_param("iidsdd", 
        $data->application_id, 
        $data->tax_rate_id, 
        $data->tax_rate, 
        $data->tax_name, 
        $data->total_amount,
        $tax_amount
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error creating assessment: ' . $stmt->error);
    }
    
    $assessment_id = $conn->insert_id;
    
    // Store selected regulatory fees
    if (!empty($data->regulatory_fees)) {
        // Check if assessment_fees table exists, if not create it
        $checkTable = $conn->query("SHOW TABLES LIKE 'assessment_fees'");
        if ($checkTable->num_rows == 0) {
            $createFeesTable = "CREATE TABLE assessment_fees (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT(11) NOT NULL,
                fee_id INT(11) NOT NULL,
                fee_name VARCHAR(255) NOT NULL,
                fee_amount DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (assessment_id) REFERENCES business_assessments(id) ON DELETE CASCADE,
                FOREIGN KEY (fee_id) REFERENCES regulatory_fee(id)
            )";
            
            if (!$conn->query($createFeesTable)) {
                throw new Exception('Error creating assessment_fees table: ' . $conn->error);
            }
        }
        
        foreach ($data->regulatory_fees as $fee) {
            $insertFee = "INSERT INTO assessment_fees 
                         (assessment_id, fee_id, fee_name, fee_amount) 
                         VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertFee);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param("iisd", 
                $assessment_id, 
                $fee->id, 
                $fee->name, 
                $fee->fee
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error inserting fee: ' . $stmt->error);
            }
        }
    }
    
    // Insert quarterly breakdown if provided
    if (!empty($data->quarterly_breakdown)) {
        foreach ($data->quarterly_breakdown as $quarter) {
            $insertQuarter = "INSERT INTO assessment_quarters 
                            (assessment_id, quarter_name, amount, due_date, days_remaining, total_days, percentage) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuarter);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            $due_date = date('Y-m-d', strtotime($quarter->dueDate));
            $stmt->bind_param("isdiiid", 
                $assessment_id, 
                $quarter->name, 
                $quarter->amount, 
                $due_date,
                $quarter->daysRemaining,
                $quarter->daysInQuarter,
                $quarter->proportionalPercentage
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error inserting quarter: ' . $stmt->error);
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Application assessed successfully',
        'assessment_id' => $assessment_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Assessment failed: ' . $e->getMessage()
    ]);
    
    // Log the error
    error_log("Assessment error: " . $e->getMessage());
} finally {
    $conn->close();
}
?>