<?php
require_once "db_market.php";

if($_SERVER['REQUEST_METHOD']==='POST'){
    // Set headers for CORS and JSON response
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");
    header('Content-Type: application/json; charset=utf-8');

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

    try {
        // Check if stall exists and is available (not reserved, occupied, or maintenance)
        $stmt = $pdo->prepare("SELECT status FROM stalls WHERE id=?");
        $stmt->execute([$stall_id]);
        $stall = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$stall){
            echo json_encode(['status'=>'error','message'=>'Stall not found']);
            exit;
        }

        if($stall['status'] !== 'available'){
            $status = $stall['status'];
            $message = 'Stall is not available for reservation.';
            
            if($status === 'reserved') {
                $message = 'This stall is already reserved.';
            } elseif($status === 'occupied') {
                $message = 'This stall is currently occupied.';
            } elseif($status === 'maintenance') {
                $message = 'This stall is under maintenance and cannot be reserved.';
            }
            
            echo json_encode(['status'=>'error','message'=>$message]);
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

        // Begin transaction
        $pdo->beginTransaction();

        // Insert renter
        $stmt2 = $pdo->prepare("INSERT INTO renters (renter_ref, stall_id, map_id, full_name, contact_number, email, address) VALUES (?,?,?,?,?,?,?)");
        $stmt2->execute([$renter_ref, $stall_id, $map_id, $full_name, $contact, $email, $address]);

        // Update stall status to reserved
        $stmt3 = $pdo->prepare("UPDATE stalls SET status='reserved' WHERE id=?");
        $stmt3->execute([$stall_id]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'status'=>'success', 
            'renter_ref'=>$renter_ref,
            'message'=>'Stall reserved successfully!'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("reserve_stall.php error: " . $e->getMessage());
        echo json_encode([
            'status'=>'error',
            'message'=>'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    // Handle non-POST requests
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(['status'=>'error','message'=>'Only POST method allowed']);
}
?>