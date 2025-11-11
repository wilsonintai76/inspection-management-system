<#
  Script: start-vue-clean-port.ps1
  Purpose: Ensure port 5176 is free, then start the Vue dev server on that port.
  Usage: Right-click -> Run with PowerShell OR `powershell -ExecutionPolicy Bypass -File .\scripts\start-vue-clean-port.ps1`
#>

param(
  [int]$Port = 5176,
  [switch]$OpenBrowser,
  [string]$ProjectPath = "d:\Inspectable\vue-frontend"
)

Write-Host "=== Vue Dev Server Startup (Port $Port) ===" -ForegroundColor Cyan

function Get-PortPids($port) {
  try {
    $connections = Get-NetTCPConnection -LocalPort $port -ErrorAction SilentlyContinue
    if (!$connections) { return @() }
    $pids = $connections | Select-Object -ExpandProperty OwningProcess -Unique
    return $pids
  } catch {
    return @()
  }
}

function Kill-Port($port) {
  $pids = Get-PortPids $port
  if ($pids.Count -eq 0) {
    Write-Host "Port $port is already free." -ForegroundColor Green
    return
  }
  Write-Host "Killing processes on port $port: $($pids -join ', ')" -ForegroundColor Yellow
  foreach ($pid in $pids) {
    try {
      Stop-Process -Id $pid -Force -ErrorAction Stop
      Write-Host " - Killed PID $pid" -ForegroundColor DarkYellow
    } catch {
      Write-Warning "Failed to kill PID $pid: $($_.Exception.Message)"
    }
  }
  Start-Sleep -Milliseconds 500
  if ((Get-PortPids $port).Count -eq 0) {
    Write-Host "Port $port successfully freed." -ForegroundColor Green
  } else {
    Write-Warning "Port $port still occupied after kill attempts."
  }
}

# 1. Ensure running from project root
if (-not (Test-Path $ProjectPath)) {
  Write-Error "Project path not found: $ProjectPath"
  exit 1
}
Set-Location $ProjectPath

# 2. Kill existing listeners
Kill-Port $Port

# 3. Start Vite dev server with strict port
Write-Host "Starting Vite dev server on port $Port..." -ForegroundColor Cyan
$env:NODE_OPTIONS=""
$startInfo = New-Object System.Diagnostics.ProcessStartInfo
$startInfo.FileName = "npm"
$startInfo.Arguments = "run dev"
$startInfo.WorkingDirectory = $ProjectPath
$startInfo.UseShellExecute = $false
$startInfo.RedirectStandardOutput = $true
$startInfo.RedirectStandardError = $true
$process = New-Object System.Diagnostics.Process
$process.StartInfo = $startInfo
$null = $process.Start()

# 4. Async read output first few lines to confirm port binding
$bound = $false
$deadline = (Get-Date).AddSeconds(15)
while (-not $process.HasExited) {
  if ($process.StandardOutput.Peek() -ge 0) {
    $line = $process.StandardOutput.ReadLine()
    if ($line) { Write-Host $line }
    if ($line -match "Local:\s+http://localhost:$Port/") {
      $bound = $true
      break
    }
  }
  if ((Get-Date) -gt $deadline) { break }
  Start-Sleep -Milliseconds 100
}

if (-not $bound) {
  Write-Warning "Dev server did not confirm binding on $Port within timeout. Check output above."
} else {
  Write-Host "Dev server running at http://localhost:$Port" -ForegroundColor Green
}

if ($OpenBrowser -and $bound) {
  Write-Host "Opening browser..." -ForegroundColor Cyan
  Start-Process "http://localhost:$Port/"
}

Write-Host "(Process continues in background. Press Ctrl+C to terminate manually if needed.)" -ForegroundColor DarkGray
