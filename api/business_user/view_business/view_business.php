<?php
// view_business.php - View Business Applications
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: ../../index.php');
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

// Get user's applications
$applications = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM business_applications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Show what we found
    echo "<!-- Debug: Found " . count($applications) . " applications for user_id: " . $_SESSION['user_id'] . " -->";
    foreach ($applications as $app) {
        echo "<!-- App ID: " . $app['id'] . ", Name: " . $app['business_name'] . " -->";
    }
    
} catch(PDOException $e) {
    $error_message = "Error fetching applications: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Business Applications - Municipal Services</title>
    <link rel="stylesheet" href="../../dashboard.css">
    <link rel="stylesheet" href="../../navbar.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .application-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
        }
        
        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .application-ref {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        
        .business-name {
            font-size: 1.3em;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .business-type {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .application-details {
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
        }
        
        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
        }
        
        .detail-value {
            color: #2c3e50;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-align: center;
            flex: 1;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-view:hover {
            background: #2980b9;
        }
        
        .btn-pay {
            background: #27ae60;
            color: white;
        }
        
        .btn-pay:hover {
            background: #219a52;
        }
        
        .no-applications {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            color: #6c757d;
        }
        
        .no-applications-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .debug-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include '../../../citizen_portal/navbar.php'; ?>

    <div class="container">
        <a href="../business_dashboard.php" class="back-button">← Back to Business Dashboard</a>
        
        <div class="header">
            <h1>My Business Applications</h1>
            <p>View and manage your business registration applications</p>
        </div>

        <!-- Debug Information -->
        <div class="debug-info">
            <strong>Debug Information:</strong><br>
            User ID: <?php echo $_SESSION['user_id']; ?><br>
            Applications Found: <?php echo count($applications); ?><br>
            Current URL: <?php echo $_SERVER['REQUEST_URI']; ?>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($applications)): ?>
            <div class="no-applications">
                <div class="no-applications-icon">📋</div>
                <h3>No Applications Found</h3>
                <p>You haven't submitted any business applications yet.</p>
                <a href="../register_business/register_business.php" class="btn btn-view" style="display: inline-block; margin-top: 15px;">
                    Register Your First Business
                </a>
            </div>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-ref">
                            <?php echo htmlspecialchars($application['application_ref']); ?>
                        </div>
                        <div class="business-name">
                            <?php echo htmlspecialchars($application['business_name']); ?>
                        </div>
                        <div class="business-type">
                            <?php echo htmlspecialchars($application['business_type']); ?>
                        </div>
                        
                        <div class="application-details">
                            <div class="detail-row">
                                <span class="detail-label">Tax Base:</span>
                                <span class="detail-value">
                                    <?php 
                                    $tax_base_type = $application['tax_base_type'];
                                    echo $tax_base_type === 'capital_investment' ? 'Capital Investment' : 'Gross Rate';
                                    ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount:</span>
                                <span class="detail-value">₱ <?php echo number_format($application['amount'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($application['application_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">
                                    <span class="status-badge status-<?php echo htmlspecialchars($application['status']); ?>">
                                        <?php echo htmlspecialchars($application['status']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Application ID:</span>
                                <span class="detail-value"><?php echo $application['id']; ?></span>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="application_details.php?id=<?php echo $application['id']; ?>" class="btn btn-view">
                                View Details (ID: <?php echo $application['id']; ?>)
                            </a>
                            <?php if ($application['status'] === 'approved'): ?>
                                <a href="../pay_tax/pay_tax.php?id=<?php echo $application['id']; ?>" class="btn btn-pay">
                                    Pay Tax
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>