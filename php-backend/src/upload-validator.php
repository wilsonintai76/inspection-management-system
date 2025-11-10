<?php
// File upload validation helper
// Provides centralized validation for all file uploads

class UploadValidator {
    // Configuration
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB in bytes
    private const MAX_TOTAL_SIZE = 50 * 1024 * 1024; // 50MB for bulk uploads
    
    // Allowed MIME types and extensions
    private const ALLOWED_TYPES = [
        // Documents
        'application/pdf' => ['pdf'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/msword' => ['doc'],
        'text/csv' => ['csv'],
        'text/plain' => ['txt'],
        
        // Images
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp']
    ];
    
    /**
     * Validate a single uploaded file
     * @param array $file The $_FILES array element
     * @param array $options Override default options
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validateFile($file, $options = []) {
        // Extract options
        $maxSize = $options['max_size'] ?? self::MAX_FILE_SIZE;
        $allowedTypes = $options['allowed_types'] ?? self::ALLOWED_TYPES;
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => self::getUploadErrorMessage($file['error'])
            ];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / (1024 * 1024), 1);
            return [
                'valid' => false,
                'error' => "File size ({$file['name']}) exceeds maximum allowed size of {$maxMB}MB"
            ];
        }
        
        // Check if file is empty
        if ($file['size'] === 0) {
            return [
                'valid' => false,
                'error' => "File ({$file['name']}) is empty"
            ];
        }
        
        // Validate MIME type and extension
        $validation = self::validateFileType($file['tmp_name'], $file['name'], $allowedTypes);
        if (!$validation['valid']) {
            return $validation;
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Validate multiple uploaded files
     * @param array $files Array of file arrays from $_FILES
     * @param array $options Override default options
     * @return array ['valid' => bool, 'error' => string|null, 'total_size' => int]
     */
    public static function validateMultipleFiles($files, $options = []) {
        $maxTotalSize = $options['max_total_size'] ?? self::MAX_TOTAL_SIZE;
        $totalSize = 0;
        $errors = [];
        
        foreach ($files as $file) {
            // Validate individual file
            $result = self::validateFile($file, $options);
            if (!$result['valid']) {
                $errors[] = $result['error'];
                continue;
            }
            
            $totalSize += $file['size'];
        }
        
        // Check total size
        if ($totalSize > $maxTotalSize) {
            $maxMB = round($maxTotalSize / (1024 * 1024), 1);
            $actualMB = round($totalSize / (1024 * 1024), 1);
            return [
                'valid' => false,
                'error' => "Total upload size ({$actualMB}MB) exceeds maximum allowed ({$maxMB}MB)",
                'total_size' => $totalSize
            ];
        }
        
        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => implode('; ', $errors),
                'total_size' => $totalSize
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'total_size' => $totalSize
        ];
    }
    
    /**
     * Validate file type using MIME type detection and extension check
     * @param string $tmpPath Temporary file path
     * @param string $filename Original filename
     * @param array $allowedTypes Allowed MIME types
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private static function validateFileType($tmpPath, $filename, $allowedTypes) {
        // Get file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Detect MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        
        // Check if MIME type is allowed
        if (!isset($allowedTypes[$mimeType])) {
            return [
                'valid' => false,
                'error' => "File type not allowed ({$filename}). Detected: {$mimeType}"
            ];
        }
        
        // Check if extension matches MIME type
        if (!in_array($extension, $allowedTypes[$mimeType], true)) {
            return [
                'valid' => false,
                'error' => "File extension mismatch ({$filename}). Extension '{$extension}' doesn't match MIME type '{$mimeType}'"
            ];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Get human-readable upload error message
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds server upload_max_filesize limit';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary upload folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Format file size in human-readable format
     * @param int $bytes File size in bytes
     * @return string Formatted size
     */
    public static function formatFileSize($bytes) {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } else {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }
    
    /**
     * Get max file size in MB
     * @return float Max file size in MB
     */
    public static function getMaxFileSizeMB() {
        return round(self::MAX_FILE_SIZE / (1024 * 1024), 1);
    }
    
    /**
     * Get max total size in MB
     * @return float Max total size in MB
     */
    public static function getMaxTotalSizeMB() {
        return round(self::MAX_TOTAL_SIZE / (1024 * 1024), 1);
    }
    
    /**
     * Get allowed file extensions as array
     * @return array List of allowed extensions
     */
    public static function getAllowedExtensions() {
        $extensions = [];
        foreach (self::ALLOWED_TYPES as $mime => $exts) {
            $extensions = array_merge($extensions, $exts);
        }
        return array_unique($extensions);
    }
}
