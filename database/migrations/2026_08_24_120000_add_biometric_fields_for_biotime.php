<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'biometric_emp_code')) {
                $table->string('biometric_emp_code', 32)->nullable()->after('staff_id');
                $table->unique('biometric_emp_code');
            }
            if (! Schema::hasColumn('staff', 'biometric_exempt')) {
                $table->boolean('biometric_exempt')->default(false)->after('biometric_emp_code');
            }
        });

        Schema::table('staff_attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_attendance', 'source')) {
                $table->string('source', 32)->nullable()->after('notes');
            }
        });

        Schema::create('biotime_punches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biotime_transaction_id')->nullable()->unique();
            $table->string('emp_code', 64);
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('punch_time');
            $table->string('punch_state', 16)->nullable();
            $table->string('terminal_sn', 64)->nullable();
            $table->string('terminal_alias', 128)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'punch_time']);
            $table->index(['emp_code', 'punch_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biotime_punches');

        Schema::table('staff_attendance', function (Blueprint $table) {
            if (Schema::hasColumn('staff_attendance', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'biometric_emp_code')) {
                $table->dropUnique(['biometric_emp_code']);
                $table->dropColumn('biometric_emp_code');
            }
            if (Schema::hasColumn('staff', 'biometric_exempt')) {
                $table->dropColumn('biometric_exempt');
            }
        });
    }
};
