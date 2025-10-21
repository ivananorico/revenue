<?php
session_start();
require_once '../../db/RPT/rpt_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Guest';

// Count applications by status
$status_counts = [];
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM rpt_applications 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$user_id]);
$status_counts_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($status_counts_result as $row) {
    $status_counts[$row['status']] = $row['count'];
}

// Get approved applications for the Pay Tax button
$approved_applications = [];
$stmt = $pdo->prepare("
    SELECT ra.id, ra.property_address, l.tdn_no, l.land_id
    FROM rpt_applications ra
    LEFT JOIN land l ON ra.id = l.application_id
    WHERE ra.user_id = ? AND ra.status = 'approved'
    ORDER BY ra.application_date DESC
");
$stmt->execute([$user_id]);
$approved_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get applications with assessment schedules for notifications
$notifications = [];
$stmt = $pdo->prepare("
    SELECT 
        ra.id,
        ra.property_address,
        ra.status,
        ras.visit_date,
        ras.assessor_name,
        ras.status as schedule_status
    FROM rpt_applications ra
    LEFT JOIN rpt_assessment_schedule ras ON ra.id = ras.application_id
    WHERE ra.user_id = ? 
    AND ra.status = 'for_assessment'
    AND ras.visit_date IS NOT NULL
    AND ras.status = 'scheduled'
    ORDER BY ras.visit_date ASC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user has any approved applications
$has_approved_app = count($approved_applications) > 0;

// FIXED: Always set primary_application_id if there are approved applications
$primary_application_id = null;
if ($has_approved_app) {
    $primary_application_id = $approved_applications[0]['id'];
}

// Calculate total applications
$total_applications = array_sum($status_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Property Tax System - Municipal Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../citizen_portal/navbar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        
        .rpt-dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .rpt-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .rpt-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2c5aa0 0%, #4a90e2 50%, #2c5aa0 100%);
        }
        
        .rpt-header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #2c5aa0 0%, #4a90e2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        .rpt-header p {
            font-size: 1.3rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        .welcome-user {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f0f7ff;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            color: #2c5aa0;
        }
        
        .welcome-user i {
            color: #4a90e2;
        }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2c5aa0;
            display: block;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Notifications */
        .notifications-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        
        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .notification-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-header h3 i {
            color: #f59e0b;
        }
        
        .notification-count {
            background: #ef4444;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: #fef7ed;
            border-radius: 12px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            background: #fef3e2;
            transform: translateX(5px);
        }
        
        .notification-icon {
            width: 48px;
            height: 48px;
            background: #f59e0b;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .notification-content h4 {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .notification-content p {
            color: #6b7280;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        
        .notification-date {
            color: #f59e0b;
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Main Cards Grid */
        .rpt-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .rpt-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .rpt-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .rpt-card::before {
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
        
        .rpt-card:hover::before {
            transform: scaleX(1);
        }
        
        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4a90e2, #2c5aa0);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 2rem;
        }
        
        .card-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .card-content p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .card-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #4a90e2, #2c5aa0);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }
        
        .card-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.3);
        }
        
        .card-button.disabled {
            background: #9ca3af;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .card-button.disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        /* Property Selection Styles */
        .property-select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .property-select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .property-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .property-info p {
            margin: 0.5rem 0;
            font-size: 0.9rem;
            color: #0369a1;
        }
        
        .property-info strong {
            color: #0c4a6e;
        }
        
        /* Status Progress */
        .status-progress {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }
        
        .status-progress h2 {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2rem;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 50px;
            right: 50px;
            height: 4px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .step-circle.active {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .step-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-align: center;
        }
        
        .step-count {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }
        
        /* Footer */
        .rpt-footer {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }
        
        .rpt-footer p {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a90e2;
            font-weight: 500;
        }
        
        /* Back Button */
        .back-to-dashboard {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6b7280;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .rpt-dashboard-container {
                padding: 1rem;
            }
            
            .rpt-header h1 {
                font-size: 2rem;
            }
            
            .rpt-cards-container {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 2rem;
            }
            
            .progress-steps::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../../citizen_portal/navbar.php'; ?>
    
    <div class="rpt-dashboard-container">
        <!-- Header Section -->
        <div class="rpt-header">
            <h1>Real Property Tax System</h1>
            <p>Manage your property registrations, assessments, and tax payments</p>
            <div class="welcome-user">
                <i class="fas fa-user-circle"></i>
                <span>Welcome, <?= htmlspecialchars($full_name) ?></span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <span class="stat-number"><?= $total_applications ?></span>
                <span class="stat-label">Total Applications</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $status_counts['approved'] ?? 0 ?></span>
                <span class="stat-label">Approved Properties</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $status_counts['for_assessment'] ?? 0 ?></span>
                <span class="stat-label">Pending Assessment</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= count($notifications) ?></span>
                <span class="stat-label">Scheduled Visits</span>
            </div>
        </div>

        <!-- Notifications Section -->
        <?php if (!empty($notifications)): ?>
        <div class="notifications-section">
            <div class="notification-header">
                <h3><i class="fas fa-bell"></i> Upcoming Property Assessments</h3>
                <span class="notification-count"><?= count($notifications) ?></span>
            </div>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="notification-content">
                        <h4>Property Assessment Scheduled</h4>
                        <p>Assessor <strong><?= htmlspecialchars($notification['assessor_name']) ?></strong> will visit your property at <strong><?= htmlspecialchars($notification['property_address']) ?></strong></p>
                        <span class="notification-date">
                            <i class="fas fa-clock"></i>
                            Scheduled for: <?= date('F j, Y', strtotime($notification['visit_date'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Progress -->
        <div class="status-progress">
            <h2>Application Progress Overview</h2>
            <div class="progress-steps">
                <div class="progress-step">
                    <div class="step-circle <?= ($status_counts['pending'] ?? 0) > 0 ? 'active' : '' ?>">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="step-label">Pending</div>
                    <div class="step-count"><?= $status_counts['pending'] ?? 0 ?> applications</div>
                </div>
                
                <div class="progress-step">
                    <div class="step-circle <?= ($status_counts['for_assessment'] ?? 0) > 0 ? 'active' : '' ?>">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="step-label">Assessment</div>
                    <div class="step-count"><?= $status_counts['for_assessment'] ?? 0 ?> applications</div>
                </div>
                
                <div class="progress-step">
                    <div class="step-circle <?= ($status_counts['assessed'] ?? 0) > 0 ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="step-label">Assessed</div>
                    <div class="step-count"><?= $status_counts['assessed'] ?? 0 ?> applications</div>
                </div>
                
                <div class="progress-step">
                    <div class="step-circle <?= ($status_counts['approved'] ?? 0) > 0 ? 'active' : '' ?>">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-label">Approved</div>
                    <div class="step-count"><?= $status_counts['approved'] ?? 0 ?> applications</div>
                </div>
            </div>
        </div>

        <!-- Main Services Grid -->
        <div class="rpt-cards-container">
            <!-- Register Property -->
            <div class="rpt-card">
                <div class="card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="card-content">
                    <h3>Register Property</h3>
                    <p>Register your property for tax assessment and obtain your official Tax Declaration Number</p>
                    <a href="../rpt_card/register_rpt/register_rpt.php" class="card-button">
                        <i class="fas fa-plus-circle"></i>
                        Start Registration
                    </a>
                </div>
            </div>

            <!-- View Applications -->
            <div class="rpt-card">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="card-content">
                    <h3>Applications</h3>
                    <p>Track your property tax applications, view status updates, and manage submitted documents</p>
                    <a href="../rpt_card/rpt_application/rpt_application.php" class="card-button">
                        <i class="fas fa-list"></i>
                        View Applications
                    </a>
                </div>
            </div>

            <!-- Pay Tax -->
            <div class="rpt-card">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="card-content">
                    <h3>Pay Property Tax</h3>
                    <p>Pay your real property tax securely online and access payment history records</p>
                    
                    <?php if ($has_approved_app): ?>
                        <?php if (count($approved_applications) === 1): ?>
                            <!-- Single approved property -->
                            <div class="property-info">
                                <p><strong>Property:</strong> <?= htmlspecialchars($approved_applications[0]['property_address']) ?></p>
                                <?php if ($approved_applications[0]['tdn_no']): ?>
                                    <p><strong>TDN:</strong> <?= htmlspecialchars($approved_applications[0]['tdn_no']) ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="../rpt_card/pay_tax/pay_tax.php?application_id=<?= $approved_applications[0]['id'] ?>" class="card-button">
                                <i class="fas fa-dollar-sign"></i>
                                Pay Tax Now
                            </a>
                        <?php else: ?>
                            <!-- Multiple approved properties -->
                            <form id="payTaxForm" method="GET" action="../rpt_card/pay_tax/pay_tax.php">
                                <select class="property-select" name="application_id" required>
                                    <option value="">Select Property to Pay Taxes</option>
                                    <?php foreach ($approved_applications as $app): ?>
                                        <option value="<?= $app['id'] ?>">
                                            <?= htmlspecialchars($app['property_address']) ?>
                                            <?php if ($app['tdn_no']): ?>
                                                (TDN: <?= htmlspecialchars($app['tdn_no']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="card-button">
                                    <i class="fas fa-dollar-sign"></i>
                                    Pay Tax Now
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="card-button disabled" disabled>
                            <i class="fas fa-exclamation-circle"></i>
                            No Approved Properties
                        </button>
                        <p class="text-sm text-gray-600 mt-2 text-center">Complete property registration first</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="rpt-footer">
            <p>Need assistance with your property tax matters?</p>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>(123) 456-7890</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>assessor@municipal.gov</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Mon-Fri 8:00 AM - 5:00 PM</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Dashboard -->
    <div class="back-to-dashboard">
        <a href="../../citizen_portal/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Main Dashboard
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add confirmation for pay tax form
            const payTaxForm = document.getElementById('payTaxForm');
            if (payTaxForm) {
                payTaxForm.addEventListener('submit', function(e) {
                    const select = this.querySelector('select[name="application_id"]');
                    const selectedOption = select.options[select.selectedIndex];
                    if (selectedOption.value && !confirm(`Proceed to pay taxes for:\n"${selectedOption.text}"`)) {
                        e.preventDefault();
                    }
                });
            }

            // Add smooth animations
            const cards = document.querySelectorAll('.rpt-card');
            cards.forEach((card, index) => {
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