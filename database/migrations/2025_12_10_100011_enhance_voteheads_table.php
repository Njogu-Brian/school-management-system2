<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            // Add code for unique identification
            if (!Schema::hasColumn('voteheads', 'code')) {
                $table->string('code')->unique()->nullable()->after('id');
            }
            
            // Add category for grouping
            if (!Schema::hasColumn('voteheads', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
            
            // Add status
            if (!Schema::hasColumn('voteheads', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('charge_type');
            }
            
            // Ensure charge_type enum includes all required values
            // Note: May need to alter enum if new values needed
            
            $table->index('code');
            $table->index('is_active');
            $table->index('charge_type');
        });
    }

    public function down(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['charge_type']);
            
            if (Schema::hasColumn('voteheads', 'code')) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('voteheads', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('voteheads', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};

