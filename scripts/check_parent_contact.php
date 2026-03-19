<?php
$root = file_exists(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 1. STUDENTS WITH NO PARENT CONTACT ===\n\n";
$r = DB::select("
    SELECT s.id, s.admission_number, s.first_name, s.last_name, s.parent_id,
           p.father_name, p.father_phone, p.mother_name, p.mother_phone, p.guardian_phone
    FROM students s
    LEFT JOIN parent_info p ON s.parent_id = p.id
    WHERE (s.parent_id IS NULL OR (
        COALESCE(TRIM(p.father_phone),'') = '' AND
        COALESCE(TRIM(p.mother_phone),'') = '' AND
        COALESCE(TRIM(p.guardian_phone),'') = ''
    ))
    ORDER BY s.id
");
echo "Count: " . count($r) . "\n";
foreach ($r as $x) {
    echo sprintf("%d | %s | %s %s | parent_id=%s | father=%s (%s) | mother=%s (%s)\n",
        $x->id, $x->admission_number ?? '-', $x->first_name, $x->last_name,
        $x->parent_id ?? 'null', $x->father_name ?? '-', $x->father_phone ?? '-',
        $x->mother_name ?? '-', $x->mother_phone ?? '-');
}

echo "\n=== 2. SIBLINGS WITH DIFFERENT PARENT DETAILS ===\n\n";
$families = DB::select("
    SELECT family_id FROM students
    WHERE family_id IS NOT NULL AND family_id > 0
    GROUP BY family_id HAVING COUNT(*) > 1
");
$mismatches = 0;
foreach ($families as $f) {
    $sibs = DB::select("
        SELECT s.id, s.admission_number, s.first_name, s.last_name, s.parent_id,
               p.father_name, p.father_phone, p.mother_name, p.mother_phone
        FROM students s LEFT JOIN parent_info p ON s.parent_id = p.id
        WHERE s.family_id = ?
    ", [$f->family_id]);
    $pids = array_unique(array_filter(array_map(fn($x)=>$x->parent_id, $sibs)));
    $fphones = array_unique(array_filter(array_map(fn($x)=>trim($x->father_phone??''), $sibs)));
    $mphones = array_unique(array_filter(array_map(fn($x)=>trim($x->mother_phone??''), $sibs)));
    if (count($pids) > 1 || count($fphones) > 1 || count($mphones) > 1) {
        $mismatches++;
        echo "Family ID {$f->family_id}:\n";
        foreach ($sibs as $s) {
            echo sprintf("  %d | %s | %s %s | parent_id=%s | father=%s (%s) | mother=%s (%s)\n",
                $s->id, $s->admission_number ?? '-', $s->first_name, $s->last_name,
                $s->parent_id ?? 'null', $s->father_name ?? '-', $s->father_phone ?? '-',
                $s->mother_name ?? '-', $s->mother_phone ?? '-');
        }
        echo "\n";
    }
}
echo "Families with mismatched sibling parent details: $mismatches\n";
