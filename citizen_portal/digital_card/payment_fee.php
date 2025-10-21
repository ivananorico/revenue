<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
    die("No payment data found. Please complete the payment form.");
}

$payment_data = $_SESSION['payment_data'];

// Database connection
require_once '../../db/Market/market_db.php';

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
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes')); // 10 minutes from now
            
            // UPDATE the existing application_fee record instead of creating new one
            $stmt = $pdo->prepare("
                UPDATE application_fee 
                SET payment_method = ?, phone_number = ?, email = ?, verification_code = ?, 
                    expires_at = ?, status = 'pending', updated_at = NOW()
                WHERE application_id = ? AND status = 'pending'
            ");
            $stmt->execute([
                $payment_data['payment_method'],
                $phone_number,
                $email,
                $verification_code,
                $expires_at,
                $payment_data['application_id']
            ]);
            
            // Store in session for verification
            $_SESSION['verification_code'] = $verification_code;
            $_SESSION['phone_number'] = $phone_number;
            $_SESSION['email'] = $email;
            $_SESSION['expires_at'] = $expires_at;
            
            $success_message = "Verification code sent! Check your phone for the code.";
            
        } catch (PDOException $e) {
            error_log("Payment error: " . $e->getMessage());
            $error_message = "Failed to process payment. Please try again.";
        }
    }
}

