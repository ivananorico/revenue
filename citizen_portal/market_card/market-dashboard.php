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

// Database connection
require_once '../../db/Market/market_db.php';

// Get the latest application with status for this user
$application_id = null;
$application_status = null;
$has_application = false;

try {
    $stmt = $pdo->prepare("SELECT id, status FROM applications WHERE user_id = ? ORDER BY application_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($application) {
        $application_id = $application['id'];
        $application_status = $application['status'];
        $has_application = true;
    }
} catch (PDOException $e) {
    // Handle error silently or log it
    error_log("Database error: " . $e->getMessage());
}

// Function to get the correct view documents file based on status
function getViewDocumentsPath($status) {
    switch ($status) {
        case 'paid':
            return 'view_documents/view_paid.php';
        case 'payment_phase':
            return 'view_documents/view_payment_phase.php';
        case 'documents_submitted':
            return 'view_documents/view_documents_submitted.php';
        case 'approved':
            return 'view_documents/view_approved.php';
        case 'pending':
        default:
            return 'view_documents/view_pending.php';
    }
}

// Function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'paid':
            return 'text-green-600 bg-green-50 border-green-200';
        case 'payment_phase':
            return 'text-yellow-600 bg-yellow-50 border-yellow-200';
        case 'documents_submitted':
            return 'text-purple-600 bg-purple-50 border-purple-200';
        case 'approved':
            return 'text-blue-600 bg-blue-50 border-blue-200';
        case 'pending':
            return 'text-blue-600 bg-blue-50 border-blue-200';
        case 'rejected':
            return 'text-red-600 bg-red-50 border-red-200';
        case 'cancelled':
            return 'text-gray-600 bg-gray-50 border-gray-200';
        case 'expired':
            return 'text-orange-600 bg-orange-50 border-orange-200';
        default:
            return 'text-gray-600 bg-gray-50 border-gray-200';
    }
}

// Function to get status description
function getStatusDescription($status) {
    $descriptions = [
        'pending' => 'Your application is under review. Please check back later for updates.',
        'approved' => 'Your application has been approved! Please proceed to the next step.',
        'payment_phase' => 'Your application is ready for payment. Please proceed with the payment process.',
        'paid' => 'Payment completed! You can now view and manage your documents.',
        'documents_submitted' => 'All documents have been submitted. Final review in progress.',
        'rejected' => 'Your application was not approved. Please contact support for more information.',
        'cancelled' => 'This application has been cancelled.',
        'expired' => 'This application has expired. Please submit a new application.'
    ];
    return $descriptions[$status] ?? 'Application is being processed.';
}

