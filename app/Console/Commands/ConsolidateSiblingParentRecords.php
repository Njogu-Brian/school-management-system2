<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidateSiblingParentRecords extends Command
{
    protected $signature = 'students:consolidate-sibling-parent-records {--dry-run : Show what would be done without making changes}';

    protected $description = 'Consolidate duplicate parent_info records for siblings with identical contact details';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made.');
            $this->newLine();
        }

        $familiesWithSiblings = DB::table('students')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotNull('family_id')
            ->where('family_id', '>', 0)
            ->select('family_id')
            ->groupBy('family_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('family_id');

        $consolidated = 0;
        $skipped = 0;
        $skippedFamilies = [];
        $deletedParents = 0;

        foreach ($familiesWithSiblings as $fid) {
            $siblings = DB::table('students')
                ->leftJoin('parent_info', 'students.parent_id', '=', 'parent_info.id')
                ->where('students.archive', 0)
                ->where('students.is_alumni', false)
                ->where('students.family_id', $fid)
                ->select(
                    'students.id as student_id',
                    'students.admission_number',
                    'students.first_name',
                    'students.last_name',
                    'students.parent_id',
                    'parent_info.father_name',
                    'parent_info.father_phone',
                    'parent_info.mother_name',
                    'parent_info.mother_phone',
                    'parent_info.guardian_phone'
                )
                ->get();

            $parentIds = $siblings->pluck('parent_id')->unique()->filter()->values();
            if ($parentIds->count() < 2) {
                continue;
            }

            // Normalize contact details for comparison
            $normalize = fn ($v) => trim((string) ($v ?? ''));
            $signatures = $siblings->map(function ($s) use ($normalize) {
                return [
                    'parent_id' => $s->parent_id,
                    'sig' => implode('|', [
                        $normalize($s->father_phone),
                        $normalize($s->mother_phone),
                        $normalize($s->guardian_phone),
                        $normalize($s->father_name),
                        $normalize($s->mother_name),
                    ]),
                ];
            });

            $uniqueSigs = $signatures->pluck('sig')->unique()->values();
            if ($uniqueSigs->count() > 1) {
                $skipped++;
                $skippedFamilies[] = [
                    'family_id' => $fid,
                    'siblings' => $siblings,
                ];
                continue; // Real differences - do not consolidate
            }

            // All same contact details - consolidate to lowest parent_id
            $canonicalParentId = $parentIds->min();
            $toRemove = $parentIds->filter(fn ($id) => $id != $canonicalParentId)->values();

            $studentIds = $siblings->pluck('student_id')->toArray();
            $studentsToUpdate = $siblings->whereIn('parent_id', $toRemove->toArray())->pluck('student_id')->toArray();

            if (empty($studentsToUpdate)) {
                continue;
            }

            $this->line("Family {$fid}: Consolidating " . count($studentsToUpdate) . " student(s) to parent_id {$canonicalParentId}, removing parent_ids: " . $toRemove->join(', '));

            if (!$dryRun) {
                DB::transaction(function () use ($studentsToUpdate, $canonicalParentId, $toRemove, &$deletedParents) {
                    // Update students
                    DB::table('students')->whereIn('id', $studentsToUpdate)->update(['parent_id' => $canonicalParentId]);

                    // Update users that reference old parent_ids
                    DB::table('users')->whereIn('parent_id', $toRemove->toArray())->update(['parent_id' => $canonicalParentId]);

                    // Update pos_orders that reference old parent_ids
                    DB::table('pos_orders')->whereIn('parent_id', $toRemove->toArray())->update(['parent_id' => $canonicalParentId]);

                    // Delete orphaned parent_info (only if no student/user references them)
                    foreach ($toRemove as $pid) {
                        $stillUsed = DB::table('students')->where('parent_id', $pid)->exists()
                            || DB::table('users')->where('parent_id', $pid)->exists()
                            || DB::table('pos_orders')->where('parent_id', $pid)->exists();
                        if (!$stillUsed) {
                            DB::table('parent_info')->where('id', $pid)->delete();
                            $deletedParents++;
                        }
                    }
                });
            }

            $consolidated++;
        }

        $this->newLine();
        $this->info("Families consolidated: {$consolidated}");
        $this->info("Families skipped (different contact details): {$skipped}");
        if (!$dryRun) {
            $this->info("Duplicate parent_info records deleted: {$deletedParents}");
        }

        if (!empty($skippedFamilies)) {
            $this->newLine();
            $this->line('<comment>Skipped family details (conflicting parent contact signatures):</comment>');
            foreach ($skippedFamilies as $sf) {
                $this->newLine();
                $this->line("--- Family ID: {$sf['family_id']} ---");
                $this->table(
                    ['Student ID', 'Admission #', 'parent_id', 'Father Phone', 'Mother Phone', 'Guardian Phone', 'Father Name', 'Mother Name'],
                    collect($sf['siblings'])->map(fn ($s) => [
                        $s->student_id,
                        $s->admission_number ?? '-',
                        $s->parent_id ?? 'null',
                        $s->father_phone ?? '-',
                        $s->mother_phone ?? '-',
                        $s->guardian_phone ?? '-',
                        $s->father_name ?? '-',
                        $s->mother_name ?? '-',
                    ])->toArray()
                );
            }
        }

        $this->newLine();
        $this->line('<comment>Moving forward to avoid duplicate parent data:</comment>');
        $this->line('1. When creating a new student and linking to a sibling: check "Use sibling\'s parent details"');
        $this->line('2. When linking students as siblings (Families > Link): parent consolidation runs automatically');
        $this->line('3. Run this command periodically: php artisan students:consolidate-sibling-parent-records');

        return 0;
    }
}
