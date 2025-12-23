# Run Android App (after start-dev.ps1 is running)

Write-Host "Building and running React Native app on Android..." -ForegroundColor Green
Write-Host "Make sure:" -ForegroundColor Yellow
Write-Host "  1. Android emulator is running OR" -ForegroundColor Yellow
Write-Host "  2. Physical device is connected with USB debugging" -ForegroundColor Yellow
Write-Host "`nPress any key to continue or Ctrl+C to cancel..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

Write-Host "`nRunning on Android..." -ForegroundColor Green
npx react-native run-android
