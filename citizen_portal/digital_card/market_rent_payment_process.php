<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if payment data exists in session
if (!isset($_SESSION['rent_payment_data'])) {
    $_SESSION['error'] = "No payment data found. Please complete the payment form.";
    header('Location: market_rent_payment_details.php');
    exit;
}

$payment_data = $_SESSION['rent_payment_data'];

// Database connection
require_once '../../db/Market/market_db.php';

// First, check and add verification columns if they don't exist
try {
    // Check for verification_code column
    $check_verification = $pdo->query("SHOW COLUMNS FROM monthly_payments LIKE 'verification_code'");
    if ($check_verification->rowCount() == 0) {
        // Add verification columns
        $pdo->exec("ALTER TABLE `monthly_payments` 
                    ADD COLUMN `verification_code` VARCHAR(6) NULL AFTER `status`,
                    ADD COLUMN `expires_at` DATETIME NULL AFTER `verification_code`,
                    ADD COLUMN `verification_attempts` INT DEFAULT 0 AFTER `expires_at`,
                    ADD COLUMN `phone_number` VARCHAR(20) NULL AFTER `verification_attempts`,
                    ADD COLUMN `email` VARCHAR(255) NULL AFTER `phone_number`,
                    ADD COLUMN `verified_at` DATETIME NULL AFTER `email`");
    }
} catch (PDOException $e) {
    error_log("Column check error: " . $e->getMessage());
}

// =============================================
// CHECK DATABASE FOR EXISTING VERIFICATION DATA
// =============================================
$existing_verification = null;
$show_verification_modal = false;

try {
    // Check if there's an active verification in the database for this renter
    $check_stmt = $pdo->prepare("
        SELECT verification_code, expires_at, phone_number, email, verification_attempts
        FROM monthly_payments 
        WHERE renter_id = ?
        AND verification_code IS NOT NULL 
        AND expires_at > NOW()
        AND status = 'pending'
        ORDER BY expires_at DESC 
        LIMIT 1
    ");
    $check_stmt->execute([$payment_data['renter_id']]);
    $existing_verification = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_verification) {
        // Found active verification in database - sync with session
        $_SESSION['rent_verification_code'] = $existing_verification['verification_code'];
        $_SESSION['rent_expires_at'] = $existing_verification['expires_at'];
        $_SESSION['rent_phone_number'] = $existing_verification['phone_number'];
        $_SESSION['rent_email'] = $existing_verification['email'];
        $show_verification_modal = true;
        
        // Also check if verification attempts exceeded
        if ($existing_verification['verification_attempts'] >= 3) {
            // Clear expired verification due to too many attempts
            $clear_stmt = $pdo->prepare("
                UPDATE monthly_payments 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0
                WHERE renter_id = ?
                AND verification_code IS NOT NULL
            ");
            $clear_stmt->execute([$payment_data['renter_id']]);
            $show_verification_modal = false;
            unset($_SESSION['rent_verification_code'], $_SESSION['rent_expires_at']);
        }
    }
} catch (PDOException $e) {
    error_log("Database check error: " . $e->getMessage());
    $error_message = "Database connection error. Please try again.";
}

// Handle phone number and email submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($phone_number)) {
        $error_message = "Please enter your phone number.";
    } elseif (empty($email)) {
        $error_message = "Please enter your email address.";
    } elseif (!preg_match('/^09[0-9]{9}$/', $phone_number)) {
        $error_message = "Please enter a valid Philippine phone number (09XXXXXXXXX).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Generate verification code
            $verification_code = sprintf("%06d", mt_rand(1, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Debug logging
            error_log("RENT PAYMENT: Starting verification for renter_id: " . $payment_data['renter_id']);
            error_log("RENT PAYMENT: Payment method: " . $payment_data['payment_method']);
            error_log("RENT PAYMENT: Payment for: " . $payment_data['payment_for']);
            
            // =============================================
            // STORE VERIFICATION DATA IN DATABASE
            // =============================================
            if ($payment_data['payment_for'] === 'all_months') {
                // Update ALL pending payments for this renter
                $update_stmt = $pdo->prepare("
                    UPDATE monthly_payments 
                    SET verification_code = ?, 
                        expires_at = ?, 
                        verification_attempts = 0,
                        payment_method = ?,
                        phone_number = ?,
                        email = ?,
                        updated_at = NOW()
                    WHERE renter_id = ? 
                    AND status = 'pending'
                ");
                
                $result = $update_stmt->execute([
                    $verification_code,
                    $expires_at,
                    $payment_data['payment_method'],
                    $phone_number,
                    $email,
                    $payment_data['renter_id']
                ]);
                
                error_log("RENT PAYMENT: All months update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("RENT PAYMENT: Rows affected: " . $update_stmt->rowCount());
                
            } else {
                // Update specific month payment
                $update_stmt = $pdo->prepare("
                    UPDATE monthly_payments 
                    SET verification_code = ?, 
                        expires_at = ?, 
                        verification_attempts = 0,
                        payment_method = ?,
                        phone_number = ?,
                        email = ?,
                        updated_at = NOW()
                    WHERE renter_id = ? 
                    AND month_year = ? 
                    AND status = 'pending'
                ");
                
                $result = $update_stmt->execute([
                    $verification_code,
                    $expires_at,
                    $payment_data['payment_method'],
                    $phone_number,
                    $email,
                    $payment_data['renter_id'],
                    $payment_data['month_year']
                ]);
                
                error_log("RENT PAYMENT: Single month update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("RENT PAYMENT: Rows affected: " . $update_stmt->rowCount());
                error_log("RENT PAYMENT: Month year used: " . $payment_data['month_year']);
                
                // Check if the month exists and is pending
                $check_month_stmt = $pdo->prepare("
                    SELECT id, month_year, amount, late_fee, status 
                    FROM monthly_payments 
                    WHERE renter_id = ? AND month_year = ? AND status = 'pending'
                ");
                $check_month_stmt->execute([$payment_data['renter_id'], $payment_data['month_year']]);
                $month_check = $check_month_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$month_check) {
                    error_log("RENT PAYMENT ERROR: Month not found or already paid. Renter ID: " . $payment_data['renter_id'] . ", Month: " . $payment_data['month_year']);
                    $error_message = "Monthly payment not found or already paid. Please check your payment details.";
                } else {
                    error_log("RENT PAYMENT: Month found - " . $month_check['month_year'] . ", Amount: " . $month_check['amount'] . ", Status: " . $month_check['status']);
                }
            }
            
            if ($result && $update_stmt->rowCount() > 0) {
                // Store in session for verification
                $_SESSION['rent_verification_code'] = $verification_code;
                $_SESSION['rent_phone_number'] = $phone_number;
                $_SESSION['rent_email'] = $email;
                $_SESSION['rent_expires_at'] = $expires_at;
                
                $success_message = "Verification code sent! Check your phone for the code.";
                $show_verification_modal = true;
                
                error_log("RENT PAYMENT: Verification code generated: " . $verification_code);
                
            } else {
                throw new Exception("No rows updated. The monthly payment may not exist or is already paid.");
            }
            
        } catch (Exception $e) {
            error_log("Payment error: " . $e->getMessage());
            $error_message = "Failed to process payment: " . $e->getMessage();
            
            // Additional debug info
            error_log("RENT PAYMENT DEBUG - Renter ID: " . $payment_data['renter_id']);
            error_log("RENT PAYMENT DEBUG - Application ID: " . $payment_data['application_id']);
            error_log("RENT PAYMENT DEBUG - Payment For: " . $payment_data['payment_for']);
            error_log("RENT PAYMENT DEBUG - Month Year: " . ($payment_data['month_year'] ?? 'N/A'));
            error_log("RENT PAYMENT DEBUG - Amount: " . $payment_data['amount']);
        }
    }
}

// Handle verification and final payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $entered_code = $_POST['verification_code'] ?? '';
    
    try {
        // =============================================
        // CHECK VERIFICATION CODE IN DATABASE
        // =============================================
        $verify_stmt = $pdo->prepare("
            SELECT verification_code, expires_at, verification_attempts
            FROM monthly_payments 
            WHERE renter_id = ?
            AND verification_code IS NOT NULL
            AND status = 'pending'
            LIMIT 1
        ");
        $verify_stmt->execute([$payment_data['renter_id']]);
        $db_verification = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$db_verification) {
            $verification_error = "No active verification found. Please request a new code.";
            $show_verification_modal = false;
        } elseif (strtotime($db_verification['expires_at']) < time()) {
            $verification_error = "Verification code has expired. Please request a new one.";
            // Clear expired verification
            $clear_stmt = $pdo->prepare("
                UPDATE monthly_payments 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0
                WHERE renter_id = ?
                AND verification_code IS NOT NULL
            ");
            $clear_stmt->execute([$payment_data['renter_id']]);
            $show_verification_modal = false;
            unset($_SESSION['rent_verification_code'], $_SESSION['rent_expires_at']);
        } elseif ($db_verification['verification_attempts'] >= 3) {
            $verification_error = "Too many failed attempts. Please request a new verification code.";
            // Clear due to too many attempts
            $clear_stmt = $pdo->prepare("
                UPDATE monthly_payments 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0
                WHERE renter_id = ?
                AND verification_code IS NOT NULL
            ");
            $clear_stmt->execute([$payment_data['renter_id']]);
            $show_verification_modal = false;
            unset($_SESSION['rent_verification_code'], $_SESSION['rent_expires_at']);
        } elseif ($entered_code === $db_verification['verification_code']) {
            // VERIFICATION SUCCESSFUL - PROCESS PAYMENT
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate reference number
                $reference_number = 'RENT-' . date('Ymd-His') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Debug: Check what we're trying to update
                error_log("RENT PAYMENT DEBUG: Processing payment for renter_id: " . $payment_data['renter_id']);
                error_log("RENT PAYMENT DEBUG: Payment for: " . $payment_data['payment_for']);
                error_log("RENT PAYMENT DEBUG: Month year: " . ($payment_data['month_year'] ?? 'N/A'));
                
                if ($payment_data['payment_for'] === 'all_months') {
                    // Update ALL pending payments for this renter
                    $update_stmt = $pdo->prepare("
                        UPDATE monthly_payments 
                        SET status = 'paid', 
                            reference_number = ?, 
                            paid_date = NOW(), 
                            payment_method = ?,
                            phone_number = ?,
                            email = ?,
                            verified_at = NOW(),
                            verification_code = NULL,
                            expires_at = NULL,
                            verification_attempts = 0,
                            updated_at = NOW()
                        WHERE renter_id = ? 
                        AND status = 'pending'
                    ");
                    $update_stmt->execute([
                        $reference_number,
                        $payment_data['payment_method'],
                        $_SESSION['rent_phone_number'],
                        $_SESSION['rent_email'],
                        $payment_data['renter_id']
                    ]);
                } else {
                    // Update specific month payment
                    $update_stmt = $pdo->prepare("
                        UPDATE monthly_payments 
                        SET status = 'paid', 
                            reference_number = ?, 
                            paid_date = NOW(), 
                            payment_method = ?,
                            phone_number = ?,
                            email = ?,
                            verified_at = NOW(),
                            verification_code = NULL,
                            expires_at = NULL,
                            verification_attempts = 0,
                            updated_at = NOW()
                        WHERE renter_id = ? 
                        AND month_year = ? 
                        AND status = 'pending'
                    ");
                    $update_stmt->execute([
                        $reference_number,
                        $payment_data['payment_method'],
                        $_SESSION['rent_phone_number'],
                        $_SESSION['rent_email'],
                        $payment_data['renter_id'],
                        $payment_data['month_year']
                    ]);
                }
                
                $affected_rows = $update_stmt->rowCount();
                error_log("RENT PAYMENT DEBUG: Affected rows: " . $affected_rows);
                error_log("RENT PAYMENT DEBUG: Reference Number generated: " . $reference_number);
                
                if ($affected_rows > 0) {
                    // Commit transaction
                    $pdo->commit();
                    
                    // Store success data
                    $_SESSION['rent_payment_success'] = [
                        'reference_number' => $reference_number,
                        'amount' => $payment_data['amount'],
                        'payment_method' => $payment_data['payment_method'],
                        'payment_for' => $payment_data['payment_for'],
                        'month_year' => $payment_data['month_year'],
                        'application_id' => $payment_data['application_id'],
                        'renter_id' => $payment_data['renter_id'],
                        'business_name' => $payment_data['business_name'],
                        'market_name' => $payment_data['market_name'],
                        'stall_name' => $payment_data['stall_name'],
                        'affected_rows' => $affected_rows
                    ];
                    
                    // Clear session data
                    unset(
                        $_SESSION['rent_payment_data'], 
                        $_SESSION['rent_verification_code'], 
                        $_SESSION['rent_phone_number'], 
                        $_SESSION['rent_email'], 
                        $_SESSION['rent_expires_at']
                    );

                    error_log("RENT PAYMENT SUCCESS: Redirecting to success page with Reference: " . $reference_number);
                    
                    // Redirect to success page
                    header('Location: market_rent_payment_success.php?application_id=' . $payment_data['application_id']);
                    exit;
                } else {
                    // Check why no rows were affected
                    if ($payment_data['payment_for'] === 'all_months') {
                        $check_stmt = $pdo->prepare("
                            SELECT COUNT(*) as total_count 
                            FROM monthly_payments 
                            WHERE renter_id = ? 
                            AND status = 'pending'
                        ");
                        $check_stmt->execute([$payment_data['renter_id']]);
                    } else {
                        $check_stmt = $pdo->prepare("
                            SELECT COUNT(*) as total_count 
                            FROM monthly_payments 
                            WHERE renter_id = ? 
                            AND month_year = ? 
                            AND status = 'pending'
                        ");
                        $check_stmt->execute([$payment_data['renter_id'], $payment_data['month_year']]);
                    }
                    
                    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    error_log("RENT PAYMENT DEBUG: Total pending payments found: " . $result['total_count']);
                    
                    throw new Exception("No payments were updated. Found " . $result['total_count'] . " pending payments.");
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Rent payment verification error: " . $e->getMessage());
                $verification_error = "Payment failed: " . $e->getMessage();
                $show_verification_modal = true;
            }
        } else {
            // INVALID CODE - INCREMENT ATTEMPTS
            $attempts_stmt = $pdo->prepare("
                UPDATE monthly_payments 
                SET verification_attempts = verification_attempts + 1
                WHERE renter_id = ?
                AND verification_code IS NOT NULL
            ");
            $attempts_stmt->execute([$payment_data['renter_id']]);
            
            $verification_error = "Invalid verification code. Please try again.";
            $show_verification_modal = true;
        }
        
    } catch (PDOException $e) {
        error_log("Verification check error: " . $e->getMessage());
        $verification_error = "System error. Please try again.";
        $show_verification_modal = true;
    }
}

// Final check - if no database verification found, don't show modal
if ($show_verification_modal && !isset($_SESSION['rent_verification_code'])) {
    $show_verification_modal = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= strtoupper($payment_data['payment_method']) ?> Payment - Monthly Rent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../navbar.css">
    <style>
        .payment-header {
            background: linear-gradient(135deg, 
                <?= $payment_data['payment_method'] === 'gcash' ? '#00a64f, #007a3d' : '#00a3ff, #0055ff' ?>);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .amount-display {
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
            margin: 2rem 0;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            margin: 1rem;
        }
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            color: #ef4444;
            margin: 1rem 0;
        }
        .payment-details {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include '../../citizen_portal/navbar.php'; ?>

    <!-- Payment Header -->
    <div class="payment-header">
        <div class="max-w-md mx-auto">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">
                    <?= $payment_data['payment_method'] === 'gcash' ? 'G' : 'M' ?>
                </span>
            </div>
            <h1 class="text-3xl font-bold mb-2"><?= strtoupper($payment_data['payment_method']) ?></h1>
            <p class="text-white/90">Monthly Rent Payment</p>
        </div>
    </div>

    <!-- Verification Modal -->
    <?php if ($show_verification_modal && isset($_SESSION['rent_verification_code'])): ?>
    <div id="verificationModal" class="modal" style="display: flex;">
        <div class="modal-content">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl">📱</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Enter Verification Code</h3>
                <p class="text-gray-600">Enter the code sent to your phone</p>
                
                <!-- Countdown Timer -->
                <div id="countdown" class="countdown">10:00</div>
                
                <?php if (isset($_SESSION['rent_verification_code'])): ?>
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-700 font-semibold">Demo Code: <?= $_SESSION['rent_verification_code'] ?></p>
                        <p class="text-green-600 text-sm">Enter this code to verify</p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" id="verificationForm">
                <div class="mb-6">
                    <label for="verification_input" class="block text-sm font-medium text-gray-700 mb-2">
                        Verification Code
                    </label>
                    <input type="text" 
                           name="verification_code" 
                           id="verification_input"
                           placeholder="Enter 6-digit code"
                           required
                           maxlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-xl tracking-widest">
                </div>

                <?php if (isset($verification_error)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                        <p class="text-red-700 text-sm"><?= htmlspecialchars($verification_error) ?></p>
                    </div>
                <?php endif; ?>

                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeModalAndClear()"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="verifyButton"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                        ✅ Verify & Pay
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-md mx-auto px-6 py-8">
        <!-- Amount Display -->
        <div class="text-center mb-8">
            <p class="text-gray-600 text-lg mb-2">Total Amount to Pay</p>
            <div class="amount-display text-green-600">
                ₱<?= number_format($payment_data['amount'], 2) ?>
            </div>
            <p class="text-gray-500 text-sm">Monthly Rent Payment</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-700"><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-700"><?= htmlspecialchars($success_message) ?></p>
            </div>
        <?php endif; ?>

        <!-- Payment Details -->
        <div class="payment-details mb-6">
            <h3 class="font-semibold text-gray-800 mb-3">Payment Details</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Renter:</span>
                    <span class="font-semibold"><?= htmlspecialchars($payment_data['business_name']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Stall:</span>
                    <span class="font-semibold"><?= htmlspecialchars($payment_data['market_name']) ?> - <?= htmlspecialchars($payment_data['stall_name']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Type:</span>
                    <span class="font-semibold">
                        <?= $payment_data['payment_for'] === 'all_months' ? 'All Unpaid Months' : 'Single Month (' . date('F Y', strtotime($payment_data['month_year'] . '-01')) . ')' ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Reference:</span>
                    <span class="font-semibold">RENT-<?= $payment_data['application_id'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Renter ID:</span>
                    <span class="font-semibold"><?= htmlspecialchars($payment_data['renter_id']) ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">Enter Your Details</h2>
            
            <form method="POST" id="paymentForm">
                <!-- Phone Number -->
                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number *
                    </label>
                    <input type="tel" 
                           name="phone_number" 
                           id="phone_number"
                           placeholder="09123456789"
                           pattern="09[0-9]{9}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-sm text-gray-500 mt-2">
                        Your <?= strtoupper($payment_data['payment_method']) ?> registered number
                    </p>
                </div>

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address *
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           placeholder="your@email.com"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-sm text-gray-500 mt-2">
                        For payment confirmation and receipt
                    </p>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors duration-200">
                    📱 Send Verification Code
                </button>
            </form>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <h3 class="font-semibold text-green-800 mb-2">Secure Payment</h3>
            <p class="text-sm text-green-700">
                Your payment is secured with end-to-end encryption. We never store your payment details.
            </p>
        </div>

        <!-- Back Button -->
        <div class="text-center mt-8">
            <a href="market_rent_payment_details.php?application_id=<?= $payment_data['application_id'] ?>" 
               class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 font-semibold">
                ← Back to Payment Methods
            </a>
        </div>
    </div>

    <script>
        // Auto-format phone number
        document.getElementById('phone_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });

        // Auto-format verification code
        const verificationInput = document.getElementById('verification_input');
        if (verificationInput) {
            verificationInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }

        // Modal functions
        function closeModalAndClear() {
            const modal = document.getElementById('verificationModal');
            if (modal) {
                modal.style.display = 'none';
                clearInterval(countdownInterval);
                
                // Clear verification data via AJAX
                fetch('clear_rent_verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'renter_id=<?= $payment_data['renter_id'] ?>'
                });
            }
        }

        function closeModal() {
            const modal = document.getElementById('verificationModal');
            if (modal) {
                modal.style.display = 'none';
                clearInterval(countdownInterval);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('verificationModal');
            if (modal && event.target === modal) {
                closeModal();
            }
        }

        // Countdown timer
        let countdownInterval;
        function startCountdown() {
            let timeLeft = 10 * 60; // 10 minutes in seconds
            const countdownElement = document.getElementById('countdown');
            const verifyButton = document.getElementById('verifyButton');
            
            if (!countdownElement || !verifyButton) return;
            
            countdownInterval = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    countdownElement.textContent = "Expired!";
                    countdownElement.style.color = "#ef4444";
                    verifyButton.disabled = true;
                    verifyButton.innerHTML = "⏰ Code Expired";
                    verifyButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                    verifyButton.classList.add('bg-gray-400');
                    
                    // Auto-clear expired verification
                    fetch('clear_rent_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'renter_id=<?= $payment_data['renter_id'] ?>'
                    });
                }
                
                timeLeft--;
            }, 1000);
        }

        // Auto-start countdown if modal is shown
        <?php if ($show_verification_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                startCountdown();
                setTimeout(() => {
                    const input = document.getElementById('verification_input');
                    if (input) input.focus();
                }, 100);
            });
        <?php endif; ?>
    </script>

</body>
</html>