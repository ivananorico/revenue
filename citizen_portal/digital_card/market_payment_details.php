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

$full_name = $_SESSION['full_name'] ?? 'Guest';
$user_id = $_SESSION['user_id'];

// Get application_id from URL parameter
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : null;

if (!$application_id) {
    $_SESSION['error'] = "No application specified.";
    header('Location: market-dashboard.php');
    exit;
}

// Database connection
require_once '../../db/Market/market_db.php';

$application = null;
$stall_rights_fee = 0;

try {
    // Get application details with fees
    $stmt = $pdo->prepare("
        SELECT 
            a.*, 
            s.name as stall_name, 
            s.price as stall_price,
            m.name as market_name,
            sr.class_name,
            sr.price as stall_rights_price
        FROM applications a 
        LEFT JOIN stalls s ON a.stall_id = s.id 
        LEFT JOIN maps m ON s.map_id = m.id 
        LEFT JOIN stall_rights sr ON s.class_id = sr.class_id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $_SESSION['error'] = "Application not found or access denied.";
        header('Location: market-dashboard.php');
        exit;
    }

    // Get stall rights fee
    $stall_rights_fee = $application['stall_rights_price'] ?? 0;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred.";
    header('Location: market-dashboard.php');
    exit;
}

// Calculate fees
$application_fee = 100.00;
$security_bond = 10000.00;
$total_amount = ($application['stall_price'] ?? 0) + $stall_rights_fee + $application_fee + $security_bond;

// Handle form submission - Redirect to confirmation page with data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Validate payment method
    if (!in_array($payment_method, ['maya', 'gcash'])) {
        $error_message = "Please select a valid payment method.";
    } else {
        try {
            // Clear any existing verification codes from database before proceeding
            $clear_stmt = $pdo->prepare("
                UPDATE application_fee 
                SET verification_code = NULL, expires_at = NULL, verification_attempts = 0
                WHERE application_id = ? AND status = 'pending'
            ");
            $clear_stmt->execute([$application_id]);
            
            // Clear any verification session data
            unset($_SESSION['verification_code'], $_SESSION['phone_number'], $_SESSION['email'], $_SESSION['expires_at']);
            
            // Store payment data in session and redirect to confirmation page
            $_SESSION['payment_data'] = [
                'application_id' => $application_id,
                'payment_method' => $payment_method,
                'application_fee' => $application_fee,
                'security_bond' => $security_bond,
                'stall_rights_fee' => $stall_rights_fee,
                'total_amount' => $total_amount,
                'stall_price' => $application['stall_price'] ?? 0,
                'business_name' => $application['business_name'],
                'market_name' => $application['market_name'],
                'stall_number' => $application['stall_number'],
                'class_name' => $application['class_name']
            ];
            
            // Redirect to confirmation page
            header('Location: payment_fee.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Clear verification error: " . $e->getMessage());
            $error_message = "Failed to process payment. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details - Market Portal</title>
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
        
        /* Application Summary */
        .application-summary {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
        }
        
        .summary-label {
            color: #0369a1;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .summary-value {
            color: #0c4a6e;
            font-weight: 700;
            font-size: 1rem;
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
            
            .payment-method {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include '../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="payment-header">
            <h1>Complete Your Payment</h1>
            <p>Secure payment for your market stall application</p>
        </div>

        <div class="max-w-6xl mx-auto">
            <!-- Application Summary -->
            <div class="payment-card mb-8">
                <h2><i class="fas fa-receipt"></i> Application Summary</h2>
                <div class="application-summary">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-label">Application ID</span>
                            <span class="summary-value">#<?= $application['id'] ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Business Name</span>
                            <span class="summary-value"><?= htmlspecialchars($application['business_name']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Stall Location</span>
                            <span class="summary-value"><?= htmlspecialchars($application['market_name']) ?> - <?= htmlspecialchars($application['stall_number']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Stall Class</span>
                            <span class="summary-value">Class <?= $application['class_name'] ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Applicant Name</span>
                            <span class="summary-value"><?= htmlspecialchars($full_name) ?></span>
                        </div>
                    </div>
                </div>
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
                        <div class="fee-item">
                            <span class="fee-label">Monthly Stall Rent</span>
                            <span class="fee-value">₱<?= number_format($application['stall_price'], 2) ?></span>
                        </div>
                        
                        <div class="fee-item">
                            <span class="fee-label">Stall Rights Fee (Class <?= $application['class_name'] ?>)</span>
                            <span class="fee-value">₱<?= number_format($stall_rights_fee, 2) ?></span>
                        </div>
                        
                        <div class="fee-item">
                            <span class="fee-label">Application Fee</span>
                            <span class="fee-value">₱<?= number_format($application_fee, 2) ?></span>
                        </div>
                        
                        <div class="fee-item">
                            <span class="fee-label">Security Bond</span>
                            <span class="fee-value">₱<?= number_format($security_bond, 2) ?></span>
                        </div>
                        
                        <div class="fee-item fee-total">
                            <span class="fee-label">Total Amount Due</span>
                            <span class="fee-value">₱<?= number_format($total_amount, 2) ?></span>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="info-box instructions-box">
                        <h3><i class="fas fa-info-circle"></i> How to Pay</h3>
                        <ol class="text-sm space-y-2">
                            <li>Select your preferred payment method (Maya or GCash)</li>
                            <li>Click "Continue to Payment" to proceed</li>
                            <li>Complete the payment process on the next page</li>
                            <li>Your stall will be automatically reserved upon successful payment</li>
                            <li>You will receive a confirmation email and receipt</li>
                        </ol>
                    </div>

                    <!-- Important Notes -->
                    <div class="info-box notes-box">
                        <h3><i class="fas fa-exclamation-triangle"></i> Important Notes</h3>
                        <ul class="text-sm space-y-2">
                            <li>Payment must be completed within 24 hours to secure your stall</li>
                            <li>Security bond is refundable upon contract termination</li>
                            <li>Contact municipal support if you encounter any payment issues</li>
                            <li>All payments are secured and encrypted</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="text-center mt-8">
                <a href="../market_card/view_documents/view_pending.php?application_id=<?= $application_id ?>" 
                   class="btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Application Details
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