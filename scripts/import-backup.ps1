param(
  [Parameter(Mandatory = $true)]
  [string]$BackupGzPath,

  [string]$ProjectRoot = "",

  [string]$MysqlPath = "C:\xampp\mysql\bin\mysql.exe",

  [switch]$ResetDatabase,

  [switch]$KeepSql,

  [int]$MaxAllowedPacketMB = 512
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
  $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
  $ProjectRoot = (Resolve-Path (Join-Path $scriptDir "..")).Path
}

function Get-EnvValue([string]$EnvFile, [string]$Key) {
  Select-String -Path $EnvFile -Pattern "^\s*$Key\s*=" | ForEach-Object {
    $raw = ($_.Line -split '=', 2)[1].Trim()
    if (($raw.StartsWith('"') -and $raw.EndsWith('"')) -or ($raw.StartsWith("'") -and $raw.EndsWith("'"))) {
      $raw.Substring(1, $raw.Length - 2)
    } else { $raw }
  } | Select-Object -First 1
}

if (-not (Test-Path $BackupGzPath)) { throw "Backup not found: $BackupGzPath" }
if (-not (Test-Path $MysqlPath)) { throw "mysql.exe not found: $MysqlPath" }

$envFile = Join-Path $ProjectRoot ".env"
if (-not (Test-Path $envFile)) { throw ".env not found: $envFile" }

$dbConn = Get-EnvValue $envFile "DB_CONNECTION"
$db     = Get-EnvValue $envFile "DB_DATABASE"
$user   = Get-EnvValue $envFile "DB_USERNAME"; if (-not $user) { $user = "root" }
$pass   = Get-EnvValue $envFile "DB_PASSWORD"
$dbHost = Get-EnvValue $envFile "DB_HOST";     if (-not $dbHost) { $dbHost = "127.0.0.1" }
$port   = Get-EnvValue $envFile "DB_PORT";     if (-not $port) { $port = "3306" }

if (-not $db) { throw "DB_DATABASE not set in $envFile" }
if ($dbConn -and $dbConn -ne "mysql" -and $dbConn -ne "mariadb") {
  Write-Warning "DB_CONNECTION is '$dbConn'. Import will still run, but your app may not use this DB until you set DB_CONNECTION=mysql."
}

Write-Host "Database: $db @ ${dbHost}:${port}"
Write-Host "Backup:   $BackupGzPath"

$sqlPath = Join-Path $env:TEMP ("import-" + $db + "-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".sql")
Write-Host "Decompressing to: $sqlPath"

$in = [System.IO.File]::OpenRead($BackupGzPath)
try {
  $gzs = New-Object System.IO.Compression.GzipStream($in, [System.IO.Compression.CompressionMode]::Decompress)
  $out = [System.IO.File]::Create($sqlPath)
  try { $gzs.CopyTo($out) } finally { $out.Dispose() }
} finally { $in.Dispose() }

$mysqlAuth = @("-h$dbHost", "-P$port", "-u$user")
if ($pass -ne $null) {
  # If DB_PASSWORD is empty string, -p"" works for "empty password" setups.
  $mysqlAuth += "-p$pass"
}

$dbEsc = $db -replace '`', '``'

if ($ResetDatabase) {
  Write-Host "Dropping database (if exists)..."
  & $MysqlPath @mysqlAuth -e "DROP DATABASE IF EXISTS ``$dbEsc``;"
}

Write-Host "Creating database (if missing)..."
& $MysqlPath @mysqlAuth -e "CREATE DATABASE IF NOT EXISTS ``$dbEsc`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Write-Host "Importing (this can take a while)..."
$args = @($mysqlAuth + @("--max_allowed_packet=$($MaxAllowedPacketMB)M", $db))
$p = Start-Process -FilePath $MysqlPath `
  -ArgumentList $args `
  -RedirectStandardInput $sqlPath `
  -NoNewWindow -Wait -PassThru

if ($p.ExitCode -ne 0) { throw "mysql import failed with exit code $($p.ExitCode)" }

Write-Host "Import complete."

if (-not $KeepSql) {
  Remove-Item $sqlPath -Force -ErrorAction SilentlyContinue
}

