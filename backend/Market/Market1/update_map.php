<?php
header('Content-Type: application/json');
include 'db_market.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['map_id']) || !isset($data['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$mapId = $data['map_id'];
$name = $data['name'];

try {
    $stmt = $pdo->prepare("UPDATE maps SET name = ? WHERE id = ?");
    $stmt->execute([$name, $mapId]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
