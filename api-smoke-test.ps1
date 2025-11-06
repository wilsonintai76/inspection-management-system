# Simple API smoke test for Inspectable PHP backend
# Run in PowerShell (no admin required)

$base = 'http://inspectable.local'
$api  = "$base/api"

function Write-Step($msg) { Write-Host "[STEP] $msg" -ForegroundColor Cyan }
function Write-Ok($msg)   { Write-Host "[ OK ] $msg" -ForegroundColor Green }
function Write-Err($msg)  { Write-Host "[ERR ] $msg" -ForegroundColor Red }

try {
  Write-Step "GET $base/"
  $resp = Invoke-WebRequest -UseBasicParsing -Uri "$base/" -TimeoutSec 8
  if ($resp.StatusCode -ne 200 -or ($resp.Content -notmatch 'Inspectable API')) { throw "Unexpected response from API root." }
  Write-Ok "API root reachable (200)"
}
catch { Write-Err $_; exit 1 }

try {
  Write-Step "GET $api/departments.php"
  $deps = Invoke-RestMethod -Method Get -Uri "$api/departments.php" -TimeoutSec 8
  Write-Ok ("Departments GET ok (count ~ {0})" -f ($deps | Measure-Object | Select-Object -ExpandProperty Count))
}
catch { Write-Err $_; exit 1 }

# Create a temp department, read it back, then delete
$tempName = "Smoke Dept $(Get-Date -Format 'HHmmss')"
try {
  Write-Step "POST $api/departments.php (name=$tempName)"
  $created = Invoke-RestMethod -Method Post -Uri "$api/departments.php" -ContentType 'application/json' -Body (@{ name = $tempName; acronym = 'SMK' } | ConvertTo-Json -Depth 3)
  if (-not $created.id) { throw "Create response missing id" }
  $id = [int]$created.id
  Write-Ok "Created department id=$id"

  Write-Step "GET $api/departments.php?id=$id"
  $dep = Invoke-RestMethod -Method Get -Uri ("$api/departments.php?id={0}" -f $id)
  if ($dep.name -ne $tempName) { throw "Fetched name mismatch" }
  Write-Ok "Fetched department matches"

  Write-Step "DELETE $api/departments.php?id=$id"
  $del = Invoke-RestMethod -Method Delete -Uri ("$api/departments.php?id={0}" -f $id)
  Write-Ok "Deleted department id=$id"
}
catch { Write-Err $_; exit 1 }

# Quick read checks for other endpoints
foreach ($ep in @('locations','inspections','users')) {
  try {
    Write-Step ("GET {0}" -f ("$api/{0}.php" -f $ep))
    $r = Invoke-RestMethod -Method Get -Uri ("$api/{0}.php" -f $ep) -TimeoutSec 8
    Write-Ok ("{0} GET ok (count ~ {1})" -f $ep, ($r | Measure-Object | Select-Object -ExpandProperty Count))
  } catch { Write-Err ("{0} GET failed: {1}" -f $ep, $_); exit 1 }
}

Write-Ok "All smoke tests passed"
