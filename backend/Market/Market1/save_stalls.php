<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "db_market.php";

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Only POST allowed");
    if (!isset($_POST['mapName']) || !isset($_FILES['mapImage']) || !isset($_POST['stalls'])) {
        throw new Exception("mapName, mapImage, and stalls are required");
    }

    $mapName = trim($_POST['mapName']);
    $stalls = json_decode($_POST['stalls'], true);
    if ($stalls === null) throw new Exception("Invalid stalls JSON");

    $file = $_FILES['mapImage'];
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Upload error code: " . $file['error']);

    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) throw new Exception("Invalid file type");

    $uploadsDir = __DIR__ . '/../../../uploads';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

    $filename = 'map_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    $target = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) throw new Exception("Failed to move uploaded file");

    $imagePath = 'uploads/' . $filename;

    // Insert map
    $stmt = $pdo->prepare("INSERT INTO maps (name, image_path) VALUES (?, ?)");
    $stmt->execute([$mapName, $imagePath]);
    $mapId = $pdo->lastInsertId();

    // Insert stalls
    $stmtStall = $pdo->prepare("
        INSERT INTO stalls (map_id, name, pos_x, pos_y, status, price, height, length, width)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($stalls as $stall) {
        $stmtStall->execute([
            $mapId,
            $stall['name'],
            $stall['pos_x'],
            $stall['pos_y'],
            $stall['status'],
            $stall['price'],
            $stall['height'],
            $stall['length'],
            $stall['width']
        ]);
    }

    echo json_encode(['status' => 'success', 'map_id' => (int)$mapId, 'image_path' => $imagePath]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
