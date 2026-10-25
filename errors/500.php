<?php require_once __DIR__ . '/../includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error | UrbanEats</title>
    <link rel="stylesheet" href="/urbaneats/UrbanEats-Cloud-Kitchen/public/style.css">
</head>
<body>
    <div class="container" style="text-align: center; margin-top: 6rem;">
        <div class="card" style="max-width: 520px; margin: 0 auto; padding: 3rem 2rem;">
            <div style="font-size: 4rem; color: var(--danger-red); margin-bottom: 0.5rem;">500</div>
            <h2 style="margin-bottom: 0.75rem;">System Error Occurred</h2>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;">
                We encountered an unexpected error while processing your request. Our technical team has been notified, and the incident has been logged.
            </p>
            <a href="/urbaneats/UrbanEats-Cloud-Kitchen/index.php" class="btn-primary" style="display: inline-block; width: auto; padding: 0.75rem 2rem; text-decoration: none;">
                Return to Home
            </a>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
