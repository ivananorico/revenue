<?php
// save_stalls.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST allowed');
    }

    $body = file_get_contents('php://input');
    $payload = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    if (empty($payload['map_id']) || !isset($payload['stalls']) || !is_array($payload['stalls'])) {
        throw new Exception('Missing map_id or stalls');
    }

    require_once __DIR__ . '/db.php';
    if (!isset($pdo) || !$pdo) {
        throw new Exception('DB connection not available (check db.php)');
    }

    $mapId = (int)$payload['map_id'];
    $stalls = $payload['stalls'];

    $pdo->beginTransaction();

    // delete existing stalls for this map
    $pdo->prepare("DELETE FROM stalls WHERE map_id = ?")->execute([$mapId]);

    $insert = $pdo->prepare("INSERT INTO stalls (map_id, name, pos_x, pos_y, status) VALUES (?, ?, ?, ?, ?)");

    foreach ($stalls as $s) {
        $name = isset($s['name']) ? trim($s['name']) : 'Stall';
        $x = isset($s['pos_x']) ? (int)$s['pos_x'] : 0;
        $y = isset($s['pos_y']) ? (int)$s['pos_y'] : 0;
        $status = (isset($s['status']) && in_array($s['status'], ['available','reserved','occupied'])) ? $s['status'] : 'available';
        $insert->execute([$mapId, $name, $x, $y, $status]);
    }

    $pdo->commit();

    echo json_encode(['status' => 'success']);
    exit;
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("save_stalls.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
