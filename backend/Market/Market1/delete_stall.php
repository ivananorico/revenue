<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json");

require 'db_market.php'; // Make sure this file returns a $pdo PDO instance

$data = json_decode(file_get_contents("php://input"), true);
$stall_id = $data["stall_id"] ?? null;

if (!$stall_id) {
    echo json_encode(["status" => "error", "message" => "Missing stall_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM stalls WHERE id = :id");
    $stmt->bindParam(':id', $stall_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Stall deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No stall found with this ID"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete stall"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
