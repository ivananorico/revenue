<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");

require_once "db.php"; // adjust path if needed

// Fetch renters + stall info
$sql = "SELECT r.id, r.renter_ref, r.full_name, r.contact_number, r.email, r.address, 
               r.date_reserved, r.status AS renter_status, 
               s.name AS stall_name, s.status AS stall_status
        FROM renters r
        JOIN stalls s ON r.stall_id = s.id";

$stmt = $pdo->query($sql);
$renters = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($renters);
