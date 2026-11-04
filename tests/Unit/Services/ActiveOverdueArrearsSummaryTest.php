<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Api\ApiFinanceSummaryController;
use App\Services\StudentFeeStatementService;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Structural + contract tests for authoritative arrears KPIs.
 * Full DB feature coverage needs a green sqlite migration graph.
 */
class ActiveOverdueArrearsSummaryTest extends PhpUnitTestCase
{
    public function test_active_overdue_method_exists_on_statement_service(): void
    {
        $this->assertTrue(method_exists(StudentFeeStatementService::class, 'activeOverdueArrearsSummary'));
        $this->assertTrue(method_exists(StudentFeeStatementService::class, 'forgetSchoolOutstandingCache'));

        $method = new ReflectionMethod(StudentFeeStatementService::class, 'activeOverdueArrearsSummary');
        $this->assertTrue($method->isPublic());
    }

    public function test_summary_controller_uses_active_overdue_for_primary_kpis(): void
    {
        $path = (new ReflectionClass(ApiFinanceSummaryController::class))->getFileName();
        $this->assertNotFalse($path);
        $source = file_get_contents($path);
        $this->assertIsString($source);

        $this->assertStringContainsString('activeOverdueArrearsSummary', $source);
        $this->assertStringContainsString("'outstanding_balance'", $source);
        $this->assertStringContainsString("'students_in_arrears'", $source);
        $this->assertStringContainsString('feeBalances', $source);
        $this->assertStringContainsString('finance_scope_label', $source);
    }

    public function test_forget_cache_clears_active_overdue_key(): void
    {
        $path = (new ReflectionClass(StudentFeeStatementService::class))->getFileName();
        $this->assertNotFalse($path);
        $source = file_get_contents($path);
        $this->assertIsString($source);

        $this->assertStringContainsString("cache()->forget('finance.active_overdue.v1')", $source);
        $this->assertStringContainsString("cache()->remember('finance.active_overdue.v1'", $source);
    }
}
