# IQRAPATH Development Environment Launcher
# Run all development services with a single command

Write-Host "Starting IQRAPATH Development Environment..." -ForegroundColor Green

# Function to check if a port is in use
function Test-Port {
    param($Port)
    try {
        $connection = New-Object System.Net.Sockets.TcpClient
        $connection.Connect("localhost", $Port)
        $connection.Close()
        return $true
    } catch {
        return $false
    }
}

# Function to kill process on port
function Stop-ProcessOnPort {
    param($Port, $ServiceName)
    try {
        $process = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess
        if ($process) {
            Write-Host "Stopping existing $ServiceName on port $Port..." -ForegroundColor Yellow
            Stop-Process -Id $process -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 2
        }
    } catch {
        # Port is available
    }
}

# Check and stop services on required ports
Stop-ProcessOnPort 8000 "Laravel Server"
Stop-ProcessOnPort 8080 "Reverb Server"
Stop-ProcessOnPort 5173 "Vite Dev Server"

Write-Host "Services to start:" -ForegroundColor Cyan
Write-Host "   - Laravel Development Server (port 8000)" -ForegroundColor White
Write-Host "   - Vite Development Server (port 5173)" -ForegroundColor White
Write-Host "   - Queue Worker (database)" -ForegroundColor White
Write-Host "   - Reverb WebSocket Server (port 8080)" -ForegroundColor White
Write-Host ""

# Create jobs array to track background processes
$jobs = @()

try {
    # Get current directory for job scripts
    $currentDir = Get-Location
    
    # Start Laravel server with Vite and Queue worker using composer script
    Write-Host "Starting Laravel server, Vite, and Queue worker..." -ForegroundColor Magenta
    $composerJob = Start-Job -ScriptBlock {
        param($dir)
        Set-Location $dir
        composer run dev
    } -ArgumentList $currentDir
    $jobs += $composerJob
    Write-Host "Laravel development services started" -ForegroundColor Green

    # Wait a moment for services to initialize
    Start-Sleep -Seconds 3

    # Start Reverb server with debug
    Write-Host "Starting Reverb WebSocket server..." -ForegroundColor Magenta
    $reverbJob = Start-Job -ScriptBlock {
        param($dir)
        Set-Location $dir
        php artisan reverb:start --debug
    } -ArgumentList $currentDir
    $jobs += $reverbJob
    Write-Host "Reverb WebSocket server started with debug mode" -ForegroundColor Green

    Write-Host ""
    Write-Host "All services started successfully!" -ForegroundColor Green
    Write-Host "Application URLs:" -ForegroundColor Cyan
    Write-Host "   - Laravel App: http://localhost:8000" -ForegroundColor White
    Write-Host "   - Vite Dev Server: http://localhost:5173" -ForegroundColor White
    Write-Host "   - WebSocket Server: ws://localhost:8080" -ForegroundColor White
    Write-Host ""
    Write-Host "Press Ctrl+C to stop all services" -ForegroundColor Yellow
    Write-Host ""

    # Monitor services and keep script running
    Write-Host "Monitoring services..." -ForegroundColor Blue
    
    while ($true) {
        # Check if any job has failed
        $failedJobs = $jobs | Where-Object { $_.State -eq "Failed" -or $_.State -eq "Stopped" }
        
        if ($failedJobs.Count -gt 0) {
            Write-Host ""
            Write-Host "One or more services have stopped!" -ForegroundColor Red
            foreach ($job in $failedJobs) {
                Write-Host "   - Job ID $($job.Id) is $($job.State)" -ForegroundColor Yellow
                $error = Receive-Job $job -ErrorAction SilentlyContinue
                if ($error) {
                    Write-Host "     Error: $error" -ForegroundColor Red
                }
            }
            break
        }

        # Show periodic status
        $runningCount = ($jobs | Where-Object { $_.State -eq "Running" }).Count
        $totalJobs = $jobs.Count
        $currentTime = Get-Date -Format 'HH:mm:ss'
        Write-Host "Services running: $runningCount/$totalJobs - $currentTime" -ForegroundColor Gray
        
        Start-Sleep -Seconds 30
    }

} catch {
    Write-Host ""
    Write-Host "Error occurred: $($_.Exception.Message)" -ForegroundColor Red
} finally {
    # Cleanup: Stop all background jobs
    Write-Host ""
    Write-Host "Stopping all services..." -ForegroundColor Yellow
    
    foreach ($job in $jobs) {
        if ($job.State -eq "Running") {
            Write-Host "   - Stopping Job ID $($job.Id)..." -ForegroundColor Gray
            Stop-Job $job -ErrorAction SilentlyContinue
            Remove-Job $job -Force -ErrorAction SilentlyContinue
        }
    }
    
    # Kill any remaining processes on our ports
    Stop-ProcessOnPort 8000 "Laravel Server"
    Stop-ProcessOnPort 8080 "Reverb Server"
    Stop-ProcessOnPort 5173 "Vite Dev Server"
    
    Write-Host "All services stopped" -ForegroundColor Green
    Write-Host "Development environment shutdown complete" -ForegroundColor Cyan
}
