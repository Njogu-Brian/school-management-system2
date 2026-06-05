# Smoke-test Admin App APIs (Staff 360 + core reads).
# Usage:
#   $env:ERP_API_BASE = "https://erp.royalkingsschools.sc.ke/api"
#   $env:ERP_EMAIL = "admin@example.com"
#   $env:ERP_PASSWORD = "your-password"
#   $env:ERP_STAFF_ID = "42"   # optional; uses first staff from list if omitted
#   .\scripts\smoke-admin-api.ps1

param(
    [string]$ApiBase = $env:ERP_API_BASE,
    [string]$Email = $env:ERP_EMAIL,
    [string]$Password = $env:ERP_PASSWORD,
    [string]$StaffId = $env:ERP_STAFF_ID,
    [string]$StudentId = $env:ERP_STUDENT_ID
)

$ErrorActionPreference = "Stop"

if (-not $ApiBase) { $ApiBase = "https://erp.royalkingsschools.sc.ke/api" }
$ApiBase = $ApiBase.TrimEnd("/")

function Write-Result($label, $ok, $detail) {
    $icon = if ($ok) { "PASS" } else { "FAIL" }
    Write-Host "[$icon] $label — $detail"
}

if (-not $Email -or -not $Password) {
    Write-Host "Set ERP_EMAIL and ERP_PASSWORD (or pass -Email / -Password)."
    Write-Host "Example: `$env:ERP_EMAIL='you@school.com'; `$env:ERP_PASSWORD='secret'; .\scripts\smoke-admin-api.ps1"
    exit 1
}

Write-Host "API base: $ApiBase"
Write-Host "Logging in as $Email ..."

$loginBody = @{ email = $Email; password = $Password } | ConvertTo-Json
try {
    $login = Invoke-RestMethod -Uri "$ApiBase/login" -Method POST -ContentType "application/json" -Body $loginBody
} catch {
    Write-Result "POST /login" $false $_.Exception.Message
    exit 1
}

$token = $login.token ?? $login.data?.token
if (-not $token) {
    Write-Result "POST /login" $false "No token in response"
    exit 1
}
Write-Result "POST /login" $true "Token received"

$headers = @{ Authorization = "Bearer $token"; Accept = "application/json" }

function Invoke-Api($method, $path) {
    return Invoke-RestMethod -Uri "$ApiBase$path" -Method $method -Headers $headers
}

# Dashboard + Settings Hub (Sprint 4)
$globalChecks = @(
    @{ Label = "GET /dashboard/stats"; Path = "/dashboard/stats" },
    @{ Label = "GET /admissions/stats"; Path = "/admissions/stats" },
    @{ Label = "GET /admissions"; Path = "/admissions?per_page=5" },
    @{ Label = "GET /settings/school"; Path = "/settings/school" },
    @{ Label = "GET /settings/academic-years"; Path = "/settings/academic-years" },
    @{ Label = "GET /settings/terms"; Path = "/settings/terms" },
    @{ Label = "GET /settings/classes"; Path = "/settings/classes" },
    @{ Label = "GET /settings/subjects"; Path = "/settings/subjects" },
    @{ Label = "GET /settings/grading"; Path = "/settings/grading" },
    @{ Label = "GET /settings/roles"; Path = "/settings/roles" }
)

foreach ($c in $globalChecks) {
    try {
        $res = Invoke-Api GET $c.Path
        $ok = $res.success -ne $false
        Write-Result $c.Label $ok $(if ($res.data) { "success=true" } else { "ok" })
    } catch {
        $code = ""
        if ($_.Exception.Response) { $code = "HTTP $([int]$_.Exception.Response.StatusCode)" }
        Write-Result $c.Label $false "$code $($_.Exception.Message)"
    }
}

# Students list (for academics probes)
try {
    $students = Invoke-Api GET "/students?per_page=5"
    $studentTotal = $students.data.total
    Write-Result "GET /students" $true "total=$studentTotal"
    if (-not $StudentId -and $students.data.data.Count -gt 0) {
        $StudentId = [string]$students.data.data[0].id
        Write-Host "  Using student id $StudentId from list"
    }
} catch {
    Write-Result "GET /students" $false $_.Exception.Message
}

if ($StudentId) {
    $studentChecks = @(
        @{ Label = "GET /students/{id}/academic-summary"; Path = "/students/$StudentId/academic-summary" },
        @{ Label = "GET /students/{id}/assessment-history"; Path = "/students/$StudentId/assessment-history?per_page=5" },
        @{ Label = "GET /report-cards?student_id="; Path = "/report-cards?student_id=$StudentId&per_page=5" }
    )
    foreach ($c in $studentChecks) {
        try {
            $res = Invoke-Api GET $c.Path
            $ok = $res.success -ne $false
            Write-Result $c.Label $ok $(if ($res.data) { "success=true" } else { "ok" })
        } catch {
            $code = ""
            if ($_.Exception.Response) { $code = "HTTP $([int]$_.Exception.Response.StatusCode)" }
            Write-Result $c.Label $false "$code $($_.Exception.Message)"
        }
    }
} else {
    Write-Host "No student id available — skipping student academics checks."
}

# Staff list
try {
    $list = Invoke-Api GET "/staff?per_page=5"
    $count = $list.data.total
    Write-Result "GET /staff" $true "total=$count"
    if (-not $StaffId -and $list.data.data.Count -gt 0) {
        $StaffId = [string]$list.data.data[0].id
        Write-Host "  Using staff id $StaffId from list"
    }
} catch {
    Write-Result "GET /staff" $false $_.Exception.Message
}

if (-not $StaffId) {
    Write-Host "No staff id available — skipping staff-scoped checks."
    exit 0
}

$checks = @(
    @{ Label = "GET /staff/{id}"; Path = "/staff/$StaffId" },
    @{ Label = "GET /staff/{id}/leave-balances"; Path = "/staff/$StaffId/leave-balances" },
    @{ Label = "GET /staff/{id}/attendance-history"; Path = "/staff/$StaffId/attendance-history" },
    @{ Label = "GET /leave-requests"; Path = "/leave-requests?staff_id=$StaffId&per_page=5" },
    @{ Label = "GET /payroll-records"; Path = "/payroll-records?staff_id=$StaffId&per_page=3" },
    @{ Label = "GET /staff/filter-options"; Path = "/staff/filter-options" }
)

foreach ($c in $checks) {
    try {
        $res = Invoke-Api GET $c.Path
        $ok = $res.success -ne $false
        $detail = if ($res.data) { "success=true" } else { "ok" }
        Write-Result $c.Label $ok $detail
    } catch {
        $code = ""
        if ($_.Exception.Response) { $code = "HTTP $([int]$_.Exception.Response.StatusCode)" }
        Write-Result $c.Label $false "$code $($_.Exception.Message)"
    }
}

Write-Host ""
Write-Host "Smoke test complete."
Write-Host "  Settings Hub: all /settings/* should PASS after backend deploy."
Write-Host "  Student 360 Academics: academic-summary + assessment-history should PASS."
Write-Host "  Staff 360: verify People → Staff → Staff 360 tabs in the app."
