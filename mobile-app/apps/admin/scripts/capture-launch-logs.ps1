# Capture Android logcat while launching Royal Kings Admin.
# Usage: connect phone via USB (USB debugging ON), then run from apps/admin:
#   npm run logs:android

$ErrorActionPreference = "Stop"
$adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"
$package = "com.royalkingsschools.admin"

if (-not (Test-Path $adb)) {
  Write-Error "adb not found at $adb. Install Android SDK platform-tools."
}

Write-Host "Waiting for Android device..."
& $adb wait-for-device
$devices = & $adb devices | Select-String "device$"
if (-not $devices) {
  Write-Error "No device authorized. On your phone: allow USB debugging and set USB mode to File transfer."
}

Write-Host "Clearing logcat..."
& $adb logcat -c

Write-Host "Launching $package ..."
& $adb shell monkey -p $package -c android.intent.category.LAUNCHER 1 | Out-Null

Write-Host "Capturing logs for 25 seconds (React Native / Expo / crashes)..."
$logFile = Join-Path $PSScriptRoot "..\launch-log.txt"
& $adb logcat -d -v time ReactNative:V ReactNativeJS:V ExpoModulesCore:V AndroidRuntime:E *:S 2>&1 |
  Tee-Object -FilePath $logFile

Write-Host ""
Write-Host "Also scanning for fatal errors..."
& $adb logcat -d -v time | Select-String -Pattern "FATAL|AndroidRuntime|ReactNativeJS|Invariant|Exception" |
  Select-Object -Last 80

Write-Host ""
Write-Host "Full filtered log saved to: $logFile"
