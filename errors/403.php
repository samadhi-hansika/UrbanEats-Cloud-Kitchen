<?php require_once __DIR__ . '/../includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 Access Denied</title>
    <link rel="stylesheet" href="<?= e(url('public/style.css')) ?>">
</head>
<body>
    <div class="container" style="text-align: center; margin-top: 8rem;">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <h1 style="color: var(--danger-red, #dc2626); font-size: 3rem; margin: 0;">403</h1>
            <h2>Access Denied</h2>
            <p style="color: var(--text-muted, #64748b);">You do not have the required permissions or role to view this protected page.</p>
            <a href="<?= e(url('index.php')) ?>" class="btn-primary" style="display: inline-block; margin-top: 1rem; text-decoration: none;">Return to Home</a>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>