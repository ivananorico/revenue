<?php
// view_business.php - View Business Applications
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: ../../index.php');
    exit;
}

// Database connection - using the same connection as your RPT system
require_once '../../../db/Business/business_db.php';

// Get user's applications with status counts
$applications = [];
$status_counts = [
    'pending' => 0,
    'assessed' => 0,
    'approved' => 0,
    'paid' => 0
];

try {
    // Get all applications for the user
    $stmt = $pdo->prepare("
        SELECT * 
        FROM business_applications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count applications by status
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM business_applications 
        WHERE user_id = ? 
        GROUP BY status
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $status_counts_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($status_counts_result as $row) {
        $status_counts[$row['status']] = $row['count'];
    }
    
} catch(PDOException $e) {
    $error_message = "Error fetching applications: " . $e->getMessage();
}

// Get approved applications for payment
$approved_applications = [];
try {
    $stmt = $pdo->prepare("
        SELECT ba.id, ba.business_name, ba.application_ref
        FROM business_applications ba
        WHERE ba.user_id = ? AND ba.status = 'approved'
        ORDER BY ba.application_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $approved_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Silently fail - this is just for the payment button
}

// Get regulatory fees
$regulatory_fees = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM regulatory_fee");
    $stmt->execute();
    $regulatory_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Silently fail
}

// Get business tax rates
$tax_rates = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM business_tax");
    $stmt->execute();
    $tax_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Silently fail
}

