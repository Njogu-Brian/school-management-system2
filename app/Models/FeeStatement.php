<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStatement extends Model
{
    protected $fillable = ['student_id', 'term_id', 'academic_year_id', 'generated_at', 'hash'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($statement) {
            if (empty($statement->hash)) {
                $statement->hash = static::generateHash($statement->id, 'STMT');
                $statement->saveQuietly(); // Save without triggering events
            }
        });
    }

    /**
     * Generate a unique hash for the fee statement
     */
    public static function generateHash(int $id, string $prefix): string
    {
        $secret = config('app.key');
        $data = $id . $prefix . $secret . microtime(true) . uniqid('', true);
        return hash('sha256', $data);
    }

    /**
     * Find fee statement by hash
     */
    public static function findByHash(string $hash): ?self
    {
        return static::where('hash', $hash)->first();
    }

    /**
     * Get route key name for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'hash';
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function year()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
