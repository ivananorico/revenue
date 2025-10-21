<?php
session_start();
require_once '../../../db/Market/market_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    $_SESSION['error'] = "No application specified.";
    header('Location: ../apply_stall.php');
    exit;
}

// Fetch application details with all related information
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
            s.name AS stall_name, 
            s.price AS stall_price,
            s.height,
            s.length, 
            s.width,
            s.status as stall_status,
            m.name AS market_name,
            sr.class_name,
            sr.price as stall_rights_price,
            sr.description as stall_rights_description,
            sec.name AS section_name
        FROM applications a
        LEFT JOIN stalls s ON a.stall_id = s.id
        LEFT JOIN maps m ON s.map_id = m.id
        LEFT JOIN stall_rights sr ON s.class_id = sr.class_id
        LEFT JOIN sections sec ON s.section_id = sec.id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        $_SESSION['error'] = "Application not found or access denied.";
        header('Location: ../apply_stall.php');
        exit;
    }

    // Get uploaded documents for this application
    $doc_stmt = $pdo->prepare("
        SELECT * FROM documents 
        WHERE application_id = ? 
        ORDER BY uploaded_at DESC
    ");
    $doc_stmt->execute([$application_id]);
    $documents = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred.";
    header('Location: ../apply_stall.php');
    exit;
}

// Function to get display name for document type
function getDocumentTypeDisplayName($documentType) {
    $displayNames = [
        'barangay_certificate' => 'Barangay Certificate',
        'id_picture' => 'ID Picture',
        'stall_rights_certificate' => 'Stall Rights Certificate',
        'business_permit' => 'Business Permit',
        'lease_contract' => 'Lease Contract'
    ];
    
    return $displayNames[$documentType] ?? ucfirst(str_replace('_', ' ', $documentType));
}

// Function to get file icon
function getFileIcon($extension) {
    $icons = [
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝'
    ];
    
    return $icons[$extension] ?? '📁';
}

// Function to get status display text
function getStatusDisplay($status) {
    $statusDisplay = [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'payment_phase' => 'Ready for Payment',
        'paid' => 'Payment Completed',
        'documents_submitted' => 'Documents Submitted',
        'expired' => 'Expired'
    ];
    return $statusDisplay[$status] ?? ucfirst($status);
}

// Function to get status description
function getStatusDescription($status) {
    $descriptions = [
        'pending' => 'Your application is currently being reviewed by our administration team.',
        'approved' => 'Your application has been approved! Please proceed to the next step.',
        'payment_phase' => 'Your application has been approved! Please complete the payment to secure your stall.',
        'paid' => 'Payment has been received. Your stall is being prepared.',
        'documents_submitted' => 'All required documents have been submitted. Final review in progress.',
        'rejected' => 'Your application was not approved. Please contact support for more information.',
        'cancelled' => 'This application has been cancelled.',
        'expired' => 'This application has expired. Please submit a new application.'
    ];
    return $descriptions[$status] ?? 'Application is being processed.';
}

// Function to get next steps based on status
function getNextSteps($status) {
    $steps = [
        'pending' => [
            'Your application is being reviewed by our administration team',
            'This process typically takes 3-5 business days',
            'You will be notified via email once your application is processed',
            'If approved, you\'ll proceed to the payment phase'
        ],
        'approved' => [
            'Your application has been approved',
            'Please wait for payment instructions',
            'You will receive an email with payment details',
            'Payment must be completed within 7 days'
        ],
        'payment_phase' => [
            'Proceed to payment to secure your stall',
            'Multiple payment options are available',
            'Payment must be completed to finalize your application',
            'After payment, you can upload remaining documents'
        ],
        'paid' => [
            'Payment has been successfully processed',
            'Your stall rights certificate is being prepared',
            'Lease contract will be generated soon',
            'You will receive notification when your stall is ready'
        ],
        'documents_submitted' => [
            'All documents have been submitted',
            'Final verification is in progress',
            'You will receive your stall assignment soon',
            'Prepare for stall setup'
        ]
    ];
    
    return $steps[$status] ?? [
        'Your application is being processed',
        'Please check back later for updates',
        'You will be notified of any changes',
        'Contact support if you have questions'
    ];
}

