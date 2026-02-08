<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Family;
use App\Models\FamilyUpdateLink;
use App\Models\PaymentLink;
use App\Models\Student;

class CleanupSingleChildFamilies extends Command
{
    protected $signature = 'families:cleanup-single-child
                            {--dry-run : List what would be done without making changes}
                            {--force : Skip confirmation}';

    protected $description = 'Delete all families that have exactly one child (unlink student, delete family). Then report payment links with no displayable name.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $families = Family::withCount('students')->having('students_count', '=', 1)->get();

        if ($families->isEmpty()) {
            $this->info('No families with exactly one child found.');
        } else {
            $this->warn('Found ' . $families->count() . ' family/families with exactly one child.');
            foreach ($families as $f) {
                $student = $f->students()->first();
                $this->line('  - Family ID ' . $f->id . ': 1 student → ' . ($student ? $student->full_name . ' (ID ' . $student->id . ')' : '?'));
            }

            if (!$dryRun) {
                if (!$force && !$this->confirm('Unlink these students and delete these families?')) {
                    $this->info('Cancelled.');
                    return 0;
                }

                foreach ($families as $family) {
                    DB::transaction(function () use ($family) {
                        $student = $family->students()->first();
                        if ($student) {
                            $student->update(['family_id' => null]);
                        }
                        FamilyUpdateLink::where('family_id', $family->id)->delete();
                        PaymentLink::where('family_id', $family->id)->whereNull('student_id')->update(['status' => 'expired']);
                        $family->delete();
                    });
                }
                $this->info('Deleted ' . $families->count() . ' single-child families.');
            } else {
                $this->info('[Dry run] No changes made.');
            }
        }

        $this->newLine();
        $this->info('--- Payment links with no name (review) ---');

        $noNameLinks = PaymentLink::with(['student', 'family.students'])
            ->get()
            ->filter(function ($link) {
                if ($link->student_id) {
                    return !$link->student; // student missing
                }
                if ($link->family_id) {
                    return !$link->family || $link->family->students->isEmpty(); // no family or no students
                }
                return true; // no student, no family
            });

        if ($noNameLinks->isEmpty()) {
            $this->info('No payment links without a displayable name.');
            return 0;
        }

        $rows = $noNameLinks->map(function ($link) {
            if ($link->student_id) {
                $reason = 'student missing';
            } elseif ($link->family_id) {
                $reason = !$link->family ? 'family missing' : ($link->family->students->isEmpty() ? 'family has no students' : 'family missing');
            } else {
                $reason = 'no student, no family';
            }
            return [
                $link->id,
                $link->hashed_id,
                $link->payment_reference,
                $link->student_id ?? '—',
                $link->family_id ?? '—',
                $link->status,
                $reason,
            ];
        })->toArray();

        $this->table(
            ['ID', 'Hashed ID', 'Reference', 'student_id', 'family_id', 'Status', 'Reason'],
            $rows
        );
        $this->warn('Total: ' . $noNameLinks->count() . ' payment link(s) with no name. Review and expire or reassign as needed.');

        return 0;
    }
}
