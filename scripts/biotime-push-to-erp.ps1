# Push BioTime 9.5 punches from the office Windows PC to the ERP.
# Run every 2 minutes with Task Scheduler (see comments at bottom).
#
# 1. Copy this file to the BioTime PC, e.g. C:\BioTime\biotime-push-to-erp.ps1
# 2. Fill in the four settings below.
# 3. Test once:  powershell -ExecutionPolicy Bypass -File C:\BioTime\biotime-push-to-erp.ps1

$ErrorActionPreference = 'Stop'

$BioTimeBase = 'http://127.0.0.1:8088'          # BioTime web port on THIS PC (try 8081 if 8088 fails)
$BioTimeUser = 'admin'
$BioTimePass = 'CHANGE_ME'                      # BioTime admin password
$ErpPunchesUrl = 'https://erp.royalkingsschools.sc.ke/api/integrations/biotime/punches'
$ErpToken = 'CHANGE_ME'                         # Same value as BIOTIME_INGEST_TOKEN on the ERP server
$StateFile = 'C:\BioTime\erp-sync-state.json'

New-Item -ItemType Directory -Force -Path (Split-Path $StateFile) | Out-Null

$lastId = 0
$lastTime = (Get-Date).AddDays(-2).ToString('yyyy-MM-dd HH:mm:ss')
if (Test-Path $StateFile) {
    $state = Get-Content $StateFile -Raw | ConvertFrom-Json
    if ($state.last_id) { $lastId = [int]$state.last_id }
    if ($state.last_time) { $lastTime = [string]$state.last_time }
}

$auth = $null
foreach ($path in @('/jwt-api-token-auth/', '/api-token-auth/')) {
    try {
        $auth = Invoke-RestMethod -Method Post -Uri ($BioTimeBase + $path) -ContentType 'application/json' -Body (@{
            username = $BioTimeUser
            password = $BioTimePass
        } | ConvertTo-Json)
        if ($auth.token) { break }
    } catch { }
}
if (-not $auth -or -not $auth.token) {
    throw 'Could not log in to BioTime. Check $BioTimeBase port and password.'
}

$headers = @{ Authorization = "JWT $($auth.token)" }
$page = 1
$all = @()
do {
    $uri = $BioTimeBase + '/iclock/api/transactions/?page=' + $page + '&page_size=200&limit=200&start_time=' + [uri]::EscapeDataString($lastTime)
    $batch = Invoke-RestMethod -Method Get -Uri $uri -Headers $headers
    $rows = @()
    if ($batch.data) { $rows = @($batch.data) }
    elseif ($batch.results) { $rows = @($batch.results) }
    $all += $rows
    $page++
} while ($rows.Count -ge 200 -and $page -lt 30)

$new = @($all | Where-Object {
    $id = 0
    if ($_.id) { $id = [int]$_.id }
    ($id -gt $lastId) -or (-not $_.id)
})

if ($new.Count -eq 0) {
    Write-Host 'No new punches.'
    exit 0
}

$payload = @{ transactions = $new } | ConvertTo-Json -Depth 8
$erpHeaders = @{ 'X-BioTime-Token' = $ErpToken; 'Content-Type' = 'application/json' }
$result = Invoke-RestMethod -Method Post -Uri $ErpPunchesUrl -Headers $erpHeaders -Body $payload
Write-Host ($result | ConvertTo-Json -Compress)

$maxId = $lastId
$maxTime = $lastTime
foreach ($row in $new) {
    if ($row.id -and [int]$row.id -gt $maxId) { $maxId = [int]$row.id }
    if ($row.punch_time -and $row.punch_time -gt $maxTime) { $maxTime = [string]$row.punch_time }
}
@{ last_id = $maxId; last_time = $maxTime } | ConvertTo-Json | Set-Content -Path $StateFile -Encoding UTF8

# Task Scheduler (run as the BioTime PC user, highest privileges, every 2 minutes):
#   Program: powershell.exe
#   Arguments: -ExecutionPolicy Bypass -File C:\BioTime\biotime-push-to-erp.ps1
