# Test Asset Upload Script
$uri = "http://localhost/inspectable-api/api/asset-uploads.php"
$filePath = "C:\wamp64\www\test_upload.csv"
$userId = "2004"  # Admin user
$notes = "Test upload - ABP 30 Sept 2025 - 400 assets"

# Create form data
$form = @{
    file = Get-Item -Path $filePath
    user_id = $userId
    notes = $notes
}

try {
    $response = Invoke-RestMethod -Uri $uri -Method Post -Form $form
    Write-Host "✓ Upload successful!" -ForegroundColor Green
    Write-Host "Batch ID: $($response.batch_id)"
    Write-Host "Total Records: $($response.total_records)"
    Write-Host "Message: $($response.message)"
} catch {
    Write-Host "✗ Upload failed!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.ErrorDetails.Message) {
        Write-Host "Details: $($_.ErrorDetails.Message)"
    }
}
