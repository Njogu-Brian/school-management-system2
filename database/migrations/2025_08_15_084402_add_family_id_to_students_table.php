<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // First ensure it's the correct type
            $table->unsignedBigInteger('family_id')->nullable()->change();

            // Add foreign key constraint
            $table->foreign('family_id')->references('id')->on('families')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
        });
    }
};

