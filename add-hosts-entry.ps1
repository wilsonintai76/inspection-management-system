# Add inspectable.local to Windows hosts file
# Run as Administrator

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$entry = "127.0.0.1       inspectable.local"

# Check if entry already exists
$hostsContent = Get-Content $hostsPath -Raw
if ($hostsContent -match 'inspectable\.local') {
    Write-Host "Entry already exists in hosts file." -ForegroundColor Yellow
    exit 0
}

# Backup hosts file
Copy-Item $hostsPath "$hostsPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"

# Add entry
Add-Content -Path $hostsPath -Value "`n$entry"
Write-Host "Successfully added: $entry" -ForegroundColor Green
Write-Host "Hosts file location: $hostsPath" -ForegroundColor Cyan
