<?php
session_start();
require_once "db_market.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['email'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['status'=>'error','message'=>'You must be logged in to reserve a stall']);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(['status'=>'error','message'=>'Only POST method allowed']);
    exit;
}

// Set headers for CORS and JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json; charset=utf-8');

// Get POST data
$stall_id = $_POST['stall_id'] ?? 0;
$map_id = $_POST['map_id'] ?? 0;

// Use session data for user info
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
$contact = $_POST['contact_number'] ?? '';
$address = $_POST['address'] ?? '';

if (!$stall_id || !$map_id || !$full_name) {
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit;
}

try {
    // Check if stall exists and is available
    $stmt = $pdo->prepare("SELECT status FROM stalls WHERE id=?");
    $stmt->execute([$stall_id]);
    $stall = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stall) {
        echo json_encode(['status'=>'error','message'=>'Stall not found']);
        exit;
    }

    if ($stall['status'] !== 'available') {
        $status = $stall['status'];
        $message = 'Stall is not available for reservation.';
        if ($status === 'reserved') $message = 'This stall is already reserved.';
        if ($status === 'occupied') $message = 'This stall is currently occupied.';
        if ($status === 'maintenance') $message = 'This stall is under maintenance.';
        echo json_encode(['status'=>'error','message'=>$message]);
        exit;
    }

    // Generate renter_ref like renter-1001
    $lastRefStmt = $pdo->query("SELECT renter_ref FROM renters ORDER BY user_id DESC LIMIT 1");
    $lastRef = $lastRefStmt->fetchColumn();
    $nextNumber = 1001;

    if ($lastRef) {
        preg_match('/renter-(\d+)/', $lastRef, $matches);
        $nextNumber = isset($matches[1]) ? (int)$matches[1]+1 : 1001;
    }

    $renter_ref = 'renter-' . $nextNumber;

    // Begin transaction
    $pdo->beginTransaction();

    // Insert renter using session info
    $stmt2 = $pdo->prepare("INSERT INTO renters (user_id, renter_ref, stall_id, map_id, full_name, contact_number, email, address) VALUES (?,?,?,?,?,?,?,?)");
    $stmt2->execute([$user_id, $renter_ref, $stall_id, $map_id, $full_name, $contact, $email, $address]);

    // Update stall status to reserved
    $stmt3 = $pdo->prepare("UPDATE stalls SET status='reserved' WHERE id=?");
    $stmt3->execute([$stall_id]);

    $pdo->commit();

    echo json_encode([
        'status'=>'success',
        'renter_ref'=>$renter_ref,
        'message'=>'Stall reserved successfully!'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("reserve_stall.php error: " . $e->getMessage());
    echo json_encode([
        'status'=>'error',
        'message'=>'Database error: ' . $e->getMessage()
    ]);
}
?>
