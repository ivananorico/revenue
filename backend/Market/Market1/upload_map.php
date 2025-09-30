<?php
// upload_map.php

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

    if (!isset($_POST['mapName']) || !isset($_FILES['mapImage'])) {
        throw new Exception('Missing mapName or mapImage');
    }

    $mapName = trim((string)$_POST['mapName']);
    $file = $_FILES['mapImage'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error code: ' . $file['error']);
    }

    // Validate extension
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed));
    }

    // Set uploads folder OUTSIDE backend
    $uploadsDir = __DIR__ . '/../../../uploads'; // adjust path: backend/Market/Market1 → project root uploads
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
        throw new Exception('Failed to create uploads directory');
    }

    // Unique filename
    $filename = 'map_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    $target = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Path to store in DB (relative to project root)
    $imagePath = 'uploads/' . $filename;

    // Insert into DB
    require_once __DIR__ . '/db_market.php';
    if (!isset($pdo) || !$pdo) {
        throw new Exception('DB connection not available');
    }

    $stmt = $pdo->prepare("INSERT INTO maps (name, image_path) VALUES (?, ?)");
    $stmt->execute([$mapName, $imagePath]);
    $mapId = $pdo->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'map_id' => (int)$mapId,
        'image_path' => $imagePath
    ]);

} catch (Exception $e) {
    error_log("upload_map.php error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
