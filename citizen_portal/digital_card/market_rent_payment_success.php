<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if success data exists
if (!isset($_SESSION['rent_payment_success'])) {
    header('Location: ../market-dashboard.php');
    exit;
}

$success_data = $_SESSION['rent_payment_success'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header('Location: ../market-dashboard.php');
    exit;
}

// Database connection
require_once '../../db/Market/market_db.php';

// Get payment details from database
$payment_details = null;
try {
    $stmt = $pdo->prepare("
        SELECT mp.*, r.business_name, r.market_name, r.stall_number, r.first_name, r.last_name
        FROM monthly_payments mp
        LEFT JOIN renters r ON mp.renter_id = r.renter_id
        WHERE r.application_id = ? 
        AND mp.reference_number = ?
        ORDER BY mp.paid_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$application_id, $success_data['reference_number']]);
    $payment_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Clear success data after displaying
unset($_SESSION['rent_payment_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Market Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../navbar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }
        
        .success-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .success-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .success-icon {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255, 255, 255, 0.3);
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-15px); }
            60% { transform: translateY(-7px); }
        }
        
        .receipt-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 2.5rem;
            text-align: center;
        }
        
        .receipt-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }
        
        .amount-display {
            font-size: 3.5rem;
            font-weight: 800;
            text-align: center;
            margin: 1.5rem 0;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            padding: 2.5rem;
        }
        
        .info-section {
            display: flex;
            flex-direction: column;
        }
        
        .info-section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.75rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 600;
            text-align: right;
        }
        
        .paid-months {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 0 2.5rem 2.5rem;
        }
        
        .months-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .month-item {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid #dcfce7;
            text-align: center;
        }
        
        .month-name {
            color: #166534;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .month-amount {
            color: #059669;
            font-weight: 800;
            font-size: 1.1rem;
        }
        
        .status-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            margin: 0 2.5rem 2.5rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .next-steps {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 20px;
            padding: 2.5rem;
            margin: 2rem 0;
        }
        
        .next-steps h3 {
            color: #0369a1;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .steps-list {
            color: #0c4a6e;
            list-style: none;
            padding: 0;
            space-y: 1rem;
        }
        
        .steps-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            border-bottom: 1px solid #e0f2fe;
        }
        
        .steps-list li:last-child {
            border-bottom: none;
        }
        
        .step-number {
            background: #0369a1;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        @media (min-width: 640px) {
            .action-buttons {
                flex-direction: row;
                justify-content: center;
            }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #2c5aa0);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-align: center;
            flex: 1;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-align: center;
            flex: 1;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        
        .confetti {
            position: fixed;
            width: 12px;
            height: 12px;
            background-color: #f00;
            animation: confetti-fall 5s linear forwards;
            z-index: 9999;
        }
        
        @keyframes confetti-fall {
            0% { 
                transform: translateY(-100px) rotate(0deg) scale(1); 
                opacity: 1; 
            }
            100% { 
                transform: translateY(100vh) rotate(720deg) scale(0); 
                opacity: 0; 
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
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
        
        @media (max-width: 768px) {
            .success-header {
                padding: 3rem 1.5rem;
            }
            
            .success-icon {
                width: 100px;
                height: 100px;
            }
            
            .amount-display {
                font-size: 2.5rem;
            }
            
            .info-grid {
                padding: 2rem 1.5rem;
                grid-template-columns: 1fr;
            }
            
            .paid-months, .status-badge {
                margin: 0 1.5rem 1.5rem;
            }
            
            .next-steps {
                padding: 2rem 1.5rem;
                margin: 1.5rem 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include '../../citizen_portal/navbar.php'; ?>

    <!-- Success Header -->
    <div class="success-header">
        <div class="max-w-4xl mx-auto">
            <div class="success-icon">
                <i class="fas fa-check text-4xl text-white"></i>
            </div>
            <h1 class="text-5xl font-bold mb-4">Payment Successful!</h1>
            <p class="text-xl text-white/90 mb-6">Your monthly rent payment has been processed successfully</p>
            <div class="receipt-badge">
                <i class="fas fa-shield-check"></i>
                Payment Confirmed & Secured
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 pb-8">
        <!-- Receipt Card -->
        <div class="receipt-card fade-in-up">
            <!-- Receipt Header -->
            <div class="receipt-header">
                <h2 class="text-3xl font-bold mb-2">Payment Receipt</h2>
                <p class="text-white/90">Official confirmation of your payment</p>
            </div>
            
            <!-- Amount Display -->
            <div class="text-center py-6">
                <p class="text-gray-600 text-lg mb-2">Total Amount Paid</p>
                <div class="amount-display">
                    ₱<?= number_format($success_data['amount'], 2) ?>
                </div>
                <p class="text-gray-500">Market Stall Monthly Rent</p>
            </div>
            
            <!-- Payment Information -->
            <div class="info-grid">
                <!-- Payment Details -->
                <div class="info-section">
                    <h3>
                        <i class="fas fa-credit-card text-blue-600"></i>
                        Payment Information
                    </h3>
                    <div class="space-y-2">
                        <div class="info-item">
                            <span class="info-label">Reference Number</span>
                            <span class="info-value font-mono"><?= htmlspecialchars($success_data['reference_number']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Amount Paid</span>
                            <span class="info-value text-green-600 font-bold">₱<?= number_format($success_data['amount'], 2) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Method</span>
                            <span class="info-value"><?= strtoupper($success_data['payment_method']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Transaction Date</span>
                            <span class="info-value"><?= date('F j, Y g:i A') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Transaction ID</span>
                            <span class="info-value font-mono text-sm"><?= uniqid('TXN_') ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Renter Information -->
                <div class="info-section">
                    <h3>
                        <i class="fas fa-user-tie text-purple-600"></i>
                        Renter Information
                    </h3>
                    <div class="space-y-2">
                        <div class="info-item">
                            <span class="info-label">Business Name</span>
                            <span class="info-value"><?= htmlspecialchars($success_data['business_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Stall Location</span>
                            <span class="info-value"><?= htmlspecialchars($success_data['market_name']) ?> - <?= htmlspecialchars($success_data['stall_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Period</span>
                            <span class="info-value">
                                <?= $success_data['payment_for'] === 'all_months' ? 'All Unpaid Months' : 'Single Month (' . date('F Y', strtotime($success_data['month_year'] . '-01')) . ')' ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Renter ID</span>
                            <span class="info-value font-mono"><?= htmlspecialchars($success_data['renter_id']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Application ID</span>
                            <span class="info-value font-mono"><?= htmlspecialchars($application_id) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paid Months Section -->
            <?php if ($payment_details && $success_data['payment_for'] === 'all_months'): ?>
            <div class="paid-months fade-in-up">
                <h3 class="font-bold text-green-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-check"></i>
                    Paid Months Summary
                </h3>
                <div class="months-grid">
                    <?php 
                    // Get all paid months for this payment
                    $months_stmt = $pdo->prepare("
                        SELECT month_year, amount, late_fee 
                        FROM monthly_payments 
                        WHERE renter_id = ? AND reference_number = ?
                    ");
                    $months_stmt->execute([$success_data['renter_id'], $success_data['reference_number']]);
                    $paid_months = $months_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($paid_months as $month): 
                    ?>
                    <div class="month-item">
                        <div class="month-name"><?= date('F Y', strtotime($month['month_year'] . '-01')) ?></div>
                        <div class="month-amount">₱<?= number_format($month['amount'] + $month['late_fee'], 2) ?></div>
                        <?php if ($month['late_fee'] > 0): ?>
                        <div class="text-xs text-orange-600 mt-1">Includes late fee</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Badge -->
            <div class="status-badge fade-in-up">
                <i class="fas fa-check-circle text-xl"></i>
                <span class="text-lg">Payment Status: COMPLETED & CONFIRMED</span>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="next-steps fade-in-up">
            <h3>
                <i class="fas fa-road"></i>
                What's Next?
            </h3>
            <ul class="steps-list">
                <li>
                    <div class="step-number">1</div>
                    <div>
                        <strong>Payment Confirmation</strong>
                        <p class="text-sm mt-1">Your payment receipt has been sent to your registered email address</p>
                    </div>
                </li>
                <li>
                    <div class="step-number">2</div>
                    <div>
                        <strong>Record Keeping</strong>
                        <p class="text-sm mt-1">Keep your reference number <strong><?= htmlspecialchars($success_data['reference_number']) ?></strong> for future inquiries</p>
                    </div>
                </li>
                <li>
                    <div class="step-number">3</div>
                    <div>
                        <strong>Rental Status</strong>
                        <p class="text-sm mt-1">Your stall rental is now up to date and in good standing</p>
                    </div>
                </li>
                <li>
                    <div class="step-number">4</div>
                    <div>
                        <strong>Next Payment</strong>
                        <p class="text-sm mt-1">Your next payment due date will be displayed in your dashboard</p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons fade-in-up">
            <a href="../../citizen_portal/market_card/pay_rent/pay_rent.php" 
               class="btn-primary">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
            <button onclick="window.print()" 
                    class="btn-success">
                <i class="fas fa-print"></i>
                Print Receipt
            </button>
        </div>

        <!-- Additional Support Info -->
        <div class="text-center mt-8 fade-in-up">
            <p class="text-gray-600 text-sm">
                Need help? Contact our support team at 
                <a href="mailto:support@municipalmarket.gov" class="text-blue-600 hover:text-blue-700 font-medium">
                    support@municipalmarket.gov
                </a>
            </p>
        </div>
    </div>

    <script>
        // Create confetti effect
        function createConfetti() {
            const colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#84cc16'];
            for (let i = 0; i < 75; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.width = Math.random() * 12 + 8 + 'px';
                    confetti.style.height = Math.random() * 12 + 8 + 'px';
                    confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 50);
            }
        }

        // Start confetti when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
            
            // Add subtle animation to receipt card
            const receiptCard = document.querySelector('.receipt-card');
            if (receiptCard) {
                receiptCard.style.animationDelay = '0.2s';
            }
        });

        // Print receipt with better styling
        function printReceipt() {
            const originalContent = document.body.innerHTML;
            const receiptContent = document.querySelector('.receipt-card').outerHTML;
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payment Receipt - <?= htmlspecialchars($success_data['reference_number']) ?></title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 20px;
                            color: #333;
                        }
                        .receipt-card { 
                            border: 2px solid #059669;
                            border-radius: 15px;
                            padding: 20px;
                            max-width: 800px;
                            margin: 0 auto;
                        }
                        .amount-display {
                            font-size: 2.5em;
                            font-weight: bold;
                            color: #059669;
                            text-align: center;
                            margin: 20px 0;
                        }
                        .info-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 20px;
                            margin: 20px 0;
                        }
                        @media print {
                            body { margin: 0; padding: 15px; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${receiptContent}
                    <div class="no-print" style="text-align: center; margin-top: 20px;">
                        <button onclick="window.close()">Close</button>
                    </div>
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
        }

        // Override the print button
        document.querySelector('.btn-success').addEventListener('click', function(e) {
            e.preventDefault();
            printReceipt();
        });
    </script>

</body>
</html>