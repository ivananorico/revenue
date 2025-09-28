<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json');
require 'db.php';
try {
    $stmt = $pdo->query("SELECT id, name FROM maps ORDER BY id DESC");
    $maps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'success','maps'=>$maps]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
