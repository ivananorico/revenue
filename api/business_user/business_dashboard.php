<?php
// business_dashboard.php - Add session validation
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Tax Dashboard - Municipal Services</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../navbar.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .dashboard-title {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .dashboard-subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            text-align: center;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            border-color: #3498db;
        }
        
        .card-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .card p {
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .card-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .back-button:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include '../../citizen_portal/navbar.php'; ?>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <a href="../dashboard.php" class="back-button">← Back to Main Dashboard</a>
        
        <div class="dashboard-header">
            <h2 class="dashboard-title">Business Tax Services</h2>
            <p class="dashboard-subtitle">Manage your business registration and tax payments</p>
        </div>
        
        <div class="cards-grid">
            <!-- Register Business Card -->
            <div class="card" onclick="location.href='../business_user/register_business/register_business.php'">
                <div class="card-icon">📝</div>
                <h3>Register Business</h3>
                <p>Register your business and apply for business permits and licenses</p>
                <div class="card-badge">New</div>
            </div>

            <!-- View Applications Card -->
            <div class="card" onclick="location.href='../business_user/view_business/view_business.php'">
                <div class="card-icon">📋</div>
                <h3>View Applications</h3>
                <p>Check the status of your business registration applications and permits</p>
                <div class="card-badge">Track</div>
            </div>

            <!-- Pay Tax Card -->
            <div class="card" onclick="location.href='pay_tax.php'">
                <div class="card-icon">💳</div>
                <h3>Pay Tax</h3>
                <p>Make business tax payments and view payment history</p>
                <div class="card-badge">Pay</div>
            </div>
        </div>
    </div>

</body>
</html>