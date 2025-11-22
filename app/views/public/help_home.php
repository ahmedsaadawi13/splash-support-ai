<?php
// FILE: /app/views/public/help_home.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SecurityHelper::escape($tenant['name']); ?> - Help Center</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div style="max-width: 1000px; margin: 0 auto; padding: 40px 20px;">
        <h1><?php echo SecurityHelper::escape($tenant['name']); ?> Help Center</h1>

        <div style="margin: 30px 0;">
            <form method="GET" action="<?php echo BASE_URL; ?>/help/<?php echo $tenant['code']; ?>/search">
                <input type="text" name="q" placeholder="Search for help..." class="form-control" style="padding: 15px; font-size: 16px;">
            </form>
        </div>

        <h2>Categories</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <div class="card">
                    <h3><?php echo SecurityHelper::escape($category['name']); ?></h3>
                    <a href="<?php echo BASE_URL; ?>/help/<?php echo $tenant['code']; ?>/category/<?php echo $category['slug']; ?>">View articles</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>Recent Articles</h2>
        <?php if (!empty($recentArticles)): ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($recentArticles as $article): ?>
                <li style="margin-bottom: 10px;">
                    <a href="<?php echo BASE_URL; ?>/help/<?php echo $tenant['code']; ?>/article/<?php echo $article['slug']; ?>">
                        <?php echo SecurityHelper::escape($article['title']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
