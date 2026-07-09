<?php

namespace App\Console\Commands;

use App\Models\PayrollRecord;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix duplicate staff created by HrPayrollJune2026Seeder:
 * - Keep Lilian Atieno (RKS/STAFF/229), remove Lyn Office (RKS/STAFF/241)
 * - Keep Philemon Iminza (RKS/STAFF/207), remove Susan Wanjiru (RKS/STAFF/232)
 *   and move Susan's June payroll onto Philemon.
 */
class FixHrPayrollDuplicateStaff extends Command
{
    protected $signature = 'hr:fix-duplicate-staff {--dry-run : Show actions without writing}';

    protected $description = 'Merge/remove Lyn Office and Susan Wanjiru duplicates into Lilian Atieno and Philemon Iminza';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $lilian = Staff::where('staff_id', 'RKS/STAFF/229')->orWhere(function ($q) {
            $q->where('first_name', 'Lilian')->where('last_name', 'Atieno');
        })->first();

        $lynOffice = Staff::where('staff_id', 'RKS/STAFF/241')->orWhere(function ($q) {
            $q->where('first_name', 'Lyn')->where('last_name', 'Office');
        })->first();

        $philemon = Staff::where('staff_id', 'RKS/STAFF/207')->orWhere(function ($q) {
            $q->where('first_name', 'Philemon')->where('last_name', 'Iminza');
        })->first();

        $susan = Staff::where('staff_id', 'RKS/STAFF/232')->orWhere(function ($q) {
            $q->where('first_name', 'Susan')->where('last_name', 'Wanjiru');
        })->first();

        $this->info('Resolved staff:');
        $this->line('  KEEP Lilian:   ' . ($lilian ? "#{$lilian->id} {$lilian->staff_id} {$lilian->full_name}" : 'NOT FOUND'));
        $this->line('  DELETE Lyn:    ' . ($lynOffice ? "#{$lynOffice->id} {$lynOffice->staff_id} {$lynOffice->full_name}" : 'NOT FOUND'));
        $this->line('  KEEP Philemon: ' . ($philemon ? "#{$philemon->id} {$philemon->staff_id} {$philemon->full_name}" : 'NOT FOUND'));
        $this->line('  DELETE Susan:  ' . ($susan ? "#{$susan->id} {$susan->staff_id} {$susan->full_name}" : 'NOT FOUND'));

        if (! $lilian || ! $philemon) {
            $this->error('Cannot proceed: keep targets missing.');
            return self::FAILURE;
        }

        if ($dry) {
            $this->warn('Dry-run only — no changes written.');
            $this->preview($lilian, $lynOffice, $philemon, $susan);
            return self::SUCCESS;
        }

        DB::transaction(function () use ($lilian, $lynOffice, $philemon, $susan) {
            if ($lynOffice) {
                $this->mergePayrollToKeeper($lynOffice->id, $lilian->id);
                $this->reassignStaffFks($lynOffice->id, $lilian->id);
                // Prefer Lilian's bank details; if Lyn had useful salary and Lilian missing, copy gross.
                if (empty($lilian->basic_salary) && $lynOffice->basic_salary) {
                    $lilian->basic_salary = $lynOffice->basic_salary;
                    $lilian->saveQuietly();
                }
                $this->deleteStaffAndUser($lynOffice);
                $this->info("Deleted Lyn Office (#{$lynOffice->id}) — kept Lilian Atieno (#{$lilian->id}).");
            } else {
                $this->warn('Lyn Office already absent.');
            }

            if ($susan) {
                // Philemon is the real person; Susan was a budget alias with his phone + preschool pay.
                $philemon->payment_method = $susan->payment_method ?: 'mpesa';
                if ($susan->basic_salary) {
                    $philemon->basic_salary = $susan->basic_salary;
                }
                if ($susan->phone_number && empty($philemon->phone_number)) {
                    $philemon->phone_number = $susan->phone_number;
                }
                // Ensure MPESA when no bank account
                if (empty($philemon->bank_account)) {
                    $philemon->payment_method = 'mpesa';
                }
                $philemon->saveQuietly();

                $this->mergePayrollToKeeper($susan->id, $philemon->id);
                $this->reassignStaffFks($susan->id, $philemon->id);
                $this->deleteStaffAndUser($susan);
                $this->info("Deleted Susan Wanjiru (#{$susan->id}) — kept Philemon Iminza (#{$philemon->id}).");
            } else {
                $this->warn('Susan Wanjiru already absent.');
            }
        });

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function preview(Staff $lilian, ?Staff $lyn, Staff $philemon, ?Staff $susan): void
    {
        if ($lyn) {
            $this->line("Would move payroll from Lyn #{$lyn->id} -> Lilian #{$lilian->id}, then delete Lyn + user #{$lyn->user_id}");
            $this->line('  Lyn payroll rows: ' . PayrollRecord::where('staff_id', $lyn->id)->count());
        }
        if ($susan) {
            $this->line("Would move payroll from Susan #{$susan->id} -> Philemon #{$philemon->id}, then delete Susan + user #{$susan->user_id}");
            $this->line('  Susan payroll rows: ' . PayrollRecord::where('staff_id', $susan->id)->count());
            $this->line("  Philemon would get payment_method=mpesa, basic_salary={$susan->basic_salary}");
        }
    }

