<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json');
include 'db_market.php';

try {
    // Use user_id as id for React compatibility
    $stmt = $pdo->query("
        SELECT 
            user_id as id,
            renter_ref,
            full_name,
            contact_number,
            email,
            date_reserved,
            status,
            stall_id,
            map_id,
            address,
            compliance_status,
            compliance_comment
        FROM renters
    ");
    
    $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($renters);
} catch (Exception $e) {
    error_log("Error fetching renters: " . $e->getMessage());
    echo json_encode([]);
}