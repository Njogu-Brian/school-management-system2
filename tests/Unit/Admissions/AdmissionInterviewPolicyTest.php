<?php

namespace Tests\Unit\Admissions;

use App\Services\Admissions\AdmissionInterviewPolicy;
use PHPUnit\Framework\TestCase;

class AdmissionInterviewPolicyTest extends TestCase
{
    public function test_foundation_is_not_applicable(): void
    {
        $policy = new AdmissionInterviewPolicy();
        $this->assertSame(AdmissionInterviewPolicy::NOT_APPLICABLE, $policy->forClassName('Foundation'));
        $this->assertSame(AdmissionInterviewPolicy::NOT_APPLICABLE, $policy->forClassName('Creche A'));
    }

    public function test_pp1_is_optional(): void
    {
        $policy = new AdmissionInterviewPolicy();
        $this->assertSame(AdmissionInterviewPolicy::OPTIONAL, $policy->forClassName('PP1'));
        $this->assertSame(AdmissionInterviewPolicy::OPTIONAL, $policy->forClassName('PP 1 Blue'));
    }

    public function test_pp2_and_grades_are_required(): void
    {
        $policy = new AdmissionInterviewPolicy();
        $this->assertSame(AdmissionInterviewPolicy::REQUIRED, $policy->forClassName('PP2'));
        $this->assertSame(AdmissionInterviewPolicy::REQUIRED, $policy->forClassName('Grade 1'));
        $this->assertSame(AdmissionInterviewPolicy::REQUIRED, $policy->forClassName('Grade 9 East'));
    }
}
