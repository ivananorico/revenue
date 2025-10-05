<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header('Content-Type: application/json');

require_once "db_market.php"; // PDO connection

$data = json_decode(file_get_contents("php://input"), true);
$renter_id = isset($data['user_id']) ? (int)$data['user_id'] : 0; // Changed from 'id' to 'user_id'

if (!$renter_id) {
    echo json_encode(['success' => false, 'message' => 'No renter ID provided']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Update renter status - changed WHERE clause to use user_id
    $stmt = $pdo->prepare("UPDATE renters SET status = 'active' WHERE user_id = ?");
    $stmt->execute([$renter_id]);

    // 2. Get renter info + stall price - changed WHERE clause to use user_id
    $stmt = $pdo->prepare("
        SELECT r.stall_id, r.date_reserved, s.price AS rent_amount
        FROM renters r
        JOIN stalls s ON r.stall_id = s.id
        WHERE r.user_id = ?
    ");
    $stmt->execute([$renter_id]);
    $renter = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($renter) {
        $stall_id = $renter['stall_id'];
        $date_reserved = new DateTime($renter['date_reserved']);
        $rent_amount = $renter['rent_amount'];

        // 3. Update stall status
        $stmt = $pdo->prepare("UPDATE stalls SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$stall_id]);

        // 4. Generate monthly rent starting next month
        $endMonth = new DateTime($date_reserved->format('Y-12-01'));
        $currentMonth = (clone $date_reserved)->modify('first day of next month');

        while ($currentMonth <= $endMonth) {
            $daysInMonth = (int)$currentMonth->format('t');
            $amountDue = $rent_amount; // full month amount

            $dueDate = $currentMonth->format('Y-m-05'); // due date 5th

            $stmtInsert = $pdo->prepare("
                INSERT INTO monthly_rent (renter_id, rent_month, amount_due, due_date, penalty, status)
                VALUES (?, ?, ?, ?, 0, 'unpaid')
            ");
            $stmtInsert->execute([
                $renter_id,
                $currentMonth->format('Y-m-01'),
                $amountDue,
                $dueDate
            ]);

            $currentMonth->modify('+1 month');
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Renter approved and monthly rent generated starting next month.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}