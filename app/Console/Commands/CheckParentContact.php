<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckParentContact extends Command
{
    protected $signature = 'students:check-parent-contact';

    protected $description = 'Check students with no parent contact and siblings with different parent details';

    public function handle(): int
    {
        $this->info('=== 1. STUDENTS WITH NO PARENT CONTACT (no father, mother, or guardian phone) ===');
        $this->newLine();

        $noContact = DB::table('students')
            ->leftJoin('parent_info', 'students.parent_id', '=', 'parent_info.id')
            ->where('students.archive', 0)
            ->where('students.is_alumni', false)
            ->where(function ($q) {
                $q->whereNull('students.parent_id')
                    ->orWhere(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('parent_info.father_phone')->orWhere('parent_info.father_phone', '');
                        })->where(function ($q3) {
                            $q3->whereNull('parent_info.mother_phone')->orWhere('parent_info.mother_phone', '');
                        })->where(function ($q3) {
                            $q3->whereNull('parent_info.guardian_phone')->orWhere('parent_info.guardian_phone', '');
                        });
                    });
            })
            ->select(
                'students.id',
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
            ->orderBy('students.id')
            ->get();

        if ($noContact->isEmpty()) {
            $this->info('None found.');
        } else {
            $this->info('Count: ' . $noContact->count());
            $this->table(
                ['ID', 'Admission #', 'Name', 'parent_id', 'Father', 'Father Phone', 'Mother', 'Mother Phone', 'Guardian Phone'],
                $noContact->map(fn ($s) => [
                    $s->id,
                    $s->admission_number ?? '-',
                    trim("{$s->first_name} {$s->last_name}"),
                    $s->parent_id ?? 'null',
                    $s->father_name ?? '-',
                    $s->father_phone ?? '-',
                    $s->mother_name ?? '-',
                    $s->mother_phone ?? '-',
                    $s->guardian_phone ?? '-',
                ])
            );
        }

        $this->newLine(2);
        $this->info('=== 2. SIBLINGS (same family_id) WITH DIFFERENT PARENT DETAILS ===');
        $this->newLine();

        $familiesWithSiblings = DB::table('students')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotNull('family_id')
            ->where('family_id', '>', 0)
            ->select('family_id')
            ->groupBy('family_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('family_id');

        $siblingMismatches = [];
        foreach ($familiesWithSiblings as $fid) {
            $siblings = DB::table('students')
                ->leftJoin('parent_info', 'students.parent_id', '=', 'parent_info.id')
                ->where('students.archive', 0)
                ->where('students.is_alumni', false)
                ->where('students.family_id', $fid)
                ->select(
                    'students.id',
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

            // Compare by parent contact "signature", not by parent_id.
            // If siblings have identical phones/names but different parent_info IDs, they are considered consistent.
            $normalize = fn ($v) => trim((string) ($v ?? ''));
            $signatures = $siblings->map(fn ($s) => implode('|', [
                $normalize($s->father_phone),
                $normalize($s->mother_phone),
                $normalize($s->guardian_phone),
                $normalize($s->father_name),
                $normalize($s->mother_name),
            ]));

            $hasMismatch = $signatures->unique()->count() > 1;

            if ($hasMismatch) {
                $siblingMismatches[] = [
                    'family_id' => $fid,
                    'siblings' => $siblings,
                ];
            }
        }

        if (empty($siblingMismatches)) {
            $this->info('None found. All siblings in the same family share consistent parent details.');
        } else {
            $this->info('Count: ' . count($siblingMismatches) . ' family/families with mismatched sibling parent details');
            foreach ($siblingMismatches as $m) {
                $this->newLine();
                $this->line("--- Family ID: {$m['family_id']} ---");
                $this->table(
                    ['ID', 'Admission #', 'Name', 'parent_id', 'Father', 'Father Phone', 'Mother', 'Mother Phone', 'Guardian Phone'],
                    collect($m['siblings'])->map(fn ($s) => [
                        $s->id,
                        $s->admission_number ?? '-',
                        trim("{$s->first_name} {$s->last_name}"),
                        $s->parent_id ?? 'null',
                        $s->father_name ?? '-',
                        $s->father_phone ?? '-',
                        $s->mother_name ?? '-',
                        $s->mother_phone ?? '-',
                        $s->guardian_phone ?? '-',
                    ])->toArray()
                );
            }
        }

        return 0;
    }
}
