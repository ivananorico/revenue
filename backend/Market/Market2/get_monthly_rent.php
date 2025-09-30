<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "db_market.php"; // PDO connection

$renter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$renter_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM monthly_rent
        WHERE renter_id = ?
        ORDER BY rent_month ASC
    ");
    $stmt->execute([$renter_id]);
    $monthly_rent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($monthly_rent);
} catch (Exception $e) {
    echo json_encode([]);
}
    