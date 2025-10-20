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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Property Tax System</title>
    <link rel="stylesheet" href="../../citizen_portal/navbar.css">
    <link rel="stylesheet" href="rpt_dashboard.css">
    <style>
        .card-button.disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .card-button.disabled:hover {
            background-color: #9ca3af;
            transform: none;
        }
        
        /* Approved properties list styles */
        .approved-properties {
            margin-top: 1rem;
            max-height: 120px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.5rem;
            background: #f9fafb;
        }
        
        .property-item {
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 0.375rem;
            background: white;
            border: 1px solid #e5e7eb;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .property-item:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
        }
        
        .property-link {
            color: #374151;
            text-decoration: none;
            display: block;
        }
        
        .property-link:hover {
            color: #1f2937;
        }
        
        .property-address {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .property-tdn {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .no-properties {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 1rem;
        }

        /* Single property display */
        .single-property {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .single-property-address {
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 0.25rem;
        }

        .single-property-tdn {
            font-size: 0.75rem;
            color: #0c4a6e;
        }

        /* Property selection dropdown */
        .property-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            background: white;
        }

        .property-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .property-info {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            border-left: 4px solid #10b981;
        }

        .property-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include '../../citizen_portal/navbar.php'; ?>
    
    <div class="rpt-dashboard-container">
        <div class="rpt-header">
            <h1>Real Property Tax Collection System</h1>
            <p>Manage your property taxes and applications</p>
        </div>

        <!-- Notifications Section -->
        <?php if (!empty($notifications) && ($status_counts['for_assessment'] ?? 0) > 0): ?>
        <div class="notifications-section">
            <div class="notification-header">
                <h3><i class="fas fa-bell"></i> Upcoming Assessments</h3>
                <span class="notification-count"><?php echo count($notifications); ?></span>
            </div>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="notification-content">
                        <h4>Property Assessment Scheduled</h4>
                        <p>Assessor <strong><?php echo htmlspecialchars($notification['assessor_name']); ?></strong> will visit your property at <strong><?php echo htmlspecialchars($notification['property_address']); ?></strong></p>
                        <span class="notification-date">
                            <i class="fas fa-clock"></i>
                            Scheduled for: <?php echo date('F j, Y', strtotime($notification['visit_date'])); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Circular Application Status Summary -->
        <div class="status-summary">
            <h2>Application Status Overview</h2>
            <div class="timeline-container">
                <div class="timeline-line"></div>
                <div class="status-cards">
                    <div class="status-circle pending <?php echo ($status_counts['pending'] ?? 0) > 0 ? 'active' : ''; ?>">
                        <div class="circle-content">
                            <span class="status-count"><?php echo $status_counts['pending'] ?? 0; ?></span>
                            <span class="status-label">Pending</span>
                        </div>
                        <div class="circle-progress"></div>
                    </div>
                    
                    <div class="status-circle for_assessment <?php echo ($status_counts['for_assessment'] ?? 0) > 0 ? 'active' : ''; ?>">
                        <div class="circle-content">
                            <span class="status-count"><?php echo $status_counts['for_assessment'] ?? 0; ?></span>
                            <span class="status-label">For Assessment</span>
                        </div>
                        <div class="circle-progress"></div>
                    </div>
                    
                    <div class="status-circle assessed <?php echo ($status_counts['assessed'] ?? 0) > 0 ? 'active' : ''; ?>">
                        <div class="circle-content">
                            <span class="status-count"><?php echo $status_counts['assessed'] ?? 0; ?></span>
                            <span class="status-label">Assessed</span>
                        </div>
                        <div class="circle-progress"></div>
                    </div>
                    
                    <div class="status-circle approved <?php echo ($status_counts['approved'] ?? 0) > 0 ? 'active' : ''; ?>">
                        <div class="circle-content">
                            <span class="status-count"><?php echo $status_counts['approved'] ?? 0; ?></span>
                            <span class="status-label">Approved</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rpt-cards-container">
            <!-- Card 1: Register RPT -->
            <div class="rpt-card">
                <div class="card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="card-content">
                    <h3>Register Property</h3>
                    <p>Register your property for tax assessment and obtain your Tax Declaration</p>
                    <a href="../rpt_card/register_rpt/register_rpt.php" class="card-button">
                        Register Now
                    </a>
                </div>
            </div>

            <!-- Card 2: Application -->
            <div class="rpt-card">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="card-content">
                    <h3>Applications</h3>
                    <p>View and manage your property tax applications and status</p>
                    <a href="../rpt_card/rpt_application/rpt_application.php" class="card-button">
                        View Applications
                    </a>
                </div>
            </div>

            <!-- Card 3: Pay Tax -->
            <div class="rpt-card">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="card-content">
                    <h3>Pay Tax</h3>
                    <p>Pay your real property tax online securely</p>
                    
                    <?php if ($has_approved_app): ?>
                        <?php if (count($approved_applications) === 1): ?>
                            <!-- Single approved property - show button with ID -->
                            <div class="property-info">
                                <p><strong>Property:</strong> <?= htmlspecialchars($approved_applications[0]['property_address']) ?></p>
                                <?php if ($approved_applications[0]['tdn_no']): ?>
                                    <p><strong>TDN:</strong> <?= htmlspecialchars($approved_applications[0]['tdn_no']) ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="../rpt_card/pay_tax/pay_tax.php?application_id=<?= $approved_applications[0]['id'] ?>" class="card-button">
                                Pay Tax Now
                            </a>
                        <?php else: ?>
                            <!-- Multiple approved properties - show dropdown selection -->
                            <form id="payTaxForm" method="GET" action="../rpt_card/pay_tax/pay_tax.php">
                                <select class="property-select" name="application_id" required>
                                    <option value="">Select a property to pay taxes</option>
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
                                    Pay Tax Now
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="card-button disabled" disabled>
                            Pay Tax Now
                        </button>
                        <p class="text-sm text-gray-600 mt-2">No approved properties yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="rpt-footer">
            <p>Need help? Contact the Municipal Assessor's Office</p>
        </div>
    </div>

    <!-- Back to Dashboard Button -->
    <div class="back-to-dashboard">
        <a href="../../citizen_portal/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Main Dashboard
        </a>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to property items
            const propertyItems = document.querySelectorAll('.property-item');
            propertyItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Add confirmation for pay tax form submission
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

            // Add confirmation for single property pay tax
            const singlePayTaxButtons = document.querySelectorAll('.card-button[href*="pay_tax.php"]');
            singlePayTaxButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const propertyInfo = this.previousElementSibling;
                    if (propertyInfo && propertyInfo.classList.contains('property-info')) {
                        const propertyText = propertyInfo.textContent.trim();
                        if (!confirm(`Proceed to pay taxes for this property?`)) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>