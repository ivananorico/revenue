<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Preserve GET or POST parameters
$application_id = $_GET['application_id'] ?? $_POST['application_id'] ?? null;
$quarter_id     = $_GET['quarter_id'] ?? $_POST['quarter_id'] ?? null;
$amount         = $_GET['amount'] ?? $_POST['amount'] ?? 0;
$payment_for    = $_GET['payment_for'] ?? $_POST['payment_for'] ?? 'single_quarter';
$payment_type   = $_GET['payment_type'] ?? $_POST['payment_type'] ?? 'business_tax';

if (!$application_id) {
    $_SESSION['error'] = "No application specified.";
    header('Location: ../business_dashboard.php');
    exit;
}

// Database connection
require_once '../../db/Business/business_db.php';

// First, check and add status column if it doesn't exist
try {
    $check_column = $pdo->query("SHOW COLUMNS FROM assessment_quarters LIKE 'status'");
    if ($check_column->rowCount() == 0) {
        // Add status column
        $pdo->exec("ALTER TABLE `assessment_quarters` ADD COLUMN `status` ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid' AFTER `percentage`");
        // Update existing records
        $pdo->exec("UPDATE `assessment_quarters` SET `status` = 'unpaid' WHERE `status` IS NULL");
    }
} catch (PDOException $e) {
    error_log("Column check error: " . $e->getMessage());
}

$application = null;
$payment_details = [];
$business_tax = null;

