<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if credit_notes table exists, create if not
        if (!Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->onDelete('set null');
                $table->string('credit_note_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->date('issued_at');
                $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index('credit_note_number');
            });
        } else {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_notes', 'id')) {
                    $table->id()->first();
                }
                if (!Schema::hasColumn('credit_notes', 'credit_note_number')) {
                    $table->string('credit_note_number')->unique()->after('id');
                }
                if (!Schema::hasColumn('credit_notes', 'invoice_item_id')) {
                    $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('invoice_items')->onDelete('set null');
                }
                if (!Schema::hasColumn('credit_notes', 'notes')) {
                    $table->text('notes')->nullable()->after('reason');
                }
                if (!Schema::hasColumn('credit_notes', 'issued_by')) {
                    $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->onDelete('set null');
                }
            });
        }
        
        // Check if debit_notes table exists, create if not
        if (!Schema::hasTable('debit_notes')) {
            Schema::create('debit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->onDelete('set null');
                $table->string('debit_note_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->date('issued_at');
                $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index('debit_note_number');
            });
        } else {
            Schema::table('debit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('debit_notes', 'id')) {
                    $table->id()->first();
                }
                if (!Schema::hasColumn('debit_notes', 'debit_note_number')) {
                    $table->string('debit_note_number')->unique()->after('id');
                }
                if (!Schema::hasColumn('debit_notes', 'invoice_item_id')) {
                    $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('invoice_items')->onDelete('set null');
                }
                if (!Schema::hasColumn('debit_notes', 'notes')) {
                    $table->text('notes')->nullable()->after('reason');
                }
                if (!Schema::hasColumn('debit_notes', 'issued_by')) {
                    $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        // Only drop columns if tables exist and were modified
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (Schema::hasColumn('credit_notes', 'issued_by')) {
                    $table->dropForeign(['issued_by']);
                }
                if (Schema::hasColumn('credit_notes', 'invoice_item_id')) {
                    $table->dropForeign(['invoice_item_id']);
                }
            });
        }
        
        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                if (Schema::hasColumn('debit_notes', 'issued_by')) {
                    $table->dropForeign(['issued_by']);
                }
                if (Schema::hasColumn('debit_notes', 'invoice_item_id')) {
                    $table->dropForeign(['invoice_item_id']);
                }
            });
        }
    }
};

