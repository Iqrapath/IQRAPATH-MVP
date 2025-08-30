@echo off
title IQRAPATH Development Environment
color 0A

echo.
echo ========================================
echo   IQRAPATH Development Environment
echo ========================================
echo.
echo Starting all development services...
echo.

:: Kill existing processes on ports to avoid conflicts
echo Cleaning up existing processes...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":8000"') do taskkill /f /pid %%a >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| find ":8080"') do taskkill /f /pid %%a >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| find ":5173"') do taskkill /f /pid %%a >nul 2>&1

echo.
echo Services to start:
echo   * Laravel Development Server (port 8000)
echo   * Vite Development Server (port 5173)
echo   * Queue Worker (database)
echo   * Reverb WebSocket Server (port 8080)
echo.

:: Start Reverb WebSocket server in background
echo Starting Reverb WebSocket server...
start "Reverb Server" /min cmd /c "php artisan reverb:start --debug"

:: Wait a moment
timeout /t 2 /nobreak >nul

:: Start the main development environment (Laravel + Vite + Queue)
echo Starting Laravel development environment...
echo.
echo ========================================
echo   Development servers are running!
echo ========================================
echo   Laravel App: http://localhost:8000
echo   Vite Dev Server: http://localhost:5173
echo   WebSocket Server: ws://localhost:8080
echo ========================================
echo   Press Ctrl+C to stop all services
echo ========================================
echo.

:: This will run the main services (Laravel, Vite, Queue)
composer run dev
