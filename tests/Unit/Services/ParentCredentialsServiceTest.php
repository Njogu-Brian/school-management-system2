<?php

namespace Tests\Unit\Services;

use App\Models\AcademicYear;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentCredentialsService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentCredentialsServiceTest extends TestCase
{
    private ParentCredentialsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ParentCredentialsService::class);
        AcademicYear::query()->update(['is_active' => false]);
        AcademicYear::query()->create(['year' => '2026', 'is_active' => true]);
    }

    /** @test */
    public function formula_password_uses_two_digit_year(): void
    {
        $parent = ParentInfo::query()->create([
            'father_name' => 'Test Parent',
            'father_phone' => '0700111222',
        ]);
        Student::factory()->create([
            'parent_id' => $parent->id,
            'admission_number' => 'RKS001',
            'archive' => 0,
        ]);

        $this->assertSame('RKS001-26', $this->service->formulaPassword($parent));
        $this->assertContains('RKS001-26', $this->service->formulaPasswordCandidatesForAdmission('RKS001'));
        $this->assertContains('RKS001-2026', $this->service->formulaPasswordCandidatesForAdmission('RKS001'));
    }

    /** @test */
    public function any_child_admission_password_works_while_must_change(): void
    {
        $parent = ParentInfo::query()->create([
            'father_name' => 'Multi Parent',
            'father_phone' => '0700333444',
        ]);
        Student::factory()->create(['parent_id' => $parent->id, 'admission_number' => 'RKS001', 'archive' => 0]);
        Student::factory()->create(['parent_id' => $parent->id, 'admission_number' => 'RKS002', 'archive' => 0]);

        $user = User::factory()->create([
            'parent_id' => $parent->id,
            'password' => Hash::make($this->service->formulaPasswordForAdmission('RKS001')),
            'must_change_password' => true,
        ]);
        $user->assignRole('Parent');

        $this->assertTrue($this->service->passwordIsValid($user, 'RKS001-26'));
        $this->assertTrue($this->service->passwordIsValid($user, 'RKS002-26'));
        $this->assertTrue($this->service->passwordIsValid($user, 'RKS002-2026'));
        $this->assertFalse($this->service->passwordIsValid($user, 'RKS999-26'));

        $user->update(['must_change_password' => false]);
        $this->assertFalse($this->service->matchesAnyTemporaryPassword($user->fresh(), 'RKS002-26'));
    }
}
