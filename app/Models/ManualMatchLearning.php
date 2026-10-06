<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualMatchLearning extends Model
{
    protected $fillable = [
        'transaction_type',
        'reference_text',
        'description_text',
        'student_id',
        'user_id',
        'match_reason',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find learned suggestions for a given reference/description (used by matching services to improve over time).
     * Returns array of suggestion arrays: ['student_id', 'student_name', 'admission_number', 'classroom_name', 'confidence', 'reason', 'match_type']
     */
    public static function findSuggestions(string $transactionType, ?string $referenceText, ?string $descriptionText): array
    {
        $learned = static::with('student.classroom')
            ->where('transaction_type', $transactionType)
            ->where('student_id', '>', 0);

        $candidates = collect();

        if (!empty($referenceText) && strlen($referenceText) >= 2) {
            $refNorm = strtoupper(trim($referenceText));
            $candidates = $candidates->merge(
                (clone $learned)->whereRaw('UPPER(TRIM(reference_text)) = ?', [$refNorm])->get()
            );
            if (strlen($refNorm) >= 4) {
                $candidates = $candidates->merge(
                    (clone $learned)->whereNotNull('reference_text')
                        ->whereRaw('UPPER(reference_text) LIKE ?', ['%' . addcslashes($refNorm, '%_') . '%'])
                        ->get()
                );
            }
        }

        $exactDescriptionIds = [];
        if (!empty($descriptionText) && strlen(trim($descriptionText)) >= 3) {
            $descExact = strtoupper(trim($descriptionText));
            $exactRows = (clone $learned)->whereNotNull('description_text')
                ->whereRaw('UPPER(TRIM(description_text)) = ?', [$descExact])
                ->get();
            foreach ($exactRows as $row) {
                $exactDescriptionIds[$row->id] = true;
            }
            $candidates = $candidates->merge($exactRows);

            if (strlen($descExact) >= 5) {
                $descNorm = addcslashes(trim($descriptionText), '%_');
                $candidates = $candidates->merge(
                    (clone $learned)->whereNotNull('description_text')
                        ->whereRaw('UPPER(description_text) LIKE ?', ['%' . strtoupper($descNorm) . '%'])
                        ->get()
                );
            }
        }

        $byStudent = [];
        foreach ($candidates->unique('id') as $learning) {
            if (!$learning->student) {
                continue;
            }
            $sid = $learning->student_id;
            if (!isset($byStudent[$sid])) {
                $byStudent[$sid] = [
                    'learning' => $learning,
                    'count' => 0,
                    'latest_at' => $learning->updated_at,
                    'exact' => false,
                ];
            }
            $byStudent[$sid]['count']++;
            if (isset($exactDescriptionIds[$learning->id])) {
                $byStudent[$sid]['exact'] = true;
            }
            if ($learning->updated_at > $byStudent[$sid]['latest_at']) {
                $byStudent[$sid]['latest_at'] = $learning->updated_at;
                $byStudent[$sid]['learning'] = $learning;
            }
        }

        $suggestions = [];
        foreach ($byStudent as $sid => $data) {
            $student = $data['learning']->student;
            // Exact bill-ref relearn is very strong (e.g. "shalin wainaina" → Shalin after a correction)
            $confidence = $data['exact']
                ? min(96, 90 + $data['count'] * 2)
                : min(95, 85 + $data['count'] * 3);
            $suggestions[] = [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'admission_number' => $student->admission_number,
                'classroom_name' => $student->classroom ? $student->classroom->name : null,
                'confidence' => $confidence,
                'reason' => 'Learned from your past manual assignment' . ($data['count'] > 1 ? " ({$data['count']} times)" : ''),
                'match_type' => 'learned',
            ];
        }
        return $suggestions;
    }
}

