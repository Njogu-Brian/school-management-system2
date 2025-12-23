# Quick Start Script for React Native App

# Step 1: Start Laravel Backend (in a separate terminal)
Write-Host "Starting Laravel backend..." -ForegroundColor Green
cd ..
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve --host=0.0.0.0 --port=8000"

# Step 2: Wait a moment for backend to start
Start-Sleep -Seconds 3

# Step 3: Start Metro Bundler
Write-Host "`nStarting Metro Bundler..." -ForegroundColor Green  
Write-Host "Keep this window open!" -ForegroundColor Yellow
npm start
