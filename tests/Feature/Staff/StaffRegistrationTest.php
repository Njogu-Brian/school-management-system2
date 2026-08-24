<?php

namespace Tests\Feature\Staff;

use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffRegistration;
use App\Models\User;
use Tests\TestCase;

class StaffRegistrationTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jael',
            'last_name' => 'Nereah',
            'gender' => 'Female',
            'date_of_birth' => '2003-06-09',
            'marital_status' => 'Single',
            'id_number' => '40699208',
            'personal_email' => 'amayunereah@gmail.com',
            'phone_number' => '0717684056',
            'emergency_contact_phone' => '0717684056',
        ], $overrides);
    }

    public function test_public_form_is_available_without_login(): void
    {
        $this->get(route('staff.public-register'))
            ->assertOk()
            ->assertSee('Staff Registration');
    }

    public function test_guest_can_submit_registration(): void
    {
        $this->post(route('staff.public-register.submit'), $this->validPayload())
            ->assertRedirect(route('staff.public-register'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('staff_registrations', [
            'id_number' => '40699208',
            'personal_email' => 'amayunereah@gmail.com',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_pending_id_is_rejected(): void
    {
        $this->post(route('staff.public-register.submit'), $this->validPayload())->assertRedirect();

        $this->from(route('staff.public-register'))
            ->post(route('staff.public-register.submit'), $this->validPayload([
                'personal_email' => 'other@example.com',
            ]))
            ->assertSessionHasErrors('id_number');

        $this->assertSame(1, StaffRegistration::count());
    }

    public function test_honeypot_does_not_create_a_registration(): void
    {
        $this->post(route('staff.public-register.submit'), $this->validPayload([
            'company_website' => 'https://spam.test',
        ]))->assertRedirect(route('staff.public-register'));

        $this->assertSame(0, StaffRegistration::count());
    }

    public function test_guest_cannot_view_admin_queue(): void
    {
        $this->get(route('staff.registrations.index'))->assertRedirect();
    }

    public function test_admin_can_approve_and_create_staff(): void
    {
        Setting::set('staff_id_prefix', 'RKS/STAFF/');
        Setting::setInt('staff_id_start', 249);
        Setting::set('staff_email_domain', 'royalkingsschools.sc.ke');

        $this->post(route('staff.public-register.submit'), $this->validPayload())->assertRedirect();
        $registration = StaffRegistration::firstOrFail();

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('staff.registrations.approve', $registration), [
                'work_email' => 'j.nereah@royalkingsschools.sc.ke',
            ])
            ->assertRedirect(route('staff.registrations.show', $registration))
            ->assertSessionHas('success');

        $registration->refresh();
        $this->assertSame('approved', $registration->status);

        $staff = Staff::where('id_number', '40699208')->first();
        $this->assertNotNull($staff);
        $this->assertSame('RKS/STAFF/249', $staff->staff_id);
        $this->assertSame('j.nereah@royalkingsschools.sc.ke', $staff->work_email);
        $this->assertTrue($staff->user->hasRole('Teacher') || $staff->user->hasRole('teacher'));
    }
}
