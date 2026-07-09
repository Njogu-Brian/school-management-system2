<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CLASSROOMS ===\n";
$classrooms = App\Models\Academics\Classroom::orderBy('name')->get(['id', 'name', 'campus', 'is_alumni']);
foreach ($classrooms as $c) {
    echo $c->id . '|' . $c->name . '|campus=' . ($c->campus ?? '') . '|alumni=' . (int) $c->is_alumni . "\n";
}

echo "\n=== GRADE 9 ===\n";
$g9Classes = App\Models\Academics\Classroom::query()
    ->where(function ($q) {
        $q->whereRaw('LOWER(name) like ?', ['%grade 9%'])
            ->orWhereRaw('LOWER(name) like ?', ['%grade9%']);
    })
    ->get();

$base = App\Models\Student::query()->where('archive', 0)->where('is_alumni', false);
echo 'active_all=' . $base->count() . "\n";

foreach ($g9Classes as $c) {
    $cnt = (clone $base)->where('classroom_id', $c->id)->count();
    echo 'class ' . $c->id . ' ' . $c->name . ' students=' . $cnt . ' is_alumni=' . (int) $c->is_alumni . "\n";
}

echo "\n=== ENROLLMENT REPORT ===\n";
$svc = app(App\Services\EnrollmentReportService::class);
$report = $svc->buildReport();
echo 'report_total=' . $report['totals']['total'] . ' rows=' . count($report['rows']) . "\n";
foreach ($report['rows'] as $row) {
    if (stripos($row['class'], 'grade 9') !== false || stripos($row['class'], 'grade9') !== false) {
        echo 'FOUND: ' . json_encode($row) . "\n";
    }
}

$inReportClassIds = collect($report['rows'])->pluck('classroom_id')->filter()->all();
$missingStudents = (clone $base)
    ->whereNotNull('classroom_id')
    ->whereNotIn('classroom_id', $inReportClassIds)
    ->with('classroom:id,name,is_alumni')
    ->get(['id', 'classroom_id', 'first_name', 'last_name']);

echo "\n=== STUDENTS IN CLASSES NOT IN REPORT (" . $missingStudents->count() . ") ===\n";
foreach ($missingStudents->groupBy('classroom_id') as $classId => $group) {
    $class = $group->first()->classroom;
    echo ($class?->name ?? 'unknown') . ' (id=' . $classId . ', is_alumni=' . (int) ($class?->is_alumni ?? 0) . '): ' . $group->count() . " students\n";
}
