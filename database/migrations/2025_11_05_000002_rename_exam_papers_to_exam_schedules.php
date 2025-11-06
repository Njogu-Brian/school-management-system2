<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('exam_papers') && !Schema::hasTable('exam_schedules')) {
            Schema::rename('exam_papers', 'exam_schedules');
        }

        Schema::table('exam_schedules', function (Blueprint $t) {
            if (!Schema::hasColumn('exam_schedules','duration_minutes')) {
                $t->unsignedInteger('duration_minutes')->nullable()->after('start_time');
            }
            if (!Schema::hasColumn('exam_schedules','min_mark')) {
                $t->decimal('min_mark', 6, 2, true)->nullable()->after('duration_minutes');
            }
            if (!Schema::hasColumn('exam_schedules','max_mark')) {
                $t->decimal('max_mark', 6, 2, true)->nullable()->after('min_mark');
            }
            if (!Schema::hasColumn('exam_schedules','weight')) {
                $t->decimal('weight', 5, 2, true)->default(1)->after('max_mark');
            }
            if (!Schema::hasColumn('exam_schedules','room')) {
                $t->string('room')->nullable()->after('weight');
            }
            if (!Schema::hasColumn('exam_schedules','invigilator_id')) {
                $t->foreignId('invigilator_id')->nullable()->after('room')->constrained('staff');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_schedules') && !Schema::hasTable('exam_papers')) {
            Schema::rename('exam_schedules', 'exam_papers');
        }
    }
};
