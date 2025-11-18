<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        // All PDF branding fields will be stored in settings table as key-value pairs
        // No table structure changes needed - settings table already supports key-value storage
        // This migration ensures default PDF branding settings exist in settings table
        
        if (Schema::hasTable('settings')) {
            // Create default PDF branding settings if they don't exist
            $defaultSettings = [
                'pdf_header_html' => null,
                'pdf_footer_html' => null,
                'pdf_logo_path' => null,
                'pdf_watermark' => null,
                'report_card_template' => 'default',
            ];

            foreach ($defaultSettings as $key => $defaultValue) {
                if (!DB::table('settings')->where('key', $key)->exists()) {
                    DB::table('settings')->insert([
                        'key' => $key,
                        'value' => $defaultValue,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // If branding_settings table exists, migrate data to settings table
        if (Schema::hasTable('branding_settings')) {
            $brandingSettings = DB::table('branding_settings')->first();
            
            if ($brandingSettings) {
                $mappings = [
                    'header_html' => 'pdf_header_html',
                    'footer_html' => 'pdf_footer_html',
                    'report_card_template' => 'report_card_template',
                ];

                foreach ($mappings as $brandingKey => $settingsKey) {
                    if (isset($brandingSettings->$brandingKey) && $brandingSettings->$brandingKey !== null) {
                        Setting::updateOrCreate(
                            ['key' => $settingsKey],
                            ['value' => (string) $brandingSettings->$brandingKey]
                        );
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Remove PDF branding settings from settings table
        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', [
                'pdf_header_html',
                'pdf_footer_html',
                'pdf_logo_path',
                'pdf_watermark',
                'report_card_template',
            ])->delete();
        }
    }
};

