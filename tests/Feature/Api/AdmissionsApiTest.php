<?php

namespace Tests\Feature\Api;

use App\Models\OnlineAdmission;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdmissionsApiTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_admin_can_list_admissions_stats_and_detail(): void
    {
        $admin = $this->createAdmin();

        $admission = OnlineAdmission::create([
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'dob' => '2015-01-01',
            'gender' => 'female',
            'application_status' => 'pending',
            'application_date' => now(),
            'application_source' => 'online',
            'residential_area' => 'Nairobi',
            'father_name' => 'Parent One',
            'father_phone' => '+254712345678',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admissions/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['pending', 'under_review', 'waitlisted', 'enrolled', 'rejected', 'total']]);

        $this->getJson('/api/admissions?per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.id', $admission->id);

        $this->getJson("/api/admissions/{$admission->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $admission->id)
            ->assertJsonStructure([
                'data' => [
                    'documents',
                    'timeline',
                    'enrollment',
                    'father',
                    'mother',
                    'guardian',
                ],
            ]);
    }

    public function test_admin_can_update_status_waitlist_and_reject(): void
    {
        $admin = $this->createAdmin();

        $admission = OnlineAdmission::create([
            'first_name' => 'Action',
            'last_name' => 'Test',
            'dob' => '2016-05-05',
            'gender' => 'male',
            'application_status' => 'pending',
            'application_date' => now(),
            'application_source' => 'online',
            'residential_area' => 'Nairobi',
            'father_name' => 'Parent',
            'father_phone' => '712345678',
            'father_phone_country_code' => '+254',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/admissions/{$admission->id}/status", [
            'application_status' => 'under_review',
            'review_notes' => 'Review started from API',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application_status', 'under_review');

        $this->postJson("/api/admissions/{$admission->id}/waitlist", [
            'review_notes' => 'Queued via API',
        ])
            ->assertOk()
            ->assertJsonPath('data.application_status', 'waitlisted')
            ->assertJsonPath('data.waitlist_position', 1);

        $admission2 = OnlineAdmission::create([
            'first_name' => 'Reject',
            'last_name' => 'Case',
            'dob' => '2016-06-06',
            'gender' => 'female',
            'application_status' => 'pending',
            'application_date' => now(),
            'residential_area' => 'Nairobi',
            'father_phone' => '712345679',
            'father_phone_country_code' => '+254',
        ]);

        $this->postJson("/api/admissions/{$admission2->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.application_status', 'rejected');
    }

    public function test_admin_can_enroll_application(): void
    {
        $admin = $this->createAdmin();
        $classroom = $this->createClassroom(['name' => 'Grade 1']);
        $category = \App\Models\StudentCategory::create([
            'name' => 'Day Scholar',
            'description' => 'Test category',
        ]);

        $admission = OnlineAdmission::create([
            'first_name' => 'Enroll',
            'last_name' => 'Me',
            'dob' => '2017-03-03',
            'gender' => 'male',
            'application_status' => 'under_review',
            'application_date' => now(),
            'application_source' => 'online',
            'residential_area' => 'Westlands',
            'father_name' => 'Father Test',
            'father_phone' => '712345670',
            'father_phone_country_code' => '+254',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admissions/{$admission->id}/enroll", [
            'classroom_id' => $classroom->id,
            'category_id' => $category->id,
            'residential_area' => 'Westlands',
            'enrollment_year' => (int) date('Y'),
            'enrollment_term' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'student' => ['id', 'admission_number', 'full_name'],
                    'application' => ['enrolled', 'application_status'],
                ],
            ])
            ->assertJsonPath('data.application.enrolled', true)
            ->assertJsonPath('data.application.application_status', 'enrolled');
    }
}
