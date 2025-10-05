<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json');

include 'db_market.php';

try {
    $stmt = $pdo->query("
        SELECT 
            user_id AS id,       -- React expects id
            renter_ref,
            full_name,
            contact_number,
            email,
            date_reserved,
            status,
            stall_id
        FROM renters
    ");
    $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Make sure it’s always an array
    if (!$renters) $renters = [];

    echo json_encode($renters);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching renters',
        'error' => $e->getMessage()
    ]);
}