try {
    // Get application and business details
    $stmt = $pdo->prepare("
        SELECT 
            ba.*,
            bt.tax_name,
            bt.tax_rate
        FROM business_applications ba
        LEFT JOIN business_assessments ba2 ON ba.id = ba2.application_id
        LEFT JOIN business_tax bt ON ba2.tax_rate_id = bt.id
        WHERE ba.id = ? AND ba.user_id = ? AND ba.status = 'assessed'
        ORDER BY ba2.assessed_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $_SESSION['error'] = "Application not found or access denied.";
        header('Location: ../business_dashboard.php');
        exit;
    }

    // Get business assessment tax details
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

    // Get unpaid quarters for display
    if ($payment_for === 'all_quarters') {
        $payments_stmt = $pdo->prepare("
            SELECT aq.* 
            FROM assessment_quarters aq
            JOIN business_assessments ba ON aq.assessment_id = ba.id
            WHERE ba.application_id = ? AND (aq.status = 'unpaid' OR aq.status IS NULL)
            ORDER BY aq.due_date ASC
        ");
        $payments_stmt->execute([$application_id]);
        $payment_details = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // For single quarter payment
        $payment_stmt = $pdo->prepare("
            SELECT aq.* 
            FROM assessment_quarters aq
            JOIN business_assessments ba ON aq.assessment_id = ba.id
            WHERE ba.application_id = ? AND aq.id = ? AND (aq.status = 'unpaid' OR aq.status IS NULL)
        ");
        $payment_stmt->execute([$application_id, $quarter_id]);
        $payment_details = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred.";
    header('Location: ../business_dashboard.php');
    exit;
}

// Handle form submission - Automated cleanup before new payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Validate payment method
    if (!in_array($payment_method, ['maya', 'gcash'])) {
        $error_message = "Please select a valid payment method.";
    } else {
        try {
            // =============================================
            // AUTOMATED CLEANUP FUNCTIONALITY
            // =============================================
            
            // 1. Clear any existing pending payment verification data for this business
            $clear_stmt = $pdo->prepare("
                UPDATE assessment_quarters 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0,
                    payment_method = NULL,
                    phone_number = NULL,
                    email = NULL,
                    verified_at = NULL
                WHERE assessment_id IN (
                    SELECT id FROM business_assessments WHERE application_id = ?
                )
                AND (expires_at < NOW() OR expires_at IS NOT NULL)
            ");
            $clear_stmt->execute([$application_id]);
            
            // 2. Clear ALL payment-related session data
            $payment_session_keys = [
                'verification_code', 'phone_number', 'email', 'expires_at',
                'tax_payment_data', 'verification_attempts', 'business_payment_data'
            ];
            foreach ($payment_session_keys as $key) {
                unset($_SESSION[$key]);
            }
            
            // =============================================
            // STORE PAYMENT DATA AND REDIRECT
            // =============================================
            
            // Store payment data in session for the next page
            $_SESSION['tax_payment_data'] = [
                'application_id' => $application_id,
                'quarter_id' => $quarter_id,
                'payment_method' => $payment_method,
                'amount' => $amount,
                'payment_for' => $payment_for,
                'payment_type' => $payment_type,
                'business_name' => $application['business_name'],
                'owner_name' => $application['owner_name'],
                'business_type' => $application['business_type'],
                'tin_id' => $application['tin_id'],
                'full_address' => $application['full_address'],
                'application_ref' => $application['application_ref'],
                'tax_name' => $business_tax['tax_name'] ?? 'Business Tax',
                'tax_rate' => $business_tax['tax_rate'] ?? 0,
                'payment_details' => $payment_details
            ];
            
            // Redirect to payment processing page
            header('Location: business_tax_payment_process.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Payment initialization error: " . $e->getMessage());
            $error_message = "Failed to initialize payment. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Tax Payment Details - Business Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../navbar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
        }
        
        /* Header Styles */
        .payment-header {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .payment-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2c5aa0 0%, #4a90e2 50%, #2c5aa0 100%);
        }
        
        .payment-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #2c5aa0 0%, #4a90e2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .payment-header p {
            color: #6b7280;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        /* Card Styles */
        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .payment-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1rem;
        }
        
        /* Security Notice */
        .security-notice {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .security-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        /* Business Summary */
        .business-summary {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
        }
        
        .summary-label {
            color: #0369a1;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .summary-value {
            color: #0c4a6e;
            font-weight: 700;
            font-size: 1rem;
        }
        
        /* Tax Information */
        .tax-info {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .tax-info h3 {
            color: #166534;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tax-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .tax-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #dcfce7;
        }
        
        .tax-label {
            color: #166534;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .tax-value {
            color: #0f766e;
            font-weight: 800;
            font-size: 1.1rem;
        }
        
        /* Payment Method Styles */
        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            position: relative;
            overflow: hidden;
        }
        
        .payment-method::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4a90e2, #2c5aa0);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .payment-method.selected {
            border-color: #3b82f6;
            background: #f0f9ff;
        }
        
        .payment-method.selected::before {
            transform: scaleX(1);
        }
        
        .payment-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .payment-method:hover .payment-icon {
            transform: scale(1.05);
        }
        
        .maya-icon {
            background: linear-gradient(135deg, #00a3ff, #0055ff);
            color: white;
        }
        
        .gcash-icon {
            background: linear-gradient(135deg, #00a64f, #007a3d);
            color: white;
        }
        
        .payment-method h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .payment-method p {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* Fee Breakdown */
        .fee-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .fee-item:hover {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin: 0 -0.5rem;
        }
        
        .fee-item:last-child {
            border-bottom: none;
        }
        
        .fee-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .fee-value {
            color: #1f2937;
            font-weight: 600;
        }
        
        .fee-total {
            border-top: 2px solid #059669;
            padding-top: 1.25rem;
            margin-top: 0.75rem;
            background: #f0f9ff;
            margin: 1rem -1.5rem 0;
            padding: 1.25rem 1.5rem;
            border-radius: 12px;
        }
        
        .fee-total .fee-label {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .fee-total .fee-value {
            color: #059669;
            font-weight: 800;
            font-size: 1.3rem;
        }
        
        /* Payment Details Items */
        .payment-details-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .payment-details-item:hover {
            background: #f0f7ff;
            border-color: #3b82f6;
        }
        
        .quarter-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 1rem;
        }
        
        .status-unpaid {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Info Boxes */
        .info-box {
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .instructions-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
        }
        
        .notes-box {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1px solid #fcd34d;
        }
        
        .info-box h3 {
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .instructions-box h3 {
            color: #0369a1;
        }
        
        .notes-box h3 {
            color: #92400e;
        }
        
        .info-box ul, .info-box ol {
            color: inherit;
            padding-left: 1.25rem;
        }
        
        .info-box li {
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #2c5aa0);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.4);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .payment-header {
                padding: 2rem 1.5rem;
            }
            
            .payment-header h1 {
                font-size: 2rem;
            }
            
            .payment-card {
                padding: 2rem 1.5rem;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .tax-grid {
                grid-template-columns: 1fr;
            }
            
            .payment-method {
                padding: 1.5rem;
            }
            
            .security-notice {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include '../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="payment-header">
            <h1>Complete Your Business Tax Payment</h1>
            <p>Secure payment for your business tax obligations</p>
        </div>

        <!-- Security Notice -->
        <div class="security-notice">
            <div class="security-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-800">Security Feature</h3>
                <p class="text-blue-700">All incomplete payment attempts are automatically cleaned up for your protection and security.</p>
            </div>
        </div>

        <div class="max-w-6xl mx-auto">
            <!-- Business Summary -->
            <div class="payment-card mb-8">
                <h2><i class="fas fa-building"></i> Business Information</h2>
                <div class="business-summary">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-label"><i class="fas fa-hashtag"></i>Application Reference</span>
                            <span class="summary-value"><?= htmlspecialchars($application['application_ref']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label"><i class="fas fa-store"></i>Business Name</span>
                            <span class="summary-value"><?= htmlspecialchars($application['business_name']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label"><i class="fas fa-user-tie"></i>Owner Name</span>
                            <span class="summary-value"><?= htmlspecialchars($application['owner_name']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label"><i class="fas fa-id-card"></i>TIN ID</span>
                            <span class="summary-value"><?= htmlspecialchars($application['tin_id']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label"><i class="fas fa-industry"></i>Business Type</span>
                            <span class="summary-value"><?= htmlspecialchars($application['business_type']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label"><i class="fas fa-map-marker-alt"></i>Business Address</span>
                            <span class="summary-value"><?= htmlspecialchars($application['full_address']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Tax Information -->
                <?php if ($business_tax): ?>
                <div class="tax-info">
                    <h3><i class="fas fa-chart-line"></i> Tax Assessment Details</h3>
                    <div class="tax-grid">
                        <div class="tax-item">
                            <div class="tax-label">Tax Type</div>
                            <div class="tax-value"><?= htmlspecialchars($business_tax['tax_name']) ?></div>
                        </div>
                        <div class="tax-item">
                            <div class="tax-label">Tax Rate</div>
                            <div class="tax-value"><?= $business_tax['tax_rate'] ?>%</div>
                        </div>
                        <div class="tax-item">
                            <div class="tax-label">Tax Amount</div>
                            <div class="tax-value">₱<?= number_format($business_tax['tax_amount'] ?? 0, 2) ?></div>
                        </div>
                        <div class="tax-item">
                            <div class="tax-label">Total Amount</div>
                            <div class="tax-value">₱<?= number_format($business_tax['total_amount'] ?? 0, 2) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Payment Details -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column - Payment Methods -->
                <div class="payment-card">
                    <h2><i class="fas fa-credit-card"></i> Choose Payment Method</h2>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                                <p class="text-red-700 font-medium"><?= htmlspecialchars($error_message) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="paymentForm" method="POST">
                        <!-- Hidden fields to pass all necessary data -->
                        <input type="hidden" name="application_id" value="<?= $application_id ?>">
                        <input type="hidden" name="quarter_id" value="<?= $quarter_id ?>">
                        <input type="hidden" name="amount" value="<?= $amount ?>">
                        <input type="hidden" name="payment_for" value="<?= $payment_for ?>">
                        <input type="hidden" name="payment_type" value="<?= $payment_type ?>">
                        
                        <!-- Maya Payment Method -->
                        <div class="payment-method mb-4" onclick="selectPaymentMethod('maya')">
                            <input type="radio" name="payment_method" value="maya" id="maya" class="hidden" required>
                            <div class="payment-icon maya-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <h3>Maya</h3>
                            <p>Pay securely using your Maya wallet with instant processing</p>
                        </div>

                        <!-- GCash Payment Method -->
                        <div class="payment-method" onclick="selectPaymentMethod('gcash')">
                            <input type="radio" name="payment_method" value="gcash" id="gcash" class="hidden" required>
                            <div class="payment-icon gcash-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3>GCash</h3>
                            <p>Pay conveniently using your GCash account with quick confirmation</p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                id="submitButton"
                                class="btn-primary mt-6 hidden">
                            <i class="fas fa-lock"></i>
                            Continue to Payment
                        </button>
                    </form>
                </div>

                <!-- Right Column - Payment Summary -->
                <div class="payment-card">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Payment Summary</h2>
                    
                    <div class="space-y-2">
                        <!-- Payment Type -->
                        <div class="fee-item">
                            <span class="fee-label">Payment Type</span>
                            <span class="font-semibold">
                                <?= $payment_for === 'all_quarters' ? 'All Unpaid Quarters' : 'Single Quarter' ?>
                            </span>
                        </div>

                        <!-- Payment Details -->
                        <?php if ($payment_for === 'all_quarters' && !empty($payment_details)): ?>
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-700 mb-3">Included Quarters:</h4>
                                <?php foreach ($payment_details as $payment): ?>
                                    <div class="payment-details-item">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($payment['quarter_name']) ?></span>
                                            <span class="font-bold text-green-600">₱<?= number_format($payment['amount'], 2) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600">
                                                Due: <?= $payment['due_date'] !== '0000-00-00' ? date('M j, Y', strtotime($payment['due_date'])) : 'Not set' ?>
                                            </span>
                                            <span class="quarter-status status-<?= $payment['days_remaining'] < 0 ? 'overdue' : 'unpaid' ?>">
                                                <i class="fas fa-clock"></i>
                                                <?= $payment['days_remaining'] ?> days
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($payment_for === 'single_quarter' && !empty($payment_details)): ?>
                            <div class="payment-details-item">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Quarter</span>
                                    <span class="font-bold"><?= htmlspecialchars($payment_details[0]['quarter_name']) ?></span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Due Date</span>
                                    <span class="font-semibold">
                                        <?= $payment_details[0]['due_date'] !== '0000-00-00' ? date('M j, Y', strtotime($payment_details[0]['due_date'])) : 'Not set' ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Status</span>
                                    <span class="quarter-status status-<?= $payment_details[0]['days_remaining'] < 0 ? 'overdue' : 'unpaid' ?>">
                                        <i class="fas fa-clock"></i>
                                        <?= $payment_details[0]['days_remaining'] ?> days
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="fee-item fee-total">
                            <span class="fee-label">Total Amount Due</span>
                            <span class="fee-value">₱<?= number_format($amount, 2) ?></span>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="info-box instructions-box">
                        <h3><i class="fas fa-info-circle"></i> How to Pay</h3>
                        <ol class="text-sm space-y-2">
                            <li>Select your preferred payment method (Maya or GCash)</li>
                            <li>Click "Continue to Payment" to proceed</li>
                            <li>Complete the payment process on the next page</li>
                            <li>Your tax payment will be automatically recorded</li>
                            <li>You will receive a confirmation email and receipt</li>
                        </ol>
                    </div>

                    <!-- Important Notes -->
                    <div class="info-box notes-box">
                        <h3><i class="fas fa-exclamation-triangle"></i> Important Notes</h3>
                        <ul class="text-sm space-y-2">
                            <li>Payment must be completed within 24 hours to avoid expiration</li>
                            <li>Late tax payments may incur penalties and interest charges</li>
                            <li>Contact the business permit office for payment assistance</li>
                            <li>All payments are secured with bank-level encryption</li>
                            <li>Keep your payment confirmation for tax filing purposes</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="text-center mt-8">
                <a href="../../api/business_user/pay_business_tax/pay_business_tax.php?application_id=<?= $application_id ?>" 
                   class="btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Tax Payments
                </a>
            </div>
        </div>
    </div>

    <script>
        let selectedMethod = '';
        
        function selectPaymentMethod(method) {
            selectedMethod = method;
            
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to chosen method
            document.querySelector(`[value="${method}"]`).closest('.payment-method').classList.add('selected');
            
            // Check the radio button
            document.getElementById(method).checked = true;
            
            // Show submit button
            const submitButton = document.getElementById('submitButton');
            submitButton.classList.remove('hidden');
            submitButton.innerHTML = `<i class="fas fa-lock"></i> Continue with ${method.charAt(0).toUpperCase() + method.slice(1)}`;
        }

        // Form submission handling
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            if (!selectedMethod) {
                e.preventDefault();
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4 mb-6';
                errorDiv.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                        <p class="text-red-700 font-medium">Please select a payment method to continue.</p>
                    </div>
                `;
                
                // Remove existing error messages
                const existingErrors = document.querySelectorAll('.bg-red-50');
                existingErrors.forEach(error => error.remove());
                
                // Insert error message
                const form = document.getElementById('paymentForm');
                form.insertBefore(errorDiv, form.firstChild);
                
                return;
            }
        });

        // Add animation to cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.payment-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>

</body>
</html>