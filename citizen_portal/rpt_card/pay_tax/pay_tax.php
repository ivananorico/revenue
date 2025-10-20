<?php
session_start();
require_once '../../../db/RPT/rpt_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['application_id'] ?? null;

// Debug: Log the request
error_log("RPT Payment Debug: User $user_id accessing application $application_id");

if (!$application_id) {
    $_SESSION['error'] = "No application specified. Please select a property from the dashboard.";
    $application = null;
} else {
    try {
        // First, let's check what's actually in the database
        $debug_stmt = $pdo->prepare("
            SELECT id, status, application_type, user_id 
            FROM rpt_applications 
            WHERE id = ?
        ");
        $debug_stmt->execute([$application_id]);
        $debug_app = $debug_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debug_app) {
            error_log("RPT Payment Debug: Application found - ID: {$debug_app['id']}, Status: {$debug_app['status']}, User ID: {$debug_app['user_id']}, Requested by: $user_id");
            
            // Check if user owns this application
            if ($debug_app['user_id'] != $user_id) {
                error_log("RPT Payment Debug: User $user_id does not own application $application_id (owned by {$debug_app['user_id']})");
                $_SESSION['error'] = "Application not found or access denied.";
                $application = null;
            } 
            // Check if application is approved
            else if ($debug_app['status'] != 'approved') {
                error_log("RPT Payment Debug: Application $application_id status is '{$debug_app['status']}', not 'approved'");
                $_SESSION['error'] = "This application is not yet approved. Current status: " . ucfirst($debug_app['status']);
                $application = null;
            } else {
                error_log("RPT Payment Debug: Application $application_id is approved and owned by user $user_id");
                
                // Now try the main query
                $stmt = $pdo->prepare("
                    SELECT 
                        ra.*,
                        l.land_id,
                        l.location,
                        l.barangay,
                        l.municipality,
                        l.lot_area,
                        l.land_use,
                        l.tdn_no as land_tdn
                    FROM rpt_applications ra
                    LEFT JOIN land l ON ra.id = l.application_id
                    WHERE ra.id = ? AND ra.user_id = ? AND ra.status = 'approved'
                ");
                $stmt->execute([$application_id, $user_id]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    error_log("RPT Payment Debug: Main query returned no results for application $application_id");
                    $_SESSION['error'] = "Application not found in system.";
                    $application = null;
                } else if (!$application['land_id']) {
                    error_log("RPT Payment Debug: No land record found for application $application_id");
                    $_SESSION['error'] = "No land assessment found for this application. Please wait for assessment to complete.";
                    $application = null;
                } else {
                    error_log("RPT Payment Debug: Land found - ID: {$application['land_id']}");
                    
                    // Continue with the rest of your existing code...
                    $tax_stmt = $pdo->prepare("
                        SELECT * FROM land_assessment_tax 
                        WHERE land_id = ? 
                        ORDER BY assessment_year DESC 
                        LIMIT 1
                    ");
                    $tax_stmt->execute([$application['land_id']]);
                    $land_tax = $tax_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$land_tax) {
                        error_log("RPT Payment Debug: No land tax assessment for land ID: {$application['land_id']}");
                        $_SESSION['error'] = "No tax assessment found for this property. Please wait for tax calculation.";
                        $application = null;
                    } else {
                        error_log("RPT Payment Debug: Land tax found - ID: {$land_tax['land_tax_id']}");
                        
                        // Get building assessment tax if exists
                        $building_stmt = $pdo->prepare("
                            SELECT b.*, bat.* 
                            FROM building b 
                            LEFT JOIN building_assessment_tax bat ON b.building_id = bat.building_id 
                            WHERE b.land_id = ? 
                            ORDER BY bat.assessment_year DESC 
                            LIMIT 1
                        ");
                        $building_stmt->execute([$application['land_id']]);
                        $building_tax = $building_stmt->fetch(PDO::FETCH_ASSOC);

                        // Get total tax
                        $total_stmt = $pdo->prepare("
                            SELECT * FROM total_tax 
                            WHERE land_tax_id = ? 
                            LIMIT 1
                        ");
                        $total_stmt->execute([$land_tax['land_tax_id']]);
                        $total_tax = $total_stmt->fetch(PDO::FETCH_ASSOC);

                        // Get unpaid quarterly payments
                        $payments_stmt = $pdo->prepare("
                            SELECT * FROM quarterly 
                            WHERE land_tax_id = ? AND status IN ('unpaid', 'overdue')
                            ORDER BY quarter_no ASC
                        ");
                        $payments_stmt->execute([$land_tax['land_tax_id']]);
                        $unpaid_payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Get payment history (paid payments)
                        $history_stmt = $pdo->prepare("
                            SELECT * FROM quarterly 
                            WHERE land_tax_id = ? AND status = 'paid'
                            ORDER BY quarter_no DESC 
                            LIMIT 6
                        ");
                        $history_stmt->execute([$land_tax['land_tax_id']]);
                        $payment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Calculate total due amount
                        $total_due = 0;
                        foreach ($unpaid_payments as $payment) {
                            $total_due += $payment['tax_amount'] + ($payment['penalty'] ?? 0);
                        }

                        $annual_tax = $total_tax['total_tax'] ?? 0;
                        $quarter_labels = ['1' => '1st Quarter (Jan-Mar)', '2' => '2nd Quarter (Apr-Jun)', '3' => '3rd Quarter (Jul-Sep)', '4' => '4th Quarter (Oct-Dec)'];
                        
                        error_log("RPT Payment Debug: Successfully loaded tax data - Annual: $annual_tax, Unpaid: " . count($unpaid_payments));
                    }
                }
            }
        } else {
            error_log("RPT Payment Debug: Application $application_id not found in database");
            $_SESSION['error'] = "Application not found in system.";
            $application = null;
        }
        
    } catch (PDOException $e) {
        error_log("RPT Payment Database error: " . $e->getMessage());
        $_SESSION['error'] = "Database error occurred: " . $e->getMessage();
        $application = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Real Property Tax - RPT System</title>
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
    </style>
</head>
<body class="bg-gray-50">
   <?php include '../../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <!-- Display any error messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$application): ?>
            <!-- Show error state when no valid application -->
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-4xl text-red-600">❌</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Unable to Load Property</h2>
                <p class="text-gray-600 mb-6">The property cannot be loaded for payment processing.</p>
                <p class="text-gray-500 text-sm mb-6">
                    Common reasons:<br>
                    • Application not yet approved<br>
                    • Property assessment not completed<br>
                    • Tax calculation pending<br>
                    • System processing in progress
                </p>
                <div class="flex gap-4 justify-center">
                    <a href="../rpt_dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors duration-200">
                        ← Back to Dashboard
                    </a>
                    <a href="../rpt_application.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors duration-200">
                        📄 View Applications
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Rest of your existing HTML code for successful application loading -->
            <!-- [Keep all your existing HTML code from the previous version] -->
            
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Pay Real Property Tax</h1>
                <p class="text-gray-600">Property Tax Declaration No: <?= htmlspecialchars($application['land_tdn'] ?? 'N/A') ?></p>
            </div>

            <!-- Property Information -->
            <div class="payment-card">
                <h2 class="text-xl font-bold text-gray-800 mb-4">🏠 Property Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Property Location</p>
                        <p class="font-semibold"><?= htmlspecialchars($application['location'] . ', ' . $application['barangay'] . ', ' . $application['municipality']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Land Area</p>
                        <p class="font-semibold"><?= number_format($application['lot_area'], 2) ?> sqm</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Land Use</p>
                        <p class="font-semibold"><?= htmlspecialchars($application['land_use']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Annual Tax</p>
                        <p class="font-semibold text-green-600">₱<?= number_format($annual_tax, 2) ?></p>
                    </div>
                </div>

                <!-- Tax Breakdown -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">Tax Breakdown</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-blue-600">Land Tax:</span>
                            <span class="font-medium">₱<?= number_format($land_tax['land_total_tax'] ?? 0, 2) ?></span>
                        </div>
                        <?php if ($building_tax): ?>
                        <div>
                            <span class="text-blue-600">Building Tax:</span>
                            <span class="font-medium">₱<?= number_format($building_tax['building_total_tax'] ?? 0, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-blue-600">Total Annual:</span>
                            <span class="font-medium">₱<?= number_format($annual_tax, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unpaid Tax Payments -->
            <div class="payment-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">📅 Unpaid Tax Installments</h2>
                    <?php if (!empty($unpaid_payments)): ?>
                        <div class="text-right">
                            <p class="text-gray-600">Total Due</p>
                            <p class="total-amount">₱<?= number_format($total_due, 2) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($unpaid_payments)): ?>
                    <div class="space-y-4">
                        <?php foreach ($unpaid_payments as $payment): ?>
                            <?php
                            $payment_amount = $payment['tax_amount'] + ($payment['penalty'] ?? 0);
                            $is_overdue = $payment['status'] === 'overdue';
                            ?>
                            <div class="tax-item">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <?= $quarter_labels[$payment['quarter_no']] ?? 'Quarter ' . $payment['quarter_no'] ?>
                                            </h3>
                                            <span class="status-badge <?= $is_overdue ? 'status-overdue' : 'status-unpaid' ?>">
                                                <?= $is_overdue ? '⚠️ Overdue' : '⏰ Unpaid' ?>
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600">Due Date:</span>
                                                <span class="font-medium"><?= date('M j, Y', strtotime($payment['due_date'])) ?></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">Quarterly Tax:</span>
                                                <span class="font-medium">₱<?= number_format($payment['tax_amount'], 2) ?></span>
                                            </div>
                                            <?php if ($payment['penalty'] > 0): ?>
                                            <div>
                                                <span class="text-gray-600">Penalty:</span>
                                                <span class="font-medium text-red-600">₱<?= number_format($payment['penalty'], 2) ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="amount-display mb-2">
                                            ₱<?= number_format($payment_amount, 2) ?>
                                        </div>
                                        <!-- Payment button for single quarter -->
                                        <a href="../../digital_card/rpt_tax_payment_details.php?application_id=<?= $application_id ?>&quarter_id=<?= $payment['quarter_id'] ?>&amount=<?= $payment_amount ?>&payment_for=quarter_<?= $payment['quarter_no'] ?>&payment_type=property_tax" 
                                           class="btn-pay-single inline-block">
                                            Pay Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pay All Button -->
                    <div class="mt-6 text-center">
                        <a href="rpt_payment_details.php?application_id=<?= $application_id ?>&payment_for=all_quarters&amount=<?= $total_due ?>&payment_type=property_tax" 
                           class="btn-pay-all inline-block">
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
                        <p class="text-gray-600">You have no unpaid tax installments for this property.</p>
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
                                        <?= $quarter_labels[$payment['quarter_no']] ?? 'Quarter ' . $payment['quarter_no'] ?>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Paid on <?= $payment['date_paid'] ? date('M j, Y', strtotime($payment['date_paid'])) : 'N/A' ?>
                                        <?php if ($payment['or_no']): ?>
                                            • OR: <?= htmlspecialchars($payment['or_no']) ?>
                                        <?php endif; ?>
                                        <?php if ($payment['penalty'] > 0): ?>
                                            • <span class="text-red-600">Penalty: ₱<?= number_format($payment['penalty'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-800">
                                        ₱<?= number_format($payment['tax_amount'] + $payment['penalty'], 2) ?>
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
                <button onclick="location.href='../rpt_dashboard.php'" 
                        class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors duration-200">
                    ← Back to Dashboard
                </button>
            </div>
        <?php endif; ?>
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