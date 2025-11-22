<?php
// FILE: /app/helpers/SecurityHelper.php

/**
 * SplashSupportAI - Security Helper
 * Handles security-related operations
 */

class SecurityHelper {

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate API key
     */
    public static function generateApiKey($prefix = 'sk') {
        return $prefix . '_' . self::generateToken(32);
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map(array('SecurityHelper', 'sanitize'), $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output for HTML
     */
    public static function escape($output) {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check for brute force attacks
     */
    public static function checkBruteForce($userId, &$attempts, &$lockedUntil) {
        if ($lockedUntil && strtotime($lockedUntil) > time()) {
            $remainingTime = strtotime($lockedUntil) - time();
            throw new Exception("Account locked. Try again in " . ceil($remainingTime / 60) . " minutes.");
        }

        if ($attempts >= 5) {
            return strtotime('+15 minutes');
        }

        return null;
    }

    /**
     * Get client IP address
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Get user agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Generate session token
     */
    public static function generateSessionToken() {
        return self::generateToken(16);
    }

    /**
     * Validate tenant isolation
     */
    public static function validateTenantAccess($userTenantId, $resourceTenantId, $userRole) {
        // Platform admins can access all tenants
        if ($userRole === 'platform_admin') {
            return true;
        }

        // Other users can only access their tenant's resources
        return $userTenantId === $resourceTenantId;
    }
}
