<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate data from system_settings to settings table
        if (Schema::hasTable('system_settings')) {
            // Use raw DB query to avoid model issues
            $systemSettings = DB::table('system_settings')->first();
            
            if ($systemSettings) {
                // Map system_settings columns to settings keys
                $mappings = [
                    'app_version' => 'app_version',
                    'maintenance_mode' => 'maintenance_mode',
                    'show_announcements' => 'show_announcements',
                    'staff_id_prefix' => 'staff_id_prefix',
                    'staff_id_start' => 'staff_id_start',
                    'student_id_prefix' => 'student_id_prefix',
                    'student_id_start' => 'student_id_start',
                    'school_name' => 'school_name',
                    'phone' => 'school_phone',
                    'email' => 'school_email',
                    'address' => 'school_address',
                    'current_term' => 'current_term',
                    'current_year' => 'current_year',
                ];

                // Migrate each field that exists in the table
                foreach ($mappings as $systemKey => $settingsKey) {
                    if (isset($systemSettings->$systemKey) && $systemSettings->$systemKey !== null) {
                        // Convert boolean to string for settings table
                        $value = is_bool($systemSettings->$systemKey) 
                            ? ($systemSettings->$systemKey ? '1' : '0')
                            : (string) $systemSettings->$systemKey;
                        
                        // Only migrate if setting doesn't already exist (don't overwrite existing settings)
                        if (!DB::table('settings')->where('key', $settingsKey)->exists()) {
                            DB::table('settings')->insert([
                                'key' => $settingsKey,
                                'value' => $value,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // Check for PDF branding fields if they exist
                $pdfFields = ['pdf_header_html', 'pdf_footer_html', 'pdf_logo_path', 'pdf_watermark'];
                foreach ($pdfFields as $field) {
                    if (isset($systemSettings->$field) && $systemSettings->$field !== null) {
                        if (!DB::table('settings')->where('key', $field)->exists()) {
                            DB::table('settings')->insert([
                                'key' => $field,
                                'value' => (string) $systemSettings->$field,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // Handle ID counter increment methods
                // If student_id_counter doesn't exist, create it from student_id_start
                if (!DB::table('settings')->where('key', 'student_id_counter')->exists()) {
                    $studentStart = $systemSettings->student_id_start ?? 1000;
                    DB::table('settings')->insert([
                        'key' => 'student_id_counter',
                        'value' => (string) $studentStart,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Drop system_settings table after migration
            Schema::dropIfExists('system_settings');
        }
    }

    public function down(): void
    {
        // Recreate system_settings table
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('app_version')->nullable();
                $table->boolean('maintenance_mode')->default(false);
                $table->boolean('show_announcements')->default(true);
                $table->string('staff_id_prefix')->default('STAFF');
                $table->unsignedBigInteger('staff_id_start')->default(1001);
                $table->string('student_id_prefix')->default('STD');
                $table->unsignedBigInteger('student_id_start')->default(5001);
                $table->text('pdf_header_html')->nullable();
                $table->text('pdf_footer_html')->nullable();
                $table->string('pdf_logo_path')->nullable();
                $table->string('pdf_watermark')->nullable();
                $table->timestamps();
            });
        }

        // Migrate data back from settings to system_settings
        $mappings = [
            'app_version' => 'app_version',
            'maintenance_mode' => 'maintenance_mode',
            'show_announcements' => 'show_announcements',
            'staff_id_prefix' => 'staff_id_prefix',
            'staff_id_start' => 'staff_id_start',
            'student_id_prefix' => 'student_id_prefix',
            'student_id_start' => 'student_id_start',
            'pdf_header_html' => 'pdf_header_html',
            'pdf_footer_html' => 'pdf_footer_html',
            'pdf_logo_path' => 'pdf_logo_path',
            'pdf_watermark' => 'pdf_watermark',
        ];

        $systemSettingData = [];
        foreach ($mappings as $settingsKey => $systemKey) {
            $setting = DB::table('settings')->where('key', $settingsKey)->first();
            if ($setting) {
                // Convert string back to boolean for boolean fields
                if (in_array($systemKey, ['maintenance_mode', 'show_announcements'])) {
                    $systemSettingData[$systemKey] = (bool) $setting->value;
                } elseif (in_array($systemKey, ['staff_id_start', 'student_id_start'])) {
                    $systemSettingData[$systemKey] = (int) $setting->value;
                } else {
                    $systemSettingData[$systemKey] = $setting->value;
                }
            }
        }

        if (!empty($systemSettingData)) {
            DB::table('system_settings')->insert(array_merge($systemSettingData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
