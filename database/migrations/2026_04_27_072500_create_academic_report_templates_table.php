<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_report_templates')) {
            return;
        }
        Schema::create('academic_report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft|published|archived
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamp('open_from')->nullable();
            $table->timestamp('open_until')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['open_from', 'open_until']);
            // Intentionally no FK: some deployments use custom auth user tables/engines.
            $table->index(['created_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_report_templates');
    }
};

