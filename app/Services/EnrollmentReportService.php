<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Student;
use Illuminate\Support\Collection;

class EnrollmentReportService
{
  /**
   * @return array{
   *   rows: list<array{classroom_id:int|null,class:string,campus:?string,boys:int,girls:int,other:int,total:int}>,
   *   totals: array{boys:int,girls:int,other:int,total:int},
   *   year:int,
   *   term:int
   * }
   */
  public function buildReport(?int $year = null, ?int $termNumber = null, ?string $campus = null): array
  {
    $year = $year ?? (int) (setting('current_year') ?? date('Y'));
    $termNumber = $termNumber ?? get_current_term_number() ?? 1;

    $classroomsQuery = Classroom::query()
      ->where('is_alumni', false)
      ->orderBy('name');

    if ($campus) {
      $classroomsQuery->where('campus', $campus);
    }

    $classrooms = $classroomsQuery->get();

    $students = Student::query()
      ->where('archive', 0)
      ->where('is_alumni', false)
      ->activeForCurrentTerm($year, $termNumber)
      ->when($campus, fn ($q) => $q->whereHas('classroom', fn ($cq) => $cq->where('campus', $campus)))
      ->get(['classroom_id', 'gender']);

    $assignedStudents = $students->whereNotNull('classroom_id');
    $countsByClass = $assignedStudents->groupBy('classroom_id')->map(function (Collection $group) {
      $boys = 0;
      $girls = 0;
      $other = 0;

      foreach ($group as $student) {
        $gender = $this->normalizeGender($student->gender);
        match ($gender) {
          'male' => $boys++,
          'female' => $girls++,
          default => $other++,
        };
      }

      return [
        'boys' => $boys,
        'girls' => $girls,
        'other' => $other,
        'total' => $boys + $girls + $other,
      ];
    });

    $rows = [];
    $totals = ['boys' => 0, 'girls' => 0, 'other' => 0, 'total' => 0];

    foreach ($classrooms as $classroom) {
      $counts = $countsByClass->get($classroom->id, [
        'boys' => 0,
        'girls' => 0,
        'other' => 0,
        'total' => 0,
      ]);

      $rows[] = [
        'classroom_id' => $classroom->id,
        'class' => $classroom->name,
        'campus' => $classroom->campus,
        'boys' => $counts['boys'],
        'girls' => $counts['girls'],
        'other' => $counts['other'],
        'total' => $counts['total'],
      ];

      $totals['boys'] += $counts['boys'];
      $totals['girls'] += $counts['girls'];
      $totals['other'] += $counts['other'];
      $totals['total'] += $counts['total'];
    }

    $unassigned = $campus ? collect() : $students->whereNull('classroom_id');
    if ($unassigned->isNotEmpty()) {
      $boys = 0;
      $girls = 0;
      $other = 0;

      foreach ($unassigned as $student) {
        $gender = $this->normalizeGender($student->gender);
        match ($gender) {
          'male' => $boys++,
          'female' => $girls++,
          default => $other++,
        };
      }

      $total = $boys + $girls + $other;
      $rows[] = [
        'classroom_id' => null,
        'class' => 'Unassigned',
        'campus' => null,
        'boys' => $boys,
        'girls' => $girls,
        'other' => $other,
        'total' => $total,
      ];

      $totals['boys'] += $boys;
      $totals['girls'] += $girls;
      $totals['other'] += $other;
      $totals['total'] += $total;
    }

    return [
      'rows' => $rows,
      'totals' => $totals,
      'year' => $year,
      'term' => $termNumber,
    ];
  }

  /**
   * @param  list<array{classroom_id:int|null,class:string,campus:?string,boys:int,girls:int,other:int,total:int}>  $rows
   * @return list<list<int|string>>
   */
  public function toExportRows(array $rows, bool $includeCampus = false): array
  {
    return array_map(function (array $row) use ($includeCampus) {
      $line = [$row['class']];

      if ($includeCampus) {
        $line[] = $row['campus'] ? ucfirst($row['campus']) : '—';
      }

      $line[] = $row['boys'];
      $line[] = $row['girls'];
      $line[] = $row['other'];
      $line[] = $row['total'];

      return $line;
    }, $rows);
  }

  /**
   * @param  array{boys:int,girls:int,other:int,total:int}  $totals
   * @return list<int|string>
   */
  public function toExportTotalsRow(array $totals, bool $includeCampus = false): array
  {
    $line = ['Grand Total'];

    if ($includeCampus) {
      $line[] = '';
    }

    $line[] = $totals['boys'];
    $line[] = $totals['girls'];
    $line[] = $totals['other'];
    $line[] = $totals['total'];

    return $line;
  }

  /**
   * @return list<string>
   */
  public function exportHeadings(bool $includeCampus = false): array
  {
    $headings = ['Class'];

    if ($includeCampus) {
      $headings[] = 'Campus';
    }

    return array_merge($headings, ['Boys', 'Girls', 'Other', 'Total']);
  }

  public function subtitle(int $year, int $termNumber, ?string $campus = null): string
  {
    $parts = [
      'Academic year '.$year,
      'Term '.$termNumber,
    ];

    if ($campus) {
      $parts[] = ucfirst($campus).' campus';
    }

    return implode(' · ', $parts);
  }

  protected function normalizeGender(?string $gender): string
  {
    $raw = strtolower(trim((string) $gender));

    return match ($raw) {
      'male', 'm', 'boy', 'boys' => 'male',
      'female', 'f', 'girl', 'girls' => 'female',
      default => 'other',
    };
  }
}