// Handle verification and final payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $entered_code = $_POST['verification_code'] ?? '';
    $stored_code = $_SESSION['verification_code'] ?? null;
    $expires_at = $_SESSION['expires_at'] ?? null;
    
    if (empty($entered_code)) {
        $verification_error = "Please enter the verification code.";
    } elseif (!$stored_code) {
        $verification_error = "Session expired. Please start over.";
    } elseif (strtotime($expires_at) < time()) {
        $verification_error = "Verification code has expired. Please request a new one.";
    } elseif ($entered_code === $stored_code) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Generate reference number
            $reference_number = 'REF-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // UPDATE payment record to paid
            $update_stmt = $pdo->prepare("
                UPDATE application_fee 
                SET status = 'paid', reference_number = ?, payment_date = NOW(), verified_at = NOW()
                WHERE application_id = ? AND status = 'pending'
            ");
            $update_stmt->execute([$reference_number, $payment_data['application_id']]);
            
            // UPDATE application status to 'paid'
            $app_stmt = $pdo->prepare("UPDATE applications SET status = 'paid', updated_at = NOW() WHERE id = ?");
            $app_stmt->execute([$payment_data['application_id']]);
            
            // Get application details with stall class_id
            $app_stmt = $pdo->prepare("
                SELECT a.*, s.price as stall_price, s.class_id, sr.price as stall_rights_price 
                FROM applications a 
                LEFT JOIN stalls s ON a.stall_id = s.id 
                LEFT JOIN stall_rights sr ON s.class_id = sr.class_id 
                WHERE a.id = ?
            ");
            $app_stmt->execute([$payment_data['application_id']]);
            $application = $app_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($application) {
                // Generate renter ID (R + year + sequence)
                $renter_id = 'R' . date('y') . str_pad($application['id'], 4, '0', STR_PAD_LEFT);
                
                // UPDATE stall status to occupied
                $stall_stmt = $pdo->prepare("UPDATE stalls SET status = 'occupied', updated_at = NOW() WHERE id = ?");
                $stall_stmt->execute([$application['stall_id']]);
                
                // Check if renter already exists
                $check_renter = $pdo->prepare("SELECT id FROM renters WHERE application_id = ?");
                $check_renter->execute([$application['id']]);
                
                if (!$check_renter->fetch()) {
                    // INSERT renter record
                    $renter_stmt = $pdo->prepare("
                        INSERT INTO renters 
                        (renter_id, application_id, user_id, stall_id, first_name, middle_name, last_name, 
                         contact_number, email, business_name, market_name, stall_number, section_name, 
                         class_name, monthly_rent, stall_rights_fee, security_bond, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $renter_stmt->execute([
                        $renter_id,
                        $application['id'],
                        $application['user_id'],
                        $application['stall_id'],
                        $application['first_name'],
                        $application['middle_name'],
                        $application['last_name'],
                        $application['contact_number'],
                        $application['email'],
                        $application['business_name'],
                        $application['market_name'],
                        $application['stall_number'],
                        $application['market_section'],
                        $payment_data['class_name'],
                        $application['stall_price'],
                        $payment_data['stall_rights_fee'],
                        $payment_data['security_bond']
                    ]);
                    
                    // INSERT lease contract
                    $contract_number = 'CNTR' . date('Y') . str_pad($application['id'], 4, '0', STR_PAD_LEFT);
                    $lease_stmt = $pdo->prepare("
                        INSERT INTO lease_contracts 
                        (application_id, renter_id, contract_number, start_date, end_date, monthly_rent, status)
                        VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?, 'active')
                    ");
                    $lease_stmt->execute([
                        $application['id'],
                        $renter_id,
                        $contract_number,
                        $application['stall_price']
                    ]);
                    
                    // INSERT stall rights issued
                    $certificate_number = 'SRC' . date('Y') . str_pad($application['id'], 4, '0', STR_PAD_LEFT);
                    $stall_rights_stmt = $pdo->prepare("
                        INSERT INTO stall_rights_issued 
                        (application_id, renter_id, certificate_number, class_id, issue_date, expiry_date, status)
                        VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active')
                    ");
                    $stall_rights_stmt->execute([
                        $application['id'],
                        $renter_id,
                        $certificate_number,
                        $application['class_id']
                    ]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Clear session data
            unset($_SESSION['payment_data'], $_SESSION['verification_code'], $_SESSION['phone_number'], $_SESSION['email'], $_SESSION['expires_at']);

            // Success - redirect to success page
            $_SESSION['success'] = "Payment completed successfully!";
            header('Location: payment_success.php?application_id=' . $payment_data['application_id']);
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            error_log("Payment verification error: " . $e->getMessage());
            $verification_error = "Payment failed. Please try again.";
        }
    } else {
        $verification_error = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= strtoupper($payment_data['payment_method']) ?> Payment - Market Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .payment-details {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
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
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Payment Header -->
    <div class="payment-header">
        <div class="max-w-md mx-auto">
            <div class="payment-icon">
                <i class="fas fa-<?= $payment_data['payment_method'] === 'gcash' ? 'mobile-alt' : 'wallet' ?> text-4xl"></i>
            </div>
            <h1 class="text-4xl font-bold mb-2"><?= strtoupper($payment_data['payment_method']) ?></h1>
            <p class="text-white/90 text-lg">Secure Payment Gateway</p>
        </div>
    </div>

    <!-- Verification Modal -->
    <div id="verificationModal" class="modal-overlay">
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
                            onclick="closeModal()"
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

    <div class="max-w-md mx-auto px-6 py-8">
        <!-- Security Badge -->
        <div class="security-badge fade-in-up">
            <i class="fas fa-lock text-blue-600 text-xl"></i>
            <span class="text-blue-700 font-semibold">Secure SSL Encrypted Payment</span>
        </div>

        <!-- Amount Display -->
        <div class="text-center mb-8 fade-in-up">
            <p class="text-gray-600 text-lg mb-3">Total Amount to Pay</p>
            <div class="amount-display">
                ₱<?= number_format($payment_data['total_amount'], 2) ?>
            </div>
            <p class="text-gray-500">Market Stall Application Fee</p>
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

        <!-- Payment Form -->
        <div class="payment-card fade-in-up">
            <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">Enter Your Details</h2>
            
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

        <!-- Payment Details -->
        <div class="payment-details fade-in-up">
            <h3 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">
                <i class="fas fa-receipt"></i>
                Payment Details
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-blue-700">Merchant:</span>
                    <span class="font-semibold">Municipal Market Portal</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-blue-700">Reference ID:</span>
                    <span class="font-semibold">APP-<?= $payment_data['application_id'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-blue-700">Payment Method:</span>
                    <span class="font-semibold"><?= strtoupper($payment_data['payment_method']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-blue-700">Business Name:</span>
                    <span class="font-semibold"><?= htmlspecialchars($payment_data['business_name']) ?></span>
                </div>
            </div>
        </div>

        <!-- Additional Security Info -->
        <div class="text-center mt-6 fade-in-up">
            <p class="text-sm text-gray-500 flex items-center justify-center gap-2">
                <i class="fas fa-shield-alt text-green-500"></i>
                Your payment is secured with bank-level encryption
            </p>
        </div>
    </div>

    <script>
        // Auto-format phone number
        document.getElementById('phone_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });

        // Auto-format verification code
        document.getElementById('verification_input')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        // Modal functions
        function openModal() {
            document.getElementById('verificationModal').style.display = 'flex';
            startCountdown();
            setTimeout(() => {
                document.getElementById('verification_input').focus();
            }, 100);
        }

        function closeModal() {
            document.getElementById('verificationModal').style.display = 'none';
            clearInterval(countdownInterval);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('verificationModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Countdown timer
        let countdownInterval;
        function startCountdown() {
            let timeLeft = 10 * 60; // 10 minutes in seconds
            const countdownElement = document.getElementById('countdown');
            const verifyButton = document.getElementById('verifyButton');
            
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
                    
                    // Show expired message
                    const form = document.getElementById('verificationForm');
                    const existingError = form.querySelector('.bg-red-50');
                    if (!existingError) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4 mb-4';
                        errorDiv.innerHTML = `
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                                <p class="text-red-700 font-medium">Verification code has expired. Please request a new one.</p>
                            </div>
                        `;
                        form.insertBefore(errorDiv, form.firstChild);
                    }
                }
                
                timeLeft--;
            }, 1000);
        }

        // Auto-open modal if verification code is generated
        <?php if (isset($_SESSION['verification_code'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openModal();
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