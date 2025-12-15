<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Add name and metadata
            $table->string('name')->nullable()->after('id');
            
            // Add FK relationships
            $table->foreignId('academic_year_id')->nullable()->after('classroom_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->after('academic_year_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('stream_id')->nullable()->after('term_id')->constrained('streams')->onDelete('cascade');
            
            // Add versioning
            $table->integer('version')->default(1)->after('stream_id');
            $table->foreignId('parent_structure_id')->nullable()->after('version')->constrained('fee_structures')->onDelete('set null');
            
            // Add status
            $table->boolean('is_active')->default(true)->after('parent_structure_id');
            
            // Add audit fields
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Migrate year to academic_year_id if possible
            // Note: Requires data migration script
            
            // Add unique constraint: one active structure per (classroom, academic_year, term, stream)
            $table->unique(['classroom_id', 'academic_year_id', 'term_id', 'stream_id', 'is_active'], 
                'unique_active_structure');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['term_id']);
            $table->dropForeign(['stream_id']);
            $table->dropForeign(['parent_structure_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            
            $table->dropUnique('unique_active_structure');
            
            $table->dropColumn([
                'name', 'academic_year_id', 'term_id', 'stream_id',
                'version', 'parent_structure_id', 'is_active',
                'created_by', 'approved_by', 'approved_at'
            ]);
        });
    }
};

