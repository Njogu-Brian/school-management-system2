<?php

namespace Tests\Unit\Services;

use App\Models\OnlineAdmission;
use App\Models\Student;
use App\Services\Students\StudentDuplicateDetector;
use Tests\TestCase;

class StudentDuplicateDetectorTest extends TestCase
{
    private StudentDuplicateDetector $detector;

    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
        $this->detector = app(StudentDuplicateDetector::class);
    }

    public function test_matches_existing_student_by_nemis_number(): void
    {
        $existing = Student::factory()->create([
            'first_name' => 'Amina',
            'last_name' => 'Otieno',
            'dob' => '2018-03-12',
            'gender' => 'female',
            'nemis_number' => 'NEM-12345',
            'admission_number' => 'RKS9001',
        ]);

        $matches = $this->detector->findStudentMatches([
            'first_name' => 'Different',
            'last_name' => 'Name',
            'dob' => '2010-01-01',
            'gender' => 'male',
            'nemis_number' => 'nem 12345',
        ]);

        $this->assertCount(1, $matches);
        $this->assertSame('nemis', $matches->first()->reason);
        $this->assertTrue($matches->first()->isHighConfidence());
        $this->assertSame($existing->id, $matches->first()->studentId);
    }

    public function test_matches_existing_student_by_name_and_dob(): void
    {
        Student::factory()->create([
            'first_name' => 'Brian',
            'last_name' => 'Mwangi',
            'dob' => '2017-06-01',
            'gender' => 'male',
            'admission_number' => 'RKS9002',
        ]);

        $matches = $this->detector->findStudentMatches([
            'first_name' => 'BRIAN',
            'last_name' => 'mwangi',
            'dob' => '2017-06-01',
            'gender' => 'Male',
        ]);

        $this->assertCount(1, $matches);
        $this->assertSame('name_dob_gender', $matches->first()->reason);
        $this->assertFalse($matches->first()->isHighConfidence());
    }

    public function test_does_not_match_when_name_is_same_but_dob_differs(): void
    {
        Student::factory()->create([
            'first_name' => 'Brian',
            'last_name' => 'Mwangi',
            'dob' => '2017-06-01',
            'gender' => 'male',
            'admission_number' => 'RKS9003',
        ]);

        $matches = $this->detector->findStudentMatches([
            'first_name' => 'Brian',
            'last_name' => 'Mwangi',
            'dob' => '2016-06-01',
            'gender' => 'male',
        ]);

        $this->assertTrue($matches->isEmpty());
    }

    public function test_still_matches_archived_students(): void
    {
        Student::factory()->create([
            'first_name' => 'Faith',
            'last_name' => 'Wanjiku',
            'dob' => '2015-09-20',
            'gender' => 'female',
            'admission_number' => 'RKS9004',
            'archive' => true,
        ]);

        $matches = $this->detector->findStudentMatches([
            'first_name' => 'Faith',
            'last_name' => 'Wanjiku',
            'dob' => '2015-09-20',
            'gender' => 'female',
        ]);

        $this->assertCount(1, $matches);
        $this->assertSame('archived', $matches->first()->status);
    }

    public function test_groups_existing_register_duplicates_by_nemis(): void
    {
        Student::factory()->create([
            'first_name' => 'One',
            'last_name' => 'Kid',
            'dob' => '2014-01-01',
            'nemis_number' => 'DUP999',
            'admission_number' => 'RKS9005',
        ]);
        Student::factory()->create([
            'first_name' => 'Other',
            'last_name' => 'Name',
            'dob' => '2013-02-02',
            'nemis_number' => 'DUP-999',
            'admission_number' => 'RKS9006',
        ]);

        $groups = $this->detector->findExistingStudentDuplicateGroups();
        $nemisGroups = collect($groups)->filter(fn ($g) => $g['reason'] === 'nemis');

        $this->assertTrue($nemisGroups->isNotEmpty());
        $this->assertGreaterThanOrEqual(2, count($nemisGroups->first()['students']));
    }

    public function test_flags_online_application_that_matches_enrolled_student(): void
    {
        Student::factory()->create([
            'first_name' => 'Lucy',
            'last_name' => 'Njeri',
            'dob' => '2019-04-04',
            'gender' => 'female',
            'admission_number' => 'RKS9007',
        ]);

        OnlineAdmission::create([
            'first_name' => 'Lucy',
            'last_name' => 'Njeri',
            'dob' => '2019-04-04',
            'gender' => 'female',
            'application_status' => 'pending',
            'application_date' => now(),
            'enrolled' => false,
            'residential_area' => 'Nairobi',
            'father_name' => 'Parent',
            'father_phone' => '+254700000001',
        ]);

        $rows = $this->detector->findApplicationsMatchingStudents();

        $this->assertNotEmpty($rows);
        $this->assertSame('Lucy Njeri', $rows[0]['full_name']);
        $this->assertNotEmpty($rows[0]['matches']);
    }

    public function test_assert_blocks_unless_confirmed(): void
    {
        Student::factory()->create([
            'first_name' => 'Ian',
            'last_name' => 'Kiprop',
            'dob' => '2016-11-11',
            'gender' => 'male',
            'admission_number' => 'RKS9008',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->detector->assertNoStudentDuplicates([
            'first_name' => 'Ian',
            'last_name' => 'Kiprop',
            'dob' => '2016-11-11',
            'gender' => 'male',
        ], false);
    }

    public function test_assert_allows_when_staff_confirms_different_child(): void
    {
        Student::factory()->create([
            'first_name' => 'Ian',
            'last_name' => 'Kiprop',
            'dob' => '2016-11-11',
            'gender' => 'male',
            'admission_number' => 'RKS9009',
        ]);

        $matches = $this->detector->assertNoStudentDuplicates([
            'first_name' => 'Ian',
            'last_name' => 'Kiprop',
            'dob' => '2016-11-11',
            'gender' => 'male',
        ], true);

        $this->assertCount(1, $matches);
    }

    public function test_identity_key_normalizes_nemis_for_import_rows(): void
    {
        $this->assertSame(
            'nemis:abc123',
            $this->detector->identityKey(['nemis_number' => 'ABC-123'])
        );
        $this->assertSame(
            $this->detector->identityKey(['first_name' => 'Ann', 'last_name' => 'Achieng', 'dob' => '2018-01-01']),
            $this->detector->identityKey(['first_name' => 'ANN', 'last_name' => 'Achieng', 'dob' => '2018-01-01'])
        );
    }
}
