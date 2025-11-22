<?php
// FILE: /app/helpers/DateHelper.php

/**
 * SplashSupportAI - Date Helper
 * Handles date/time formatting and timezone conversions
 */

class DateHelper {

    /**
     * Convert UTC to tenant timezone
     */
    public static function toTenantTimezone($utcDate, $timezone = null) {
        if (!$timezone) {
            $timezone = DEFAULT_TIMEZONE;
        }

        try {
            $date = new DateTime($utcDate, new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($timezone));
            return $date;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Format date for display
     */
    public static function format($date, $format = 'Y-m-d H:i:s', $timezone = null) {
        if (is_string($date)) {
            $dateObj = self::toTenantTimezone($date, $timezone);
            if ($dateObj) {
                return $dateObj->format($format);
            }
            return $date;
        }
        return $date->format($format);
    }

    /**
     * Format date as relative time (e.g., "2 hours ago")
     */
    public static function timeAgo($datetime, $timezone = null) {
        $dateObj = self::toTenantTimezone($datetime, $timezone);
        if (!$dateObj) {
            return 'Unknown';
        }

        $now = new DateTime('now', new DateTimeZone($timezone ?? DEFAULT_TIMEZONE));
        $diff = $now->diff($dateObj);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return 'Just now';
    }

    /**
     * Add minutes to datetime
     */
    public static function addMinutes($datetime, $minutes) {
        $date = new DateTime($datetime);
        $date->modify("+{$minutes} minutes");
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Calculate SLA due time
     */
    public static function calculateSLADue($createdAt, $slaMinutes) {
        return self::addMinutes($createdAt, $slaMinutes);
    }

    /**
     * Check if SLA is breached
     */
    public static function isSLABreached($dueAt) {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $due = new DateTime($dueAt, new DateTimeZone('UTC'));
        return $now > $due;
    }

    /**
     * Get current UTC datetime
     */
    public static function now() {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * Get first day of current month
     */
    public static function firstDayOfMonth() {
        return gmdate('Y-m-01 00:00:00');
    }

    /**
     * Get last day of current month
     */
    public static function lastDayOfMonth() {
        return gmdate('Y-m-t 23:59:59');
    }

    /**
     * Format duration in minutes to human readable
     */
    public static function formatDuration($minutes) {
        if ($minutes < 60) {
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        $result = $hours . ' hour' . ($hours != 1 ? 's' : '');
        if ($mins > 0) {
            $result .= ' ' . $mins . ' minute' . ($mins != 1 ? 's' : '');
        }

        return $result;
    }
}
