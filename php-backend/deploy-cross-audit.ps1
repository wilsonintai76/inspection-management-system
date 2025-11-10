# Quick deployment script to copy new cross-audit files
# Run this script to update the Apache-served files

Write-Host "Cross-Audit Deployment Script" -ForegroundColor Cyan
Write-Host "============================`n" -ForegroundColor Cyan

# Files that need to be deployed
$files = @(
    "public\api\cross-audit.php",
    "public\api\eligible-auditors.php",
    "public\api\inspections.php"
)

# Common Apache paths (check which one exists)
$possiblePaths = @(
    "C:\wamp64\www\inspectable-api",
    "C:\xampp\htdocs\inspectable-api",
    "C:\laragon\www\inspectable-api",
    "C:\wamp\www\inspectable-api"
)

$apachePath = $null
foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $apachePath = $path
        Write-Host "✓ Found Apache directory: $apachePath" -ForegroundColor Green
        break
    }
}

if (-not $apachePath) {
    Write-Host "✗ Could not find Apache document root" -ForegroundColor Red
    Write-Host "`nPlease manually copy these files:" -ForegroundColor Yellow
    foreach ($file in $files) {
        Write-Host "  - $file"
    }
    Write-Host "`nTo your Apache inspectable-api directory" -ForegroundColor Yellow
    exit 1
}

# Copy files
Write-Host "`nCopying files..." -ForegroundColor Cyan
foreach ($file in $files) {
    $source = Join-Path (Get-Location) $file
    $dest = Join-Path $apachePath $file.Replace("public\", "")
    
    if (Test-Path $source) {
        # Create directory if needed
        $destDir = Split-Path $dest -Parent
        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        
        Copy-Item $source $dest -Force
        Write-Host "  ✓ Copied: $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Not found: $file" -ForegroundColor Red
    }
}

Write-Host "`n✓ Deployment complete!" -ForegroundColor Green
Write-Host "Test the API at: http://localhost/inspectable-api/api/cross-audit.php" -ForegroundColor Cyan
