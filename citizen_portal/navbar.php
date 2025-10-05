<?php
// Do NOT call session_start() here if already started in main page

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get logged-in user name
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="navbar.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr(htmlspecialchars($full_name), 0, 1)) ?>
                    </div>
                    <h1 class="user-greeting">Welcome, <?= htmlspecialchars($full_name) ?></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="?logout=true" class="logout-btn">
                    <svg class="logout-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M6 12.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h3A1.5 1.5 0 0 1 11 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0v2z"/>
                        <path d="M4.5 8a.5.5 0 0 0 .5.5h5.793l-1.647 1.646a.5.5 0 0 0 .708.708l2.5-2.5a.5.5 0 0 0 0-.708l-2.5-2.5a.5.5 0 0 0-.708.708L10.293 7.5H5a.5.5 0 0 0-.5.5z"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </nav>
</body>
</html>