    private function mergePayrollToKeeper(int $fromStaffId, int $toStaffId): void
    {
        $fromRows = PayrollRecord::where('staff_id', $fromStaffId)->get();
        foreach ($fromRows as $row) {
            $existing = PayrollRecord::where('payroll_period_id', $row->payroll_period_id)
                ->where('staff_id', $toStaffId)
                ->first();

            if ($existing) {
                // Keep the richer record (higher gross / more deductions), delete the other.
                $keepFrom = (float) $row->gross_salary > (float) $existing->gross_salary
                    || (float) $row->total_deductions > (float) $existing->total_deductions;
                if ($keepFrom) {
                    $existing->delete();
                    $row->staff_id = $toStaffId;
                    $row->saveQuietly();
                } else {
                    $row->delete();
                }
            } else {
                $row->staff_id = $toStaffId;
                $row->saveQuietly();
            }
        }
    }

    private function reassignStaffFks(int $fromStaffId, int $toStaffId): void
    {
        $tables = [
            'salary_structures' => 'staff_id',
            'staff_statutory_exemptions' => 'staff_id',
            'staff_advances' => 'staff_id',
            'custom_deductions' => 'staff_id',
            'salary_histories' => 'staff_id',
            'staff_attendance' => 'staff_id',
            'leave_requests' => 'staff_id',
            'staff_leave_balances' => 'staff_id',
            'staff_documents' => 'staff_id',
            'staff_metas' => 'staff_id',
            'staff_meta' => 'staff_id',
            'staff_profile_changes' => 'staff_id',
            'staff_skills' => 'staff_id',
            'staff_qualifications' => 'staff_id',
            'staff_certifications' => 'staff_id',
        ];

        foreach ($tables as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            try {
                DB::table($table)->where($column, $fromStaffId)->update([$column => $toStaffId]);
            } catch (\Throwable $e) {
                // Unique conflicts: delete from-side duplicates
                DB::table($table)->where($column, $fromStaffId)->delete();
            }
        }

        // Clear supervisor refs pointing at deleted staff
        if (Schema::hasColumn('staff', 'supervisor_id')) {
            Staff::where('supervisor_id', $fromStaffId)->update(['supervisor_id' => $toStaffId]);
        }
        if (Schema::hasTable('staff_supervisor')) {
            DB::table('staff_supervisor')->where('staff_id', $fromStaffId)->update(['staff_id' => $toStaffId]);
            DB::table('staff_supervisor')->where('supervisor_staff_id', $fromStaffId)->update(['supervisor_staff_id' => $toStaffId]);
        }
    }

    private function deleteStaffAndUser(Staff $staff): void
    {
        $userId = $staff->user_id;

        // Remove remaining child rows that still point at this staff
        foreach ([
            'payroll_records', 'salary_structures', 'staff_statutory_exemptions',
            'staff_advances', 'custom_deductions', 'salary_histories',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'staff_id')) {
                DB::table($table)->where('staff_id', $staff->id)->delete();
            }
        }

        $staff->delete();

        if ($userId) {
            $stillLinked = Staff::where('user_id', $userId)->exists();
            if (! $stillLinked) {
                User::where('id', $userId)->delete();
            }
        }
    }
}