// Set correct paths for navbar
$asset_path = '../';
$logout_path = '../logout.php';
$login_path = '../index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../images/SAN.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Portal - Municipal Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../navbar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .card-hover {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            border-left-color: #4a90e2;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid;
        }
        
        .icon-container {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover .icon-container {
            transform: scale(1.1);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">

    <?php include '../navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-6">
                <i class="fas fa-store text-3xl text-blue-600"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">Market Stall Portal</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Welcome back, <span class="font-semibold text-blue-600"><?= htmlspecialchars($full_name) ?></span>! 
                Manage your market stall applications and rental activities.
            </p>
        </div>

        <!-- Quick Stats -->
        <?php if ($has_application): ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12 max-w-6xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Application ID</p>
                        <p class="text-2xl font-bold text-gray-800">#<?= $application_id ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Current Status</p>
                        <p class="text-lg font-semibold <?= str_replace('text-', '', explode(' ', getStatusColor($application_status))[0]) ?>">
                            <?= ucfirst(str_replace('_', ' ', $application_status)) ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Next Action</p>
                        <p class="text-lg font-semibold text-gray-800">
                            <?= $application_status === 'pending' ? 'Wait for Review' : 
                                 ($application_status === 'approved' ? 'Submit Documents' : 
                                 ($application_status === 'payment_phase' ? 'Make Payment' : 'Check Status')) ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-forward text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Support</p>
                        <p class="text-lg font-semibold text-gray-800">24/7 Available</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-headset text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-6xl mx-auto mb-12">
            
            <!-- Apply for Stall -->
            <div class="bg-white rounded-2xl shadow-lg card-hover border border-gray-100 overflow-hidden">
                <div class="p-8 text-center">
                    <div class="icon-container w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-file-contract text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Apply for Stall</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Submit new application for market stall rental with complete requirements and documentation
                    </p>
                    <button onclick="location.href='../../market_portal/market_portal.php'" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Start New Application
                    </button>
                </div>
            </div>

            <!-- View Documents -->
            <div class="bg-white rounded-2xl shadow-lg card-hover border border-gray-100 overflow-hidden">
                <div class="p-8 text-center">
                    <div class="icon-container w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-folder-open text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">View Documents</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Access your application documents, permits, rental agreements and official certificates
                    </p>
                    
                    <?php if ($has_application): ?>
                        <?php 
                        $view_documents_path = getViewDocumentsPath($application_status);
                        $status_color = getStatusColor($application_status);
                        ?>
                        <button onclick="location.href='<?= $view_documents_path ?>?application_id=<?= $application_id ?>'" 
                                class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl mb-3">
                            <i class="fas fa-eye mr-2"></i>
                            View My Documents
                        </button>
                        <div class="status-badge <?= $status_color ?>">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            <?= ucfirst(str_replace('_', ' ', $application_status)) ?>
                        </div>
                    <?php else: ?>
                        <button onclick="alert('Please submit an application first to access documents.')" 
                                class="w-full bg-gray-400 cursor-not-allowed text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 mb-3">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            No Application Found
                        </button>
                        <p class="text-sm text-gray-500">Submit an application to access documents</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pay Rent -->
            <div class="bg-white rounded-2xl shadow-lg card-hover border border-gray-100 overflow-hidden">
                <div class="p-8 text-center">
                    <div class="icon-container w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-credit-card text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Pay Rent</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Pay your monthly stall rental fees securely and view complete payment history records
                    </p>
                    
                    <?php if ($has_application): ?>
                        <button onclick="location.href='pay_rent/pay_rent.php?application_id=<?= $application_id ?>'" 
                                class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl mb-3">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Make Payment
                        </button>
                        <p class="text-sm text-green-600 font-medium">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Secure Payment Gateway
                        </p>
                    <?php else: ?>
                        <button onclick="alert('Please submit an application first to make payments.')" 
                                class="w-full bg-gray-400 cursor-not-allowed text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 mb-3">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            No Application Found
                        </button>
                        <p class="text-sm text-gray-500">Submit an application to make payments</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Application Status Section -->
        <?php if ($has_application): ?>
        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Application Status Details</h3>
                <div class="status-badge <?= $status_color ?> text-sm">
                    <i class="fas fa-info-circle mr-2"></i>
                    Application #<?= $application_id ?>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-blue-800 mb-2">Current Status Information</h4>
                        <p class="text-blue-700 mb-3"><?= getStatusDescription($application_status) ?></p>
                        <div class="flex items-center text-sm text-blue-600">
                            <i class="fas fa-clock mr-2"></i>
                            <span>Last updated: Recently</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Footer -->
        <div class="text-center">
            <button onclick="location.href='../dashboard.php'" 
                    class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-200 inline-flex items-center space-x-3 shadow-lg hover:shadow-xl">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Main Dashboard</span>
            </button>
            
            <div class="mt-6 text-gray-500 text-sm">
                <p>Need help? Contact Municipal Support at 
                    <a href="tel:+1234567890" class="text-blue-600 hover:text-blue-700 font-medium">
                        <i class="fas fa-phone mr-1"></i>(123) 456-7890
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            
            cards.forEach((card, index) => {
                // Add staggered animation
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>

</body>
</html>