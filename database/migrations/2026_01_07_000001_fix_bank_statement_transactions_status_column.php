<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change status from enum to string to support 'unmatched' and other statuses
        // Using raw SQL as Laravel doesn't easily support modifying enum columns
        DB::statement("ALTER TABLE bank_statement_transactions MODIFY COLUMN status VARCHAR(20) DEFAULT 'draft'");
        
        // Update any existing 'unmatched' values if they exist (though they shouldn't with the old enum)
        // This is just a safety measure
    }

    public function down(): void
    {
        // Revert back to enum (but this might fail if there are 'unmatched' values)
        // We'll keep it as string for safety
        DB::statement("ALTER TABLE bank_statement_transactions MODIFY COLUMN status ENUM('draft', 'confirmed', 'rejected') DEFAULT 'draft'");
    }
};

