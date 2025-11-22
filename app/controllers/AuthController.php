<?php
// FILE: /app/controllers/AuthController.php

/**
 * SplashSupportAI - Authentication Controller
 */

class AuthController extends Controller {

    public function __construct() {
        // Don't call parent constructor to avoid auth check
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
        } else {
            $this->view('auth/login', array(
                'csrf_token' => $this->generateCsrf()
            ));
        }
    }

    private function handleLogin() {
        $this->verifyCsrf();

        $email = $this->input('email');
        $password = $this->input('password');

        // Validation
        $errors = ValidationHelper::validate($_POST, array(
            'email' => 'required|email',
            'password' => 'required'
        ));

        if (!empty($errors)) {
            $this->view('auth/login', array(
                'errors' => $errors,
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        // Find user
        $userModel = $this->model('User');
        $user = $userModel->findByEmail($email);

        if (!$user) {
            $this->view('auth/login', array(
                'error' => 'Invalid credentials',
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
            $this->view('auth/login', array(
                'error' => "Account locked. Try again in {$remainingTime} minutes.",
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        // Verify password
        if (!SecurityHelper::verifyPassword($password, $user['password_hash'])) {
            $userModel->incrementFailedAttempts($user['id']);

            if ($user['failed_login_attempts'] + 1 >= 5) {
                $lockUntil = strtotime('+15 minutes');
                $userModel->lockAccount($user['id'], $lockUntil);
            }

            $this->view('auth/login', array(
                'error' => 'Invalid credentials',
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        // Check if user is active
        if ($user['status'] !== 'active') {
            $this->view('auth/login', array(
                'error' => 'Account is inactive',
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        // Update last login
        $userModel->updateLastLogin($user['id']);

        // Log activity
        $activityLog = $this->model('ActivityLog');
        $activityLog->log($user['tenant_id'], $user['id'], 'user', $user['id'], 'login', 'User logged in');

        // Set session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['tenant_id'] = $user['tenant_id'];

        $this->redirect('/dashboard');
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $activityLog = $this->model('ActivityLog');
            $activityLog->log(
                $_SESSION['tenant_id'] ?? null,
                $_SESSION['user_id'],
                'user',
                $_SESSION['user_id'],
                'logout',
                'User logged out'
            );
        }

        session_destroy();
        $this->redirect('/login');
    }

    public function register() {
        // Simplified registration - in production, add proper validation and workflow
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegistration();
        } else {
            $this->view('auth/register', array(
                'csrf_token' => $this->generateCsrf()
            ));
        }
    }

    private function handleRegistration() {
        $this->verifyCsrf();

        // Validate input
        $errors = ValidationHelper::validate($_POST, array(
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'company_name' => 'required'
        ));

        if (!empty($errors)) {
            $this->view('auth/register', array(
                'errors' => $errors,
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        // Check if email exists
        $userModel = $this->model('User');
        if ($userModel->findByEmail($this->input('email'))) {
            $this->view('auth/register', array(
                'error' => 'Email already exists',
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        try {
            // Create tenant
            $tenantModel = $this->model('Tenant');
            $tenantCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->input('company_name'))) . rand(100, 999);

            $tenantId = $tenantModel->insert(array(
                'name' => $this->input('company_name'),
                'code' => $tenantCode,
                'primary_contact_name' => $this->input('name'),
                'primary_contact_email' => $this->input('email'),
                'status' => 'active'
            ));

            // Create user as tenant_admin
            $userId = $userModel->create(array(
                'tenant_id' => $tenantId,
                'name' => $this->input('name'),
                'email' => $this->input('email'),
                'password' => $this->input('password'),
                'role' => 'tenant_admin',
                'status' => 'active'
            ));

            // Assign default plan (Starter)
            $subscriptionModel = new Model();
            $subscriptionModel->table = 'tenant_subscriptions';
            $subscriptionModel->insert(array(
                'tenant_id' => $tenantId,
                'plan_id' => 1,
                'status' => 'trialing',
                'start_date' => date('Y-m-d'),
                'renewal_date' => date('Y-m-d', strtotime('+30 days'))
            ));

            // Initialize usage tracking
            $usageModel = new Model();
            $usageModel->table = 'tenant_usage';
            $usageModel->insert(array(
                'tenant_id' => $tenantId,
                'reset_date' => date('Y-m-d', strtotime('+30 days'))
            ));

            // Auto login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = 'tenant_admin';
            $_SESSION['tenant_id'] = $tenantId;

            $this->redirect('/dashboard');
        } catch (Exception $e) {
            $this->view('auth/register', array(
                'error' => 'Registration failed. Please try again.',
                'csrf_token' => $this->generateCsrf()
            ));
        }
    }
}
