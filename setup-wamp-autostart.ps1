# Setup WAMP to Auto-Start with Windows
# This script creates a Windows Task Scheduler task to start WAMP on boot

Write-Host "Setting up WAMP Auto-Start..." -ForegroundColor Cyan

# Task configuration
$taskName = "WAMP Server Auto-Start"
$wampPath = "C:\wamp64\wampmanager.exe"

# Check if WAMP exists
if (-not (Test-Path $wampPath)) {
    Write-Host "ERROR: WAMP not found at $wampPath" -ForegroundColor Red
    Write-Host "Please update the wampPath variable if WAMP is installed elsewhere." -ForegroundColor Yellow
    exit 1
}

# Remove existing task if it exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Removing existing task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create action to run WAMP
$action = New-ScheduledTaskAction -Execute $wampPath

# Create trigger to run at logon
$trigger = New-ScheduledTaskTrigger -AtLogOn

# Create settings
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable:$false

# Get current user
$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -RunLevel Highest

# Register the task
Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description "Automatically starts WAMP Server when Windows starts"

Write-Host "`n✅ WAMP Auto-Start configured successfully!" -ForegroundColor Green
Write-Host "`nTask Details:" -ForegroundColor Cyan
Write-Host "  Name: $taskName" -ForegroundColor White
Write-Host "  Trigger: At user logon" -ForegroundColor White
Write-Host "  Action: Start WAMP ($wampPath)" -ForegroundColor White
Write-Host "  Run Level: Administrator (Highest)" -ForegroundColor White
Write-Host "`nTo verify, open Task Scheduler and look for: $taskName" -ForegroundColor Yellow
Write-Host "To remove autostart, run: Unregister-ScheduledTask -TaskName '$taskName' -Confirm:`$false" -ForegroundColor Yellow
