# Setup script to add 'run app' command to PowerShell
# Run this once to enable the 'run app' command

Write-Host "Setting up 'run app' command for IQRAPATH development..." -ForegroundColor Green

# Get PowerShell profile path
$profilePath = $PROFILE

# Create profile directory if it doesn't exist
$profileDir = Split-Path $profilePath
if (!(Test-Path $profileDir)) {
    New-Item -ItemType Directory -Path $profileDir -Force | Out-Null
    Write-Host "Created PowerShell profile directory: $profileDir" -ForegroundColor Yellow
}

# Function to add to PowerShell profile
$functionCode = @'

# IQRAPATH Development Environment
function run {
    param([string]$command)
    
    if ($command -eq "app") {
        $scriptPath = Join-Path (Get-Location) "run-app.ps1"
        if (Test-Path $scriptPath) {
            Write-Host "Starting IQRAPATH development environment..." -ForegroundColor Green
            & $scriptPath
        } else {
            Write-Host "Error: run-app.ps1 not found in current directory" -ForegroundColor Red
            Write-Host "Make sure you're in the IQRAPATH project directory" -ForegroundColor Yellow
        }
    } else {
        Write-Host "Available commands:" -ForegroundColor Cyan
        Write-Host "  run app    - Start IQRAPATH development environment" -ForegroundColor White
    }
}

# Alias for convenience
Set-Alias -Name runapp -Value run

'@

# Check if the function already exists in profile
$profileExists = Test-Path $profilePath
$functionExists = $false

if ($profileExists) {
    $existingContent = Get-Content $profilePath -Raw
    $functionExists = $existingContent -match "function run"
}

if (!$functionExists) {
    # Add function to PowerShell profile
    Add-Content -Path $profilePath -Value $functionCode
    Write-Host "✅ Added 'run app' command to PowerShell profile" -ForegroundColor Green
} else {
    Write-Host "⚠️  'run app' command already exists in PowerShell profile" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Setup complete! You can now use:" -ForegroundColor Cyan
Write-Host "  • run app      - Start all development services" -ForegroundColor White  
Write-Host "  • runapp       - Same as above (alias)" -ForegroundColor White
Write-Host ""
Write-Host "Note: Restart your PowerShell terminal or run:" -ForegroundColor Yellow
Write-Host "  . `$PROFILE" -ForegroundColor Gray
Write-Host ""
Write-Host "Then navigate to your IQRAPATH project directory and run:" -ForegroundColor Yellow
Write-Host "  run app" -ForegroundColor White
