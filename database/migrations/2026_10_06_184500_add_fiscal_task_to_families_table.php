<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            if (!Schema::hasColumn('families', 'fiscal_task')) {
                $table->string('fiscal_task', 16)->nullable()->after('mother_email');
                $table->index('fiscal_task');
            }
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            if (Schema::hasColumn('families', 'fiscal_task')) {
                $table->dropIndex(['fiscal_task']);
                $table->dropColumn('fiscal_task');
            }
        });
    }
};
