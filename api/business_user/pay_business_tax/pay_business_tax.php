<?php
session_start();

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

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    $_SESSION['error'] = "No application specified.";
    header('Location: business_dashboard.php');
    exit;
}

// Fetch application and payment details
try {
    // Get application details
    $stmt = $pdo->prepare("
        SELECT 
            ba.*
        FROM business_applications ba
        WHERE ba.id = ? AND ba.user_id = ? AND ba.status = 'assessed'
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        $_SESSION['error'] = "Application not found or access denied.";
        header('Location: business_dashboard.php');
        exit;
    }

    // Get business assessment tax
    $tax_stmt = $pdo->prepare("
        SELECT ba.*, bt.tax_name, bt.tax_rate 
        FROM business_assessments ba
        JOIN business_tax bt ON ba.tax_rate_id = bt.id
        WHERE ba.application_id = ?
        ORDER BY ba.assessed_at DESC 
        LIMIT 1
    ");
    $tax_stmt->execute([$application_id]);
    $business_tax = $tax_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$business_tax) {
        $_SESSION['error'] = "No tax assessment found for this business.";
        header('Location: business_dashboard.php');
        exit;
    }

    // Get assessment fees
    $fees_stmt = $pdo->prepare("
        SELECT af.*, rf.name as fee_name 
        FROM assessment_fees af
        JOIN regulatory_fee rf ON af.fee_id = rf.id
        WHERE af.assessment_id = ?
    ");
    $fees_stmt->execute([$business_tax['id']]);
    $assessment_fees = $fees_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all unpaid quarterly payments
    $payments_stmt = $pdo->prepare("
        SELECT * FROM assessment_quarters 
        WHERE assessment_id = ? AND days_remaining >= 0
        ORDER BY due_date ASC
    ");
    $payments_stmt->execute([$business_tax['id']]);
    $unpaid_payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get overdue quarterly payments
    $overdue_stmt = $pdo->prepare("
        SELECT * FROM assessment_quarters 
        WHERE assessment_id = ? AND days_remaining < 0
        ORDER BY due_date ASC
    ");
    $overdue_stmt->execute([$business_tax['id']]);
    $overdue_payments = $overdue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine unpaid and overdue payments
    $all_unpaid_payments = array_merge($overdue_payments, $unpaid_payments);

    // Get payment history (paid payments)
    $history_stmt = $pdo->prepare("
        SELECT aq.* 
        FROM assessment_quarters aq
        WHERE aq.assessment_id = ? AND aq.days_remaining < -30
        ORDER BY aq.due_date DESC 
        LIMIT 6
    ");
    $history_stmt->execute([$business_tax['id']]);
    $payment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total regulatory fees
    $total_fees = 0;
    foreach ($assessment_fees as $fee) {
        $total_fees += $fee['fee_amount'];
    }

    $tax_amount = $business_tax['tax_amount'] ?? 0;
    $total_amount = $business_tax['total_amount'] ?? 0;
    
    // Calculate total due amount from unpaid quarters - USE ACTUAL AMOUNTS FROM DATABASE
    $total_due = 0;
    foreach ($all_unpaid_payments as $payment) {
        $total_due += $payment['amount'];
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred.";
    header('Location: business_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Business Tax - Business Permit System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../navbar.css">
    <style>
        .payment-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }
        .tax-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }
        .tax-item:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }
        .btn-pay-single {
            background: #10b981;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-pay-single:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .btn-pay-all {
            background: #d97706;
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-pay-all:hover {
            background: #b45309;
            transform: translateY(-1px);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-unpaid {
            background: #fef3c7;
            color: #92400e;
        }
        .status-overdue {
            background: #fee2e2;
            color: #dc2626;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .amount-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #059669;
        }
        .total-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #dc2626;
            text-align: center;
            margin: 1rem 0;
        }
        .fee-item {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
   <?php include '../../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <!-- Display any error messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Pay Business Tax</h1>
            <p class="text-gray-600">Application Reference: <?= htmlspecialchars($application['application_ref']) ?></p>
        </div>

        <!-- Business Information -->
        <div class="payment-card">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🏢 Business Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Business Name</p>
                    <p class="font-semibold"><?= htmlspecialchars($application['business_name']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Owner Name</p>
                    <p class="font-semibold"><?= htmlspecialchars($application['owner_name']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Business Type</p>
                    <p class="font-semibold"><?= htmlspecialchars($application['business_type']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">TIN ID</p>
                    <p class="font-semibold"><?= htmlspecialchars($application['tin_id']) ?></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-gray-600">Business Address</p>
                    <p class="font-semibold"><?= htmlspecialchars($application['full_address']) ?></p>
                </div>
            </div>

            <!-- Tax Breakdown -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-blue-800 mb-2">Tax & Fee Breakdown</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-blue-600">Tax Base Amount:</span>
                        <span class="font-medium">₱<?= number_format($application['amount'], 2) ?></span>
                    </div>
                    <div>
                        <span class="text-blue-600">Tax Rate:</span>
                        <span class="font-medium"><?= $business_tax['tax_rate'] ?? 0 ?>% (<?= $business_tax['tax_name'] ?? 'N/A' ?>)</span>
                    </div>
                    <div>
                        <span class="text-blue-600">Tax Amount:</span>
                        <span class="font-medium">₱<?= number_format($tax_amount, 2) ?></span>
                    </div>
                </div>
                
                <!-- Regulatory Fees -->
                <?php if (!empty($assessment_fees)): ?>
                <div class="mt-4">
                    <h4 class="font-semibold text-blue-800 mb-2">Regulatory Fees:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php foreach ($assessment_fees as $fee): ?>
                        <div class="fee-item">
                            <span class="text-blue-600"><?= htmlspecialchars($fee['fee_name']) ?>:</span>
                            <span class="font-medium">₱<?= number_format($fee['fee_amount'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2 pt-2 border-t border-blue-200">
                        <span class="text-blue-800 font-semibold">Total Fees:</span>
                        <span class="font-bold">₱<?= number_format($total_fees, 2) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Total Amount -->
                <div class="mt-4 pt-4 border-t border-blue-200">
                    <span class="text-blue-800 font-bold text-lg">Total Annual Amount:</span>
                    <span class="font-bold text-lg text-green-600">₱<?= number_format($total_amount, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Quarterly Tax Payments -->
        <div class="payment-card">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">📅 Quarterly Tax Payments</h2>
                <?php if (!empty($all_unpaid_payments)): ?>
                    <div class="text-right">
                        <p class="text-gray-600">Total Due</p>
                        <p class="total-amount">₱<?= number_format($total_due, 2) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($all_unpaid_payments)): ?>
                <div class="space-y-4">
                    <?php foreach ($all_unpaid_payments as $payment): ?>
                        <?php
                        $payment_amount = $payment['amount']; // Use actual amount from database
                        $is_overdue = $payment['days_remaining'] < 0;
                        $status = $is_overdue ? 'overdue' : 'unpaid';
                        ?>
                        <div class="tax-item">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-4 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-800">
                                            <?= htmlspecialchars($payment['quarter_name']) ?>
                                        </h3>
                                        <span class="status-badge <?= $status === 'overdue' ? 'status-overdue' : 'status-unpaid' ?>">
                                            <?= $status === 'overdue' ? '⚠️ Overdue' : '⏰ Unpaid' ?>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600">Due Date:</span>
                                            <span class="font-medium">
                                                <?= $payment['due_date'] !== '0000-00-00' ? date('M j, Y', strtotime($payment['due_date'])) : 'Not set' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Days Remaining:</span>
                                            <span class="font-medium <?= $payment['days_remaining'] < 0 ? 'text-red-600' : 'text-green-600' ?>">
                                                <?= $payment['days_remaining'] ?> days
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Quarterly Amount:</span>
                                            <span class="font-medium">₱<?= number_format($payment['amount'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="amount-display mb-2">
                                        ₱<?= number_format($payment['amount'], 2) ?>
                                    </div>
                                    <!-- CORRECTED PAYMENT LINK -->
                                    <a href="../../../citizen_portal/digital_card/business_tax_payment_details.php?application_id=<?= $application_id ?>&quarter_id=<?= $payment['id'] ?>&amount=<?= $payment['amount'] ?>&payment_for=<?= urlencode($payment['quarter_name']) ?>&payment_type=business_tax" 
                                       class="btn-pay-single">
                                        Pay Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pay All Button -->
                <div class="mt-6 text-center">
                    <a href="../../digital_card/business_tax_payment_details.php?application_id=<?= $application_id ?>&payment_for=all_quarters&amount=<?= $total_due ?>&payment_type=business_tax" 
                       class="btn-pay-all">
                        💳 Pay All Unpaid Quarters (₱<?= number_format($total_due, 2) ?>)
                    </a>
                </div>

            <?php else: ?>
                <!-- No Unpaid Payments -->
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl text-green-600">✅</span>
                    </div>
                    <h3 class="text-xl font-semibold text-green-800 mb-2">All Taxes Paid!</h3>
                    <p class="text-gray-600">You have no unpaid tax installments for this business.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment History -->
        <div class="payment-card">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">📋 Payment History</h2>
            
            <?php if (!empty($payment_history)): ?>
                <div class="space-y-3">
                    <?php foreach ($payment_history as $payment): ?>
                    <div class="tax-item">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold text-gray-800">
                                    <?= htmlspecialchars($payment['quarter_name']) ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Due Date: <?= $payment['due_date'] !== '0000-00-00' ? date('M j, Y', strtotime($payment['due_date'])) : 'N/A' ?>
                                    • Amount: ₱<?= number_format($payment['amount'], 2) ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-800">
                                    ₱<?= number_format($payment['amount'], 2) ?>
                                </div>
                                <span class="status-badge status-paid">Paid</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <p>No payment history found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex justify-center gap-4">
            <a href="../business_dashboard.php" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors duration-200 no-underline">
                ← Back to Dashboard
            </a>
            <a href="../view_business/view_business.php" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors duration-200 no-underline">
                📄 View Applications
            </a>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const taxItems = document.querySelectorAll('.tax-item');
            taxItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add confirmation for pay all
            const payAllButton = document.querySelector('.btn-pay-all');
            if (payAllButton) {
                payAllButton.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to pay all unpaid quarters? This will process a single payment for all outstanding amounts.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>