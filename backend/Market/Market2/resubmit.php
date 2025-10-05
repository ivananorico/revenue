<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../db/market_db.php"; // PDO connection

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = (int)$data['user_id'];
$comment = trim($data['comment']);

try {
    $stmt = $pdo->prepare("
        UPDATE renters 
        SET status = 'resubmit', 
            stall_status = 'reserved', 
            compliance_comment = :comment
        WHERE user_id = :user_id
    ");
    $stmt->execute([
        ':comment' => $comment,
        ':user_id' => $user_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Renter requested to resubmit successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
