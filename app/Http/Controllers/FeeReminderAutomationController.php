<?php

namespace App\Http\Controllers;

use App\Services\FeeReminderAutomationSettings;
use Illuminate\Http\Request;

class FeeReminderAutomationController extends Controller
{
    public function edit()
    {
        $settings = FeeReminderAutomationSettings::load();

        return view('communication.fee_reminder_automation', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'send_time' => ['required', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
            'days_before_due' => 'nullable|string|max:500',
            'days_after_overdue' => 'nullable|string|max:500',
            'channels_before_due' => 'nullable|array',
            'channels_before_due.*' => 'in:email,sms,whatsapp',
            'channels_on_due' => 'nullable|array',
            'channels_on_due.*' => 'in:email,sms,whatsapp',
            'channels_after_overdue' => 'nullable|array',
            'channels_after_overdue.*' => 'in:email,sms,whatsapp',
            'clearance_enabled' => 'nullable|boolean',
            'clearance_days_before' => 'nullable|string|max:500',
            'clearance_days_after' => 'nullable|string|max:500',
            'clearance_channels_before' => 'nullable|array',
            'clearance_channels_before.*' => 'in:email,sms,whatsapp',
            'clearance_channels_on' => 'nullable|array',
            'clearance_channels_on.*' => 'in:email,sms,whatsapp',
            'clearance_channels_after' => 'nullable|array',
            'clearance_channels_after.*' => 'in:email,sms,whatsapp',
        ]);

        $validated['enabled'] = $request->boolean('enabled');
        $validated['clearance_enabled'] = $request->boolean('clearance_enabled');

        FeeReminderAutomationSettings::fromValidatedArray($validated)->save();

        return redirect()->route('communication.fee-reminder-automation.edit')
            ->with('success', 'Fee reminder automation settings saved.');
    }
}
