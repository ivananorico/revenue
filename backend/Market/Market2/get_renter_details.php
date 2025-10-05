<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "db_market.php";

// Get the renter ID from React Router
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(["error" => "No ID provided"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*, 
            s.name AS stall_name, 
            s.status AS stall_status,
            s.width AS stall_width,
            s.height AS stall_height,
            s.length AS stall_length,
            (s.width * s.length) AS stall_area
        FROM renters r
        JOIN stalls s ON r.stall_id = s.id
        WHERE r.user_id = ?
    ");
    $stmt->execute([$id]);
    $renter = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($renter) {
        echo json_encode($renter);
    } else {
        echo json_encode(["error" => "Renter not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
