<?php

namespace Tests\Feature\Api;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\User;
use Tests\TestCase;

class BioTimeIngestTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
        config(['biotime.ingest_token' => 'test-biotime-token']);
    }

    public function test_health_requires_token(): void
    {
        $this->getJson('/api/integrations/biotime/health')
            ->assertUnauthorized();

        $this->withHeader('X-BioTime-Token', 'test-biotime-token')
            ->getJson('/api/integrations/biotime/health')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_ingest_maps_trailing_staff_id_digits_and_rebuilds_in_out(): void
    {
        $staff = $this->makeStaff([
            'staff_id' => 'RKS/STAFF/201',
            'biometric_emp_code' => null,
        ]);

        $this->withHeader('X-BioTime-Token', 'test-biotime-token')
            ->postJson('/api/integrations/biotime/punches', [
                'transactions' => [
                    [
                        'id' => 11,
                        'emp_code' => 201,
                        'punch_time' => '2026-08-24 07:55:01',
                        'terminal_alias' => 'Gate In',
                    ],
                    [
                        'id' => 12,
                        'emp_code' => '201',
                        'punch_time' => '2026-08-24 16:10:00',
                        'terminal_alias' => 'Gate Out',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.unmatched', 0)
            ->assertJsonPath('data.days', 1);

        $row = StaffAttendance::query()->where('staff_id', $staff->id)->whereDate('date', '2026-08-24')->first();
        $this->assertNotNull($row);
        $this->assertSame('present', $row->status);
        $this->assertSame('biometric', $row->source);
        $this->assertSame('07:55:01', \Carbon\Carbon::parse($row->check_in_time)->format('H:i:s'));
        $this->assertSame('16:10:00', \Carbon\Carbon::parse($row->check_out_time)->format('H:i:s'));
    }

    public function test_explicit_biometric_code_wins_and_unmatched_codes_are_counted(): void
    {
        $staff = $this->makeStaff([
            'staff_id' => 'RKS/STAFF/242',
            'biometric_emp_code' => '9001',
        ]);

        $this->withHeader('X-BioTime-Token', 'test-biotime-token')
            ->postJson('/api/integrations/biotime/punches', [
                'transactions' => [
                    [
                        'id' => 21,
                        'emp_code' => '9001',
                        'punch_time' => '2026-08-24 08:00:00',
                    ],
                    [
                        'id' => 22,
                        'emp_code' => '9999',
                        'punch_time' => '2026-08-24 08:01:00',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.unmatched', 1);

        $this->assertTrue(
            StaffAttendance::query()->where('staff_id', $staff->id)->whereDate('date', '2026-08-24')->exists()
        );
    }

    public function test_single_punch_is_flagged_incomplete(): void
    {
        $staff = $this->makeStaff([
            'staff_id' => 'RKS/STAFF/210',
            'biometric_emp_code' => '210',
        ]);

        $this->withHeader('X-BioTime-Token', 'test-biotime-token')
            ->postJson('/api/integrations/biotime/punches', [
                'transactions' => [
                    [
                        'id' => 31,
                        'emp_code' => '210',
                        'punch_time' => '2026-08-24 07:40:00',
                    ],
                ],
            ])
            ->assertOk();

        $row = StaffAttendance::query()->where('staff_id', $staff->id)->first();
        $this->assertNotNull($row);
        $this->assertNull($row->check_out_time);
        $this->assertStringContainsString('Incomplete', (string) $row->notes);
    }

    private function makeStaff(array $overrides = []): Staff
    {
        $user = User::factory()->create();

        return Staff::create(array_merge([
            'user_id' => $user->id,
            'staff_id' => 'RKS/STAFF/'.fake()->unique()->numerify('###'),
            'first_name' => 'Gate',
            'last_name' => 'Staff',
            'work_email' => $user->email,
            'phone_number' => '+254700000010',
            'id_number' => fake()->unique()->numerify('########'),
            'status' => 'active',
        ], $overrides));
    }
}
