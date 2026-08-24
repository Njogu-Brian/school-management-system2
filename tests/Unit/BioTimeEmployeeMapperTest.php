<?php

namespace Tests\Unit;

use App\Services\BioTime\BioTimeEmployeeMapper;
use PHPUnit\Framework\TestCase;

class BioTimeEmployeeMapperTest extends TestCase
{
    public function test_suggested_emp_code_uses_trailing_digits(): void
    {
        $this->assertSame('201', BioTimeEmployeeMapper::suggestedEmpCode('RKS/STAFF/201'));
        $this->assertSame('25', BioTimeEmployeeMapper::suggestedEmpCode('25'));
        $this->assertNull(BioTimeEmployeeMapper::suggestedEmpCode('STAFF'));
        $this->assertNull(BioTimeEmployeeMapper::suggestedEmpCode(null));
    }

    public function test_normalize_trims_codes(): void
    {
        $this->assertSame('201', BioTimeEmployeeMapper::normalize(' 201 '));
        $this->assertSame('', BioTimeEmployeeMapper::normalize(null));
    }
}
