<?php
// FILE: /app/views/public/help_article.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SecurityHelper::escape($article['title']); ?> - Help Center</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">
        <div style="margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>/help/<?php echo $tenantCode; ?>">← Back to Help Center</a>
        </div>

        <article class="card">
            <h1><?php echo SecurityHelper::escape($article['title']); ?></h1>
            <div style="color: #718096; font-size: 14px; margin-bottom: 20px;">
                Published: <?php echo DateHelper::format($article['published_at'], 'F j, Y'); ?> |
                Views: <?php echo $article['view_count']; ?>
            </div>
            <div class="article-content">
                <?php echo $article['content_html']; ?>
            </div>
        </article>
    </div>
</body>
</html>
