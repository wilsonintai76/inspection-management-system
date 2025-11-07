# Installing PhpSpreadsheet for Excel Support

## Overview
The Asset Inspection upload feature currently supports CSV files natively. For Excel (.xlsx, .xls) support, you need to install the PhpSpreadsheet library via Composer.

## Quick Setup (WAMP Environment)

### 1. Install Composer
If you don't have Composer installed:
1. Download from: https://getcomposer.org/download/
2. Run the installer
3. Add to PATH if needed

### 2. Navigate to Backend Directory
```bash
cd C:\wamp64\www\inspectable-api
```

### 3. Initialize Composer (if no composer.json exists)
```bash
composer init
```
Press Enter for all prompts to accept defaults.

### 4. Install PhpSpreadsheet
```bash
composer require phpoffice/phpspreadsheet
```

This will:
- Create `vendor/` directory
- Download PhpSpreadsheet and dependencies
- Generate `composer.lock`

### 5. Update asset-uploads.php (if needed)
The code already checks for PhpSpreadsheet. Once installed, Excel upload will work automatically.

## Alternative: CSV Only
If you prefer not to install Composer/PhpSpreadsheet:
- Users can convert Excel files to CSV before uploading
- Most spreadsheet programs (Excel, Google Sheets, LibreOffice) can export to CSV
- This keeps the system simple with zero dependencies

## Verifying Installation
After installing PhpSpreadsheet, try uploading a .xlsx file:
1. Login as Admin
2. Go to Asset Inspection → Upload New Data
3. Select an Excel file
4. Upload should succeed

## Troubleshooting

### Error: "Excel support not installed"
- PhpSpreadsheet is not installed
- Run `composer require phpoffice/phpspreadsheet` in the backend directory

### Error: "Failed to parse Excel"
- File may be corrupted
- Check that headers are in the first row
- Try converting to CSV and uploading that instead

### Composer Not Found
- Install Composer: https://getcomposer.org/
- On Windows with WAMP, you may need to restart your terminal after installation

## Production Deployment
When deploying to production:
1. Copy the entire `vendor/` directory
2. Or run `composer install` on the production server
3. Ensure PHP has ZIP extension enabled (required by PhpSpreadsheet)

## File Size Limits
For large Excel files, check PHP settings:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

Adjust in `php.ini` as needed.
