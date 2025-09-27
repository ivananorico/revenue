<?php
// upload_map.php
// NO BOM or whitespace before <?php

// Allow React dev server / other origins to call this
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

// Log PHP errors to a file (do not display them to the client)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

ob_start(); // capture any stray output so JSON stays clean

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

    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
        throw new Exception('Failed to create uploads directory');
    }

    // unique filename
    $filename = 'map_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    $target = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Build public URL to the uploaded file
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /revenue/backend/Market/Market1
    $baseUrl = $protocol . '://' . $host . $scriptDir;
    $imageUrl = $baseUrl . '/uploads/' . $filename;

    // insert into DB (expects $pdo from db.php)
    require_once __DIR__ . '/db.php'; // make sure this file sets $pdo (PDO)
    if (!isset($pdo) || !$pdo) {
        throw new Exception('DB connection not available (check db.php)');
    }

    $stmt = $pdo->prepare("INSERT INTO maps (name, image_path) VALUES (?, ?)");
    $stmt->execute([$mapName, 'uploads/' . $filename]);
    $mapId = $pdo->lastInsertId();

    ob_end_clean(); // drop any stray output
    echo json_encode([
        'status' => 'success',
        'map_id' => (int)$mapId,
        'image_path' => $imageUrl
    ]);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    // log error
    error_log("upload_map.php error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
