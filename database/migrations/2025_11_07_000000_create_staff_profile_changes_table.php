<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_profile_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('submitted_by'); // user_id
            $table->json('changes'); // { field => ["old" => ..., "new" => ...], ... }
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable(); // admin user
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profile_changes');
    }
};
