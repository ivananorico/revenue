<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include 'db.php'; // adjust path if needed

$map_id = isset($_GET['map_id']) ? intval($_GET['map_id']) : 0;

try {
    // Fetch map from 'maps' table
    $stmt = $pdo->prepare("SELECT * FROM maps WHERE id = ?");
    $stmt->execute([$map_id]);
    $map = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$map) {
        echo json_encode(["status" => "error", "message" => "Map not found"]);
        exit;
    }

    // Fetch stalls for this map
    $stmt2 = $pdo->prepare("SELECT * FROM stalls WHERE map_id = ?");
    $stmt2->execute([$map_id]);
    $stalls = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "map" => [
            "id" => $map['id'],
            "name" => $map['name'],
            "image_path" => $map['image_path']
        ],
        "stalls" => $stalls
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
