<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
{
    $settings = Setting::all()->keyBy('key');

    $availableModules = [
        'attendance',
        'transport',
        'kitchen',
        'communication',
        'reports',
        'fees',
        'admissions',
        'settings',
        'users',
    ];

    $modules = [];
    if (isset($settings['enabled_modules'])) {
        $modules = json_decode($settings['enabled_modules']->value, true);
    }

    return view('settings.index', [
        'settings' => $settings,
        'modules' => $availableModules,
        'enabledModules' => $modules ?? [],
    ]);
}


    public function updateSettings(Request $request)
    {
        $request->validate([

            'school_name' => 'required|string|max:255',
            'school_email' => 'nullable|email',
            'school_phone' => 'nullable|string|max:20',
            'school_address' => 'nullable|string|max:255',

            // Branding
            'school_logo' => 'nullable|image|mimes:jpg,jpeg,png',
            'login_background' => 'nullable|image|mimes:jpg,jpeg,png',

            // Regional
            'timezone' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',

            // System
            'system_version' => 'nullable|string|max:50',
            'auto_backup' => 'nullable|in:yes,no',
        ]);

        // Save text fields
        foreach ($request->except(['_token', 'school_logo', 'login_background']) as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Save images
        foreach (['school_logo', 'login_background'] as $imageKey) {
            if ($request->hasFile($imageKey)) {
                $file = $request->file($imageKey)->store('branding', 'public');
                Setting::updateOrCreate(['key' => $imageKey], ['value' => $file]);
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function updateModules(Request $request)
    {
        $request->validate([
            'modules' => 'array',
        ]);

        Setting::updateOrCreate(
            ['key' => 'enabled_modules'],
            ['value' => json_encode($request->modules ?? [])]
        );

        return redirect()->back()->with('success', 'Modules updated successfully.');
    }
    public function updateRegional(Request $request)
    {
        $request->validate([
            'timezone' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
        ]);

        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Regional settings updated.');
    }

    public function updateSystem(Request $request)
    {
        $request->validate([
            'system_version' => 'nullable|string|max:50',
            'enable_backup' => 'nullable|in:0,1',
        ]);

        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'System options updated.');
    }

    public function updateBranding(Request $request)
    {
        $request->validate([
            'school_logo' => 'nullable|image|mimes:jpg,jpeg,png',
            'login_background' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        foreach (['school_logo', 'login_background'] as $imageKey) {
            if ($request->hasFile($imageKey)) {
                $filePath = $request->file($imageKey)->store('branding', 'public');
                Setting::updateOrCreate(['key' => $imageKey], ['value' => $filePath]);
            }
        }

        return back()->with('success', 'Branding updated.');
    }

}

