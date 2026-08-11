<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control-plane school registry (DB-per-school SaaS).
 * Lives on the control-plane host; each row points at a tenant ERP API URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools_registry', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->string('api_base_url');
            $table->string('status', 32)->default('active'); // active | suspended | provisioning
            $table->string('logo_url')->nullable();
            $table->string('primary_color', 16)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools_registry');
    }
};
