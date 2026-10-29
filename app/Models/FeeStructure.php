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
        // Ensure it's properly cast to int or null
        $targetCategoryId = $studentCategoryId !== null ? (int)$studentCategoryId : ($this->student_category_id !== null ? (int)$this->student_category_id : null);
        
        // Resolve academic year and year value
        $targetAcademicYearId = $academicYearId ?? $this->academic_year_id;
        $targetYear = null;
        if ($targetAcademicYearId) {
            $academicYear = \App\Models\AcademicYear::find($targetAcademicYearId);
            $targetYear = $academicYear ? $academicYear->year : ($this->year ?? date('Y'));
        } else {
            $targetYear = $this->year ?? date('Y');
        }
        
        foreach ($classroomIds as $classroomId) {
            // First, try to find existing structure (check both active and inactive)
            // The unique constraint is: (classroom_id, academic_year_id, term_id, stream_id, student_category_id, is_active)
            $targetTermId = $termId ?? $this->term_id;
            $targetStreamId = $this->stream_id;
            
            // Build query to find existing structure
            // CRITICAL: Must match ALL fields including student_category_id exactly
            // Use a single comprehensive whereRaw to ensure ALL conditions are applied together
            $buildQuery = function($isActive) use ($classroomId, $targetAcademicYearId, $targetCategoryId, $targetTermId, $targetStreamId) {
                // Build the raw SQL condition to ensure exact matching
                $conditions = ['classroom_id = ?', 'is_active = ?'];
                $bindings = [$classroomId, $isActive ? 1 : 0];
                
                // Handle academic_year_id
                if ($targetAcademicYearId === null) {
                    $conditions[] = 'academic_year_id IS NULL';
                } else {
                    $conditions[] = 'academic_year_id = ?';
                    $bindings[] = $targetAcademicYearId;
                }
                
                // CRITICAL: Handle student_category_id - must match EXACTLY
                // This is the most important check to prevent picking wrong category
                if ($targetCategoryId === null) {
                    $conditions[] = 'student_category_id IS NULL';
                } else {
                    $conditions[] = 'student_category_id = ?';
                    $bindings[] = (int)$targetCategoryId;
                }
                
                // Handle term_id
                if ($targetTermId === null) {
                    $conditions[] = 'term_id IS NULL';
                } else {
                    $conditions[] = 'term_id = ?';
                    $bindings[] = $targetTermId;
                }
                
                // Handle stream_id
                if ($targetStreamId === null) {
                    $conditions[] = 'stream_id IS NULL';
                } else {
                    $conditions[] = 'stream_id = ?';
                    $bindings[] = $targetStreamId;
                }
                
                // Use whereRaw with all conditions to ensure they're all applied
                return static::whereRaw(implode(' AND ', $conditions), $bindings);
            };
            
            // First try to find active structure with EXACT category match
            $existingStructure = $buildQuery(true)->first();
            
            // If no active structure found, check for inactive one with EXACT category match
            if (!$existingStructure) {
                $existingStructure = $buildQuery(false)->first();
            }
            
            // CRITICAL: Verify the found structure matches the target category exactly
            // Use strict comparison to catch any edge cases
            if ($existingStructure) {
                $foundCategoryId = $existingStructure->student_category_id;
                $categoryMatches = ($targetCategoryId === null && $foundCategoryId === null) || 
                                  ($targetCategoryId !== null && (int)$foundCategoryId === (int)$targetCategoryId);
                
                if (!$categoryMatches) {
                    // Category mismatch - this should never happen with proper query, but safety check
                    \Log::warning('Fee structure replication: Found structure with mismatched category', [
                        'found_category_id' => $foundCategoryId,
                        'target_category_id' => $targetCategoryId,
                        'classroom_id' => $classroomId,
                        'structure_id' => $existingStructure->id,
                    ]);
                    $existingStructure = null;
                } else {
                    // Update existing structure
                    $existingStructure->update([
                        'name' => $this->name ?? ($this->classroom->name ?? 'Fee Structure'),
                        'parent_structure_id' => $this->id,
                        'is_active' => true,
                        'year' => $targetYear,
                        'created_by' => auth()->id() ?? $this->created_by,
                    ]);
                    
                    // Delete existing charges for this structure to avoid duplicates
                    $existingStructure->charges()->delete();
                    
                    $newStructure = $existingStructure;
                }
            }
            
            if (!$existingStructure) {
                // Create new structure with explicit category
                // Double-check that no structure exists with different category before creating
                $conflictingStructure = static::where('classroom_id', $classroomId)
                    ->where('academic_year_id', $targetAcademicYearId)
                    ->where('term_id', $targetTermId === null ? null : $targetTermId)
                    ->where('stream_id', $targetStreamId === null ? null : $targetStreamId)
                    ->where('is_active', true)
                    ->where(function($q) use ($targetCategoryId) {
                        if ($targetCategoryId === null) {
                            $q->whereNotNull('student_category_id');
                        } else {
                            $q->where('student_category_id', '!=', $targetCategoryId)
                              ->orWhereNull('student_category_id');
                        }
                    })
                    ->first();
                
                if ($conflictingStructure) {
                    \Log::warning('Fee structure replication: Conflicting structure found with different category', [
                        'conflicting_category_id' => $conflictingStructure->student_category_id,
                        'target_category_id' => $targetCategoryId,
                        'classroom_id' => $classroomId,
                        'conflicting_structure_id' => $conflictingStructure->id,
                    ]);
                }
                
                // Create new structure
                $newStructure = static::create([
                    'classroom_id' => $classroomId,
                    'academic_year_id' => $targetAcademicYearId,
                    'term_id' => $termId ?? $this->term_id,
                    'stream_id' => $this->stream_id,
                    'student_category_id' => $targetCategoryId, // Explicitly set the target category
                    'name' => $this->name ?? ($this->classroom->name ?? 'Fee Structure'),
                    'parent_structure_id' => $this->id,
                    'version' => 1,
                    'is_active' => true,
                    'created_by' => auth()->id() ?? $this->created_by,
                    'year' => $targetYear,
                ]);
                
                // Final verification that the created structure has the correct category
                $newStructure->refresh();
                if ($newStructure->student_category_id != $targetCategoryId) {
                    \Log::error('Fee structure replication: Created structure has wrong category!', [
                        'created_category_id' => $newStructure->student_category_id,
                        'target_category_id' => $targetCategoryId,
                        'structure_id' => $newStructure->id,
                    ]);
                    // Fix it
                    $newStructure->update(['student_category_id' => $targetCategoryId]);
                }
            }
            
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
