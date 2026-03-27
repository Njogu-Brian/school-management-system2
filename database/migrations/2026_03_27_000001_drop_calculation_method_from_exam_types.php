<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_types') && Schema::hasColumn('exam_types', 'calculation_method')) {
            Schema::table('exam_types', function (Blueprint $table) {
                $table->dropColumn('calculation_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_types') && !Schema::hasColumn('exam_types', 'calculation_method')) {
            Schema::table('exam_types', function (Blueprint $table) {
                $table->enum('calculation_method', ['average','sum','weighted','best_of','pass_fail','cbc'])
                    ->default('average')
                    ->after('code');
            });
        }
    }
};
