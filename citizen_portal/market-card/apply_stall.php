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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Stall - Municipal Services</title>
    <link rel="stylesheet" href="apply_stall.css">
    <link rel="stylesheet" href="../navbar.css">
</head>
<body>

    <!-- Include Navbar -->
    <?php include '../navbar.php'; ?>

    <div class="container">
        <div class="content">
            <h2>Market Stall Rental</h2>
            <p>Click the button below to apply for a stall.</p>

            <!-- Apply Stall Button -->
            <a href="../../market_portal/market_portal.php" class="button">Apply Stall</a>

            <div class="back-link">
                <a href="../../citizen_portal/dashboard.php">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>