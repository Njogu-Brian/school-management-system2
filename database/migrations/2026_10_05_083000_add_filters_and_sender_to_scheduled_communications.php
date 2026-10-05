<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_communications', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduled_communications', 'sender_id')) {
                $table->string('sender_id', 32)->nullable()->after('target');
            }
            if (! Schema::hasColumn('scheduled_communications', 'fee_balance_only')) {
                $table->boolean('fee_balance_only')->default(false)->after('classroom_ids');
            }
            if (! Schema::hasColumn('scheduled_communications', 'no_fee_balance_only')) {
                $table->boolean('no_fee_balance_only')->default(false)->after('fee_balance_only');
            }
            if (! Schema::hasColumn('scheduled_communications', 'exclude_staff')) {
                $table->boolean('exclude_staff')->default(false)->after('no_fee_balance_only');
            }
            if (! Schema::hasColumn('scheduled_communications', 'exclude_student_ids')) {
                $table->text('exclude_student_ids')->nullable()->after('exclude_staff');
            }
            if (! Schema::hasColumn('scheduled_communications', 'message')) {
                $table->longText('message')->nullable()->after('template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_communications', function (Blueprint $table) {
            foreach (['sender_id', 'fee_balance_only', 'no_fee_balance_only', 'exclude_staff', 'exclude_student_ids', 'message'] as $col) {
                if (Schema::hasColumn('scheduled_communications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
