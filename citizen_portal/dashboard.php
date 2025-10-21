<?php
// dashboard.php - Add session validation
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="images/SAN.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Municipal Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: #333;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2c5aa0 0%, #4a90e2 50%, #2c5aa0 100%);
        }

        .dashboard-title {
            font-size: 2.5rem;
            color: #2c5aa0;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .dashboard-subtitle {
            font-size: 1.2rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .welcome-user {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #f0f7ff;
            padding: 12px 20px;
            border-radius: 50px;
            margin-top: 1rem;
            display: inline-flex;
        }

        .welcome-user i {
            color: #4a90e2;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            border-color: #4a90e2;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #4a90e2;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4a90e2, #2c5aa0);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color: white;
        }

        .card h3 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .card p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .card-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
        }

        .stats-bar {
            display: flex;
            justify-content: space-between;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4a90e2;
            display: block;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
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

            .stats-bar {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Municipal Services Dashboard</h2>
            <p class="dashboard-subtitle">Access and manage all municipal services in one place</p>
            <div class="welcome-user">
                <i class="fas fa-user-circle"></i>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number">3</span>
                <span class="stat-label">Available Services</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Service Availability</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">Digital Process</span>
            </div>
        </div>
        
        <div class="cards-grid">
            <!-- Market Stall Rental Card -->
            <div class="card" onclick="location.href='market_card/market-dashboard.php'">
                <div class="card-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h3>Market Stall Rental</h3>
                <p>Apply for market stall rentals, manage existing stalls, and process rental payments through our streamlined digital system.</p>
                <div class="card-badge">Available</div>
            </div>

            <!-- Real Property Register and Tax Card -->
            <div class="card" onclick="location.href='rpt_card/rpt_dashboard.php'">
                <div class="card-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>Real Property Register & Tax</h3>
                <p>Register properties, calculate taxes, view property records, and process tax payments efficiently.</p>
                <div class="card-badge">Available</div>
            </div>

            <!-- Business Tax Card -->
            <div class="card" onclick="location.href='../api/business_user/business_dashboard.php'">
                <div class="card-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>Business Tax</h3>
                <p>Calculate business taxes, file returns, make payments, and manage your business registration online.</p>
                <div class="card-badge">Available</div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>

</body>
</html>