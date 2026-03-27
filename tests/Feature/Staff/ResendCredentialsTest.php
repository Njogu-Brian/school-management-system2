<?php

namespace Tests\Feature\Staff;

use App\Models\CommunicationTemplate;
use App\Models\Staff;
use App\Models\User;
use App\Services\CommunicationService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResendCredentialsTest extends TestCase
{
    public function test_resend_credentials_sets_error_when_sms_send_fails(): void
    {
        Role::firstOrCreate(['name' => 'Secretary']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $staffUser = User::factory()->create([
            'name' => 'Jane Staff',
            'email' => 'jane.staff@example.com',
        ]);

        $staff = Staff::create([
            'user_id' => $staffUser->id,
            'staff_id' => 'STAFF1001',
            'first_name' => 'Jane',
            'last_name' => 'Staff',
            'work_email' => 'jane.staff@example.com',
            'phone_number' => '+254712345678',
            'id_number' => '12345678',
            'status' => 'active',
        ]);

        CommunicationTemplate::create([
            'code' => 'staff_welcome_sms',
            'title' => 'Welcome Staff (SMS)',
            'type' => 'sms',
            'subject' => null,
            'content' => 'Hello {{staff_name}}',
        ]);

        $mock = \Mockery::mock(CommunicationService::class);
        $mock->shouldReceive('sendSMS')
            ->once()
            ->andReturn([
                'success' => false,
                'status' => 'failed',
                'provider_status' => 'error',
                'status_code' => '500',
                'result' => ['status' => 'error'],
                'error' => 'Provider rejected message',
            ]);
        $this->app->instance(CommunicationService::class, $mock);

        $response = $this->actingAs($admin)
            ->from(route('staff.show', $staff->id))
            ->post(route('staff.resend-credentials', $staff->id));

        $response->assertRedirect(route('staff.show', $staff->id));
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'SMS: Provider rejected message',
            (string) session('error')
        );
    }
}

