<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_info_id')->unique();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_credited', 12, 2)->default(0);
            $table->decimal('total_debited', 12, 2)->default(0);
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            $table->foreign('parent_info_id')->references('id')->on('parent_info')->onDelete('cascade');
        });

        Schema::create('parent_wallet_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_wallet_id')->constrained('parent_wallets')->onDelete('cascade');
            $table->string('type', 40); // deposit, fee_allocation, spend, adjustment
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['parent_wallet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('parent_wallet_saving_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_info_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2);
            $table->string('frequency', 20)->default('weekly');
            $table->unsignedTinyInteger('day_of_week')->default(1); // 0=Sun .. 6=Sat
            $table->time('remind_at_time')->default('08:00:00');
            $table->string('timezone', 64)->default('Africa/Nairobi');
            $table->timestamp('next_remind_at')->nullable();
            $table->boolean('active')->default(true);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->foreign('parent_info_id')->references('id')->on('parent_info')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['active', 'next_remind_at']);
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_transactions', 'parent_wallet_id')) {
                $table->unsignedBigInteger('parent_wallet_id')->nullable()->after('student_id');
            }
            if (! Schema::hasColumn('payment_transactions', 'purpose')) {
                $table->string('purpose', 40)->nullable()->after('admin_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'purpose')) {
                $table->dropColumn('purpose');
            }
            if (Schema::hasColumn('payment_transactions', 'parent_wallet_id')) {
                $table->dropColumn('parent_wallet_id');
            }
        });
        Schema::dropIfExists('parent_wallet_saving_plans');
        Schema::dropIfExists('parent_wallet_ledger');
        Schema::dropIfExists('parent_wallets');
    }
};
