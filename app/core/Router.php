<?php
// FILE: /app/core/Router.php

/**
 * SplashSupportAI - Router Class
 * Handles URL routing and dispatches to appropriate controllers
 */

class Router {
    private $controller = 'DashboardController';
    private $method = 'index';
    private $params = array();

    /**
     * Route the request
     */
    public function route() {
        $url = $this->parseUrl();

        // Handle public routes (no authentication required)
        if (!empty($url) && $url[0] === 'api') {
            return $this->handleApiRoutes($url);
        }

        if (!empty($url) && $url[0] === 'help') {
            return $this->handlePublicHelpRoutes($url);
        }

        if (!empty($url) && $url[0] === 'chat') {
            return $this->handleChatRoutes($url);
        }

        // Handle authentication routes
        if (!empty($url) && ($url[0] === 'login' || $url[0] === 'logout' || $url[0] === 'register')) {
            $this->controller = 'AuthController';
            $this->method = $url[0];
            return $this->dispatch();
        }

        // Check if user is authenticated for protected routes
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Controller
        if (!empty($url) && file_exists(APP_PATH . '/controllers/' . ucfirst($url[0]) . 'Controller.php')) {
            $this->controller = ucfirst($url[0]) . 'Controller';
            unset($url[0]);
        }

        // Load controller
        require_once APP_PATH . '/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Method
        if (isset($url[1]) && method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
        }

        // Parameters
        $this->params = $url ? array_values($url) : array();

        // Dispatch
        call_user_func_array(array($this->controller, $this->method), $this->params);
    }

    /**
     * Parse URL
     */
    private function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return array();
    }

    /**
     * Handle API routes
     */
    private function handleApiRoutes($url) {
        array_shift($url); // Remove 'api'

        require_once APP_PATH . '/controllers/ApiController.php';
        $controller = new ApiController();

        if (!empty($url[0]) && method_exists($controller, $url[0])) {
            $method = $url[0];
            array_shift($url);
            $params = $url ? array_values($url) : array();
            call_user_func_array(array($controller, $method), $params);
        } else {
            http_response_code(404);
            echo json_encode(array('error' => 'API endpoint not found'));
        }
        exit;
    }

    /**
     * Handle public help center routes
     */
    private function handlePublicHelpRoutes($url) {
        require_once APP_PATH . '/controllers/PublicHelpController.php';
        $controller = new PublicHelpController();

        array_shift($url); // Remove 'help'

        if (empty($url)) {
            $controller->index();
        } else {
            $tenantCode = $url[0];
            array_shift($url);

            if (empty($url)) {
                $controller->tenantHome($tenantCode);
            } elseif ($url[0] === 'article' && isset($url[1])) {
                $controller->article($tenantCode, $url[1]);
            } elseif ($url[0] === 'category' && isset($url[1])) {
                $controller->category($tenantCode, $url[1]);
            } elseif ($url[0] === 'search') {
                $controller->search($tenantCode);
            }
        }
        exit;
    }

    /**
     * Handle chat widget routes
     */
    private function handleChatRoutes($url) {
        require_once APP_PATH . '/controllers/ChatController.php';
        $controller = new ChatController();

        array_shift($url); // Remove 'chat'

        if (!empty($url[0]) && method_exists($controller, $url[0])) {
            $method = $url[0];
            array_shift($url);
            $params = $url ? array_values($url) : array();
            call_user_func_array(array($controller, $method), $params);
        } else {
            http_response_code(404);
            echo json_encode(array('error' => 'Chat endpoint not found'));
        }
        exit;
    }

    /**
     * Dispatch to controller
     */
    private function dispatch() {
        require_once APP_PATH . '/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        if (method_exists($this->controller, $this->method)) {
            call_user_func_array(array($this->controller, $this->method), $this->params);
        } else {
            http_response_code(404);
            echo "404 - Method not found";
        }
    }
}
