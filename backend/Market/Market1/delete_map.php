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

$host = "localhost";
$port = 3307; // your port
$dbname = "market";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete stalls first
    $stmt = $pdo->prepare("DELETE FROM stalls WHERE map_id = ?");
    $stmt->execute([$map_id]);

    // Delete map
    $stmt = $pdo->prepare("DELETE FROM maps WHERE id = ?");
    $stmt->execute([$map_id]);

    echo json_encode(["status"=>"success"]);

} catch (PDOException $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
?>
