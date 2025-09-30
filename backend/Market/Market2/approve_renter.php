<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once 'db.php'; // your database connection

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$id = (int)$data['id'];

// Update renter status and stall status
try {
    // Update renter
    $stmt = $pdo->prepare("UPDATE renters SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);

    // Get the stall_id of this renter
    $stmt2 = $pdo->prepare("SELECT stall_id FROM renters WHERE id = ?");
    $stmt2->execute([$id]);
    $stall_id = $stmt2->fetchColumn();

    // Update stall status
    if ($stall_id) {
        $stmt3 = $pdo->prepare("UPDATE stalls SET status = 'occupied' WHERE id = ?");
        $stmt3->execute([$stall_id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
