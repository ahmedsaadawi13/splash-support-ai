<?php
// FILE: /app/views/errors/error.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - SplashSupportAI</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">⚠️</div>
            <h2 class="text-center">Error <?php echo $code ?? 500; ?></h2>
            <p class="text-center"><?php echo SecurityHelper::escape($message ?? 'An error occurred'); ?></p>
            <div class="text-center mt-20">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
