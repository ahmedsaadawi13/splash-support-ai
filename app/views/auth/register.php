<?php
// FILE: /app/views/auth/register.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SplashSupportAI</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">SplashSupportAI</div>
            <p class="text-center mb-20">Start your 30-day free trial</p>

            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo SecurityHelper::escape($error); ?></div>
            <?php endif; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $field => $fieldErrors): ?>
                    <?php foreach ($fieldErrors as $error): ?>
                        <div><?php echo SecurityHelper::escape($error); ?></div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/register">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            </form>

            <div class="text-center mt-20">
                <a href="<?php echo BASE_URL; ?>/login">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>
