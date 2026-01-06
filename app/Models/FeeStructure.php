<?php

namespace App\Models;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class FeeStructure extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'classroom_id',
        'academic_year_id',
        'term_id',
        'stream_id',
        'student_category_id', // For category-specific fee structures (e.g., staff students, boarding)
        'year', // Keep for backward compatibility
        'version',
        'parent_structure_id',
        'is_active',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function charges(): HasMany
    {
        return $this->hasMany(FeeCharge::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function studentCategory(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class, 'student_category_id');
    }

    public function parentStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'parent_structure_id');
    }

    public function childStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'parent_structure_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FeeStructureVersion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Create a new version snapshot
     */
    public function createVersion(string $changeNotes = null): FeeStructureVersion
    {
        $snapshot = [
            'name' => $this->name,
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'term_id' => $this->term_id,
            'stream_id' => $this->stream_id,
            'charges' => $this->charges->map(function ($charge) {
                return [
                    'votehead_id' => $charge->votehead_id,
                    'term' => $charge->term,
                    'amount' => $charge->amount,
                ];
            })->toArray(),
        ];

        $nextVersion = $this->versions()->max('version_number') + 1;

        return $this->versions()->create([
            'version_number' => $nextVersion,
            'structure_snapshot' => $snapshot,
            'created_by' => auth()->id(),
            'change_notes' => $changeNotes,
        ]);
    }

    /**
     * Replicate structure to other classrooms
     * If studentCategoryId is provided, use it; otherwise use the source structure's category
     * This allows replication to different categories when explicitly requested
     */
public function replicateTo(array $classroomIds, ?int $academicYearId = null, ?int $termId = null, ?int $studentCategoryId = null): array
    {
        $replicated = [];
        
        // Ensure charges are loaded
        $this->load('charges');
        
        // Use the provided category ID, or fall back to source structure's category
        $targetCategoryId = $studentCategoryId ?? $this->student_category_id;
        
        foreach ($classroomIds as $classroomId) {
            // Use updateOrCreate to handle existing structures (due to unique constraint)
            $newStructure = static::updateOrCreate(
                [
                    'classroom_id' => $classroomId,
                    'academic_year_id' => $academicYearId ?? $this->academic_year_id,
                    'term_id' => $termId ?? $this->term_id,
                    'stream_id' => $this->stream_id, // Keep same stream or null
                    'student_category_id' => $targetCategoryId, // Use the target category
                    'is_active' => true,
                ],
                [
                    'name' => $this->name ?? ($this->classroom->name ?? 'Fee Structure'),
                    'parent_structure_id' => $this->id,
                    'version' => 1,
                    'is_active' => true,
                    'created_by' => auth()->id() ?? $this->created_by,
                    'year' => $this->year ?? ($academicYearId ? \App\Models\AcademicYear::find($academicYearId)?->year : null),
                ]
            );
            
            // Delete existing charges for this structure to avoid duplicates
            $newStructure->charges()->delete();
            
            // Replicate charges
            foreach ($this->charges as $charge) {
                FeeCharge::create([
                    'fee_structure_id' => $newStructure->id,
                    'votehead_id' => $charge->votehead_id,
                    'term' => $charge->term,
                    'amount' => $charge->amount,
                ]);
            }
            
            $replicated[] = $newStructure;
        }
        
        return $replicated;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
