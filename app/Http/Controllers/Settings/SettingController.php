<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\StaffCategory;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\CustomField;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SettingController extends Controller
{
    /**
     * Show General Settings page
     */
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

        $enabledModules = [];
        if (isset($settings['enabled_modules'])) {
            $enabledModules = json_decode($settings['enabled_modules']->value, true);
        }

        return view('settings.index', [
            'settings'       => $settings,
            'modules'        => $availableModules,
            'enabledModules' => $enabledModules ?? [],
        ]);
    }

    /**
     * Update General Settings (school info, branding, etc.)
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'school_name'       => 'required|string|max:255',
            'school_email'      => 'nullable|email',
            'school_phone'      => 'nullable|string|max:20',
            'school_address'    => 'nullable|string|max:255',
            'school_logo'       => 'nullable|image|mimes:jpg,jpeg,png',
            'login_background'  => 'nullable|image|mimes:jpg,jpeg,png',
            'timezone'          => 'nullable|string|max:100',
            'currency'          => 'nullable|string|max:10',
            'system_version'    => 'nullable|string|max:50',
            'auto_backup'       => 'nullable|in:yes,no',
        ]);

        try {
            // Save text fields
            foreach ($request->except(['_token', 'school_logo', 'login_background']) as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }

            // Save images to /public/images
            foreach (['school_logo', 'login_background'] as $imageKey) {
                if ($request->hasFile($imageKey)) {
                    $filename = time() . '_' . $request->file($imageKey)->getClientOriginalName();
                    $request->file($imageKey)->move(public_path('images'), $filename);

                    Setting::updateOrCreate(['key' => $imageKey], ['value' => $filename]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Settings update failed: " . $e->getMessage());
            return back()->withErrors('Error saving settings. Please try again.');
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Update enabled modules
     */
    public function updateModules(Request $request)
    {
        $request->validate(['modules' => 'array']);

        try {
            Setting::updateOrCreate(
                ['key' => 'enabled_modules'],
                ['value' => json_encode($request->modules ?? [])]
            );
        } catch (\Exception $e) {
            Log::error("Failed to update modules: " . $e->getMessage());
            return back()->withErrors('Error updating modules.');
        }

        return redirect()->back()->with('success', 'Modules updated successfully.');
    }

    /**
     * Update Regional settings (timezone, currency)
     */
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

    /**
     * Update System settings (version, backup toggle)
     */
    public function updateSystem(Request $request)
    {
        $request->validate([
            'system_version' => 'nullable|string|max:50',
            'enable_backup'  => 'nullable|in:0,1',
        ]);

        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'System options updated.');
    }

    /**
     * Update Branding (school logo, login background, and finance colors/fonts)
     */
    public function updateBranding(Request $request)
    {
        $request->validate([
            'school_logo'      => 'nullable|image|mimes:jpg,jpeg,png',
            'login_background' => 'nullable|image|mimes:jpg,jpeg,png',
            'finance_primary_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_success_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_warning_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_danger_color'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_info_color'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_surface_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_border_color'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_text_color'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_muted_color'     => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'finance_primary_font'    => 'nullable|string|max:100',
            'finance_heading_font'    => 'nullable|string|max:100',
        ]);

        // Handle file uploads
        foreach (['school_logo', 'login_background'] as $imageKey) {
            if ($request->hasFile($imageKey)) {
                $filename = time() . '_' . $request->file($imageKey)->getClientOriginalName();
                $request->file($imageKey)->move(public_path('images'), $filename);

                Setting::updateOrCreate(['key' => $imageKey], ['value' => $filename]);
            }
        }

        // Handle finance color settings
        $colorKeys = [
            'finance_primary_color',
            'finance_secondary_color',
            'finance_success_color',
            'finance_warning_color',
            'finance_danger_color',
            'finance_info_color',
            'finance_surface_color',
            'finance_border_color',
            'finance_text_color',
            'finance_muted_color',
            'finance_primary_font',
            'finance_heading_font',
        ];

        foreach ($colorKeys as $key) {
            if ($request->has($key) && $request->$key) {
                Setting::updateOrCreate(['key' => $key], ['value' => $request->$key]);
            }
        }

        return back()->with('success', 'Branding settings updated successfully.');
    }

    /**
     * Update ID prefix & counters
     */
    public function updateIdSettings(Request $request)
    {
        $request->validate([
            'staff_id_prefix'   => 'required|string|max:10',
            'staff_id_start'    => 'required|integer|min:1',
            'student_id_prefix' => 'required|string|max:10',
            'student_id_start'  => 'required|integer|min:1',
        ]);

        // Save to settings table
        Setting::set('staff_id_prefix', $request->staff_id_prefix);
        Setting::setInt('staff_id_start', $request->staff_id_start);
        Setting::set('student_id_prefix', $request->student_id_prefix);
        Setting::setInt('student_id_start', $request->student_id_start);

        // Initialize student_id_counter if it doesn't exist
        if (!Setting::where('key', 'student_id_counter')->exists()) {
            Setting::setInt('student_id_counter', $request->student_id_start);
        }

        return redirect()->back()->with('success', 'ID settings updated successfully.');
    }

    public function placeholders()
    {
        $systemPlaceholders = [
            ['key' => 'school_name', 'value' => setting('school_name')],
            ['key' => 'school_phone', 'value' => setting('school_phone')],
            ['key' => 'date', 'value' => now()->format('d M Y')],
            ['key' => 'student_name', 'value' => 'Student’s full name'],
            ['key' => 'class_name', 'value' => 'Classroom name'],
            ['key' => 'father_name', 'value' => 'Parent’s full name'],
            ['key' => 'staff_name', 'value' => 'Staff full name'],
        ];

        $customPlaceholders = \App\Models\CustomPlaceholder::all();

        return view('settings.partials.placeholders', compact('systemPlaceholders', 'customPlaceholders'));
    }

    public function storePlaceholder(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|unique:custom_placeholders,key',
            'value' => 'required|string',
        ]);

        \App\Models\CustomPlaceholder::create($data);
        return back()->with('success', 'Placeholder added successfully.');
    }

}
