# Pull active staff from the ERP and create/update employees in local BioTime 9.5.
# Run on the BioTime Windows PC after adding staff or changing names/PINs in the ERP.
#
# powershell -ExecutionPolicy Bypass -File C:\ZKBioTime\biotime-sync-employees-from-erp.ps1

$ErrorActionPreference = 'Stop'

$BioTimeBase = 'http://127.0.0.1:8088'
$BioTimeUser = 'admin'
$BioTimePass = 'CHANGE_ME'
$ErpEmployeesUrl = 'https://erp.royalkingsschools.sc.ke/api/integrations/biotime/employees'
$ErpToken = 'CHANGE_ME'

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
    throw 'Could not log in to BioTime.'
}

$headers = @{ Authorization = "JWT $($auth.token)"; 'Content-Type' = 'application/json' }
$erpHeaders = @{ 'X-BioTime-Token' = $ErpToken }

$payload = Invoke-RestMethod -Method Get -Uri $ErpEmployeesUrl -Headers $erpHeaders
$employees = @($payload.data.employees)
$departmentId = [int]$payload.data.defaults.department_id
$areaIds = @($payload.data.defaults.area_ids)
if ($areaIds.Count -eq 0) { $areaIds = @(1) }

Write-Host ("ERP employees to sync: " + $employees.Count)

$created = 0
$updated = 0
$skipped = 0

foreach ($emp in $employees) {
    $code = [string]$emp.emp_code
    if ([string]::IsNullOrWhiteSpace($code)) {
        $skipped++
        continue
    }

    $existing = $null
    try {
        $lookup = Invoke-RestMethod -Method Get -Uri ($BioTimeBase + '/personnel/api/employees/?emp_code=' + [uri]::EscapeDataString($code)) -Headers $headers
        if ($lookup.data -and $lookup.data.Count -gt 0) {
            $existing = $lookup.data[0]
        }
    } catch { }

    $body = @{
        emp_code = $code
        first_name = [string]$emp.first_name
        last_name = [string]$emp.last_name
        department = $departmentId
        area = $areaIds
    }

    if ($existing -and $existing.id) {
        $id = [int]$existing.id
        Invoke-RestMethod -Method Patch -Uri ($BioTimeBase + '/personnel/api/employees/' + $id + '/') -Headers $headers -Body ($body | ConvertTo-Json -Depth 4) | Out-Null
        $updated++
        Write-Host "Updated $code ($($emp.first_name) $($emp.last_name))"
    } else {
        Invoke-RestMethod -Method Post -Uri ($BioTimeBase + '/personnel/api/employees/') -Headers $headers -Body ($body | ConvertTo-Json -Depth 4) | Out-Null
        $created++
        Write-Host "Created $code ($($emp.first_name) $($emp.last_name))"
    }
}

Write-Host "Done. created=$created updated=$updated skipped=$skipped"
