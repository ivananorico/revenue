<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['map_id'])) {
    echo json_encode(["status"=>"error","message"=>"map_id is required"]);
    exit;
}

$map_id = $data['map_id'];

require_once "db_market.php"; // PDO connection

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First, get all stall images and the map image before deleting
    $stmt = $pdo->prepare("SELECT image_path FROM stalls WHERE map_id = ?");
    $stmt->execute([$map_id]);
    $stallImages = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT image_path FROM maps WHERE id = ?");
    $stmt->execute([$map_id]);
    $mapImage = $stmt->fetchColumn();

    // Delete stalls first
    $stmt = $pdo->prepare("DELETE FROM stalls WHERE map_id = ?");
    $stmt->execute([$map_id]);

    // Delete map
    $stmt = $pdo->prepare("DELETE FROM maps WHERE id = ?");
    $stmt->execute([$map_id]);

    // Delete image files
    $basePath = $_SERVER['DOCUMENT_ROOT'] . '/revenue/';
    
    // Delete map image
    if ($mapImage && file_exists($basePath . $mapImage)) {
        unlink($basePath . $mapImage);
    }

    // Delete stall images
    foreach ($stallImages as $stallImage) {
        if ($stallImage && file_exists($basePath . $stallImage)) {
            unlink($basePath . $stallImage);
        }
    }

    echo json_encode(["status"=>"success", "message"=>"Map and associated images deleted successfully"]);

} catch (PDOException $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
?>