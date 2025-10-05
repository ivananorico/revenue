<?php
session_start();
require_once 'market_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get renter ID from URL and verify
$renter_id = $_GET['renter_id'] ?? 0;
if ($renter_id != $_SESSION['user_id']) {
    die("Unauthorized access.");
}

// Fetch renter info
try {
    $stmt = $pdo->prepare("
        SELECT r.*, s.name AS stall_name, m.name AS map_name
        FROM renters r
        LEFT JOIN stalls s ON r.stall_id = s.id
        LEFT JOIN maps m ON r.map_id = m.id
        WHERE r.user_id = :renter_id
    ");
    $stmt->bindParam(':renter_id', $renter_id);
    $stmt->execute();
    $renter = $stmt->fetch();

    // Fetch monthly rent info
    $stmt2 = $pdo->prepare("
        SELECT * FROM monthly_rent
        WHERE renter_id = :renter_id
        ORDER BY rent_month DESC
    ");
    $stmt2->bindParam(':renter_id', $renter_id);
    $stmt2->execute();
    $monthly_rents = $stmt2->fetchAll();
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Application - <?= htmlspecialchars($renter['full_name']) ?></title>
<link rel="stylesheet" href="view_apply_stall.css">
<link rel="stylesheet" href="../navbar.css">
</head>
<body>

<?php include '../navbar.php'; ?>

<div class="view-application-container">
    <div class="view-application-content">
        <header class="application-header">
            <h2>Application Details</h2>
        </header>

        <main class="application-main">
            <section class="renter-info-section">
                <h3 class="section-title">Renter Information</h3>
                <div class="info-table-container">
                    <table class="info-table">
                        <tbody>
                            <tr>
                                <th scope="row">Renter Reference</th>
                                <td><?= htmlspecialchars($renter['renter_ref']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Full Name</th>
                                <td><?= htmlspecialchars($renter['full_name']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Stall</th>
                                <td><?= htmlspecialchars($renter['stall_name']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Market Map</th>
                                <td><?= htmlspecialchars($renter['map_name']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Contact Number</th>
                                <td><?= htmlspecialchars($renter['contact_number']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Email</th>
                                <td><?= htmlspecialchars($renter['email']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Address</th>
                                <td><?= htmlspecialchars($renter['address']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Date Reserved</th>
                                <td><?= htmlspecialchars($renter['date_reserved']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Status</th>
                                <td class="status-cell"><?= htmlspecialchars($renter['status']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rent-records-section">
                <h3 class="section-title">Monthly Rent Records</h3>
                <?php if ($monthly_rents): ?>
                    <div class="rent-table-container">
                        <table class="rent-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Amount Due</th>
                                    <th>Amount Paid</th>
                                    <th>Due Date</th>
                                    <th>Payment Date</th>
                                    <th>Penalty</th>
                                    <th>Total Due</th>
                                    <th>Receipt No</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_rents as $rent): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rent['rent_month']) ?></td>
                                        <td class="amount-cell"><?= htmlspecialchars(number_format($rent['amount_due'], 2)) ?></td>
                                        <td class="amount-cell"><?= htmlspecialchars(number_format($rent['amount_paid'], 2)) ?></td>
                                        <td><?= htmlspecialchars($rent['due_date']) ?></td>
                                        <td><?= htmlspecialchars($rent['payment_date'] ?? '-') ?></td>
                                        <td class="amount-cell"><?= htmlspecialchars(number_format($rent['penalty'], 2)) ?></td>
                                        <td class="amount-cell"><?= htmlspecialchars(number_format($rent['total_due'], 2)) ?></td>
                                        <td><?= htmlspecialchars($rent['receipt_no'] ?? '-') ?></td>
                                        <td class="status-cell"><?= htmlspecialchars($rent['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-records-message">
                        <p>No monthly rent records found.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer class="application-footer">
            <a href="apply_stall.php" class="back-link">← Back to Apply Stall</a>
        </footer>
    </div>
</div>
</body>
</html>