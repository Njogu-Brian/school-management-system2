<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BladePartialsTest extends TestCase
{
    public function test_generic_alert_partials_use_shared_feedback_components(): void
    {
        session()->flash('success', 'Saved successfully.');
        $this->withViewErrors(new ViewErrorBag(['default' => new MessageBag()]));

        $this->assertStringContainsString(
            'Saved successfully.',
            view('partials.alerts')->render()
        );
        $this->assertStringContainsString(
            'alert-success',
            view('students.partials.alerts')->render()
        );
        $this->assertStringContainsString(
            'alert-success',
            view('finance.invoices.partials.alerts')->render()
        );
    }

    public function test_dashboard_flash_partial_preserves_success_and_error_feedback(): void
    {
        session()->flash('error', 'The operation failed.');

        $this->assertStringContainsString(
            'The operation failed.',
            view('dashboard.partials.flash')->render()
        );
    }
}
