<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "db_market.php"; // PDO connection

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, s.name AS stall_name, s.status AS stall_status
        FROM renters r
        JOIN stalls s ON r.stall_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $renter = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($renter ? [$renter] : []);
} catch (Exception $e) {
    echo json_encode([]);
}
