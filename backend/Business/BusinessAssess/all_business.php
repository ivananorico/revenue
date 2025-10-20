<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost:3307';
$dbname = 'business';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get status filter from query parameters
    $status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    
    // Validate status
    $valid_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'pending';
    }
    
    // Fetch applications with the specified status
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            owner_name,
            application_ref,
            business_name,
            business_type,
            amount,
            tax_base_type,
            tin_id,
            full_address,
            application_date,
            status,
            created_at
        FROM business_applications 
        WHERE status = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$status]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'count' => count($applications),
        'status' => $status
    ]);
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'applications' => []
    ]);
}
?>