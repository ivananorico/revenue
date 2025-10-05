<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="dashboard.css">
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?= htmlspecialchars($full_name) ?>!</h2>

    <div class="card" onclick="location.href='market-card/apply_stall.php'">
        <h3>Market Stall Rental</h3>
        <p>Click to view available stalls and apply.</p>
    </div>

    <br><br>
    <a href="logout.php">Logout</a>
</body>
</html>
