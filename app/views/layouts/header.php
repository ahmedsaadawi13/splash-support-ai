<?php
// FILE: /app/views/layouts/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? SecurityHelper::escape($pageTitle) . ' - ' : ''; ?>SplashSupportAI</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="main-layout">
        <?php if (isset($user)): ?>
        <aside class="sidebar">
            <div class="sidebar-logo">SplashSupportAI</div>
            <ul class="sidebar-nav">
                <li><a href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>/tickets">Tickets</a></li>
                <?php if (in_array($user['role'], array('tenant_admin', 'support_manager'))): ?>
                <li><a href="<?php echo BASE_URL; ?>/reports">Reports</a></li>
                <li><a href="<?php echo BASE_URL; ?>/settings">Settings</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/logout">Logout</a></li>
            </ul>
        </aside>
        <?php endif; ?>

        <main class="main-content">
            <?php if (isset($user)): ?>
            <div class="topbar">
                <div class="topbar-left">
                    <h1><?php echo isset($pageTitle) ? SecurityHelper::escape($pageTitle) : 'Dashboard'; ?></h1>
                </div>
                <div class="topbar-right">
                    <?php if (isset($tenant)): ?>
                    <span><?php echo SecurityHelper::escape($tenant['name']); ?></span>
                    <?php endif; ?>
                    <span><?php echo SecurityHelper::escape($user['name']); ?></span>
                    <span class="badge badge-<?php echo $user['role']; ?>"><?php echo strtoupper($user['role']); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <div class="content">
                <?php
                // Flash messages
                if (isset($_SESSION['flash'])):
                    foreach ($_SESSION['flash'] as $type => $message):
                ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'error' : 'success'; ?>">
                    <?php echo SecurityHelper::escape($message); ?>
                </div>
                <?php
                    endforeach;
                    unset($_SESSION['flash']);
                endif;
                ?>
