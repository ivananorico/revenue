<?php
session_start();
require_once 'market_db.php'; // your main DB connection

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the full name and email directly from the session
$full_name = $_SESSION['full_name'] ?? 'Guest';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Apply Stall</title>
    <link rel="stylesheet" href="apply_stall.css">
</head>
<body>
    <div class="container">
        <h2>Market Stall Rental</h2>
        <p>Welcome, <?= htmlspecialchars($full_name) ?>!</p>
        <p>Click the button below to apply for a stall.</p>

        <!-- Pass full_name and email via GET if needed (optional) -->
        <a href="../../market_portal/market_portal.php" class="button">Apply Stall</a>

        <br><br>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
