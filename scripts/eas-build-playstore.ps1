# Build Royal Kings Users + Admin Android AABs for Play Store.
# Always run from the app folders - never from the monorepo root.
#
# Usage (PowerShell, from repo root):
#   .\scripts\eas-build-playstore.ps1
#   .\scripts\eas-build-playstore.ps1 -App users
#   .\scripts\eas-build-playstore.ps1 -App admin
#   .\scripts\eas-build-playstore.ps1 -Profile preview

param(
  [ValidateSet('both', 'users', 'admin')]
  [string]$App = 'both',
  [ValidateSet('production', 'preview')]
  [string]$Profile = 'production',
  [switch]$Wait
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

function Assert-UsersVersion {
  $gradle = Join-Path $root 'mobile-app\apps\users\android\app\build.gradle'
  $text = Get-Content $gradle -Raw
  if ($text -notmatch 'versionCode\s+(\d+)') { throw 'Users versionCode not found in android/app/build.gradle' }
  $code = [int]$Matches[1]
  Write-Host "Users native versionCode = $code (Play production is 6; need >= 7)"
  if ($code -lt 7) { throw "Users versionCode $code would conflict with Play Console" }
}

function Assert-AdminVersion {
  $cfg = Join-Path $root 'mobile-app\apps\admin\app.config.ts'
  $text = Get-Content $cfg -Raw
  if ($text -notmatch 'versionCode:\s*(\d+)') { throw 'Admin versionCode not found in app.config.ts' }
  $code = [int]$Matches[1]
  Write-Host "Admin app.config versionCode = $code (Play active is 12; need >= 13)"
  if ($code -lt 13) { throw "Admin versionCode $code would conflict with Play Console" }
}

function Invoke-AppBuild([string]$Name) {
  $dir = Join-Path $root "mobile-app\apps\$Name"
  if (-not (Test-Path (Join-Path $dir 'eas.json'))) {
    throw "Missing eas.json in $dir - wrong folder"
  }
  Push-Location $dir
  try {
    Write-Host ""
    Write-Host "=== Building $Name ($Profile) from $dir ===" -ForegroundColor Cyan
    $env:EAS_BUILD_NO_EXPO_GO_WARNING = 'true'
    if ($Wait) {
      eas build --platform android --profile $Profile --non-interactive
    } else {
      eas build --platform android --profile $Profile --non-interactive --no-wait
    }
    if ($LASTEXITCODE -ne 0) { throw "eas build failed for $Name (exit $LASTEXITCODE)" }
  }
  finally {
    Pop-Location
  }
}

Write-Host "Do NOT run eas build from the repo root - that creates the wrong Expo project." -ForegroundColor Yellow

if ($App -eq 'both' -or $App -eq 'users') {
  Assert-UsersVersion
  Invoke-AppBuild 'users'
}
if ($App -eq 'both' -or $App -eq 'admin') {
  Assert-AdminVersion
  Invoke-AppBuild 'admin'
}

Write-Host ""
Write-Host "Done. Watch builds at https://expo.dev/accounts/briannjogu" -ForegroundColor Green
