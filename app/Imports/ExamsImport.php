<?php

namespace App\Imports;

use App\Models\Academics\Exam;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ExamsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Ignore header row
        $rows->skip(1)->each(function ($row) {
            [$name,$type,$modality,$year,$termName,$className,$subjectName,$start,$end,$max,$weight] = array_pad($row->toArray(), 11, null);

            $yearModel  = AcademicYear::where('year', $year)->first();
            $termModel  = Term::where('name', $termName)->first();
            $classroom  = Classroom::where('name', $className)->first();
            $subject    = Subject::where('name', $subjectName)->first();

            if (!$yearModel || !$termModel || !$classroom || !$subject) return;

            Exam::create([
                'name'             => $name,
                'type'             => strtolower($type),
                'modality'         => strtolower($modality) === 'online' ? 'online' : 'physical',
                'academic_year_id' => $yearModel->id,
                'term_id'          => $termModel->id,
                'classroom_id'     => $classroom->id,
                'subject_id'       => $subject->id,
                'starts_on'        => $start ? date('Y-m-d H:i', strtotime($start)) : null,
                'ends_on'          => $end ? date('Y-m-d H:i', strtotime($end)) : null,
                'max_marks'        => $max ?: 100,
                'weight'           => $weight ?: 0,
            ]);
        });
    }
}
