<?php
session_start();
require_once 'market_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Guest';
$email     = $_SESSION['email'] ?? 'Not set';

// Fetch renter info (only one stall per user)
try {
    $stmt = $pdo->prepare("
        SELECT r.*, s.name AS stall_name, m.name AS map_name
        FROM renters r
        LEFT JOIN stalls s ON r.stall_id = s.id
        LEFT JOIN maps m ON r.map_id = m.id
        WHERE r.user_id = :user_id
        LIMIT 1
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $renter = $stmt->fetch(); // only fetch one row
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Stall - Municipal Services</title>
    <link rel="stylesheet" href="apply_stall.css">
    <link rel="stylesheet" href="../navbar.css">
</head>
<body>

<?php include '../navbar.php'; ?>

<div class="apply-stall-container">
    <div class="apply-stall-content">
        <header class="apply-stall-header">
            <h2>Market Stall Rental</h2>
        </header>

        <main class="apply-stall-main">
            <?php if (!$renter): ?>
                <div class="no-application-section">
                    <p class="application-text">Click the button below to apply for a stall.</p>
                    <a href="../../market_portal/market_portal.php" class="apply-button">Apply Stall</a>
                </div>
            <?php else: ?>
                <div class="application-card">
                    <h3 class="card-title">Your Stall Application</h3>
                    <div class="card-details">
                        <p class="detail-item">
                            <strong>Stall:</strong> 
                            <span><?= htmlspecialchars($renter['stall_name']) ?></span>
                        </p>
                        <p class="detail-item">
                            <strong>Market Map:</strong> 
                            <span><?= htmlspecialchars($renter['map_name']) ?></span>
                        </p>
                        <p class="detail-item">
                            <strong>Status:</strong> 
                            <span class="status-text"><?= htmlspecialchars(ucfirst($renter['status'])) ?></span>
                        </p>
                    </div>
                    
                    <a href="view_apply_stall.php?renter_id=<?= $renter['user_id'] ?>" class="view-details-button">View Details</a>
                </div>
            <?php endif; ?>
        </main>

        <footer class="apply-stall-footer">
            <a href="../../citizen_portal/dashboard.php" class="back-link">← Back to Dashboard</a>
        </footer>
    </div>
</div>
</body>
</html>