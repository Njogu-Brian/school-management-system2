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

Write-Host "Force-stopping and cold-starting $package ..."
& $adb shell am force-stop $package | Out-Null
Start-Sleep -Seconds 1
& $adb shell am start -n "$package/.MainActivity" | Out-Null

Write-Host "Capturing logs for 15 seconds..."
Start-Sleep -Seconds 15

$logFile = Join-Path $PSScriptRoot "..\launch-log.txt"
& $adb logcat -d -v time 2>&1 | Tee-Object -FilePath $logFile | Out-Null

Write-Host ""
Write-Host "=== Key errors (if any) ==="
Get-Content $logFile |
  Select-String -Pattern "FATAL|AndroidRuntime|ReactNativeJS|JavascriptException|RNCNetInfo|Invariant|NativeModule" |
  Select-Object -Last 40

Write-Host ""
Write-Host "Full log saved to: $logFile"
