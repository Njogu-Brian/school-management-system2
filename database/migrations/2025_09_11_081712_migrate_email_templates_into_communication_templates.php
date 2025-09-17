<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_templates') && Schema::hasTable('communication_templates')) {
            $emails = DB::table('email_templates')->get();

            foreach ($emails as $email) {
                $exists = DB::table('communication_templates')->where('code', $email->code)->exists();

                if (! $exists) {
                    DB::table('communication_templates')->insert([
                        'code'       => $email->code,
                        'title'      => $email->title,
                        'type'       => 'email',
                        'subject'    => $email->title, // adjust if you had a subject field
                        'content'    => $email->message,
                        'attachment' => $email->attachment,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Remove only migrated records that were unique
        DB::table('communication_templates')->where('type', 'email')->delete();
    }
};
