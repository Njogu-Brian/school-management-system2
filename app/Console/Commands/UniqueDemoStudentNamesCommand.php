<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Re-assign unique student names using Royal Kings name-token pools
 * (distinct first/middle/last parts — not 1:1 real identities).
 *
 * The students list shows full_name = first + middle + last, but when middle
 * is empty or people scan first+last, repeats look like duplicates. This
 * command guarantees unique first+last (and unique full name).
 *
 *   php artisan demo:unique-student-names --force
 */
class UniqueDemoStudentNamesCommand extends Command
{
    protected $signature = 'demo:unique-student-names
                            {--force : Skip confirmation / allow non-demo DB names}
                            {--dry-run : Show planned unique names without writing}
                            {--pool= : Path to demo_name_pool.json (default: storage/app/demo_name_pool.json)}';

    protected $description = 'Assign unique first+last(+middle) student names from RK name pools';

    /** @var list<string> */
    private array $firstNames = [];

    /** @var list<string> */
    private array $middleNames = [];

    /** @var list<string> */
    private array $lastNames = [];

    public function handle(): int
    {
        $dbName = (string) DB::connection()->getDatabaseName();
        $this->warn("Target database: {$dbName}");

        if (str_contains(strtolower($dbName), 'demo') === false && ! $this->option('force')) {
            $this->error('Refusing: DB name does not contain "demo". Use --force on a confirmed demo clone.');

            return self::FAILURE;
        }

        if (! $this->loadPools()) {
            return self::FAILURE;
        }

        $this->line(sprintf(
            'Name pools: first=%d middle=%d last=%d (unique first|last capacity ≈ %d)',
            count($this->firstNames),
            count($this->middleNames),
            count($this->lastNames),
            count($this->firstNames) * count($this->lastNames)
        ));

        if (! $this->option('dry-run') && ! $this->option('force')
            && ! $this->confirm("Assign unique student names in [{$dbName}]?", false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $hasNameCol = Schema::hasColumn('students', 'name');
        $usedFirstLast = [];
        $usedFull = [];
        $index = 0;
        $updated = 0;
        $dryRun = (bool) $this->option('dry-run');

        // Shuffle pools deterministically so adjacent students don't share a surname.
        $this->shufflePools(20261001);

        DB::table('students')->orderBy('id')->chunkById(100, function ($students) use (
            $hasNameCol, &$usedFirstLast, &$usedFull, &$index, &$updated, $dryRun
        ) {
            foreach ($students as $student) {
                [$fn, $mn, $ln] = $this->nextUniqueParts($usedFirstLast, $usedFull, $index);
                $full = trim(implode(' ', array_filter([$fn, $mn, $ln], fn ($p) => $p !== '')));

                if ($dryRun) {
                    $this->line("#{$student->id} {$student->admission_number} → {$full}");
                    $updated++;

                    continue;
                }

                $payload = [
                    'first_name' => $fn,
                    'middle_name' => $mn !== '' ? $mn : null,
                    'last_name' => $ln,
                    'emergency_contact_name' => $fn.' '.$ln.' Guardian',
                ];
                if ($hasNameCol) {
                    $payload['name'] = $full;
                }

                DB::table('students')->where('id', $student->id)->update($payload);
                $updated++;
            }
        });

        if (! $dryRun) {
            $this->syncDenormalizedNames();
        }

        $dupFull = $dryRun ? 0 : $this->countDuplicateFullNames();
        $dupFl = $dryRun ? 0 : $this->countDuplicateFirstLast();
        $this->info(($dryRun ? 'Would update' : 'Updated')." {$updated} students.");
        if (! $dryRun) {
            $this->line("Duplicate full names: {$dupFull}");
            $this->line("Duplicate first+last (list scan): {$dupFl}");
        }

        return self::SUCCESS;
    }

    private function loadPools(): bool
    {
        $path = (string) ($this->option('pool') ?: storage_path('app/demo_name_pool.json'));

        if (is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (! is_array($json)) {
                $this->error("Invalid JSON pool: {$path}");

                return false;
            }
            $this->firstNames = $this->normalizeList($json['first'] ?? []);
            $this->middleNames = $this->normalizeList($json['middle'] ?? []);
            $this->lastNames = $this->normalizeList($json['last'] ?? []);
            $this->info("Loaded name pool from {$path}");
        } else {
            $this->warn("Pool file missing ({$path}); using built-in Kenyan name lists.");
            $this->firstNames = $this->builtinFirst();
            $this->middleNames = $this->builtinMiddle();
            $this->lastNames = $this->builtinLast();
        }

        if ($this->firstNames === [] || $this->lastNames === []) {
            $this->error('Name pools are empty.');

            return false;
        }

        if ($this->middleNames === []) {
            $this->middleNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        }

        return true;
    }

    /**
     * @param  list<mixed>  $items
     * @return list<string>
     */
    private function normalizeList(array $items): array
    {
        $out = [];
        $seen = [];
        foreach ($items as $item) {
            if (! is_string($item)) {
                continue;
            }
            $name = $this->titleCaseName(trim($item));
            if ($name === '') {
                continue;
            }
            // Skip multi-word "last names" that are clearly full compounds dumping into last_name
            // Keep them if short (≤3 words) — common in Kenya (e.g. "Odhiambo Ochieng").
            $key = Str::lower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }

        return $out;
    }

    private function titleCaseName(string $name): string
    {
        $parts = preg_split('/\s+/', $name) ?: [];
        $parts = array_map(function (string $p) {
            if ($p === '') {
                return $p;
            }
            // Preserve particles
            $lower = Str::lower($p);
            if (in_array($lower, ['de', 'da', 'van', 'von'], true)) {
                return $lower;
            }

            return Str::ucfirst($lower);
        }, $parts);

        return trim(implode(' ', $parts));
    }

    private function shufflePools(int $seed): void
    {
        mt_srand($seed);
        $this->firstNames = $this->shuffleList($this->firstNames);
        $this->middleNames = $this->shuffleList($this->middleNames);
        $this->lastNames = $this->shuffleList($this->lastNames);
        mt_srand();
    }

    /**
     * @param  list<string>  $list
     * @return list<string>
     */
    private function shuffleList(array $list): array
    {
        $n = count($list);
        for ($i = $n - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        }

        return array_values($list);
    }

    /**
     * @param  array<string, true>  $usedFirstLast
     * @param  array<string, true>  $usedFull
     * @return array{0: string, 1: string, 2: string}
     */
    private function nextUniqueParts(array &$usedFirstLast, array &$usedFull, int &$index): array
    {
        $F = count($this->firstNames);
        $M = count($this->middleNames);
        $L = count($this->lastNames);
        $capacity = $F * $L;
        $attempts = 0;

        while ($attempts < $capacity + 2000) {
            $i = $index++;
            $attempts++;

            // Stride last names every student so the list isn't "Abigail Otieno" × N.
            $fn = $this->firstNames[$i % $F];
            $ln = $this->lastNames[($i * 7 + intdiv($i, $F)) % $L];
            $mn = $this->middleNames[($i * 3) % $M];

            $flKey = Str::lower($fn.'|'.$ln);
            $fullKey = Str::lower(trim("{$fn} {$mn} {$ln}"));

            if (isset($usedFirstLast[$flKey]) || isset($usedFull[$fullKey])) {
                // Probe next last name for same first
                $resolved = false;
                for ($k = 1; $k < $L; $k++) {
                    $ln2 = $this->lastNames[($i * 7 + intdiv($i, $F) + $k) % $L];
                    $fl2 = Str::lower($fn.'|'.$ln2);
                    $full2 = Str::lower(trim("{$fn} {$mn} {$ln2}"));
                    if (! isset($usedFirstLast[$fl2]) && ! isset($usedFull[$full2])) {
                        $ln = $ln2;
                        $flKey = $fl2;
                        $fullKey = $full2;
                        $resolved = true;
                        break;
                    }
                }
                if (! $resolved) {
                    continue;
                }
            }

            $usedFirstLast[$flKey] = true;
            $usedFull[$fullKey] = true;

            return [$fn, $mn, $ln];
        }

        $fallbackFirst = 'Learner'.$index;
        $fallbackLast = 'Demo'.$index;
        $usedFirstLast[Str::lower($fallbackFirst.'|'.$fallbackLast)] = true;
        $usedFull[Str::lower("{$fallbackFirst} Demo {$fallbackLast}")] = true;

        return [$fallbackFirst, 'Demo', $fallbackLast];
    }

    private function syncDenormalizedNames(): void
    {
        if (Schema::hasTable('legacy_statement_terms') && Schema::hasColumn('legacy_statement_terms', 'student_name')) {
            DB::statement('
                UPDATE legacy_statement_terms lst
                INNER JOIN students s ON s.id = lst.student_id
                SET lst.student_name = TRIM(CONCAT(s.first_name, " ", COALESCE(s.middle_name, ""), " ", s.last_name))
                WHERE lst.student_id IS NOT NULL
            ');
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'payer_name') && Schema::hasColumn('payments', 'student_id')) {
            DB::statement('
                UPDATE payments p
                INNER JOIN students s ON s.id = p.student_id
                SET p.payer_name = TRIM(CONCAT(COALESCE(s.first_name, "Demo"), " ", COALESCE(s.last_name, "Payer")))
                WHERE p.student_id IS NOT NULL
            ');
        }

        if (Schema::hasTable('bank_transactions') && Schema::hasColumn('bank_transactions', 'student_id')) {
            $sets = [];
            if (Schema::hasColumn('bank_transactions', 'payer_name')) {
                $sets[] = 'b.payer_name = TRIM(CONCAT(COALESCE(s.first_name, "Demo"), " ", COALESCE(s.last_name, "Payer")))';
            }
            if (Schema::hasColumn('bank_transactions', 'matched_student_name')) {
                $sets[] = 'b.matched_student_name = TRIM(CONCAT(COALESCE(s.first_name, ""), " ", COALESCE(s.last_name, "")))';
            }
            if ($sets !== []) {
                DB::statement('
                    UPDATE bank_transactions b
                    INNER JOIN students s ON s.id = b.student_id
                    SET '.implode(', ', $sets).'
                    WHERE b.student_id IS NOT NULL
                ');
            }
        }
    }

    private function countDuplicateFullNames(): int
    {
        $row = DB::table('students')
            ->selectRaw('COUNT(*) - COUNT(DISTINCT LOWER(TRIM(CONCAT(first_name, " ", COALESCE(middle_name, ""), " ", last_name)))) AS dupes')
            ->first();

        return (int) ($row->dupes ?? 0);
    }

    private function countDuplicateFirstLast(): int
    {
        $row = DB::table('students')
            ->selectRaw('COUNT(*) - COUNT(DISTINCT LOWER(CONCAT(TRIM(first_name), "|", TRIM(last_name)))) AS dupes')
            ->first();

        return (int) ($row->dupes ?? 0);
    }

    /** @return list<string> */
    private function builtinFirst(): array
    {
        return [
            'Amani', 'Baraka', 'Chebet', 'Dalila', 'Eshe', 'Faraji', 'Grace', 'Halima', 'Imani', 'Jabari',
            'Kamau', 'Lina', 'Makena', 'Nia', 'Purity', 'Rehema', 'Sifa', 'Taji', 'Uwase', 'Victor',
            'Wanjiku', 'Yara', 'Zuri', 'Brian', 'Mercy', 'Kelvin', 'Faith', 'Daniel', 'Naomi', 'Samuel',
            'Aisha', 'Peter', 'Joy', 'Collins', 'Ann', 'Eric', 'Lucy', 'Abigail', 'Benson', 'Caroline',
            'Dennis', 'Esther', 'Francis', 'Gladys', 'Henry', 'Irene', 'James', 'Karen', 'Leonard', 'Millicent',
        ];
    }

    /** @return list<string> */
    private function builtinMiddle(): array
    {
        return [
            'Kariuki', 'Wambui', 'Odhiambo', 'Njeri', 'Omondi', 'Achieng', 'Mwangi', 'Chepkemoi', 'Mutiso', 'Atieno',
            'Kimani', 'Wekesa', 'Nyambura', 'Ochieng', 'Mutua', 'Aoko', 'Kiptoo', 'Wairimu', 'Otieno', 'Cherono',
        ];
    }

    /** @return list<string> */
    private function builtinLast(): array
    {
        return [
            'Otieno', 'Wanjiru', 'Kamau', 'Ochieng', 'Njeri', 'Mwangi', 'Achieng', 'Kiptoo', 'Mutua', 'Nyambura',
            'Okello', 'Cheruiyot', 'Waweru', 'Adhiambo', 'Karanja', 'Muthoni', 'Onyango', 'Njoroge', 'Kimani', 'Wekesa',
            'Omollo', 'Koech', 'Githinji', 'Mboya', 'Rotich', 'Ndungu', 'Wafula', 'Kiplagat', 'Obiero', 'Maina',
            'Were', 'Sang', 'Gichuru', 'Owino', 'Kibet', 'Muriuki', 'Simiyu', 'Korir', 'Odongo', 'Wambua',
        ];
    }
}
