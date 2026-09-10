<?php

namespace Tests\Feature\Students;

use App\Models\User;
use Tests\TestCase;

class StudentAdmissionWorkflowTest extends TestCase
{
    public function test_authorized_user_sees_the_five_step_admission_wizard(): void
    {
        $user = $this->createUser([], 'Admin');

        $response = $this->actingAs($user)->get(route('students.create'));

        $response->assertOk()
            ->assertSee('admissionWizard')
            ->assertSee('Student Information')
            ->assertSee('Parent / Guardian')
            ->assertSee('Academic Information')
            ->assertSee('Additional Information')
            ->assertSee('Review &amp; Submit', false)
            ->assertSee('studentAdmissionForm')
            ->assertSee(route('students.store'), false);
    }

    public function test_view_only_role_cannot_open_admission_workflow(): void
    {
        $user = $this->createUser([], 'Parent');

        $this->actingAs($user)
            ->get(route('students.create'))
            ->assertForbidden();
    }

    public function test_admission_validation_redirect_preserves_entered_input(): void
    {
        $user = $this->createUser([], 'Admin');
        $enteredFirstName = 'Validation Preserve';

        $response = $this->actingAs($user)->post(route('students.store'), [
            'first_name' => $enteredFirstName,
            'last_name' => 'Student',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasInput('first_name', $enteredFirstName);
        $response->assertSessionHasErrors();
    }
}
