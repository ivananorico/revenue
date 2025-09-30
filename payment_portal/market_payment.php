<?php
require_once "db_market.php";   // $marketDb
require_once "db_payment.php";  // $paymentDb

$renter = null;
$pendingRents = [];

// Handle AJAX payment submission
if(isset($_POST['pay_rent_id'], $_POST['pay_renter_id'], $_POST['payment_method'], $_POST['phone'])){
    $rent_id = $_POST['pay_rent_id'];
    $renter_id = $_POST['pay_renter_id'];
    $payment_method = $_POST['payment_method'];
    $phone = $_POST['phone'];

    // Fetch rent details
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

        try {
            // Update monthly_rent
            $update = $marketDb->prepare("
                UPDATE monthly_rent
                SET amount_paid = ?, payment_date = NOW(), status = 'paid', receipt_no = ?
                WHERE id = ?
            ");
            $update->execute([$total_due, $receipt_no, $rent_id]);

            // Insert into digital_payment DB with payment_method and phone
            $insert = $paymentDb->prepare("
                INSERT INTO market_rent_payments
                (renter_id, renter_ref, rent_month, amount_due, penalty, total_paid, payment_date, receipt_no, payment_method, status, phone)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'paid', ?)
            ");
            $insert->execute([
                $renter_id,
                $renterRow['renter_ref'],
                $rent['rent_month'],
                $rent['amount_due'],
                $rent['penalty'],
                $total_due,
                $receipt_no,
                $payment_method,
                $phone
            ]);

            // Return JSON for AJAX
            echo json_encode([
                'success' => true,
                'receipt_no' => $receipt_no,
                'amount_paid' => number_format($total_due, 2),
                'rent_month' => $rent['rent_month'],
                'payment_date' => date('Y-m-d H:i:s')
            ]);
        } catch(PDOException $e){
            echo json_encode(['success'=>false, 'message'=>'Insert failed: '.$e->getMessage()]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Rent or renter not found']);
        exit;
    }
}

// Handle fetching pending rents by reference ID
if(isset($_POST['reference_id'])){
    $ref_id = $_POST['reference_id'];

    // Fetch renter info
    $stmt = $marketDb->prepare("
        SELECT r.*, s.name AS stall_name, s.price AS stall_price, m.name AS map_name
        FROM renters r
        JOIN stalls s ON r.stall_id = s.id
        JOIN maps m ON r.map_id = m.id
        WHERE r.renter_ref = ?
    ");
    $stmt->execute([$ref_id]);
    $renter = $stmt->fetch(PDO::FETCH_ASSOC);

    if($renter){
        // Fetch pending rents
        $stmt = $marketDb->prepare("
            SELECT mr.*, s.name AS stall_name, s.price AS stall_price, m.name AS map_name
            FROM monthly_rent mr
            JOIN renters r ON mr.renter_id = r.id
            JOIN stalls s ON r.stall_id = s.id
            JOIN maps m ON r.map_id = m.id
            WHERE mr.renter_id = ? 
            ORDER BY mr.rent_month ASC
        ");
        $stmt->execute([$renter['id']]);
        $pendingRents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Digital Payment Portal</title>
<style>
#modalOverlay {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    z-index:999;
}
#paymentModal, #receiptModal {
    display:none; 
    position:fixed; 
    top:20%; 
    left:50%; 
    transform:translateX(-50%); 
    background:#fff; 
    padding:20px; 
    border:1px solid #000; 
    z-index:1000;
}
</style>
</head>
<body>
<h1>Digital Payment Portal</h1>

<form method="POST">
    <label>Enter Reference ID:</label>
    <input type="text" name="reference_id" required>
    <button type="submit">Check Payments</button>
</form>

<?php if(isset($_POST['reference_id'])): ?>
    <?php if(!$renter): ?>
        <p>Reference ID not found.</p>
    <?php else: ?>
        <h2>Renter Info</h2>
        <p>Name: <?= htmlspecialchars($renter['full_name']) ?></p>
        <p>Contact: <?= htmlspecialchars($renter['contact_number']) ?></p>
        <p>Stall: <?= htmlspecialchars($renter['stall_name']) ?></p>
        <p>Map: <?= htmlspecialchars($renter['map_name']) ?></p>

        <?php if(!$pendingRents): ?>
            <p>No rent records found.</p>
        <?php else: ?>
            <h3>Rent Records</h3>
            <table border="1" cellpadding="5" cellspacing="0">
                <tr>
                    <th>No</th>
                    <th>Month</th>
                    <th>Amount Due</th>
                    <th>Penalty</th>
                    <th>Total Due</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Receipt No</th>
                    <th>Action</th>
                </tr>
                <?php $count=1; foreach($pendingRents as $rent): ?>
                <tr id="rentRow<?= $rent['id'] ?>">
                    <td><?= $count++ ?></td>
                    <td><?= $rent['rent_month'] ?></td>
                    <td><?= number_format($rent['amount_due'],2) ?></td>
                    <td><?= number_format($rent['penalty'],2) ?></td>
                    <td><?= number_format($rent['total_due'],2) ?></td>
                    <td id="status<?= $rent['id'] ?>"><?= $rent['status'] ?></td>
                    <td id="payment_date<?= $rent['id'] ?>"><?= $rent['payment_date'] ?? '-' ?></td>
                    <td id="receipt_no<?= $rent['id'] ?>"><?= $rent['receipt_no'] ?? '-' ?></td>
                    <td>
                        <?php if($rent['status'] != 'paid'): ?>
                            <button type="button" onclick="openPaymentModal(<?= $renter['id'] ?>, <?= $rent['id'] ?>)">Pay</button>
                        <?php else: ?>
                            Paid
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal Overlay -->
<div id="modalOverlay"></div>

<!-- Payment Modal -->
<div id="paymentModal">
    <h2>Pay Rent</h2>
    <label for="paymentMethod">Payment Method:</label>
    <select id="paymentMethod">
        <option value="gcash">GCash</option>
        <option value="maya">Maya</option>
        <option value="bank_transfer">Bank Transfer</option>
    </select>
    <br><br>
    <label for="phone">Phone Number:</label>
    <input type="text" id="phone" placeholder="Enter your phone number" required>
    <br><br>
    <button id="confirmPayBtn">Confirm Payment</button>
    <button onclick="closePaymentModal()">Cancel</button>
</div>

<!-- Receipt Modal -->
<div id="receiptModal">
    <h2>Payment Receipt</h2>
    <div id="receiptInfo"></div>
    <button onclick="closeReceiptModal()">Close</button>
</div>

<script>
let currentRenterId = null;
let currentRentId = null;

function openPaymentModal(renterId, rentId){
    currentRenterId = renterId;
    currentRentId = rentId;
    document.getElementById('paymentModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function closePaymentModal(){
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

document.getElementById('confirmPayBtn').addEventListener('click', function(){
    const paymentMethod = document.getElementById('paymentMethod').value;
    const phone = document.getElementById('phone').value.trim();

    if(phone === ''){
        alert('Please enter phone number.');
        return;
    }

    const formData = new FormData();
    formData.append('pay_renter_id', currentRenterId);
    formData.append('pay_rent_id', currentRentId);
    formData.append('payment_method', paymentMethod);
    formData.append('phone', phone);

    fetch('market_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            document.getElementById('status'+currentRentId).innerText = 'paid';
            document.getElementById('payment_date'+currentRentId).innerText = data.payment_date;
            document.getElementById('receipt_no'+currentRentId).innerText = data.receipt_no;

            const actionCell = document.querySelector(`#rentRow${currentRentId} td:last-child`);
            actionCell.innerText = 'Paid';

            document.getElementById('receiptInfo').innerHTML = `
                <strong>Receipt No:</strong> ${data.receipt_no}<br>
                <strong>Amount Paid:</strong> ${data.amount_paid}<br>
                <strong>Rent Month:</strong> ${data.rent_month}<br>
                <strong>Payment Date:</strong> ${data.payment_date}
            `;
            document.getElementById('receiptModal').style.display = 'block';
            closePaymentModal();
        } else {
            alert(data.message);
        }
    });
});

function closeReceiptModal(){
    document.getElementById('receiptModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}
</script>
</body>
</html>
