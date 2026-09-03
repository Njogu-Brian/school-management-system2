<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_activity_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('votehead_id')->constrained('voteheads')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('term');
            $table->string('action', 16); // join | leave
            $table->string('status', 16)->default('pending'); // pending | approved | rejected | cancelled
            $table->decimal('requested_amount', 12, 2)->default(0);
            $table->text('parent_note')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['student_id', 'year', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_activity_change_requests');
    }
};
