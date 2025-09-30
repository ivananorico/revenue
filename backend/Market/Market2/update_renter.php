<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json');

require_once "db_market.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No renter ID provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE renters
        SET full_name = ?, contact_number = ?, email = ?, address = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['full_name'],
        $data['contact_number'],
        $data['email'],
        $data['address'],
        $data['id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Renter info updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
