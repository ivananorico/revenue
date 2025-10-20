<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if success data exists
if (!isset($_SESSION['tax_payment_success'])) {
    header('Location: ../rpt_dashboard.php');
    exit;
}

$success_data = $_SESSION['tax_payment_success'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header('Location: ../rpt_dashboard.php');
    exit;
}

// Database connection
require_once '../../db/RPT/rpt_db.php'; 

// Get payment details from database
$payment_details = null;
$paid_quarters = [];
try {
    $stmt = $pdo->prepare("
        SELECT q.*, ra.property_address, l.tdn_no, l.location, l.barangay, l.municipality
        FROM quarterly q
        LEFT JOIN land_assessment_tax lat ON q.land_tax_id = lat.land_tax_id
        LEFT JOIN land l ON lat.land_id = l.land_id
        LEFT JOIN rpt_applications ra ON l.application_id = ra.id
        WHERE q.or_no = ? AND ra.id = ?
        ORDER BY q.quarter_no ASC
    ");
    $stmt->execute([$success_data['or_number'], $application_id]);
    $paid_quarters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($paid_quarters)) {
        $payment_details = $paid_quarters[0];
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Clear success data after displaying
unset($_SESSION['tax_payment_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - RPT System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../navbar.css">
    <style>
        .success-animation {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .receipt-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            color: white;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f00;
            animation: confetti-fall 5s linear forwards;
            pointer-events: none;
            z-index: 1000;
        }
        
        @keyframes confetti-fall {
            0% { 
                transform: translateY(-100px) rotate(0deg); 
                opacity: 1; 
            }
            100% { 
                transform: translateY(100vh) rotate(360deg); 
                opacity: 0; 
            }
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .receipt-card {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .confetti {
                display: none !important;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .quarter-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin: 0.25rem;
            display: inline-block;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

       <?php include '../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Success Header -->
        <div class="text-center mb-8 fade-in">
            <div class="success-animation mb-4">
                <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Tax Payment Successful!</h1>
            <p class="text-gray-600 text-lg">Your property tax payment has been processed successfully</p>
        </div>

        <!-- E-wallet Style Receipt -->
        <div class="receipt-card p-6 mb-6 shadow-2xl">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold mb-2">TAX PAYMENT RECEIPT</h2>
                <p class="text-blue-100">Real Property Tax Payment</p>
            </div>

            <!-- Reference Number -->
            <div class="bg-white bg-opacity-20 rounded-lg p-4 mb-4 text-center">
                <p class="text-sm text-blue-100 mb-1">Official Receipt Number</p>
                <p class="text-2xl font-bold font-mono tracking-wider">
                    <?= htmlspecialchars($success_data['or_number']) ?>
                </p>
            </div>

            <!-- Payment Details -->
            <div class="space-y-3 mb-4">
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Date & Time</span>
                    <span class="font-semibold"><?= date('M j, Y g:i A') ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Transaction ID</span>
                    <span class="font-semibold"><?= htmlspecialchars($success_data['transaction_id']) ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Application ID</span>
                    <span class="font-semibold">#<?= htmlspecialchars($application_id) ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Payment Type</span>
                    <span class="font-semibold text-right">
                        <?= $success_data['payment_for'] === 'all_quarters' ? 'All Unpaid Quarters' : 'Single Quarter' ?>
                    </span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Payment Method</span>
                    <span class="font-semibold"><?= strtoupper($success_data['payment_method']) ?></span>
                </div>
            </div>

            <!-- Property Information -->
            <div class="border-t border-blue-300 pt-4 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-blue-100">Property Address</span>
                    <span class="font-semibold text-right"><?= htmlspecialchars($success_data['property_address']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Tax Declaration No.</span>
                    <span class="font-semibold"><?= htmlspecialchars($success_data['land_tdn']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-blue-100">Assessment Year</span>
                    <span class="font-semibold"><?= htmlspecialchars($success_data['assessment_year']) ?></span>
                </div>
            </div>

            <!-- Paid Quarters Section -->
            <?php if (!empty($paid_quarters)): ?>
            <div class="border-t border-blue-300 pt-4 mb-4">
                <p class="text-blue-100 text-sm mb-2">Paid Quarters:</p>
                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                    <div class="flex flex-wrap justify-center gap-2">
                        <?php 
                        $quarter_labels = ['1' => 'Q1', '2' => 'Q2', '3' => 'Q3', '4' => 'Q4'];
                        foreach ($paid_quarters as $quarter): 
                        ?>
                        <div class="quarter-badge">
                            <?= $quarter_labels[$quarter['quarter_no']] ?? 'Q' . $quarter['quarter_no'] ?>
                            - ₱<?= number_format($quarter['tax_amount'] + $quarter['penalty'], 2) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Amount -->
            <div class="border-t border-blue-300 mt-4 pt-4 text-center">
                <p class="text-blue-100 text-sm">Total Amount Paid</p>
                <p class="text-3xl font-bold">₱<?= number_format($success_data['amount'], 2) ?></p>
            </div>

            <!-- Status -->
            <div class="text-center mt-4">
                <span class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-semibold shadow-lg">
                    ✅ PAYMENT COMPLETED
                </span>
            </div>
        </div>

        <!-- Next Steps Information -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6 fade-in">
            <h3 class="font-semibold text-blue-800 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                What's Next?
            </h3>
            <ul class="text-sm text-blue-700 space-y-2 list-disc list-inside">
                <li>Your payment receipt has been recorded in our system</li>
                <li><strong>Save your OR number:</strong> <?= htmlspecialchars($success_data['or_number']) ?></li>
                <li>Your property tax is now up to date</li>
                <li>Next payment due date will be shown in your dashboard</li>
                <li>You will receive payment confirmation via email</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3 no-print">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button onclick="window.print()" 
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center shadow-lg">
                    <span class="mr-2">🖨️</span>
                    Print Receipt
                </button>
                
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="../../citizen_portal/rpt_card/rpt_dashboard.php" 
                   class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center shadow-lg text-center">
                    <span class="mr-2">🏠</span>
                    Back to Dashboard
                </a>
                

            </div>
        </div>

        <!-- Support Info -->
        <div class="text-center mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg no-print fade-in">
            <p class="text-yellow-700 text-sm flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <strong>Important:</strong> Keep your OR number for future reference
            </p>
            <p class="text-yellow-600 text-xs mt-1">
                For payment inquiries: assessor@lgu.gov.ph | Phone: (02) 1234-5678
            </p>
        </div>
    </div>

    <script>
        // Create confetti effect
        function createConfetti() {
            const colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#84cc16'];
            const confettiCount = 100;
            
            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.width = Math.random() * 12 + 8 + 'px';
                    confetti.style.height = Math.random() * 12 + 8 + 'px';
                    confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0%';
                    confetti.style.opacity = Math.random() * 0.7 + 0.3;
                    document.body.appendChild(confetti);
                    
                    // Remove confetti after animation
                    setTimeout(() => {
                        if (confetti.parentNode) {
                            confetti.parentNode.removeChild(confetti);
                        }
                    }, 5000);
                }, i * 30);
            }
        }

        // Add receipt animation
        function animateReceipt() {
            const receipt = document.querySelector('.receipt-card');
            if (receipt) {
                receipt.style.transform = 'scale(0.9)';
                receipt.style.opacity = '0';
                
                setTimeout(() => {
                    receipt.style.transition = 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
                    receipt.style.transform = 'scale(1)';
                    receipt.style.opacity = '1';
                }, 200);
            }
        }

        // Start animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
            animateReceipt();
            
            // Add success sound (optional)
            try {
                const audio = new Audio('success-sound.mp3');
                audio.volume = 0.3;
                // audio.play().catch(e => console.log('Audio play failed:', e));
            } catch (e) {
                console.log('Audio not available');
            }
        });

        // Enhanced print functionality
        function enhancePrint() {
            window.print();
        }

        // Add keyboard shortcut for print (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                enhancePrint();
            }
        });
    </script>

</body>
</html>