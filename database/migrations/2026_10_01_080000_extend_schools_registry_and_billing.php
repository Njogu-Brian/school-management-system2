<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control-plane extensions: per-school DB credentials + billing fields.
 * Run against the control-plane database (edulynk_control).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schools_registry')) {
            Schema::create('schools_registry', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name');
                $table->string('slug', 64)->unique();
                $table->string('api_base_url');
                $table->string('status', 32)->default('active');
                $table->string('logo_url')->nullable();
                $table->string('primary_color', 16)->nullable();
                $table->string('secondary_color', 16)->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 32)->nullable();
                $table->string('db_name', 64)->nullable();
                $table->string('db_username', 64)->nullable();
                $table->text('db_password_encrypted')->nullable();
                $table->string('db_host', 128)->nullable()->default('127.0.0.1');
                $table->unsignedSmallInteger('db_port')->nullable()->default(3306);
                $table->string('billing_status', 32)->default('trial'); // trial|current|overdue|suspended
                $table->decimal('monthly_fee', 12, 2)->default(0);
                $table->date('next_due_date')->nullable();
                $table->unsignedInteger('student_count_cached')->default(0);
                $table->timestamp('provisioned_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index('status');
                $table->index('billing_status');
            });
        } else {
            Schema::table('schools_registry', function (Blueprint $table) {
                if (! Schema::hasColumn('schools_registry', 'secondary_color')) {
                    $table->string('secondary_color', 16)->nullable()->after('primary_color');
                }
                if (! Schema::hasColumn('schools_registry', 'db_name')) {
                    $table->string('db_name', 64)->nullable()->after('contact_phone');
                }
                if (! Schema::hasColumn('schools_registry', 'db_username')) {
                    $table->string('db_username', 64)->nullable()->after('db_name');
                }
                if (! Schema::hasColumn('schools_registry', 'db_password_encrypted')) {
                    $table->text('db_password_encrypted')->nullable()->after('db_username');
                }
                if (! Schema::hasColumn('schools_registry', 'db_host')) {
                    $table->string('db_host', 128)->nullable()->default('127.0.0.1')->after('db_password_encrypted');
                }
                if (! Schema::hasColumn('schools_registry', 'db_port')) {
                    $table->unsignedSmallInteger('db_port')->nullable()->default(3306)->after('db_host');
                }
                if (! Schema::hasColumn('schools_registry', 'billing_status')) {
                    $table->string('billing_status', 32)->default('trial')->after('db_port');
                }
                if (! Schema::hasColumn('schools_registry', 'monthly_fee')) {
                    $table->decimal('monthly_fee', 12, 2)->default(0)->after('billing_status');
                }
                if (! Schema::hasColumn('schools_registry', 'next_due_date')) {
                    $table->date('next_due_date')->nullable()->after('monthly_fee');
                }
                if (! Schema::hasColumn('schools_registry', 'student_count_cached')) {
                    $table->unsignedInteger('student_count_cached')->default(0)->after('next_due_date');
                }
                if (! Schema::hasColumn('schools_registry', 'provisioned_at')) {
                    $table->timestamp('provisioned_at')->nullable()->after('student_count_cached');
                }
            });
        }

        if (! Schema::hasTable('school_subscriptions')) {
            Schema::create('school_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_registry_id')->constrained('schools_registry')->cascadeOnDelete();
                $table->string('period', 7); // YYYY-MM
                $table->decimal('amount_due', 12, 2);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->string('status', 32)->default('open'); // open|paid|partial|waived|overdue
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['school_registry_id', 'period']);
            });
        }

        if (! Schema::hasTable('school_payments')) {
            Schema::create('school_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_registry_id')->constrained('schools_registry')->cascadeOnDelete();
                $table->foreignId('school_subscription_id')->nullable()->constrained('school_subscriptions')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('method', 64)->nullable(); // mpesa|bank|cash|other
                $table->string('reference', 128)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('recorded_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_payments');
        Schema::dropIfExists('school_subscriptions');
        // Do not drop schools_registry; only added columns would need reverse — leave table.
    }
};
