# Build release APK on Windows when the project path is too long for CMake/Ninja.
# Run:  cd mobile-app\scripts
#        .\build-android-release.ps1
# Or:   powershell -ExecutionPolicy Bypass -File .\scripts\build-android-release.ps1
# (from repo root: .\mobile-app\scripts\build-android-release.ps1)

$ErrorActionPreference = "Stop"
$mobileRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$mobileFolderName = Split-Path -Leaf $mobileRoot
# Map the *parent* of mobile-app — NOT mobile-app itself. If R: points at mobile-app,
# paths become R:\android (next to drive root). Node's package.json walk breaks at R:\
# (dirname(R:\) === R:\), so expo-modules-autolinking fails in settings.gradle.
$substRoot = Split-Path -Parent $mobileRoot
$driveLetter = "R"

# Free the drive letter if it was already mapped to this repo
$existing = subst 2>$null | Where-Object { $_ -match "^${driveLetter}:\\" }
if ($existing) {
    subst "${driveLetter}:" /d 2>$null
}

cmd /c "subst ${driveLetter}: `"$substRoot`""
Set-Location "${driveLetter}:\${mobileFolderName}\android"

$env:NODE_ENV = "production"
.\gradlew.bat assembleRelease --no-daemon --max-workers=1
$exit = $LASTEXITCODE

Set-Location $PSScriptRoot
subst "${driveLetter}:" /d 2>$null

exit $exit
