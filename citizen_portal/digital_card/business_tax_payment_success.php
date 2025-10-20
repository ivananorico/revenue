<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if success data exists in session
if (!isset($_SESSION['business_payment_success'])) {
    $_SESSION['error'] = "No payment success data found.";
    header('Location: business_dashboard.php');
    exit;
}

$success_data = $_SESSION['business_payment_success'];
$full_name = $_SESSION['full_name'] ?? 'Guest';

// Clear success data from session after displaying
unset($_SESSION['business_payment_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Business Tax</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../navbar.css">
    <style>
        .success-animation {
            animation: successPulse 2s ease-in-out;
        }
        
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f0f;
            opacity: 0.7;
            animation: confettiFall 5s linear forwards;
        }
        
        @keyframes confettiFall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .receipt-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .payment-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .amount-highlight {
            background: linear-gradient(135deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include '../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-6 py-8">
        <!-- Success Header -->
        <div class="text-center mb-12">
            <div class="success-animation inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mb-6">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Payment Successful!</h1>
            <p class="text-xl text-gray-600">Your business tax payment has been processed successfully</p>
            <div class="mt-4">
                <span class="payment-badge"><?= strtoupper($success_data['payment_method']) ?> • PAID</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            <!-- Receipt Card -->
            <div class="receipt-card p-8 mb-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Official Receipt</h2>
                    <p class="text-gray-600">Business Tax Payment Confirmation</p>
                </div>

                <!-- Payment Details -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Left Column - Transaction Details -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Details</h3>
                        <div class="space-y-3">
                            <div class="detail-row">
                                <span class="text-gray-600">Transaction ID:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['transaction_id']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">OR Number:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['or_number']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="font-semibold text-gray-800"><?= strtoupper($success_data['payment_method']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">Payment Date:</span>
                                <span class="font-semibold text-gray-800"><?= date('F j, Y') ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">Payment Time:</span>
                                <span class="font-semibold text-gray-800"><?= date('g:i A') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Business Details -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Information</h3>
                        <div class="space-y-3">
                            <div class="detail-row">
                                <span class="text-gray-600">Application Ref:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['application_ref']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">Business Name:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['business_name']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">Business Type:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['business_type']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">Owner Name:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['owner_name']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="text-gray-600">TIN:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['tin_id']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax Breakdown -->
                <div class="border-t border-gray-200 pt-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tax Breakdown</h3>
                    <div class="space-y-3">
                        <div class="detail-row">
                            <span class="text-gray-600">Tax Type:</span>
                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['tax_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-600">Tax Rate:</span>
                            <span class="font-semibold text-gray-800"><?= $success_data['tax_rate'] ?>%</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-gray-600">Payment Type:</span>
                            <span class="font-semibold text-gray-800">
                                <?= $success_data['payment_for'] === 'all_quarters' ? 'All Unpaid Quarters' : 'Single Quarter' ?>
                            </span>
                        </div>
                        <?php if ($success_data['payment_for'] === 'single_quarter' && !empty($success_data['payment_details'])): ?>
                        <div class="detail-row">
                            <span class="text-gray-600">Quarter:</span>
                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($success_data['payment_details'][0]['quarter_name']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Amount Summary -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
                    <div class="text-center">
                        <p class="text-gray-600 text-sm mb-2">Total Amount Paid</p>
                        <div class="amount-highlight text-4xl font-bold mb-2">
                            ₱<?= number_format($success_data['amount'], 2) ?>
                        </div>
                        <p class="text-green-700 text-sm">Payment successfully processed via <?= strtoupper($success_data['payment_method']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Next Steps & Important Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Next Steps -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">What's Next?</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <span class="text-blue-600 font-semibold text-sm">1</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Download Receipt</h4>
                                <p class="text-gray-600 text-sm mt-1">Save or print this receipt for your records.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <span class="text-blue-600 font-semibold text-sm">2</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Tax Certificate</h4>
                                <p class="text-gray-600 text-sm mt-1">Your updated tax certificate will be available in your dashboard within 24 hours.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <span class="text-blue-600 font-semibold text-sm">3</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Business Permit</h4>
                                <p class="text-gray-600 text-sm mt-1">Your business permit will be automatically updated with the payment status.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Important Information</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Keep This Receipt</h4>
                                <p class="text-gray-600 text-sm mt-1">This serves as your official proof of payment for business tax.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Tax Deduction</h4>
                                <p class="text-gray-600 text-sm mt-1">This payment is tax-deductible. Keep records for accounting purposes.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Next Payment</h4>
                                <p class="text-gray-600 text-sm mt-1">Monitor your dashboard for upcoming tax payments and deadlines.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center space-y-4 md:space-y-0 md:space-x-4 md:flex md:justify-center">
                <button onclick="printReceipt()" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Receipt
                </button>
                
                <button onclick="downloadPDF()" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download PDF
                </button>
                
                <a href="../business_dashboard.php" class="w-full md:w-auto bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <!-- Support Information -->
            <div class="text-center mt-12">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 inline-block">
                    <h4 class="font-semibold text-blue-800 mb-2">Need Help?</h4>
                    <p class="text-blue-700 text-sm">
                        Contact Business Permit Office: <span class="font-semibold">(02) 123-4567</span> | 
                        Email: <span class="font-semibold">business@localgov.ph</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Print receipt function
        function printReceipt() {
            const receiptContent = document.querySelector('.receipt-card').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Business Tax Receipt - <?= htmlspecialchars($success_data['or_number']) ?></title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px; 
                            color: #333;
                        }
                        .receipt { 
                            border: 2px solid #333; 
                            padding: 20px; 
                            max-width: 600px; 
                            margin: 0 auto;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 20px; 
                            border-bottom: 2px solid #333; 
                            padding-bottom: 10px;
                        }
                        .detail-row { 
                            display: flex; 
                            justify-content: space-between; 
                            margin: 8px 0; 
                            padding: 4px 0;
                            border-bottom: 1px solid #eee;
                        }
                        .amount { 
                            font-size: 24px; 
                            font-weight: bold; 
                            text-align: center; 
                            margin: 20px 0;
                            color: #059669;
                        }
                        .footer { 
                            text-align: center; 
                            margin-top: 20px; 
                            font-size: 12px; 
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; }
                            .receipt { border: none; box-shadow: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <h1>OFFICIAL RECEIPT</h1>
                            <h2>Business Tax Payment</h2>
                            <p>Date: <?= date('F j, Y') ?> | Time: <?= date('g:i A') ?></p>
                        </div>
                        ${receiptContent}
                        <div class="footer">
                            <p>This is an computer-generated receipt. No signature required.</p>
                            <p>Transaction ID: <?= htmlspecialchars($success_data['transaction_id']) ?></p>
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Download PDF function (simulated)
        function downloadPDF() {
            alert('PDF download feature would be implemented here with a proper PDF generation library.');
            // In a real implementation, you would use a PDF library like jsPDF or make an API call
        }

        // Create confetti effect
        function createConfetti() {
            const colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.width = Math.random() * 10 + 5 + 'px';
                    confetti.style.height = Math.random() * 10 + 5 + 'px';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 100);
            }
        }

        // Initialize confetti on page load
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
        });
    </script>

</body>
</html>