// Get assessment data for applications
$application_assessments = [];
if (!empty($applications)) {
    $application_ids = array_column($applications, 'id');
    $placeholders = str_repeat('?,', count($application_ids) - 1) . '?';
    
    try {
        $stmt = $pdo->prepare("
            SELECT ba.*, aq.quarter_name, aq.amount as quarter_amount, aq.status as payment_status,
                   aq.date_paid, aq.or_no
            FROM business_assessments ba
            LEFT JOIN assessment_quarters aq ON ba.id = aq.assessment_id
            WHERE ba.application_id IN ($placeholders)
            ORDER BY ba.assessed_at DESC
        ");
        $stmt->execute($application_ids);
        $assessment_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize by application_id
        foreach ($assessment_data as $assessment) {
            $application_assessments[$assessment['application_id']] = $assessment;
        }
    } catch(PDOException $e) {
        // Silently fail
    }
}

// Check if user has any approved applications
$has_approved_app = count($approved_applications) > 0;

// Calculate total regulatory fees
$total_regulatory_fees = 0;
foreach ($regulatory_fees as $fee) {
    $total_regulatory_fees += $fee['fee'];
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

        /* Status Summary Styles */
        .status-summary {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .status-summary h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .status-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            color: white;
        }

        .status-card.pending {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .status-card.assessed {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .status-card.approved {
            background: linear-gradient(135deg, #27ae60, #219a52);
        }

        .status-card.paid {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .status-count {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .status-label {
            font-size: 0.9em;
            opacity: 0.9;
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
        
        .status-assessed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
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

        /* Data Sections */
        .data-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .data-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .fees-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .fee-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .fee-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .fee-amount {
            color: #27ae60;
            font-weight: bold;
        }

        .tax-rates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .tax-rate-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #e74c3c;
        }

        .tax-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .tax-rate {
            color: #e74c3c;
            font-size: 1.2em;
            font-weight: bold;
        }

        .assessment-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #27ae60;
        }

        .assessment-detail {
            margin-bottom: 8px;
        }

        .payment-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #2196f3;
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

        <!-- Status Summary -->
        <div class="status-summary">
            <h3>Application Status Overview</h3>
            <div class="status-cards">
                <div class="status-card pending">
                    <div class="status-count"><?php echo $status_counts['pending']; ?></div>
                    <div class="status-label">Pending</div>
                </div>
                <div class="status-card assessed">
                    <div class="status-count"><?php echo $status_counts['assessed']; ?></div>
                    <div class="status-label">Assessed</div>
                </div>
                <div class="status-card approved">
                    <div class="status-count"><?php echo $status_counts['approved']; ?></div>
                    <div class="status-label">Approved</div>
                </div>
                <div class="status-card paid">
                    <div class="status-count"><?php echo $status_counts['paid']; ?></div>
                    <div class="status-label">Paid</div>
                </div>
            </div>
        </div>

        <!-- Regulatory Fees Section -->
        <div class="data-section">
            <h3>📊 Regulatory Fees</h3>
            <div class="fees-grid">
                <?php foreach ($regulatory_fees as $fee): ?>
                    <div class="fee-item">
                        <div class="fee-name"><?php echo htmlspecialchars($fee['name']); ?></div>
                        <div class="fee-amount">₱ <?php echo number_format($fee['fee'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 5px;">
                <strong>Total Regulatory Fees: ₱ <?php echo number_format($total_regulatory_fees, 2); ?></strong>
            </div>
        </div>

        <!-- Tax Rates Section -->
        <div class="data-section">
            <h3>💰 Business Tax Rates</h3>
            <div class="tax-rates-grid">
                <?php foreach ($tax_rates as $tax): ?>
                    <div class="tax-rate-item">
                        <div class="tax-name"><?php echo htmlspecialchars($tax['tax_name']); ?></div>
                        <div class="tax-rate"><?php echo $tax['tax_rate']; ?>%</div>
                        <div style="font-size: 0.8em; color: #7f8c8d;"><?php echo htmlspecialchars($tax['tax_type']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
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
                <a href="../register_business/register_business.php" class="btn btn-pay" style="display: inline-block; margin-top: 15px;">
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
                                <span class="detail-label">Owner:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($application['owner_name']); ?></span>
                            </div>
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
                        </div>

                        <!-- Show Assessment Information if available -->
                        <?php if (isset($application_assessments[$application['id']])): 
                            $assessment = $application_assessments[$application['id']];
                        ?>
                            <div class="assessment-info">
                                <h4>Assessment Details</h4>
                                <div class="assessment-detail">
                                    <strong>Tax Applied:</strong> <?php echo htmlspecialchars($assessment['tax_name']); ?> (<?php echo $assessment['tax_rate']; ?>%)
                                </div>
                                <div class="assessment-detail">
                                    <strong>Total Amount:</strong> ₱ <?php echo number_format($assessment['total_amount'], 2); ?>
                                </div>
                                <div class="assessment-detail">
                                    <strong>Tax Amount:</strong> ₱ <?php echo number_format($assessment['tax_amount'], 2); ?>
                                </div>
                                <div class="assessment-detail">
                                    <strong>Assessed On:</strong> <?php echo date('M j, Y', strtotime($assessment['assessed_at'])); ?>
                                </div>
                                
                                <?php if ($assessment['quarter_name']): ?>
                                    <div class="payment-info">
                                        <strong>Quarter:</strong> <?php echo htmlspecialchars($assessment['quarter_name']); ?><br>
                                        <strong>Amount Due:</strong> ₱ <?php echo number_format($assessment['quarter_amount'], 2); ?><br>
                                        <strong>Payment Status:</strong> 
                                        <span class="status-badge status-<?php echo htmlspecialchars($assessment['payment_status']); ?>">
                                            <?php echo htmlspecialchars($assessment['payment_status']); ?>
                                        </span>
                                        <?php if ($assessment['date_paid']): ?>
                                            <br><strong>Paid On:</strong> <?php echo date('M j, Y', strtotime($assessment['date_paid'])); ?>
                                            <?php if ($assessment['or_no']): ?>
                                                <br><strong>OR Number:</strong> <?php echo htmlspecialchars($assessment['or_no']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Only show Pay Tax button for approved applications -->
                        <?php if ($application['status'] === 'approved'): ?>
                            <div class="action-buttons">
                                <a href="../pay_tax/pay_tax.php?application_id=<?php echo $application['id']; ?>" class="btn btn-pay">
                                    Pay Tax
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>