<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if payment data exists in session
if (!isset($_SESSION['tax_payment_data'])) {
    $_SESSION['error'] = "No payment data found. Please complete the payment form.";
    header('Location: business_tax_payment_details.php');
    exit;
}

$payment_data = $_SESSION['tax_payment_data'];
$full_name = $_SESSION['full_name'] ?? 'Guest';

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

// =============================================
// CHECK DATABASE FOR EXISTING VERIFICATION DATA
// =============================================
$existing_verification = null;
$show_verification_modal = false;

try {
    // Check if there's an active verification in the database for this business
    $check_stmt = $pdo->prepare("
        SELECT verification_code, expires_at, phone_number, email, verification_attempts
        FROM assessment_quarters 
        WHERE id = ?
        AND verification_code IS NOT NULL 
        AND expires_at > NOW()
        AND (status = 'unpaid' OR status IS NULL)
        ORDER BY expires_at DESC 
        LIMIT 1
    ");
    $check_stmt->execute([$payment_data['quarter_id']]);
    $existing_verification = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_verification) {
        // Found active verification in database - sync with session
        $_SESSION['verification_code'] = $existing_verification['verification_code'];
        $_SESSION['expires_at'] = $existing_verification['expires_at'];
        $_SESSION['phone_number'] = $existing_verification['phone_number'];
        $_SESSION['email'] = $existing_verification['email'];
        $show_verification_modal = true;
        
        // Also check if verification attempts exceeded
        if ($existing_verification['verification_attempts'] >= 3) {
            // Clear expired verification due to too many attempts
            $clear_stmt = $pdo->prepare("
                UPDATE assessment_quarters 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0
                WHERE id = ?
            ");
            $clear_stmt->execute([$payment_data['quarter_id']]);
            $show_verification_modal = false;
            unset($_SESSION['verification_code'], $_SESSION['expires_at']);
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
            error_log("BUSINESS PAYMENT: Starting verification for quarter_id: " . $payment_data['quarter_id']);
            error_log("BUSINESS PAYMENT: Payment method: " . $payment_data['payment_method']);
            error_log("BUSINESS PAYMENT: Payment for: " . $payment_data['payment_for']);
            
            // =============================================
            // STORE VERIFICATION DATA IN DATABASE
            // =============================================
            if ($payment_data['payment_for'] === 'all_quarters') {
                // Update ALL unpaid quarterly payments for this business
                $update_stmt = $pdo->prepare("
                    UPDATE assessment_quarters 
                    SET verification_code = ?, 
                        expires_at = ?, 
                        verification_attempts = 0,
                        payment_method = ?,
                        phone_number = ?,
                        email = ?,
                        updated_at = NOW()
                    WHERE assessment_id IN (
                        SELECT id FROM business_assessments WHERE application_id = ?
                    )
                    AND (status = 'unpaid' OR status IS NULL)
                ");
                
                $result = $update_stmt->execute([
                    $verification_code,
                    $expires_at,
                    $payment_data['payment_method'],
                    $phone_number,
                    $email,
                    $payment_data['application_id']
                ]);
                
                error_log("BUSINESS PAYMENT: All quarters update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("BUSINESS PAYMENT: Rows affected: " . $update_stmt->rowCount());
                
            } else {
                // Update specific quarter payment
                $update_stmt = $pdo->prepare("
                    UPDATE assessment_quarters 
                    SET verification_code = ?, 
                        expires_at = ?, 
                        verification_attempts = 0,
                        payment_method = ?,
                        phone_number = ?,
                        email = ?,
                        updated_at = NOW()
                    WHERE id = ? 
                    AND (status = 'unpaid' OR status IS NULL)
                ");
                
                $result = $update_stmt->execute([
                    $verification_code,
                    $expires_at,
                    $payment_data['payment_method'],
                    $phone_number,
                    $email,
                    $payment_data['quarter_id']
                ]);
                
                error_log("BUSINESS PAYMENT: Single quarter update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("BUSINESS PAYMENT: Rows affected: " . $update_stmt->rowCount());
                error_log("BUSINESS PAYMENT: Quarter ID used: " . $payment_data['quarter_id']);
                
                // Check if the quarter exists and is unpaid
                $check_quarter_stmt = $pdo->prepare("
                    SELECT id, quarter_name, amount, status 
                    FROM assessment_quarters 
                    WHERE id = ? AND (status = 'unpaid' OR status IS NULL)
                ");
                $check_quarter_stmt->execute([$payment_data['quarter_id']]);
                $quarter_check = $check_quarter_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$quarter_check) {
                    error_log("BUSINESS PAYMENT ERROR: Quarter not found or already paid. ID: " . $payment_data['quarter_id']);
                    $error_message = "Quarter not found or already paid. Please check your payment details.";
                } else {
                    error_log("BUSINESS PAYMENT: Quarter found - " . $quarter_check['quarter_name'] . ", Amount: " . $quarter_check['amount'] . ", Status: " . ($quarter_check['status'] ?? 'NULL'));
                }
            }
            
            if ($result && $update_stmt->rowCount() > 0) {
                // Store in session for verification
                $_SESSION['verification_code'] = $verification_code;
                $_SESSION['phone_number'] = $phone_number;
                $_SESSION['email'] = $email;
                $_SESSION['expires_at'] = $expires_at;
                
                $success_message = "Verification code sent! Check your phone for the code.";
                $show_verification_modal = true;
                
                error_log("BUSINESS PAYMENT: Verification code generated: " . $verification_code);
                
            } else {
                throw new Exception("No rows updated. The quarter may not exist or is already paid.");
            }
            
        } catch (Exception $e) {
            error_log("Payment error: " . $e->getMessage());
            $error_message = "Failed to process payment: " . $e->getMessage();
            
            // Additional debug info
            error_log("BUSINESS PAYMENT DEBUG - Application ID: " . $payment_data['application_id']);
            error_log("BUSINESS PAYMENT DEBUG - Quarter ID: " . $payment_data['quarter_id']);
            error_log("BUSINESS PAYMENT DEBUG - Payment For: " . $payment_data['payment_for']);
            error_log("BUSINESS PAYMENT DEBUG - Amount: " . $payment_data['amount']);
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
            FROM assessment_quarters 
            WHERE id = ?
            AND verification_code IS NOT NULL
            AND (status = 'unpaid' OR status IS NULL)
            LIMIT 1
        ");
        $verify_stmt->execute([$payment_data['quarter_id']]);
        $db_verification = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$db_verification) {
            $verification_error = "No active verification found. Please request a new code.";
            $show_verification_modal = false;
        } elseif (strtotime($db_verification['expires_at']) < time()) {
            $verification_error = "Verification code has expired. Please request a new one.";
            // Clear expired verification
            $clear_stmt = $pdo->prepare("
                UPDATE assessment_quarters 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0
                WHERE id = ?
            ");
            $clear_stmt->execute([$payment_data['quarter_id']]);
            $show_verification_modal = false;
            unset($_SESSION['verification_code'], $_SESSION['expires_at']);
        } elseif ($db_verification['verification_attempts'] >= 3) {
            $verification_error = "Too many failed attempts. Please request a new verification code.";
            // Clear due to too many attempts
            $clear_stmt = $pdo->prepare("
                UPDATE assessment_quarters 
                SET verification_code = NULL, 
                    expires_at = NULL, 
                    verification_attempts = 0
                WHERE id = ?
            ");
            $clear_stmt->execute([$payment_data['quarter_id']]);
            $show_verification_modal = false;
            unset($_SESSION['verification_code'], $_SESSION['expires_at']);
        } elseif ($entered_code === $db_verification['verification_code']) {
            // VERIFICATION SUCCESSFUL - PROCESS PAYMENT
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate OR number and transaction ID
                $or_number = 'OR-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $transaction_id = 'BUS-' . date('Ymd-His') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Debug: Check what we're trying to update
                error_log("BUSINESS PAYMENT DEBUG: Processing payment for application_id: " . $payment_data['application_id']);
                error_log("BUSINESS PAYMENT DEBUG: Payment for: " . $payment_data['payment_for']);
                error_log("BUSINESS PAYMENT DEBUG: Quarter ID: " . ($payment_data['quarter_id'] ?? 'N/A'));
                
                if ($payment_data['payment_for'] === 'all_quarters') {
                    // Update ALL unpaid quarterly payments for this business
                    $update_stmt = $pdo->prepare("
                        UPDATE assessment_quarters 
                        SET status = 'paid', 
                            payment_method = ?,
                            phone_number = ?,
                            email = ?,
                            date_paid = CURDATE(),
                            or_no = ?,
                            verified_at = NOW(),
                            verification_code = NULL,
                            expires_at = NULL,
                            verification_attempts = 0,
                            updated_at = NOW()
                        WHERE assessment_id IN (
                            SELECT id FROM business_assessments WHERE application_id = ?
                        )
                        AND (status = 'unpaid' OR status IS NULL)
                    ");
                    $update_stmt->execute([
                        $payment_data['payment_method'],
                        $_SESSION['phone_number'],
                        $_SESSION['email'],
                        $or_number,
                        $payment_data['application_id']
                    ]);
                } else {
                    // Update specific quarter payment
                    $update_stmt = $pdo->prepare("
                        UPDATE assessment_quarters 
                        SET status = 'paid', 
                            payment_method = ?,
                            phone_number = ?,
                            email = ?,
                            date_paid = CURDATE(),
                            or_no = ?,
                            verified_at = NOW(),
                            verification_code = NULL,
                            expires_at = NULL,
                            verification_attempts = 0,
                            updated_at = NOW()
                        WHERE id = ? 
                        AND (status = 'unpaid' OR status IS NULL)
                    ");
                    $update_stmt->execute([
                        $payment_data['payment_method'],
                        $_SESSION['phone_number'],
                        $_SESSION['email'],
                        $or_number,
                        $payment_data['quarter_id']
                    ]);
                }
                
                $affected_rows = $update_stmt->rowCount();
                error_log("BUSINESS PAYMENT DEBUG: Affected rows: " . $affected_rows);
                error_log("BUSINESS PAYMENT DEBUG: OR Number generated: " . $or_number);
                
                if ($affected_rows > 0) {
                    // Also update the business application status to 'paid'
                    $update_application_stmt = $pdo->prepare("
                        UPDATE business_applications 
                        SET status = 'paid'
                        WHERE id = ?
                    ");
                    $update_application_stmt->execute([$payment_data['application_id']]);
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Store success data
                    $_SESSION['business_payment_success'] = [
                        'transaction_id' => $transaction_id,
                        'or_number' => $or_number,
                        'amount' => $payment_data['amount'],
                        'payment_method' => $payment_data['payment_method'],
                        'payment_for' => $payment_data['payment_for'],
                        'quarter_id' => $payment_data['quarter_id'],
                        'application_id' => $payment_data['application_id'],
                        'business_name' => $payment_data['business_name'],
                        'owner_name' => $payment_data['owner_name'],
                        'business_type' => $payment_data['business_type'],
                        'tin_id' => $payment_data['tin_id'],
                        'business_address' => $payment_data['full_address'],
                        'application_ref' => $payment_data['application_ref'],
                        'tax_name' => $payment_data['tax_name'],
                        'tax_rate' => $payment_data['tax_rate'],
                        'payment_details' => $payment_data['payment_details'],
                        'affected_rows' => $affected_rows
                    ];
                    
                    // Clear session data
                    unset(
                        $_SESSION['tax_payment_data'], 
                        $_SESSION['verification_code'], 
                        $_SESSION['phone_number'], 
                        $_SESSION['email'], 
                        $_SESSION['expires_at']
                    );

                    error_log("BUSINESS PAYMENT SUCCESS: Redirecting to success page with OR: " . $or_number);
                    
                    // Redirect to success page
                    header('Location: business_tax_payment_success.php?application_id=' . $payment_data['application_id']);
                    exit;
                } else {
                    // Check why no rows were affected
                    if ($payment_data['payment_for'] === 'all_quarters') {
                        $check_stmt = $pdo->prepare("
                            SELECT COUNT(*) as total_count 
                            FROM assessment_quarters 
                            WHERE assessment_id IN (
                                SELECT id FROM business_assessments WHERE application_id = ?
                            )
                            AND (status = 'unpaid' OR status IS NULL)
                        ");
                        $check_stmt->execute([$payment_data['application_id']]);
                    } else {
                        $check_stmt = $pdo->prepare("
                            SELECT COUNT(*) as total_count 
                            FROM assessment_quarters 
                            WHERE id = ?
                            AND (status = 'unpaid' OR status IS NULL)
                        ");
                        $check_stmt->execute([$payment_data['quarter_id']]);
                    }
                    
                    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    error_log("BUSINESS PAYMENT DEBUG: Total unpaid quarterly payments found: " . $result['total_count']);
                    
                    throw new Exception("No quarterly payments were updated. Found " . $result['total_count'] . " unpaid payments.");
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Business payment verification error: " . $e->getMessage());
                $verification_error = "Payment failed: " . $e->getMessage();
                $show_verification_modal = true;
            }
        } else {
            // INVALID CODE - INCREMENT ATTEMPTS
            $attempts_stmt = $pdo->prepare("
                UPDATE assessment_quarters 
                SET verification_attempts = verification_attempts + 1
                WHERE id = ?
                AND verification_code IS NOT NULL
            ");
            $attempts_stmt->execute([$payment_data['quarter_id']]);
            
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
if ($show_verification_modal && !isset($_SESSION['verification_code'])) {
    $show_verification_modal = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= strtoupper($payment_data['payment_method']) ?> Payment - Business Tax</title>
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
        
        .payment-header {
            background: linear-gradient(135deg, 
                <?= $payment_data['payment_method'] === 'gcash' ? '#00a64f, #007a3d' : '#00a3ff, #0055ff' ?>);
            color: white;
            padding: 3rem 2rem;
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
            background: rgba(255, 255, 255, 0.3);
        }
        
        .payment-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .amount-display {
            font-size: 3.5rem;
            font-weight: 800;
            text-align: center;
            margin: 2rem 0;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
            position: relative;
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4a90e2, #2c5aa0);
            border-radius: 24px 24px 0 0;
        }
        
        .countdown {
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            color: #ef4444;
            margin: 1.5rem 0;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
        
        .verification-input {
            letter-spacing: 0.5em;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .demo-code {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
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
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .business-details {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: #0369a1;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-value {
            color: #0c4a6e;
            font-weight: 700;
            font-size: 0.95rem;
        }
        
        .tax-info {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .tax-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .tax-item {
            text-align: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #dcfce7;
        }
        
        .tax-label {
            color: #166534;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .tax-value {
            color: #0f766e;
            font-weight: 800;
            font-size: 1rem;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
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
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @media (max-width: 768px) {
            .payment-header {
                padding: 2rem 1.5rem;
            }
            
            .amount-display {
                font-size: 2.5rem;
            }
            
            .payment-card {
                padding: 2rem 1.5rem;
            }
            
            .modal-content {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .tax-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include '../../citizen_portal/navbar.php'; ?>

    <!-- Payment Header -->
    <div class="payment-header">
        <div class="max-w-md mx-auto">
            <div class="payment-icon">
                <i class="fas fa-<?= $payment_data['payment_method'] === 'gcash' ? 'mobile-alt' : 'wallet' ?> text-4xl"></i>
            </div>
            <h1 class="text-4xl font-bold mb-2"><?= strtoupper($payment_data['payment_method']) ?></h1>
            <p class="text-white/90 text-lg">Business Tax Payment Gateway</p>
        </div>
    </div>

    <!-- Verification Modal -->
    <?php if ($show_verification_modal && isset($_SESSION['verification_code'])): ?>
    <div id="verificationModal" class="modal-overlay" style="display: flex;">
        <div class="modal-content">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Enter Verification Code</h3>
                <p class="text-gray-600">Enter the 6-digit code sent to your phone</p>
                
                <!-- Countdown Timer -->
                <div id="countdown" class="countdown">10:00</div>
                
                <?php if (isset($_SESSION['verification_code'])): ?>
                    <div class="demo-code">
                        <p class="font-semibold text-lg">Demo Code: <?= $_SESSION['verification_code'] ?></p>
                        <p class="text-white/90 text-sm mt-1">Enter this code to verify your payment</p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" id="verificationForm">
                <div class="mb-6">
                    <label for="verification_input" class="block text-sm font-medium text-gray-700 mb-3">
                        Verification Code
                    </label>
                    <input type="text" 
                           name="verification_code" 
                           id="verification_input"
                           placeholder="000000"
                           required
                           maxlength="6"
                           class="form-input verification-input text-center tracking-widest">
                    <p class="text-sm text-gray-500 mt-2 text-center">
                        6-digit code sent via SMS
                    </p>
                </div>

                <?php if (isset($verification_error)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                            <p class="text-red-700 font-medium"><?= htmlspecialchars($verification_error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeModalAndClear()"
                            class="btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" 
                            id="verifyButton"
                            class="btn-success">
                        <i class="fas fa-check-circle"></i>
                        Verify & Pay
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Security Badge -->
        <div class="security-badge">
            <i class="fas fa-lock text-blue-600 text-xl"></i>
            <span class="text-blue-700 font-semibold">Secure SSL Encrypted Payment</span>
        </div>

        <!-- Amount Display -->
        <div class="text-center mb-8 fade-in-up">
            <p class="text-gray-600 text-lg mb-3">Total Amount to Pay</p>
            <div class="amount-display">
                ₱<?= number_format($payment_data['amount'], 2) ?>
            </div>
            <p class="text-gray-500">Business Tax Payment</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 fade-in-up">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <p class="text-red-700 font-medium"><?= htmlspecialchars($error_message) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 fade-in-up">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <p class="text-green-700 font-medium"><?= htmlspecialchars($success_message) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Business Details -->
        <div class="business-details fade-in-up">
            <h3 class="font-bold text-blue-800 mb-4 flex items-center gap-2">
                <i class="fas fa-building"></i>
                Business Information
            </h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-hashtag"></i>Reference</span>
                    <span class="detail-value"><?= htmlspecialchars($payment_data['application_ref']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-store"></i>Business Name</span>
                    <span class="detail-value"><?= htmlspecialchars($payment_data['business_name']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-user-tie"></i>Owner Name</span>
                    <span class="detail-value"><?= htmlspecialchars($payment_data['owner_name']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-id-card"></i>TIN ID</span>
                    <span class="detail-value"><?= htmlspecialchars($payment_data['tin_id']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-industry"></i>Business Type</span>
                    <span class="detail-value"><?= htmlspecialchars($payment_data['business_type']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-receipt"></i>Payment Type</span>
                    <span class="detail-value">
                        <?= $payment_data['payment_for'] === 'all_quarters' ? 'All Unpaid Quarters' : 'Single Quarter' ?>
                    </span>
                </div>
            </div>
            
            <!-- Tax Information -->
            <div class="tax-info">
                <h4 class="font-bold text-green-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-chart-line"></i>
                    Tax Assessment Details
                </h4>
                <div class="tax-grid">
                    <div class="tax-item">
                        <div class="tax-label">Tax Type</div>
                        <div class="tax-value"><?= htmlspecialchars($payment_data['tax_name']) ?></div>
                    </div>
                    <div class="tax-item">
                        <div class="tax-label">Tax Rate</div>
                        <div class="tax-value"><?= $payment_data['tax_rate'] ?>%</div>
                    </div>
                    <div class="tax-item">
                        <div class="tax-label">Payment For</div>
                        <div class="tax-value">
                            <?= $payment_data['payment_for'] === 'all_quarters' ? 
                                count($payment_data['payment_details']) . ' Quarters' : 
                                htmlspecialchars($payment_data['payment_details'][0]['quarter_name']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="payment-card fade-in-up">
            <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-credit-card mr-2"></i>
                Enter Your Details
            </h2>
            
            <form method="POST" id="paymentForm">
                <!-- Phone Number -->
                <div class="mb-6">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-mobile-alt mr-2 text-gray-400"></i>
                        Phone Number *
                    </label>
                    <input type="tel" 
                           name="phone_number" 
                           id="phone_number"
                           placeholder="09123456789"
                           pattern="09[0-9]{9}"
                           required
                           class="form-input"
                           value="<?= $_SESSION['phone_number'] ?? '' ?>">
                    <p class="text-sm text-gray-500 mt-2">
                        Your <?= strtoupper($payment_data['payment_method']) ?> registered mobile number
                    </p>
                </div>

                <!-- Email -->
                <div class="mb-8">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                        Email Address *
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           placeholder="your@email.com"
                           required
                           class="form-input"
                           value="<?= $_SESSION['email'] ?? '' ?>">
                    <p class="text-sm text-gray-500 mt-2">
                        For payment confirmation and digital receipt
                    </p>
                </div>

                <button type="submit" 
                        class="btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Send Verification Code
                </button>
            </form>
        </div>

        <!-- Security Notice -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mt-6 fade-in-up">
            <div class="flex items-start gap-3">
                <i class="fas fa-shield-alt text-green-500 text-xl mt-1"></i>
                <div>
                    <h3 class="font-semibold text-green-800 mb-2">Secure Payment Guarantee</h3>
                    <p class="text-sm text-green-700">
                        Your payment is secured with bank-level encryption. We never store your payment details 
                        and all transactions are protected by our secure payment gateway.
                    </p>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="text-center mt-8 fade-in-up">
            <a href="business_tax_payment_details.php?application_id=<?= $payment_data['application_id'] ?>" 
               class="btn-secondary inline-flex">
                <i class="fas fa-arrow-left"></i>
                Back to Payment Methods
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
                fetch('clear_verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'quarter_id=<?= $payment_data['quarter_id'] ?>'
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
                    countdownElement.textContent = "00:00";
                    countdownElement.style.color = "#ef4444";
                    verifyButton.disabled = true;
                    verifyButton.innerHTML = '<i class="fas fa-clock"></i> Code Expired';
                    verifyButton.classList.remove('btn-success');
                    verifyButton.style.background = '#9ca3af';
                    
                    // Auto-clear expired verification
                    fetch('clear_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'quarter_id=<?= $payment_data['quarter_id'] ?>'
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

        // Add enter key support for forms
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const activeForm = document.activeElement.closest('form');
                if (activeForm) {
                    const submitButton = activeForm.querySelector('button[type="submit"]');
                    if (submitButton && !submitButton.disabled) {
                        submitButton.click();
                    }
                }
            }
        });
    </script>

</body>
</html>