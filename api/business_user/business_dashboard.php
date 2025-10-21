<?php
// business_dashboard.php - Add session validation
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: ../index.php');
    exit;
}

// Database connection
$host = 'localhost:3307';
$dbname = 'business';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get user's businesses
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT id, business_name, application_ref, status 
    FROM business_applications 
    WHERE user_id = ? 
    ORDER BY application_date DESC
");
$stmt->execute([$user_id]);
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$assessed_businesses = array_filter($businesses, function($business) {
    return $business['status'] === 'assessed';
});

// Get business statistics
$total_businesses = count($businesses);
$assessed_count = count($assessed_businesses);
$pending_count = count(array_filter($businesses, function($b) { return $b['status'] === 'pending'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../../citizen_portal/images/SAN.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Tax Dashboard - Municipal Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../navbar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Header Styles */
        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2c5aa0 0%, #4a90e2 50%, #2c5aa0 100%);
        }
        
        .dashboard-title {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #2c5aa0 0%, #4a90e2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        .dashboard-subtitle {
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
        
        /* Main Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .card::before {
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
        
        .card:hover::before {
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
            margin: 0 auto 1.5rem auto;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
        }
        
        .card:hover .card-icon {
            transform: scale(1.1);
        }
        
        .card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .card p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
            flex-grow: 1;
        }
        
        .card-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Button Styles */
        .card-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #4a90e2, #2c5aa0);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
            font-size: 1rem;
        }
        
        .card-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.3);
        }
        
        .card-button.register {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .card-button.register:hover {
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .card-button.view {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .card-button.view:hover {
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
        }
        
        .card-button.pay {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        
        .card-button.pay:hover {
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }
        
        .card-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .card-button:disabled:hover {
            box-shadow: none;
            transform: none;
        }
        
        /* Business List Styles */
        .business-list {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-top: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        
        .business-list-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .business-list-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .business-list-header p {
            color: #6b7280;
        }
        
        .business-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border-left: 4px solid #4a90e2;
        }
        
        .business-item:hover {
            background: #f0f7ff;
            transform: translateX(5px);
            border-left-color: #2c5aa0;
        }
        
        .business-info {
            flex-grow: 1;
        }
        
        .business-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .business-ref {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .business-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #d1fae5;
            color: #065f46;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .business-status::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
        }
        
        .pay-tax-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .pay-tax-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(139, 92, 246, 0.3);
        }
        
        .no-businesses {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #d1d5db;
        }
        
        .no-businesses i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #9ca3af;
        }
        
        /* Back Button */
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
            margin-bottom: 2rem;
        }
        
        .back-button:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            margin-top: 3rem;
        }
        
        .dashboard-footer p {
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a90e2;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .dashboard-title {
                font-size: 2rem;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .business-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .pay-tax-btn {
                align-self: stretch;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include '../../citizen_portal/navbar.php'; ?>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <a href="../../citizen_portal/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Main Dashboard
        </a>
        
        <!-- Header Section -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Business Tax Services</h1>
            <p class="dashboard-subtitle">Manage your business registration, permits, and tax payments in one place</p>
            <div class="welcome-user">
                <i class="fas fa-user-circle"></i>
                <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <span class="stat-number"><?= $total_businesses ?></span>
                <span class="stat-label">Total Businesses</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $assessed_count ?></span>
                <span class="stat-label">Ready for Payment</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $pending_count ?></span>
                <span class="stat-label">Pending Review</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Online Support</span>
            </div>
        </div>
        
        <!-- Main Services Grid -->
        <div class="cards-grid">
            <!-- Register Business Card -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h3>Register Business</h3>
                <p>Register your business and apply for business permits and licenses. Start your business registration process with our streamlined digital system.</p>
                <a href="register_business/register_business.php" class="card-button register">
                    <i class="fas fa-plus-circle"></i>
                    Start Registration
                </a>
                <div class="card-badge">New</div>
            </div>

            <!-- View Applications Card -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3>View Applications</h3>
                <p>Check the status of your business registration applications and permits. Track your application progress and receive real-time updates.</p>
                <a href="view_business/view_business.php" class="card-button view">
                    <i class="fas fa-list"></i>
                    View Applications
                </a>
                <div class="card-badge">Track</div>
            </div>

            <!-- Pay Tax Card -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>Pay Business Tax</h3>
                <p>Make business tax payments and view payment history. Manage your tax obligations conveniently through our secure payment gateway.</p>
                
                <?php if (empty($assessed_businesses)): ?>
                    <!-- No businesses - show disabled state -->
                    <button class="card-button pay" disabled>
                        <i class="fas fa-exclamation-circle"></i>
                        No Businesses Available
                    </button>
                    <p class="text-sm text-gray-600 mt-2 text-center">Complete business registration first</p>
                <?php elseif (count($assessed_businesses) === 1): ?>
                    <!-- Single business - direct link -->
                    <?php $single_business = reset($assessed_businesses); ?>
                    <a href="pay_business_tax/pay_business_tax.php?application_id=<?= $single_business['id'] ?>" class="card-button pay">
                        <i class="fas fa-dollar-sign"></i>
                        Pay Taxes Now
                    </a>
                <?php else: ?>
                    <!-- Multiple businesses - show selection -->
                    <a href="#businessList" class="card-button pay">
                        <i class="fas fa-dollar-sign"></i>
                        Select Business to Pay
                    </a>
                <?php endif; ?>
                
                <div class="card-badge">Pay</div>
            </div>
        </div>

        <!-- Business List Section -->
        <?php if (count($assessed_businesses) > 1): ?>
        <div id="businessList" class="business-list">
            <div class="business-list-header">
                <h3>Select a Business to Pay Taxes</h3>
                <p>Choose from your assessed businesses to proceed with tax payment</p>
            </div>
            
            <?php foreach ($assessed_businesses as $business): ?>
                <div class="business-item">
                    <div class="business-info">
                        <div class="business-name"><?= htmlspecialchars($business['business_name']) ?></div>
                        <div class="business-ref">Reference: <?= htmlspecialchars($business['application_ref']) ?></div>
                        <div class="business-status"><?= ucfirst($business['status']) ?></div>
                    </div>
                    <a href="pay_business_tax/pay_business_tax.php?application_id=<?= $business['id'] ?>" 
                       class="pay-tax-btn">
                        <i class="fas fa-arrow-right"></i>
                        Pay Taxes
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (empty($assessed_businesses) && !empty($businesses)): ?>
        <div class="business-list">
            <div class="no-businesses">
                <i class="fas fa-clock"></i>
                <h3>No Businesses Ready for Payment</h3>
                <p>Your businesses are currently under review. Please check back later or view your applications for status updates.</p>
                <a href="view_business/view_business.php" class="card-button view" style="width: auto; margin-top: 1rem;">
                    <i class="fas fa-external-link-alt"></i>
                    Check Application Status
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="dashboard-footer">
            <p>Need assistance with your business registration or tax payments?</p>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>(123) 456-7890</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>business@municipal.gov</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Mon-Fri 8:00 AM - 5:00 PM</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            const stats = document.querySelectorAll('.stat-card');
            
            // Animate cards
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            // Animate stats
            stats.forEach((stat, index) => {
                stat.style.opacity = '0';
                stat.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    stat.style.transition = 'all 0.4s ease';
                    stat.style.opacity = '1';
                    stat.style.transform = 'scale(1)';
                }, index * 100 + 400);
            });
        });
    </script>
</body>
</html>