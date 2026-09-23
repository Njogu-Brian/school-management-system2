<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expense_statement_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_statement_imports', 'pdf_password')) {
                $table->text('pdf_password')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('expense_statement_imports', 'verification_code')) {
                $table->string('verification_code', 64)->nullable()->after('pdf_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_statement_imports', function (Blueprint $table) {
            if (Schema::hasColumn('expense_statement_imports', 'verification_code')) {
                $table->dropColumn('verification_code');
            }
            if (Schema::hasColumn('expense_statement_imports', 'pdf_password')) {
                $table->dropColumn('pdf_password');
            }
        });
    }
};
