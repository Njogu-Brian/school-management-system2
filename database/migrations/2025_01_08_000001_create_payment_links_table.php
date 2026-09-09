<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_links')) {
            return;
        }

        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 20)->unique();
            $table->string('hashed_id', 20)->unique();
            // Parent tables (students, invoices, families, payments) are created in later
            // migrations, so keep unsigned IDs here and add FKs only when those tables exist.
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('family_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('description')->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('max_uses')->default(1);
            $table->integer('use_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('invoice_id');
            $table->index('status');
            $table->index('expires_at');
        });

        $foreigns = [
            ['student_id', 'students', 'cascade'],
            ['invoice_id', 'invoices', 'set null'],
            ['family_id', 'families', 'set null'],
            ['payment_id', 'payments', 'set null'],
            ['created_by', 'users', 'set null'],
        ];
        foreach ($foreigns as [$column, $parent, $onDelete]) {
            if (Schema::hasTable($parent)) {
                Schema::table('payment_links', function (Blueprint $table) use ($column, $parent, $onDelete) {
                    $table->foreign($column)->references('id')->on($parent)->onDelete($onDelete);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};

