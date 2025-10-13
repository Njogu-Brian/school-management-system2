<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('homework', function (Blueprint $table) {
            if (!Schema::hasColumn('homework','assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->after('id')->constrained('users');
            }
            if (!Schema::hasColumn('homework','target_scope')) {
                $table->enum('target_scope',['class','stream','students','school'])->default('class')->after('due_date');
            }
        });
    }

    public function down(): void {
        Schema::table('homework', function (Blueprint $table) {
            if (Schema::hasColumn('homework','assigned_by')) {
                $table->dropConstrainedForeignId('assigned_by');
            }
            if (Schema::hasColumn('homework','target_scope')) {
                $table->dropColumn('target_scope');
            }
        });
    }
};
