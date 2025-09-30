<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json');
include 'db.php'; // database connection

try {
    $stmt = $pdo->query("SELECT * FROM renters"); // adjust table name
    $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($renters);
} catch (Exception $e) {
    echo json_encode([]);
}
