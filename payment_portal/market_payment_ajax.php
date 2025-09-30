<?php
require_once "db_market.php";
require_once "db_payment.php";

if(isset($_POST['pay_renter_id'], $_POST['pay_rent_id'])){
    $renter_id = $_POST['pay_renter_id'];
    $rent_id = $_POST['pay_rent_id'];

    // Fetch rent
    $stmt = $marketDb->prepare("SELECT * FROM monthly_rent WHERE id = ?");
    $stmt->execute([$rent_id]);
    $rent = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch renter_ref
    $stmt2 = $marketDb->prepare("SELECT renter_ref FROM renters WHERE id = ?");
    $stmt2->execute([$renter_id]);
    $renterRow = $stmt2->fetch(PDO::FETCH_ASSOC);

    if($rent && $renterRow){
        $total_due = $rent['amount_due'] + $rent['penalty'];
        $receipt_no = 'RCPT-' . time() . '-' . rand(1000,9999);

        // Update monthly_rent
        $update = $marketDb->prepare("
            UPDATE monthly_rent
            SET amount_paid = ?, payment_date = NOW(), status = 'paid', receipt_no = ?
            WHERE id = ?
        ");
        $update->execute([$total_due, $receipt_no, $rent_id]);

        // Insert into digital_payment DB
        $insert = $paymentDb->prepare("
            INSERT INTO market_rent_payments
            (renter_id, renter_ref, rent_month, amount_due, penalty, total_paid, payment_date, receipt_no, status)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, 'paid')
        ");
        $insert->execute([
            $renter_id,
            $renterRow['renter_ref'],
            $rent['rent_month'],
            $rent['amount_due'],
            $rent['penalty'],
            $total_due,
            $receipt_no
        ]);

        echo json_encode([
            'success' => true,
            'receipt_no' => $receipt_no,
            'amount_paid' => number_format($total_due,2),
            'rent_month' => $rent['rent_month'],
            'payment_date' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid rent or renter']);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Invalid request']);
}
?>