// Function to get stall class description
function getStallClassDescription($class_name) {
    $descriptions = [
        'A' => 'Premium Location - High traffic area with maximum visibility',
        'B' => 'Standard Location - Medium traffic area with good visibility',
        'C' => 'Economy Location - Basic location with standard traffic flow'
    ];
    return $descriptions[$class_name] ?? 'Standard stall location';
}

// Function to get file URL
function getFileUrl($filePath) {
    if (!$filePath) {
        return false;
    }
    $baseUrl = '/revenue/market_portal/';
    return $baseUrl . ltrim($filePath, '/');
}

// Calculate fees for display
$application_fee = 100.00;
$security_bond = 10000.00;
$stall_rights_fee = $application['stall_rights_price'] ?? 0;
$total_amount = ($application['stall_price'] ?? 0) + $stall_rights_fee + $application_fee + $security_bond;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Under Review - Municipal Services</title>
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
        .application-header {
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
        
        .application-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2c5aa0 0%, #4a90e2 50%, #2c5aa0 100%);
        }
        
        .application-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #2c5aa0 0%, #4a90e2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .application-id {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        /* Status Card */
        .status-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .status-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        
        .status-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .status-description {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-pending { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .status-approved { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .status-payment_phase { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .status-paid { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
        .status-documents_submitted { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .status-rejected { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        
        /* Payment Phase Section */
        .payment-section {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 12px 40px rgba(139, 92, 246, 0.3);
            color: white;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .fee-breakdown {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .fee-breakdown h3 {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .fee-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
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
            background: #f0f9ff;
            margin: 0 -2rem;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-top: 1rem;
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
        
        .btn-pay {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        
        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }
        
        .info-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.75rem;
        }
        
        /* Stall Class Badges */
        .stall-class-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stall-class-A { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .stall-class-B { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
        .stall-class-C { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        
        /* File Display */
        .file-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .file-display:hover {
            background: #f0f7ff;
            border-color: #3b82f6;
            transform: translateY(-2px);
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .file-icon {
            font-size: 1.5rem;
        }
        
        .file-details {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .file-type {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .file-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-view, .btn-download {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .btn-view {
            background: #3b82f6;
            color: white;
        }
        
        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn-download:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        /* Next Steps */
        .next-steps {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .next-steps h3 {
            color: #0369a1;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .next-steps ul {
            color: #0c4a6e;
            list-style: none;
            padding: 0;
        }
        
        .next-steps li {
            padding: 0.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .next-steps li::before {
            content: '✓';
            color: #059669;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        @media (min-width: 640px) {
            .action-buttons {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 0;
            max-width: 90vw;
            max-height: 90vh;
            width: 800px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .document-viewer {
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .document-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .document-info h4 {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .application-header {
                padding: 2rem 1.5rem;
            }
            
            .application-header h1 {
                font-size: 2rem;
            }
            
            .status-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .payment-section {
                padding: 2rem 1.5rem;
            }
            
            .fee-breakdown {
                padding: 1.5rem;
            }
            
            .info-card {
                padding: 1.5rem;
            }
            
            .file-display {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .file-actions {
                align-self: stretch;
                justify-content: space-between;
            }
            
            .btn-view, .btn-download {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../../citizen_portal/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="application-header">
            <h1>Application Status</h1>
            <p class="application-id">Application ID: #<?= $application['id'] ?></p>
        </div>

        <!-- Status Card -->
        <div class="status-card">
            <div class="status-content">
                <div class="status-info">
                    <h2>
                        <i class="fas fa-clipboard-check"></i>
                        Application Status: <?= getStatusDisplay($application['status']) ?>
                    </h2>
                    <p class="status-description"><?= getStatusDescription($application['status']) ?></p>
                </div>
                <div class="status-badge status-<?= strtolower($application['status']) ?>">
                    <?= getStatusDisplay($application['status']) ?>
                </div>
            </div>
        </div>

        <?php if ($application['status'] === 'payment_phase'): ?>
        <!-- PAYMENT PHASE SECTION -->
        <div class="payment-section">
            <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-white mb-2">Ready for Payment</h2>
                <p class="text-white/90 text-lg">Your application has been approved! Please complete the payment to proceed.</p>
            </div>
            
            <div class="fee-breakdown">
                <h3>Payment Summary</h3>
                <div class="fee-item">
                    <span class="fee-label">Monthly Stall Rent:</span>
                    <span class="fee-value">₱<?= number_format($application['stall_price'], 2) ?></span>
                </div>
                <div class="fee-item">
                    <span class="fee-label">Stall Rights Fee (Class <?= $application['class_name'] ?>):</span>
                    <span class="fee-value">₱<?= number_format($stall_rights_fee, 2) ?></span>
                </div>
                <div class="fee-item">
                    <span class="fee-label">Application Fee:</span>
                    <span class="fee-value">₱<?= number_format($application_fee, 2) ?></span>
                </div>
                <div class="fee-item">
                    <span class="fee-label">Security Bond:</span>
                    <span class="fee-value">₱<?= number_format($security_bond, 2) ?></span>
                </div>
                <div class="fee-item fee-total">
                    <span class="fee-label">Total Amount Due:</span>
                    <span class="fee-value">₱<?= number_format($total_amount, 2) ?></span>
                </div>
                
                <div class="mt-6 text-center">
                    <button onclick="location.href='../../digital_card/market_payment_details.php?application_id=<?= $application_id ?>'" 
                            class="btn-pay px-8 py-3 text-lg font-semibold">
                        <i class="fas fa-credit-card mr-2"></i>
                        Proceed to Payment
                    </button>
                    <p class="text-sm text-gray-600 mt-2">Secure payment gateway • Multiple payment options available</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stall Information Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Stall Details -->
            <div class="info-card">
                <h3><i class="fas fa-store"></i> Stall Details</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Stall Number</p>
                            <p class="font-semibold text-lg"><?= htmlspecialchars($application['stall_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Market</p>
                            <p class="font-semibold"><?= htmlspecialchars($application['market_name']) ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Section</p>
                            <p class="font-semibold"><?= htmlspecialchars($application['section_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Stall Status</p>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold 
                                <?= $application['stall_status'] === 'available' ? 'bg-green-100 text-green-800' : 
                                   ($application['stall_status'] === 'occupied' ? 'bg-blue-100 text-blue-800' : 
                                   ($application['stall_status'] === 'reserved' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) ?>">
                                <?= ucfirst($application['stall_status']) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Dimensions</p>
                        <p class="font-semibold">
                            <?= $application['length'] ?>m (Length) × <?= $application['width'] ?>m (Width) × <?= $application['height'] ?>m (Height)
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Total Area: <?= number_format($application['length'] * $application['width'], 2) ?> sqm</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Monthly Rent</p>
                        <p class="font-semibold text-2xl text-green-600">₱<?= number_format($application['stall_price'], 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Stall Rights Information -->
            <div class="info-card">
                <h3><i class="fas fa-file-contract"></i> Stall Rights</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Stall Class</p>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="stall-class-badge stall-class-<?= $application['class_name'] ?>">
                                Class <?= $application['class_name'] ?>
                            </span>
                            <span class="text-lg font-bold text-purple-600">
                                ₱<?= number_format($stall_rights_fee, 2) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Class Description</p>
                        <p class="font-medium text-gray-700 mt-1">
                            <?= getStallClassDescription($application['class_name']) ?>
                        </p>
                        <?php if (!empty($application['stall_rights_description'])): ?>
                            <p class="text-sm text-gray-600 mt-2"><?= htmlspecialchars($application['stall_rights_description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">About Stall Rights</h4>
                        <p class="text-sm text-blue-700">
                            Stall Rights Fee grants you the privilege to operate in this market location. 
                            This is a one-time fee that establishes your right to the stall space.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Information -->
        <div class="info-card mb-6">
            <h3><i class="fas fa-briefcase"></i> Business Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Business Name</p>
                        <p class="font-semibold text-lg"><?= htmlspecialchars($application['business_name']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Application Type</p>
                        <p class="font-semibold"><?= ucfirst($application['application_type']) ?></p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Application Date</p>
                        <p class="font-semibold"><?= date('F j, Y g:i A', strtotime($application['application_date'])) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Last Updated</p>
                        <p class="font-semibold"><?= date('F j, Y g:i A', strtotime($application['updated_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uploaded Documents -->
        <?php if (!empty($documents)): ?>
        <div class="info-card mb-6">
            <h3><i class="fas fa-file-alt"></i> Uploaded Documents</h3>
            <div class="space-y-3">
                <?php foreach ($documents as $doc): ?>
                <div class="file-display">
                    <div class="file-info">
                        <div class="file-icon"><?= getFileIcon($doc['file_extension']) ?></div>
                        <div class="file-details">
                            <div class="file-name"><?= htmlspecialchars($doc['file_name']) ?></div>
                            <div class="file-type">
                                <?= getDocumentTypeDisplayName($doc['document_type']) ?> • 
                                <?= strtoupper($doc['file_extension']) ?> • 
                                Uploaded: <?= date('M j, Y g:i A', strtotime($doc['uploaded_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button onclick="openDocumentModal('<?= getFileUrl($doc['file_path']) ?>', '<?= $doc['file_extension'] ?>', '<?= htmlspecialchars($doc['file_name']) ?>', '<?= getDocumentTypeDisplayName($doc['document_type']) ?>')" 
                                class="btn-view">
                            <i class="fas fa-eye mr-1"></i> View
                        </button>
                        <a href="<?= getFileUrl($doc['file_path']) ?>" download class="btn-download">
                            <i class="fas fa-download mr-1"></i> Download
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Next Steps -->
        <div class="next-steps">
            <h3><i class="fas fa-road"></i> What's Next?</h3>
            <ul>
                <?php foreach (getNextSteps($application['status']) as $step): ?>
                    <li><?= $step ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <button onclick="location.href='../market-dashboard.php'" 
                    class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </button>
            
            </div>
        </div>
    </div>

    <!-- Document Modal -->
    <div id="documentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Document Viewer</h3>
                <button class="modal-close" onclick="closeDocumentModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="document-viewer">
                    <img id="modalImage" src="" alt="Document" style="display: none;">
                    <iframe id="modalPdf" src="" width="100%" height="500px" style="display: none; border: none;"></iframe>
                    <div id="unsupportedFile" style="display: none; text-align: center; padding: 2rem;">
                        <p>This file type cannot be previewed. Please download the file to view it.</p>
                        <a id="downloadLink" href="#" download class="btn-download inline-block mt-4">
                            <i class="fas fa-download mr-2"></i>Download File
                        </a>
                    </div>
                </div>
                <div class="document-info">
                    <h4>Document Information</h4>
                    <p><strong>File Name:</strong> <span id="fileName"></span></p>
                    <p><strong>Document Type:</strong> <span id="documentType"></span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDocumentModal(fileUrl, fileExtension, fileName, documentType) {
            const modal = document.getElementById('documentModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalImage = document.getElementById('modalImage');
            const modalPdf = document.getElementById('modalPdf');
            const unsupportedFile = document.getElementById('unsupportedFile');
            const downloadLink = document.getElementById('downloadLink');
            const fileNameSpan = document.getElementById('fileName');
            const documentTypeSpan = document.getElementById('documentType');
            
            // Set modal title and file info
            modalTitle.textContent = fileName;
            fileNameSpan.textContent = fileName;
            documentTypeSpan.textContent = documentType;
            
            // Hide all viewers first
            modalImage.style.display = 'none';
            modalPdf.style.display = 'none';
            unsupportedFile.style.display = 'none';
            
            // Check file type and show appropriate viewer
            const extension = fileExtension.toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(extension)) {
                // Image file
                modalImage.src = fileUrl;
                modalImage.style.display = 'block';
                modalImage.alt = fileName;
            } else if (extension === 'pdf') {
                // PDF file
                modalPdf.src = fileUrl;
                modalPdf.style.display = 'block';
            } else {
                // Unsupported file type
                downloadLink.href = fileUrl;
                downloadLink.download = fileName;
                unsupportedFile.style.display = 'block';
            }
            
            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDocumentModal() {
            const modal = document.getElementById('documentModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Clear modal content
            document.getElementById('modalImage').src = '';
            document.getElementById('modalPdf').src = '';
        }

        // Close modal when clicking outside
        document.getElementById('documentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocumentModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDocumentModal();
            }
        });
    </script>
</body>
</html>