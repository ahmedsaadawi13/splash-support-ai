-- FILE: /database.sql
-- SplashSupportAI - Complete Database Schema
-- MySQL 5.7+ / MariaDB 10.2+

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================================
-- TENANTS & USERS
-- ============================================================================

DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `primary_contact_name` VARCHAR(255) DEFAULT NULL,
  `primary_contact_email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `default_timezone` VARCHAR(50) DEFAULT 'UTC',
  `default_language` VARCHAR(10) DEFAULT 'en',
  `default_currency` VARCHAR(3) DEFAULT 'USD',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_code` (`code`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('platform_admin', 'tenant_admin', 'support_manager', 'support_agent', 'read_only') NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `avatar_path` VARCHAR(255) DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `failed_login_attempts` INT DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  INDEX `idx_tenant_role` (`tenant_id`, `role`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `teams`;
CREATE TABLE `teams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `team_users`;
CREATE TABLE `team_users` (
  `team_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `tenant_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`team_id`, `user_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_tenant` (`tenant_id`),
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSCRIPTION & BILLING
-- ============================================================================

DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `billing_cycle` ENUM('monthly', 'yearly') DEFAULT 'monthly',
  `max_users` INT DEFAULT NULL,
  `max_tickets_per_month` INT DEFAULT NULL,
  `max_ai_tokens_per_month` INT DEFAULT NULL,
  `max_channels` INT DEFAULT NULL,
  `features_json` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tenant_subscriptions`;
CREATE TABLE `tenant_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('trialing', 'active', 'past_due', 'canceled') DEFAULT 'trialing',
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `renewal_date` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_renewal` (`renewal_date`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tenant_usage`;
CREATE TABLE `tenant_usage` (
  `tenant_id` INT UNSIGNED NOT NULL,
  `current_ticket_count_month` INT DEFAULT 0,
  `current_ai_tokens_used_month` INT DEFAULT 0,
  `current_api_calls_month` INT DEFAULT 0,
  `storage_bytes_used` BIGINT DEFAULT 0,
  `reset_date` DATE NOT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tenant_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `due_date` DATE NOT NULL,
  `status` ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid',
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant_status` (`tenant_id`, `status`),
  INDEX `idx_due_date` (`due_date`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `method` VARCHAR(50) DEFAULT NULL,
  `transaction_reference` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_invoice` (`invoice_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CHANNELS
-- ============================================================================

DROP TABLE IF EXISTS `channels`;
CREATE TABLE `channels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('email', 'web_chat', 'whatsapp', 'api') NOT NULL,
  `inbound_address_or_id` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `config_json` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant_type` (`tenant_id`, `type`),
  INDEX `idx_active` (`is_active`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CUSTOMERS
-- ============================================================================

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `external_id` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant_email` (`tenant_id`, `email`),
  INDEX `idx_tenant_phone` (`tenant_id`, `phone`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TICKETS & MESSAGES
-- ============================================================================

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `public_id` VARCHAR(50) NOT NULL,
  `channel_id` INT UNSIGNED DEFAULT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `customer_phone` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('new', 'open', 'pending', 'on_hold', 'resolved', 'closed') DEFAULT 'new',
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `type` ENUM('question', 'incident', 'problem', 'task') DEFAULT 'question',
  `assignee_user_id` INT UNSIGNED DEFAULT NULL,
  `team_id` INT UNSIGNED DEFAULT NULL,
  `sla_policy_id` INT UNSIGNED DEFAULT NULL,
  `due_at` DATETIME DEFAULT NULL,
  `first_response_at` DATETIME DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `last_customer_reply_at` DATETIME DEFAULT NULL,
  `last_agent_reply_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_public_id` (`tenant_id`, `public_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_assignee` (`assignee_user_id`),
  INDEX `idx_team` (`team_id`),
  INDEX `idx_channel` (`channel_id`),
  INDEX `idx_customer` (`customer_id`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`channel_id`) REFERENCES `channels`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assignee_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ticket_messages`;
CREATE TABLE `ticket_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `ticket_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('customer', 'agent', 'system', 'ai') NOT NULL,
  `sender_user_id` INT UNSIGNED DEFAULT NULL,
  `message_type` ENUM('text', 'note') DEFAULT 'text',
  `body_text` TEXT NOT NULL,
  `body_html` TEXT DEFAULT NULL,
  `is_internal` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ticket` (`ticket_id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ticket_attachments`;
CREATE TABLE `ticket_attachments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `ticket_id` INT UNSIGNED NOT NULL,
  `message_id` INT UNSIGNED DEFAULT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `size_bytes` BIGINT DEFAULT 0,
  `uploaded_by_user_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ticket` (`ticket_id`),
  INDEX `idx_message` (`message_id`),
  INDEX `idx_tenant` (`tenant_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`message_id`) REFERENCES `ticket_messages`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SLA MANAGEMENT
-- ============================================================================

DROP TABLE IF EXISTS `sla_policies`;
CREATE TABLE `sla_policies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `first_response_time_minutes` INT NOT NULL,
  `resolution_time_minutes` INT NOT NULL,
  `business_hours_json` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ticket_sla_status`;
CREATE TABLE `ticket_sla_status` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `ticket_id` INT UNSIGNED NOT NULL,
  `policy_id` INT UNSIGNED NOT NULL,
  `first_response_due_at` DATETIME DEFAULT NULL,
  `first_response_met` TINYINT(1) DEFAULT 0,
  `resolution_due_at` DATETIME DEFAULT NULL,
  `resolution_met` TINYINT(1) DEFAULT 0,
  `breached` TINYINT(1) DEFAULT 0,
  `last_checked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ticket` (`ticket_id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_breached` (`breached`),
  INDEX `idx_first_response_due` (`first_response_due_at`),
  INDEX `idx_resolution_due` (`resolution_due_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`policy_id`) REFERENCES `sla_policies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ROUTING RULES
-- ============================================================================

DROP TABLE IF EXISTS `routing_rules`;
CREATE TABLE `routing_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `priority_order` INT DEFAULT 0,
  `criteria_json` TEXT NOT NULL,
  `action_json` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
  INDEX `idx_priority` (`priority_order`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- KNOWLEDGE BASE
-- ============================================================================

DROP TABLE IF EXISTS `kb_categories`;
CREATE TABLE `kb_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_slug` (`tenant_id`, `slug`),
  INDEX `idx_parent` (`parent_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `kb_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `kb_articles`;
CREATE TABLE `kb_articles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL,
  `content_html` LONGTEXT NOT NULL,
  `content_text` LONGTEXT NOT NULL,
  `is_public` TINYINT(1) DEFAULT 1,
  `tags` VARCHAR(500) DEFAULT NULL,
  `view_count` INT DEFAULT 0,
  `created_by_user_id` INT UNSIGNED DEFAULT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_slug` (`tenant_id`, `slug`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_public` (`is_public`),
  INDEX `idx_published` (`published_at`),
  FULLTEXT KEY `idx_fulltext_search` (`title`, `content_text`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `kb_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CHAT SESSIONS
-- ============================================================================

DROP TABLE IF EXISTS `chat_sessions`;
CREATE TABLE `chat_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `session_token` VARCHAR(100) NOT NULL UNIQUE,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `customer_phone` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('open', 'closed') DEFAULT 'open',
  `ticket_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant_status` (`tenant_id`, `status`),
  INDEX `idx_ticket` (`ticket_id`),
  INDEX `idx_token` (`session_token`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NOTIFICATIONS
-- ============================================================================

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_read` (`user_id`, `is_read`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- API KEYS
-- ============================================================================

DROP TABLE IF EXISTS `tenant_api_keys`;
CREATE TABLE `tenant_api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `api_key` VARCHAR(100) NOT NULL UNIQUE,
  `name` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_key` (`api_key`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ACTIVITY LOGS
-- ============================================================================

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CANNED RESPONSES
-- ============================================================================

DROP TABLE IF EXISTS `canned_responses`;
CREATE TABLE `canned_responses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `shortcode` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by_user_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_shortcode` (`tenant_id`, `shortcode`),
  INDEX `idx_active` (`is_active`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Plans
INSERT INTO `plans` (`id`, `name`, `description`, `price`, `billing_cycle`, `max_users`, `max_tickets_per_month`, `max_ai_tokens_per_month`, `max_channels`, `features_json`) VALUES
(1, 'Starter', 'Perfect for small teams', 29.00, 'monthly', 5, 100, 10000, 2, '{"whatsapp_integration":false,"advanced_reports":false,"custom_sla":false,"chatbot_widget":true,"api_access":false}'),
(2, 'Professional', 'For growing businesses', 99.00, 'monthly', 20, 500, 50000, 5, '{"whatsapp_integration":true,"advanced_reports":true,"custom_sla":true,"chatbot_widget":true,"api_access":true}'),
(3, 'Enterprise', 'Unlimited scale', 299.00, 'monthly', NULL, NULL, 200000, NULL, '{"whatsapp_integration":true,"advanced_reports":true,"custom_sla":true,"chatbot_widget":true,"api_access":true}');

-- Platform Admin User
INSERT INTO `users` (`id`, `tenant_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, NULL, 'Platform Administrator', 'admin@splashsupportai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'platform_admin', 'active');
-- Password: password

-- Demo Tenant 1: Acme Corp
INSERT INTO `tenants` (`id`, `name`, `code`, `primary_contact_name`, `primary_contact_email`, `phone`, `default_timezone`, `status`) VALUES
(1, 'Acme Corporation', 'acme', 'John Doe', 'john@acme.com', '+1-555-0100', 'America/New_York', 'active');

INSERT INTO `tenant_subscriptions` (`tenant_id`, `plan_id`, `status`, `start_date`, `renewal_date`) VALUES
(1, 2, 'active', '2025-01-01', '2025-02-01');

INSERT INTO `tenant_usage` (`tenant_id`, `current_ticket_count_month`, `current_ai_tokens_used_month`, `reset_date`) VALUES
(1, 0, 0, '2025-02-01');

-- Demo Tenant 1 Users
INSERT INTO `users` (`id`, `tenant_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(2, 1, 'Jane Smith', 'jane@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 'active'),
(3, 1, 'Bob Manager', 'bob@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'support_manager', 'active'),
(4, 1, 'Alice Agent', 'alice@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'support_agent', 'active');

-- Demo Tenant 1 Team
INSERT INTO `teams` (`id`, `tenant_id`, `name`, `description`) VALUES
(1, 1, 'Support Team', 'Main customer support team');

INSERT INTO `team_users` (`team_id`, `user_id`, `tenant_id`) VALUES
(1, 3, 1),
(1, 4, 1);

-- Demo Tenant 1 Channels
INSERT INTO `channels` (`id`, `tenant_id`, `name`, `type`, `inbound_address_or_id`, `is_active`) VALUES
(1, 1, 'General Support Email', 'email', 'support@acme.com', 1),
(2, 1, 'Website Chat', 'web_chat', 'acme-widget-001', 1),
(3, 1, 'API Channel', 'api', 'api-acme', 1);

-- Demo Tenant 1 SLA Policy
INSERT INTO `sla_policies` (`id`, `tenant_id`, `name`, `description`, `first_response_time_minutes`, `resolution_time_minutes`, `is_active`) VALUES
(1, 1, 'Standard SLA', 'Standard response and resolution times', 60, 480, 1);

-- Demo Tenant 1 Knowledge Base
INSERT INTO `kb_categories` (`id`, `tenant_id`, `name`, `slug`) VALUES
(1, 1, 'Getting Started', 'getting-started'),
(2, 1, 'Billing', 'billing'),
(3, 1, 'Troubleshooting', 'troubleshooting');

INSERT INTO `kb_articles` (`id`, `tenant_id`, `category_id`, `title`, `slug`, `content_html`, `content_text`, `is_public`, `published_at`) VALUES
(1, 1, 1, 'How to Create Your First Ticket', 'how-to-create-first-ticket', '<h1>Creating Your First Ticket</h1><p>This guide will help you create your first support ticket...</p>', 'Creating Your First Ticket. This guide will help you create your first support ticket...', 1, '2025-01-01 00:00:00'),
(2, 1, 2, 'Understanding Your Bill', 'understanding-your-bill', '<h1>Understanding Your Bill</h1><p>Your monthly bill includes...</p>', 'Understanding Your Bill. Your monthly bill includes...', 1, '2025-01-01 00:00:00');

-- Demo Tenant 1 API Key
INSERT INTO `tenant_api_keys` (`tenant_id`, `api_key`, `name`, `is_active`) VALUES
(1, 'acme_live_sk_1234567890abcdefghijklmnopqrstuvwxyz', 'Production API Key', 1);

-- Demo Tenant 1 Sample Customers
INSERT INTO `customers` (`id`, `tenant_id`, `name`, `email`, `phone`) VALUES
(1, 1, 'Sarah Johnson', 'sarah@example.com', '+1-555-0201'),
(2, 1, 'Mike Wilson', 'mike@example.com', '+1-555-0202');

-- Demo Tenant 1 Sample Tickets
INSERT INTO `tickets` (`id`, `tenant_id`, `public_id`, `channel_id`, `customer_id`, `subject`, `customer_name`, `customer_email`, `status`, `priority`, `assignee_user_id`, `team_id`, `sla_policy_id`, `created_at`) VALUES
(1, 1, 'TCK-10001', 1, 1, 'Cannot login to my account', 'Sarah Johnson', 'sarah@example.com', 'open', 'high', 4, 1, 1, '2025-01-15 10:30:00'),
(2, 1, 'TCK-10002', 2, 2, 'Billing question about my invoice', 'Mike Wilson', 'mike@example.com', 'resolved', 'normal', 4, 1, 1, '2025-01-14 14:20:00');

-- Demo Tenant 1 Sample Ticket Messages
INSERT INTO `ticket_messages` (`tenant_id`, `ticket_id`, `sender_type`, `sender_user_id`, `body_text`, `created_at`) VALUES
(1, 1, 'customer', NULL, 'I cannot login to my account. I keep getting an error message.', '2025-01-15 10:30:00'),
(1, 1, 'agent', 4, 'Hi Sarah, I apologize for the inconvenience. Can you please tell me what error message you are seeing?', '2025-01-15 10:45:00'),
(1, 2, 'customer', NULL, 'I have a question about my latest invoice. Why was I charged twice?', '2025-01-14 14:20:00'),
(1, 2, 'agent', 4, 'Hi Mike, I have reviewed your account and found that one charge was a duplicate. I have processed a refund. You should see it in 3-5 business days.', '2025-01-14 14:35:00'),
(1, 2, 'customer', NULL, 'Thank you so much! That resolves my issue.', '2025-01-14 14:40:00');

-- Update ticket with response times
UPDATE `tickets` SET `first_response_at` = '2025-01-15 10:45:00', `last_agent_reply_at` = '2025-01-15 10:45:00', `last_customer_reply_at` = '2025-01-15 10:30:00' WHERE `id` = 1;
UPDATE `tickets` SET `first_response_at` = '2025-01-14 14:35:00', `resolved_at` = '2025-01-14 14:40:00', `last_agent_reply_at` = '2025-01-14 14:35:00', `last_customer_reply_at` = '2025-01-14 14:40:00' WHERE `id` = 2;

-- Demo Tenant 1 SLA Status
INSERT INTO `ticket_sla_status` (`tenant_id`, `ticket_id`, `policy_id`, `first_response_due_at`, `first_response_met`, `resolution_due_at`, `resolution_met`, `breached`) VALUES
(1, 1, 1, '2025-01-15 11:30:00', 1, '2025-01-15 18:30:00', 0, 0),
(1, 2, 1, '2025-01-14 15:20:00', 1, '2025-01-14 22:20:00', 1, 0);

-- Demo Tenant 1 Canned Responses
INSERT INTO `canned_responses` (`tenant_id`, `shortcode`, `title`, `content`, `created_by_user_id`) VALUES
(1, 'welcome', 'Welcome Message', 'Thank you for contacting Acme Corporation support. We have received your request and will respond shortly.', 2),
(1, 'closing', 'Ticket Closing', 'We are glad we could help! If you have any other questions, please do not hesitate to reach out.', 2);

-- Demo Tenant 2: TechStart Inc
INSERT INTO `tenants` (`id`, `name`, `code`, `primary_contact_name`, `primary_contact_email`, `default_timezone`, `status`) VALUES
(2, 'TechStart Inc', 'techstart', 'Emily Chen', 'emily@techstart.io', 'America/Los_Angeles', 'active');

INSERT INTO `tenant_subscriptions` (`tenant_id`, `plan_id`, `status`, `start_date`, `renewal_date`) VALUES
(2, 1, 'trialing', '2025-01-20', '2025-02-20');

INSERT INTO `tenant_usage` (`tenant_id`, `current_ticket_count_month`, `current_ai_tokens_used_month`, `reset_date`) VALUES
(2, 0, 0, '2025-02-20');

INSERT INTO `users` (`tenant_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(2, 'Emily Chen', 'emily@techstart.io', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 'active');

-- Demo Routing Rule
INSERT INTO `routing_rules` (`tenant_id`, `name`, `is_active`, `priority_order`, `criteria_json`, `action_json`) VALUES
(1, 'Billing to Bob', 1, 1, '{"field":"subject","operator":"contains","value":"billing"}', '{"action":"assign_user","user_id":3}');

-- Demo Activity Logs
INSERT INTO `activity_logs` (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `description`, `created_at`) VALUES
(1, 4, 'ticket', 1, 'created', 'Ticket TCK-10001 created', '2025-01-15 10:30:00'),
(1, 4, 'ticket', 1, 'assigned', 'Ticket TCK-10001 assigned to Alice Agent', '2025-01-15 10:31:00'),
(1, 4, 'ticket', 2, 'status_changed', 'Ticket TCK-10002 status changed to resolved', '2025-01-14 14:40:00');
