<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");

header('Content-Type: application/json');
require 'db.php';

if (!isset($_GET['map_id'])) {
    echo json_encode(['status'=>'error','message'=>'map_id is required']);
    exit;
}

$map_id = $_GET['map_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM maps WHERE id = ?");
    $stmt->execute([$map_id]);
    $map = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$map) {
        echo json_encode(['status'=>'error','message'=>'Map not found']);
        exit;
    }

    $stmt2 = $pdo->prepare("SELECT * FROM stalls WHERE map_id = ?");
    $stmt2->execute([$map_id]);
    $stalls = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $map['stalls'] = $stalls;

    echo json_encode(['status'=>'success','map'=>$map]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
