<?php require_once __DIR__ . '/../includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error | UrbanEats</title>
    <link rel="stylesheet" href="/urbaneats/UrbanEats-Cloud-Kitchen/public/style.css">
</head>
<body class="error-page-body">
    <main class="error-page-shell">
        <div class="error-card">
            <div class="error-code">500</div>
            <h1 class="error-title">System Error Occurred</h1>
            <p class="error-message">
                We encountered an unexpected error while processing your request. Our technical team has been notified, and the incident has been logged.
            </p>
            <a href="/urbaneats/UrbanEats-Cloud-Kitchen/index.php" class="btn-primary error-action">
                Return to Home
            </a>
        </div>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
