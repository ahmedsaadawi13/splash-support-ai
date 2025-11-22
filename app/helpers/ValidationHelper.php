<?php
// FILE: /app/helpers/ValidationHelper.php

/**
 * SplashSupportAI - Validation Helper
 * Handles input validation
 */

class ValidationHelper {

    /**
     * Validate email
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate required field
     */
    public static function required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }

    /**
     * Validate minimum length
     */
    public static function minLength($value, $min) {
        return mb_strlen($value) >= $min;
    }

    /**
     * Validate maximum length
     */
    public static function maxLength($value, $max) {
        return mb_strlen($value) <= $max;
    }

    /**
     * Validate phone number (simple)
     */
    public static function phone($phone) {
        return preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/', $phone);
    }

    /**
     * Validate URL
     */
    public static function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     */
    public static function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate float
     */
    public static function float($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Validate enum value
     */
    public static function enum($value, $allowedValues) {
        return in_array($value, $allowedValues);
    }

    /**
     * Validate date format
     */
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate password strength
     */
    public static function passwordStrength($password, $minLength = 8) {
        if (strlen($password) < $minLength) {
            return false;
        }
        // At least one letter and one number
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }
        return true;
    }

    /**
     * Validate multiple fields
     */
    public static function validate($data, $rules) {
        $errors = array();

        foreach ($rules as $field => $fieldRules) {
            $value = isset($data[$field]) ? $data[$field] : null;
            $fieldRules = explode('|', $fieldRules);

            foreach ($fieldRules as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParams = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : array();

                switch ($ruleName) {
                    case 'required':
                        if (!self::required($value)) {
                            $errors[$field][] = ucfirst($field) . ' is required';
                        }
                        break;
                    case 'email':
                        if ($value && !self::email($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email';
                        }
                        break;
                    case 'min':
                        if ($value && !self::minLength($value, $ruleParams[0])) {
                            $errors[$field][] = ucfirst($field) . ' must be at least ' . $ruleParams[0] . ' characters';
                        }
                        break;
                    case 'max':
                        if ($value && !self::maxLength($value, $ruleParams[0])) {
                            $errors[$field][] = ucfirst($field) . ' must not exceed ' . $ruleParams[0] . ' characters';
                        }
                        break;
                    case 'integer':
                        if ($value && !self::integer($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be an integer';
                        }
                        break;
                    case 'enum':
                        if ($value && !self::enum($value, $ruleParams)) {
                            $errors[$field][] = ucfirst($field) . ' must be one of: ' . implode(', ', $ruleParams);
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
