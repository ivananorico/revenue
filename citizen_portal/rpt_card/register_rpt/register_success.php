<?php
// register_success.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../../db/RPT/rpt_db.php';

// Initialize application variable
$application = null;
$error_message = '';

// Get application ID from URL
$application_id = $_GET['application_id'] ?? 0;

// Fetch application details
if ($application_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rpt_applications WHERE id = ? AND user_id = ?");
        $stmt->execute([$application_id, $_SESSION['user_id']]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            $error_message = "Application not found or you don't have permission to view it.";
        }
    } catch (PDOException $e) {
        error_log("Error fetching application: " . $e->getMessage());
        $error_message = "An error occurred while retrieving application details.";
    }
} else {
    $error_message = "No application ID provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - LGU Real Property Tax</title>
    <link rel="stylesheet" href="../../citizen_portal/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.5;
        }

        .rpt-register-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Official Header */
        .official-header {
            background: #1a365d;
            color: white;
            padding: 25px;
            text-align: center;
            border-bottom: 5px solid #e53e3e;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .lgu-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .lgu-logo i {
            font-size: 2.5rem;
            color: #e53e3e;
        }

        .lgu-title {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .lgu-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        /* Success Card */
        .success-card {
            background: white;
            border: 2px solid #2d3748;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .success-header {
            background: #2d3748;
            color: white;
            padding: 20px;
            border-bottom: 2px solid #e53e3e;
        }

        .success-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-weight: bold;
        }

        .success-title i {
            color: #48bb78;
        }

        .success-subtitle {
            margin-top: 8px;
            font-size: 1rem;
            opacity: 0.9;
        }

        .success-content {
            padding: 25px;
        }

        /* Application Details */
        .application-details {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e53e3e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 140px;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 500;
            text-align: right;
            flex: 1;
        }

        .application-id {
            background: #ebf8ff;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            color: #2b6cb0;
        }

        .status-badge {
            background: #fffaf0;
            color: #dd6b20;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid #fed7aa;
        }

        /* Process Timeline */
        .process-timeline {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .timeline-title {
            color: #276749;
            margin-bottom: 15px;
        }

        .timeline-steps {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .timeline-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #48bb78;
        }

        .step-number {
            background: #48bb78;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            flex-shrink: 0;
        }

        .step-content h4 {
            color: #2d3748;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .step-content p {
            color: #718096;
            font-size: 0.9rem;
        }

        /* Contact Information */
        .contact-section {
            background: #fffaf0;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .contact-title {
            color: #dd6b20;
            margin-bottom: 15px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
        }

        .contact-item i {
            color: #dd6b20;
            width: 20px;
            text-align: center;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            padding: 20px 0;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 12px 24px;
            border: 2px solid;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            min-width: 180px;
            justify-content: center;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: #2d3748;
            color: white;
            border-color: #2d3748;
        }

        .btn-primary:hover {
            background: #4a5568;
            border-color: #4a5568;
        }

        .btn-secondary {
            background: white;
            color: #4a5568;
            border-color: #cbd5e0;
        }

        .btn-secondary:hover {
            border-color: #4a5568;
            color: #2d3748;
        }

        /* Error State */
        .error-card {
            background: white;
            border: 2px solid #e53e3e;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .error-icon {
            font-size: 3rem;
            color: #e53e3e;
            margin-bottom: 15px;
        }

        .error-title {
            color: #2d3748;
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .error-message {
            color: #718096;
            margin-bottom: 20px;
        }

        /* Footer */
        .official-footer {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-size: 0.9rem;
            border-top: 1px solid #e2e8f0;
            margin-top: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .rpt-register-container {
                padding: 15px;
            }

            .lgu-title {
                font-size: 1.4rem;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .detail-value {
                text-align: left;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Print Styles */
        @media print {
            .action-buttons {
                display: none;
            }
            
            .success-header {
                background: #2d3748 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <?php include '../../../citizen_portal/navbar.php'; ?>
    
    <div class="rpt-register-container">
        <!-- Official LGU Header -->
        <div class="official-header">
            <div class="lgu-logo">
                <i class="fas fa-landmark"></i>
                <div>
                    <div class="lgu-title">LOCAL GOVERNMENT UNIT</div>
                    <div class="lgu-subtitle">Real Property Tax Management System</div>
                </div>
            </div>
        </div>

        <?php if ($application): ?>
            <!-- Success State -->
            <div class="success-card">
                <div class="success-header">
                    <div class="success-title">
                        <i class="fas fa-check-circle"></i>
                        APPLICATION SUBMITTED SUCCESSFULLY
                    </div>
                    <div class="success-subtitle">
                        Your Real Property Tax registration has been received and is now being processed.
                    </div>
                </div>

                <div class="success-content">
                    <!-- Application Details -->
                    <div class="application-details">
                        <div class="section-title">
                            <i class="fas fa-file-contract"></i>
                            APPLICATION INFORMATION
                        </div>
                        <div class="details-grid">
                            <div class="detail-row">
                                <span class="detail-label">Application ID:</span>
                                <span class="detail-value application-id">RPT-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Current Status:</span>
                                <span class="detail-value status-badge">Pending Assessment</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Property Address:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($application['property_address']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Property Type:</span>
                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $application['property_type'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date Submitted:</span>
                                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($application['application_date'])); ?></span>
                            </div>
                            <?php if ($application['application_type'] === 'transfer' && !empty($application['previous_owner'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Previous Owner:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($application['previous_owner']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Process Timeline -->
                    <div class="process-timeline">
                        <div class="section-title timeline-title">
                            <i class="fas fa-list-ol"></i>
                            NEXT STEPS IN THE PROCESS
                        </div>
                        <div class="timeline-steps">
                            <div class="timeline-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Document Verification</h4>
                                    <p>Review of submitted documents (3-5 working days)</p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Property Assessment</h4>
                                    <p>Schedule and conduct property inspection</p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Tax Computation</h4>
                                    <p>Calculate assessed value and tax due</p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Issuance of Tax Declaration</h4>
                                    <p>Receive official tax declaration certificate</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="contact-section">
                        <div class="section-title contact-title">
                            <i class="fas fa-info-circle"></i>
                            FOR INQUIRIES AND ASSISTANCE
                        </div>
                        <div class="contact-grid">
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>(02) 1234-5678</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>rpt@lgu.gov.ph</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock"></i>
                                <span>Monday - Friday, 8AM - 5PM</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Treasurer's Office, Municipal Hall</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='../rpt_dashboard.php'">
                            <i class="fas fa-tachometer-alt"></i> My Dashboard
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.location.href='register_rpt.php'">
                            <i class="fas fa-plus"></i> New Application
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Error State -->
            <div class="error-card">
                <i class="fas fa-exclamation-triangle error-icon"></i>
                <h2 class="error-title">Application Not Found</h2>
                <p class="error-message"><?php echo $error_message ?: 'The requested application could not be located in our system.'; ?></p>
                
                <div class="contact-section" style="margin-top: 20px;">
                    <div class="section-title contact-title">
                        <i class="fas fa-life-ring"></i>
                        NEED ASSISTANCE?
                    </div>
                    <p style="margin-bottom: 15px; color: #718096;">If you believe this is an error, please contact our support team:</p>
                    <div class="contact-grid">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>(02) 1234-5678</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@lgu.gov.ph</span>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='../rpt_dashboard.php'">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='register_rpt.php'">
                        <i class="fas fa-arrow-left"></i> Back to Registration
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Official Footer -->
        <div class="official-footer">
            <p>© <?php echo date('Y'); ?> Local Government Unit - Real Property Tax Management System</p>
            <p>This is an official document. Please keep this reference for your records.</p>
        </div>
    </div>

    <script>
        // Simple print confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const printButton = document.querySelector('button[onclick="window.print()"]');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    setTimeout(() => {
                        alert('Document sent to printer. Please keep a copy for your records.');
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>