<?php
// business_dashboard.php - Add session validation
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: ../index.php');
    exit;
}

// Database connection
$host = 'localhost:3307';
$dbname = 'business';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get user's businesses
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT id, business_name, application_ref, status 
    FROM business_applications 
    WHERE user_id = ? 
    ORDER BY application_date DESC
");
$stmt->execute([$user_id]);
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$assessed_businesses = array_filter($businesses, function($business) {
    return $business['status'] === 'assessed';
});
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
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: 100%;
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
            flex-grow: 1;
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

        /* Button Styles */
        .card-button {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .card-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .card-button.register {
            background: #27ae60;
        }

        .card-button.register:hover {
            background: #219652;
        }

        .card-button.view {
            background: #e67e22;
        }

        .card-button.view:hover {
            background: #d35400;
        }

        .card-button.pay {
            background: #9b59b6;
        }

        .card-button.pay:hover {
            background: #8e44ad;
        }

        /* Business List Styles */
        .business-list {
            margin-top: 30px;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .business-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .business-info {
            flex-grow: 1;
        }

        .business-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1.1em;
        }

        .business-ref {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .business-status {
            color: #27ae60;
            font-size: 0.85em;
            font-weight: 600;
        }

        .pay-tax-btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pay-tax-btn:hover {
            background: #219652;
            transform: translateY(-2px);
        }

        .no-businesses {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include '../../citizen_portal/navbar.php'; ?>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <a href="../../citizen_portal/dashboard.php" class="back-button">← Back to Main Dashboard</a>
        
        <div class="dashboard-header">
            <h2 class="dashboard-title">Business Tax Services</h2>
            <p class="dashboard-subtitle">Manage your business registration and tax payments</p>
        </div>
        
        <div class="cards-grid">
            <!-- Register Business Card -->
            <div class="card">
                <div class="card-icon">📝</div>
                <h3>Register Business</h3>
                <p>Register your business and apply for business permits and licenses. Start your business registration process here.</p>
                <a href="register_business/register_business.php" class="card-button register">
                    Register Now
                </a>
                <div class="card-badge">New</div>
            </div>

            <!-- View Applications Card -->
            <div class="card">
                <div class="card-icon">📋</div>
                <h3>View Applications</h3>
                <p>Check the status of your business registration applications and permits. Track your application progress.</p>
                <a href="view_business/view_business.php" class="card-button view">
                    View Applications
                </a>
                <div class="card-badge">Track</div>
            </div>

            <!-- Pay Tax Card -->
            <div class="card">
                <div class="card-icon">💳</div>
                <h3>Pay Tax</h3>
                <p>Make business tax payments and view payment history. Manage your tax obligations conveniently.</p>
                
                <?php if (empty($assessed_businesses)): ?>
                    <!-- No businesses - show disabled state -->
                    <button class="card-button pay" disabled style="background: #95a5a6; cursor: not-allowed;">
                        No Businesses Available
                    </button>
                <?php elseif (count($assessed_businesses) === 1): ?>
                    <!-- Single business - direct link -->
                    <?php $single_business = reset($assessed_businesses); ?>
                    <a href="pay_business_tax/pay_business_tax.php?application_id=<?= $single_business['id'] ?>" class="card-button pay">
                        Pay Taxes
                    </a>
                <?php else: ?>
                    <!-- Multiple businesses - show selection -->
                    <a href="#businessList" class="card-button pay">
                        Pay Taxes
                    </a>
                <?php endif; ?>
                
                <div class="card-badge">Pay</div>
            </div>
        </div>

        <!-- Business List Section (Only show if multiple businesses) -->
        <?php if (count($assessed_businesses) > 1): ?>
        <div id="businessList" class="business-list">
            <h3 style="color: #2c3e50; margin-bottom: 20px; text-align: center;">Select a Business to Pay Taxes</h3>
            
            <?php foreach ($assessed_businesses as $business): ?>
                <div class="business-item">
                    <div class="business-info">
                        <div class="business-name"><?= htmlspecialchars($business['business_name']) ?></div>
                        <div class="business-ref">Reference: <?= htmlspecialchars($business['application_ref']) ?></div>
                        <div class="business-status">Status: <?= ucfirst($business['status']) ?></div>
                    </div>
                    <a href="pay_business_tax/pay_business_tax.php?application_id=<?= $business['id'] ?>" 
                       class="pay-tax-btn">
                        Pay Taxes ›
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (empty($assessed_businesses)): ?>
        <!-- No businesses message -->
        <div class="business-list">
            <div class="no-businesses">
                <div style="font-size: 3em; margin-bottom: 15px;">🏢</div>
                <h4 style="color: #2c3e50; margin-bottom: 10px;">No Assessed Businesses Found</h4>
                <p style="margin-bottom: 20px;">You need to register and get a business assessed before you can pay taxes.</p>
                <a href="register_business/register_business.php" class="pay-tax-btn">
                    Register Business First
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>