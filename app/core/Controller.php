<?php
// FILE: /app/core/Controller.php

/**
 * SplashSupportAI - Base Controller Class
 * Parent class for all controllers
 */

class Controller {
    protected $currentUser = null;
    protected $currentTenant = null;

    /**
     * Constructor
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Load current user if logged in
        if (isset($_SESSION['user_id'])) {
            $this->loadCurrentUser();
        }
    }

    /**
     * Load model
     */
    protected function model($model) {
        $modelPath = APP_PATH . '/models/' . $model . '.php';
        if (file_exists($modelPath)) {
            require_once $modelPath;
            return new $model();
        }
        throw new Exception("Model {$model} not found");
    }

    /**
     * Load view
     */
    protected function view($view, $data = array()) {
        $viewPath = APP_PATH . '/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            extract($data);
            require_once $viewPath;
        } else {
            throw new Exception("View {$view} not found");
        }
    }

    /**
     * Redirect
     */
    protected function redirect($path) {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    /**
     * JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Verify CSRF token
     */
    protected function verifyCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[CSRF_TOKEN_NAME] ?? '';
            if (!isset($_SESSION[CSRF_TOKEN_NAME]) || $token !== $_SESSION[CSRF_TOKEN_NAME]) {
                $this->error('Invalid CSRF token', 403);
            }
        }
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrf() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Check if user is authenticated
     */
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * Check user role
     */
    protected function requireRole($roles) {
        if (!is_array($roles)) {
            $roles = array($roles);
        }

        if (!$this->currentUser || !in_array($this->currentUser['role'], $roles)) {
            $this->error('Access denied', 403);
        }
    }

    /**
     * Load current user
     */
    private function loadCurrentUser() {
        $userModel = $this->model('User');
        $this->currentUser = $userModel->findById($_SESSION['user_id']);

        if ($this->currentUser && $this->currentUser['tenant_id']) {
            $tenantModel = $this->model('Tenant');
            $this->currentTenant = $tenantModel->findById($this->currentUser['tenant_id']);
        }
    }

    /**
     * Get current tenant ID (with security check)
     */
    protected function getCurrentTenantId() {
        if ($this->currentUser['role'] === 'platform_admin') {
            // Platform admins can work across tenants
            return isset($_SESSION['active_tenant_id']) ? $_SESSION['active_tenant_id'] : null;
        }
        return $this->currentUser['tenant_id'];
    }

    /**
     * Error response
     */
    protected function error($message, $code = 500) {
        http_response_code($code);
        $this->view('errors/error', array('message' => $message, 'code' => $code));
        exit;
    }

    /**
     * Get request input
     */
    protected function input($key, $default = null) {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }

    /**
     * Get all POST data
     */
    protected function postData() {
        return $_POST;
    }

    /**
     * Flash message
     */
    protected function flash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get flash message
     */
    protected function getFlash($key) {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }
}
