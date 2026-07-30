# Poll EAS builds until finished, then attempt Play submit, then shut down the PC.
$ErrorActionPreference = 'Continue'
$previewId = '592b313c-758e-4e3b-92e7-43ce4ae23daf'
# Production ID filled after upload
$prodId = $env:EAS_PROD_BUILD_ID
$usersDir = 'd:\Projects\school-management-system2\school-management-system2\mobile-app\apps\users'
$log = 'd:\Projects\school-management-system2\school-management-system2\mobile-app\apps\users\eas-shutdown-monitor.log'

function Log($msg) {
  $line = "$(Get-Date -Format o) $msg"
  Add-Content -Path $log -Value $line
  Write-Output $line
}

Set-Location $usersDir
Log "Monitor started. preview=$previewId prod=$prodId"

function Get-Status([string]$id) {
  if (-not $id) { return 'UNKNOWN' }
  try {
    $json = npx eas build:view $id --json 2>$null | Out-String
    $obj = $json | ConvertFrom-Json
    return [string]$obj.status
  } catch {
    return 'UNKNOWN'
  }
}

$deadline = (Get-Date).AddHours(2)
$previewDone = $false
$prodDone = $false
$previewOk = $false
$prodOk = $false

while ((Get-Date) -lt $deadline) {
  if (-not $previewDone) {
    $ps = Get-Status $previewId
    Log "preview status=$ps"
    if ($ps -in @('FINISHED','ERRORED','CANCELED')) {
      $previewDone = $true
      $previewOk = ($ps -eq 'FINISHED')
    }
  }
  if ($prodId -and -not $prodDone) {
    $qs = Get-Status $prodId
    Log "production status=$qs"
    if ($qs -in @('FINISHED','ERRORED','CANCELED')) {
      $prodDone = $true
      $prodOk = ($qs -eq 'FINISHED')
    }
  }
  if ($previewDone -and (($prodId -and $prodDone) -or (-not $prodId))) {
    break
  }
  Start-Sleep -Seconds 90
}

Log "Builds settled. previewOk=$previewOk prodOk=$prodOk"

if ($prodOk -and $prodId) {
  Log "Attempting Play Store submit for $prodId"
  npx eas submit --platform android --id $prodId --profile production --non-interactive --no-wait 2>&1 | Tee-Object -FilePath $log -Append
  Log "Submit command finished with exit $LASTEXITCODE"
} else {
  Log "Skipping submit (production build not finished successfully)."
}

Log "Scheduling Windows shutdown in 90 seconds. Cancel with: shutdown /a"
shutdown.exe /s /t 90 /c "EAS Users builds finished. PC shutting down. Run shutdown /a within 90s to cancel."
Log "Shutdown scheduled."
