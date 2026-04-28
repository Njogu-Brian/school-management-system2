<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // MySQL/MariaDB store enums as native ENUM, so we must add the new value.
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `school_days` MODIFY `type` ENUM('school_day','holiday','midterm_break','term_break','weekend','custom_off_day') NOT NULL DEFAULT 'school_day'"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `school_days` MODIFY `type` ENUM('school_day','holiday','midterm_break','weekend','custom_off_day') NOT NULL DEFAULT 'school_day'"
            );
        }
    }
};

