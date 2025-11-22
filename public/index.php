<?php
// FILE: /public/index.php

/**
 * SplashSupportAI - Application Entry Point
 * All requests are routed through this file
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load core classes
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Router.php';

// Load helpers
require_once APP_PATH . '/helpers/SecurityHelper.php';
require_once APP_PATH . '/helpers/ValidationHelper.php';
require_once APP_PATH . '/helpers/FileHelper.php';
require_once APP_PATH . '/helpers/AIHelper.php';
require_once APP_PATH . '/helpers/DateHelper.php';

// Initialize router
$router = new Router();
$router->route();
