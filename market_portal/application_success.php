<?php
session_start();
// Less strict check - only redirect if neither success message nor application ID exists
if (!isset($_SESSION['success']) && !isset($_SESSION['application_id'])) {
    header('Location: market_portal.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - Market Stall Portal</title>
    <link rel="stylesheet" href="../citizen_portal/navbar.css">
    <link rel="stylesheet" href="application_success.css">
</head>
<body>
    <?php include '../citizen_portal/navbar.php'; ?>
    
    <div class="portal-container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Application Submitted Successfully!</h1>
            <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
            
            <?php if (isset($_SESSION['application_id'])): ?>
                <div class="application-id">
                    <strong>Application ID:</strong> #<?php echo $_SESSION['application_id']; ?>
                </div>
            <?php endif; ?>
            
            <<div class="success-actions">
    <a href="../citizen_portal/market_card/view_documents/view_pending.php?application_id=<?= $_SESSION['application_id'] ?>" class="btn-secondary">View Application Status</a>   
</div>
        </div>
    </div>
    
    <?php
    // Keep session data for now - let destination pages handle clearing
    // Or optionally clear after a delay using JavaScript
    ?>
    
    <script>
        // Optional: Clear session data after user navigates away
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Only clear if it's an internal navigation
                    if (this.href && !this.target) {
                        // You could optionally send an AJAX request to clear sessions
                        // fetch('clear_sessions.php').then(() => window.location = this.href);
                    }
                });
            });
        });
    </script>
</body>
</html>