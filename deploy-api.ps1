# Deploy PHP API to WAMP
# This script copies the PHP backend to the WAMP www directory

$source = "D:\Inspectable\php-backend"
$destination = "C:\wamp64\www\inspectable-api"

Write-Host "Deploying PHP API to WAMP..." -ForegroundColor Cyan

# Remove old deployment
if (Test-Path $destination) {
    Write-Host "Removing old deployment..." -ForegroundColor Yellow
    Remove-Item -Path $destination -Recurse -Force
}

# Create destination directory
New-Item -ItemType Directory -Path $destination -Force | Out-Null

# Copy files
Write-Host "Copying public folder..." -ForegroundColor Green
Copy-Item -Path "$source\public\*" -Destination $destination -Recurse -Force

Write-Host "Copying src folder..." -ForegroundColor Green
Copy-Item -Path "$source\src" -Destination "$destination\src" -Recurse -Force

Write-Host "Copying config file..." -ForegroundColor Green
Copy-Item -Path "$source\config.php" -Destination "$destination\config.php" -Force

Write-Host ""
Write-Host "✓ Deployment complete!" -ForegroundColor Green
Write-Host ""
Write-Host "API available at: http://localhost/inspectable-api" -ForegroundColor Cyan
Write-Host "Test endpoint: http://localhost/inspectable-api/index.php" -ForegroundColor Cyan
