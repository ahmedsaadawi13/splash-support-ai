<?php
// FILE: /app/controllers/PublicHelpController.php

/**
 * SplashSupportAI - Public Help Center Controller
 */

class PublicHelpController {

    public function __construct() {
        // No authentication required for public help center
    }

    public function index() {
        echo "<h1>SplashSupportAI Help Center</h1>";
        echo "<p>Please specify a tenant in the URL: /help/{tenant_code}</p>";
    }

    public function tenantHome($tenantCode) {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findByCode($tenantCode);

        if (!$tenant) {
            http_response_code(404);
            echo "Help center not found";
            return;
        }

        $db = new Database();
        $db->query("SELECT * FROM kb_categories WHERE tenant_id = :tenant_id AND parent_id IS NULL ORDER BY name ASC");
        $db->bind(':tenant_id', $tenant['id']);
        $categories = $db->all();

        $kbModel = new KBArticle();
        $recentArticles = $kbModel->getPublishedArticles($tenant['id'], null, 10);

        require_once APP_PATH . '/views/public/help_home.php';
    }

    public function article($tenantCode, $slug) {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findByCode($tenantCode);

        if (!$tenant) {
            http_response_code(404);
            echo "Help center not found";
            return;
        }

        $kbModel = new KBArticle();
        $article = $kbModel->findBySlug($tenant['id'], $slug);

        if (!$article || !$article['is_public'] || !$article['published_at']) {
            http_response_code(404);
            echo "Article not found";
            return;
        }

        // Increment view count
        $kbModel->incrementViewCount($article['id']);

        require_once APP_PATH . '/views/public/help_article.php';
    }

    public function category($tenantCode, $slug) {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findByCode($tenantCode);

        if (!$tenant) {
            http_response_code(404);
            echo "Help center not found";
            return;
        }

        $db = new Database();
        $db->query("SELECT * FROM kb_categories WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
        $db->bind(':tenant_id', $tenant['id']);
        $db->bind(':slug', $slug);
        $category = $db->single();

        if (!$category) {
            http_response_code(404);
            echo "Category not found";
            return;
        }

        $kbModel = new KBArticle();
        $articles = $kbModel->getPublishedArticles($tenant['id'], $category['id']);

        require_once APP_PATH . '/views/public/help_category.php';
    }

    public function search($tenantCode) {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findByCode($tenantCode);

        if (!$tenant) {
            http_response_code(404);
            echo "Help center not found";
            return;
        }

        $query = $_GET['q'] ?? '';

        $articles = array();
        if ($query) {
            $kbModel = new KBArticle();
            $articles = $kbModel->search($tenant['id'], $query, 20);
        }

        require_once APP_PATH . '/views/public/help_search.php';
    }
}
