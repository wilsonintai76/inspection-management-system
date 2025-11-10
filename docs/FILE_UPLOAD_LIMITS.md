# File Upload Limits Implementation

## Overview
Implemented comprehensive file upload validation to protect against storage exhaustion and security vulnerabilities. This is critical for hosting on InfinityFree with a 5GB storage limit.

## File Size Limits

### Per-File Limit: 10MB
- Maximum size for any single uploaded file
- Applies to all upload endpoints

### Total Upload Limit: 50MB
- Maximum combined size for bulk/multiple file uploads
- Prevents abuse of batch upload endpoints

## Security Features

### MIME Type Validation
- Uses PHP's `finfo_file()` for accurate MIME detection
- Not relying solely on file extensions (prevents spoofing)
- Validates MIME type matches expected extension

### Allowed File Types

#### Data Import Files (Asset Uploads, Department Bulk Import)
- CSV: `text/csv`, `text/plain`
- Excel 2007+: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Excel 97-2003: `application/vnd.ms-excel`

#### Summary Documents (Department Summary)
- PDF: `application/pdf`
- JPEG: `image/jpeg`
- PNG: `image/png`
- GIF: `image/gif`
- WebP: `image/webp`
- Word 2007+: `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
- Word 97-2003: `application/msword`
- Excel (see above)

## Implementation

### Backend Components

#### 1. UploadValidator Class
**Location:** `php-backend/src/upload-validator.php`

**Methods:**
- `validateFile($file, $options)` - Validates single file
- `validateMultipleFiles($files, $options)` - Validates batch uploads with total size check
- `validateFileType($tmpPath, $filename, $allowedTypes)` - MIME detection and validation

**Configuration:**
```php
const MAX_FILE_SIZE = 10 * 1024 * 1024;      // 10MB
const MAX_TOTAL_SIZE = 50 * 1024 * 1024;     // 50MB
```

#### 2. Integration in Upload APIs

**asset-uploads.php:**
```php
require_once __DIR__ . '/../../src/upload-validator.php';

$validation = UploadValidator::validateMultipleFiles($files, [
    'allowed_types' => ['csv', 'xlsx', 'xls'],
    'max_size' => 10 * 1024 * 1024,
    'max_total_size' => 50 * 1024 * 1024
]);

if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['error' => $validation['error']]);
    exit;
}
```

**department-bulk-import.php:**
- Same validation as asset-uploads.php
- Only allows CSV and Excel files

**department-summary.php:**
- Allows broader file types (PDF, images, Word, Excel)
- Same size limits apply

### Frontend Updates

#### 1. AssetUpload.vue
Added file size notice to requirements:
```
- Format: Excel (.xlsx, .xls) or CSV
- File size limit: Max 10MB per file, 50MB total
```

#### 2. Departments.vue - Bulk Import
Added file size notice:
```
- Format: Excel (.xlsx, .xls) or CSV
- File size limit: Max 10MB per file, 50MB total
```

#### 3. Departments.vue - Summary Upload
Added file size notice:
```
Select multiple files. Supported: PDF, JPG, PNG, Excel, Word. Max 10MB per file.
```

## Error Messages

### User-Friendly Feedback
The validator provides clear error messages for common issues:
- "File exceeds maximum size of 10MB"
- "Total upload size exceeds maximum of 50MB"
- "File type not allowed. Expected: CSV, XLSX, XLS"
- "File type does not match extension"
- Standard PHP upload errors (e.g., "No file uploaded", "Server error")

## Deployment

### Files Deployed to Apache (C:\wamp64\www\inspectable-api\)
- `src/upload-validator.php` (new)
- `api/asset-uploads.php` (modified)
- `api/department-bulk-import.php` (modified)
- `api/department-summary.php` (modified)

### Frontend Files
- `vue-frontend/src/views/AssetUpload.vue` (modified)
- `vue-frontend/src/views/Departments.vue` (modified)

## Testing Checklist

### Backend Validation
- [ ] Upload file > 10MB (should reject)
- [ ] Upload multiple files > 50MB total (should reject)
- [ ] Upload invalid file type (e.g., .exe, .zip) (should reject MIME mismatch)
- [ ] Rename .exe to .xlsx (should reject MIME mismatch)
- [ ] Upload valid CSV file (should succeed)
- [ ] Upload valid Excel file (should succeed)
- [ ] Upload valid PDF to summary (should succeed)

### Frontend Display
- [ ] File size limits visible on AssetUpload page
- [ ] File size limits visible on Bulk Import dialog
- [ ] File size limits visible on Summary Upload dialog
- [ ] Error messages displayed to user when validation fails

### API Responses
- [ ] 400 status code returned on validation failure
- [ ] Error message in JSON response
- [ ] Descriptive error helps user fix the issue

## Benefits

### Storage Protection
- Prevents accidental/malicious large file uploads
- Protects against hitting InfinityFree's 5GB limit
- Allows monitoring and planning for storage usage

### Security
- MIME validation prevents file type spoofing
- Reduces attack surface for malicious file uploads
- File extension validation ensures expected formats

### User Experience
- Clear file size limits prevent wasted upload attempts
- Descriptive error messages help users fix issues
- Frontend validation guides could be added next

## Future Enhancements

### Phase 2 (Recommended)
1. **Magic Byte Validation:** Check file signatures beyond MIME
2. **Frontend Pre-Validation:** Validate file size in browser before upload
3. **Progress Indicators:** Show upload progress for large files
4. **Virus Scanning:** Integrate ClamAV or similar if available

### Phase 3 (Advanced)
1. **Compression:** Auto-compress images before storage
2. **External Storage:** Move to Cloudinary/ImgBB for images
3. **File Quarantine:** Isolate suspicious uploads for admin review
4. **Upload Logging:** Track all upload attempts with user/IP

## Related Optimizations

This is **Optimization #1** from the InfinityFree hosting plan.

**Next Steps:**
- #2: Asset list pagination (reduce query load)
- #3: Storage monitoring dashboard (track remaining capacity)
- #4: Database indexes (improve query performance)

See `README.md` for the complete optimization roadmap.
