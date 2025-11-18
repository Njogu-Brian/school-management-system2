<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->text('content');
                $table->boolean('active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('announcements', function (Blueprint $table) {
                if (!Schema::hasColumn('announcements', 'active')) {
                    $table->boolean('active')->default(true);
                }
                if (!Schema::hasColumn('announcements', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
