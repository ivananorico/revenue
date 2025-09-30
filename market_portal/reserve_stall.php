<?php
require_once "db_market.php";

if($_SERVER['REQUEST_METHOD']==='POST'){
    $stall_id = $_POST['stall_id'] ?? 0;
    $map_id = $_POST['map_id'] ?? 0;
    $full_name = $_POST['full_name'] ?? '';
    $contact = $_POST['contact_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';

    if(!$stall_id || !$map_id || !$full_name){
        echo json_encode(['status'=>'error','message'=>'Missing required fields']);
        exit;
    }

    // Check if stall is available
    $stmt = $pdo->prepare("SELECT status FROM stalls WHERE id=?");
    $stmt->execute([$stall_id]);
    $stall = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$stall || $stall['status'] !== 'available'){
        echo json_encode(['status'=>'error','message'=>'Stall not available']);
        exit;
    }

    // Generate renter_ref like renter-1001
    $lastRefStmt = $pdo->query("SELECT renter_ref FROM renters ORDER BY id DESC LIMIT 1");
    $lastRef = $lastRefStmt->fetchColumn();

    if ($lastRef) {
        // Extract number and increment
        preg_match('/renter-(\d+)/', $lastRef, $matches);
        $nextNumber = isset($matches[1]) ? (int)$matches[1]+1 : 1001;
    } else {
        $nextNumber = 1001; // starting number
    }

    $renter_ref = 'renter-' . $nextNumber;

    // Insert renter
    $stmt2 = $pdo->prepare("INSERT INTO renters (renter_ref, stall_id, map_id, full_name, contact_number, email, address) VALUES (?,?,?,?,?,?,?)");
    $stmt2->execute([$renter_ref, $stall_id, $map_id, $full_name, $contact, $email, $address]);

    // Update stall status
    $stmt3 = $pdo->prepare("UPDATE stalls SET status='reserved' WHERE id=?");
    $stmt3->execute([$stall_id]);

    echo json_encode(['status'=>'success', 'renter_ref'=>$renter_ref]);
}
?>
