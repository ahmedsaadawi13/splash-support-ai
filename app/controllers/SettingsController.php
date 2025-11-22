<?php
// FILE: /app/controllers/SettingsController.php

/**
 * SplashSupportAI - Settings Controller
 */

class SettingsController extends Controller {

    public function index() {
        $this->requireRole(array('tenant_admin'));
        $tenantId = $this->getCurrentTenantId();

        $this->view('settings/index', array(
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'csrf_token' => $this->generateCsrf()
        ));
    }

    public function users() {
        $this->requireRole(array('tenant_admin', 'support_manager'));
        $tenantId = $this->getCurrentTenantId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreateUser();
            return;
        }

        $userModel = $this->model('User');
        $users = $userModel->findByTenant($tenantId);

        $this->view('settings/users', array(
            'users' => $users,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'csrf_token' => $this->generateCsrf()
        ));
    }

    private function handleCreateUser() {
        $this->verifyCsrf();
        $tenantId = $this->getCurrentTenantId();

        $errors = ValidationHelper::validate($_POST, array(
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'role' => 'required|enum:tenant_admin,support_manager,support_agent,read_only'
        ));

        if (!empty($errors)) {
            $this->flash('error', 'Validation failed');
            $this->redirect('/settings/users');
        }

        $userModel = $this->model('User');
        if ($userModel->findByEmail($this->input('email'))) {
            $this->flash('error', 'Email already exists');
            $this->redirect('/settings/users');
        }

        try {
            $userModel->create(array(
                'tenant_id' => $tenantId,
                'name' => $this->input('name'),
                'email' => $this->input('email'),
                'password' => $this->input('password'),
                'role' => $this->input('role'),
                'status' => 'active'
            ));

            $this->flash('success', 'User created successfully');
        } catch (Exception $e) {
            $this->flash('error', 'Failed to create user');
        }

        $this->redirect('/settings/users');
    }

    public function channels() {
        $this->requireRole(array('tenant_admin'));
        $tenantId = $this->getCurrentTenantId();

        $channelModel = $this->model('Channel');
        $channels = $channelModel->findByTenant($tenantId);

        $this->view('settings/channels', array(
            'channels' => $channels,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'csrf_token' => $this->generateCsrf()
        ));
    }

    public function sla() {
        $this->requireRole(array('tenant_admin', 'support_manager'));
        $tenantId = $this->getCurrentTenantId();

        $slaModel = $this->model('SLAPolicy');
        $policies = $slaModel->findByTenant($tenantId);

        $this->view('settings/sla', array(
            'policies' => $policies,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'csrf_token' => $this->generateCsrf()
        ));
    }

    public function subscription() {
        $this->requireRole(array('tenant_admin'));
        $tenantId = $this->getCurrentTenantId();

        $tenantModel = $this->model('Tenant');
        $subscription = $tenantModel->getActiveSubscription($tenantId);
        $usage = $tenantModel->getUsage($tenantId);

        $planModel = $this->model('Plan');
        $plans = $planModel->getActivePlans();

        $this->view('settings/subscription', array(
            'subscription' => $subscription,
            'usage' => $usage,
            'plans' => $plans,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'csrf_token' => $this->generateCsrf()
        ));
    }
}
