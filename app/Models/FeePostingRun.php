<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\{AcademicYear, Term, User, Invoice, InvoiceItem};

class FeePostingRun extends Model
{
    use HasFactory;
    protected $fillable = [
        'hash',
        'academic_year_id',
        'term_id',
        'run_type',
        'status',
        'is_active',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'filters_applied',
        'items_posted_count',
        'notes',
    ];
    
    protected $casts = [
        'filters_applied' => 'array',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'items_posted_count' => 'integer',
        'is_active' => 'boolean',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($run) {
            if (empty($run->hash)) {
                $run->hash = $run->generateHash();
            }
        });
    }
    
    /**
     * Generate a unique hash for this posting run
     */
    public function generateHash(): string
    {
        $secret = config('app.key');
        $data = ($this->id ?? time()) . 'RUN' . $secret . microtime(true);
        return hash('sha256', $data);
    }
    
    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'hash';
    }


    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function diffs(): HasMany
    {
        return $this->hasMany(PostingDiff::class, 'posting_run_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isDryRun(): bool
    {
        return $this->run_type === 'dry_run';
    }

    public function isCommit(): bool
    {
        return $this->run_type === 'commit';
    }

    public function isReversal(): bool
    {
        return $this->run_type === 'reversal';
    }

    public function canBeReversed(): bool
    {
        return $this->status === 'completed' 
            && $this->run_type === 'commit' 
            && !$this->reversed_at
            && $this->is_active; // Only the active run can be reversed
    }
    
    /**
     * Get the latest active run
     */
    public static function getLatestActive(): ?self
    {
        return static::where('status', 'completed')
            ->where('run_type', 'commit')
            ->whereNull('reversed_at')
            ->where('is_active', true)
            ->orderBy('posted_at', 'desc')
            ->first();
    }
    
    /**
     * Activate this run and deactivate all others
     */
    public function activate(): void
    {
        DB::transaction(function () {
            // Deactivate all other runs
            static::where('id', '!=', $this->id)
                ->update(['is_active' => false]);
            
            // Activate this run
            $this->update(['is_active' => true]);
        });
    }
    
    /**
     * Deactivate this run and activate the previous latest
     */
    public function deactivateAndActivatePrevious(): void
    {
        DB::transaction(function () {
            // Deactivate this run
            $this->update(['is_active' => false]);
            
            // Find and activate the previous latest completed, non-reversed run
            $previous = static::where('status', 'completed')
                ->where('run_type', 'commit')
                ->whereNull('reversed_at')
                ->where('id', '!=', $this->id)
                ->orderBy('posted_at', 'desc')
                ->first();
            
            if ($previous) {
                $previous->update(['is_active' => true]);
            }
        });
    }
}

