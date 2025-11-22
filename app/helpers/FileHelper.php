<?php
// FILE: /app/helpers/FileHelper.php

/**
 * SplashSupportAI - File Helper
 * Handles file upload and management
 */

class FileHelper {

    /**
     * Upload file
     */
    public static function upload($file, $destination, $allowedTypes = null) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload');
        }

        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds limit');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            default:
                throw new Exception('Upload error occurred');
        }

        // Check file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('File size exceeds ' . self::formatBytes(MAX_UPLOAD_SIZE));
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowed = $allowedTypes ?? explode(',', ALLOWED_MIME_TYPES);
        if (!in_array($mimeType, $allowed)) {
            throw new Exception('File type not allowed');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = self::generateUniqueFilename($extension);
        $filepath = $destination . '/' . $filename;

        // Create directory if not exists
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return array(
            'original_name' => $file['name'],
            'stored_name' => $filename,
            'file_path' => $filepath,
            'mime_type' => $mimeType,
            'size_bytes' => $file['size']
        );
    }

    /**
     * Generate unique filename
     */
    public static function generateUniqueFilename($extension = '') {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        return $extension ? $uuid . '.' . $extension : $uuid;
    }

    /**
     * Delete file
     */
    public static function delete($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Format bytes to human readable
     */
    public static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get file extension
     */
    public static function getExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is image
     */
    public static function isImage($mimeType) {
        return strpos($mimeType, 'image/') === 0;
    }

    /**
     * Serve file download
     */
    public static function download($filepath, $filename = null) {
        if (!file_exists($filepath)) {
            throw new Exception('File not found');
        }

        $filename = $filename ?? basename($filepath);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filepath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache');

        readfile($filepath);
        exit;
    }

    /**
     * Validate file path (prevent directory traversal)
     */
    public static function validatePath($path, $allowedBasePath) {
        $realPath = realpath($path);
        $realBasePath = realpath($allowedBasePath);

        if ($realPath === false || $realBasePath === false) {
            return false;
        }

        return strpos($realPath, $realBasePath) === 0;
    }
}
