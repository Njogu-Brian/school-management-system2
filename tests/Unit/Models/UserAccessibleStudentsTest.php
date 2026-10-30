<?php

namespace Tests\Unit\Models;

use App\Models\Family;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class UserAccessibleStudentsTest extends TestCase
{
    /** @test */
    public function mother_sees_children_across_households_with_same_mother_contact(): void
    {
        $motherPhone = '+254720778060';
        $motherEmail = 'laureadhis@gmail.com';

        $householdA = ParentInfo::query()->create([
            'father_name' => 'Isaac Odhiambo Abuya',
            'father_phone' => '+254732442677',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => $motherPhone,
            'mother_email' => $motherEmail,
            'guardian_name' => 'Rosila Atieno Ondiege',
            'guardian_phone' => '+254722592669',
        ]);

        $householdB = ParentInfo::query()->create([
            'father_name' => 'John Mwangi Maina',
            'father_phone' => '+254720778061',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => $motherPhone,
            'mother_email' => $motherEmail,
            'guardian_name' => 'Rosila Atieno Ondiege',
            'guardian_phone' => '+254722592669',
        ]);

        $family = Family::query()->create([
            'guardian_name' => 'Laureen Adhiambo',
            'phone' => '254720778060',
        ]);

        $amanda = Student::factory()->create([
            'parent_id' => $householdA->id,
            'family_id' => $family->id,
            'admission_number' => 'RKS637T',
            'archive' => 0,
        ]);
        $darrell = Student::factory()->create([
            'parent_id' => $householdA->id,
            'family_id' => $family->id,
            'admission_number' => 'RKS749T',
            'archive' => 0,
        ]);
        $delany = Student::factory()->create([
            'parent_id' => $householdB->id,
            'family_id' => null,
            'admission_number' => 'RKS638T',
            'archive' => 0,
        ]);

        $motherUser = User::factory()->create([
            'parent_id' => $householdA->id,
            'name' => 'Laureen Adhiambo',
            'email' => $motherEmail,
            'phone_number' => $motherPhone,
        ]);
        $motherUser->assignRole('Parent');

        $ids = $motherUser->accessibleStudentIds();
        sort($ids);

        $this->assertSame(
            [(int) $amanda->id, (int) $darrell->id, (int) $delany->id],
            $ids
        );
    }

    /** @test */
    public function father_only_sees_his_own_household_children(): void
    {
        $motherPhone = '+254720778060';

        $householdA = ParentInfo::query()->create([
            'father_name' => 'Isaac Odhiambo Abuya',
            'father_phone' => '+254732442677',
            'father_email' => 'isaac.abuya@uonbi.ac.ke',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => $motherPhone,
        ]);

        $householdB = ParentInfo::query()->create([
            'father_name' => 'John Mwangi Maina',
            'father_phone' => '+254720778061',
            'father_email' => 'jommwangi12@gmail.com',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => $motherPhone,
        ]);

        $amanda = Student::factory()->create([
            'parent_id' => $householdA->id,
            'admission_number' => 'RKS637F',
            'archive' => 0,
        ]);
        Student::factory()->create([
            'parent_id' => $householdB->id,
            'admission_number' => 'RKS638F',
            'archive' => 0,
        ]);

        $fatherUser = User::factory()->create([
            'parent_id' => $householdA->id,
            'name' => 'Isaac Odhiambo Abuya',
            'email' => 'isaac.abuya@uonbi.ac.ke',
            'phone_number' => '+254732442677',
        ]);
        $fatherUser->assignRole('Parent');

        $this->assertSame([(int) $amanda->id], $fatherUser->accessibleStudentIds());
    }
}
