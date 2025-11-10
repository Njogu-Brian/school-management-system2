<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SchoolDay extends Model
{
    protected $fillable = [
        'date',
        'type',
        'name',
        'description',
        'is_kenyan_holiday',
        'is_custom',
    ];

    protected $casts = [
        'date' => 'date',
        'is_kenyan_holiday' => 'boolean',
        'is_custom' => 'boolean',
    ];

    const TYPE_SCHOOL_DAY = 'school_day';
    const TYPE_HOLIDAY = 'holiday';
    const TYPE_MIDTERM_BREAK = 'midterm_break';
    const TYPE_WEEKEND = 'weekend';
    const TYPE_CUSTOM_OFF_DAY = 'custom_off_day';

    /**
     * Check if a date is a school day
     */
    public static function isSchoolDay($date): bool
    {
        $date = Carbon::parse($date)->toDateString();
        
        // Check if it's a weekend
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY) {
            return false;
        }

        // Check database record
        $record = static::where('date', $date)->first();
        if ($record) {
            return $record->type === self::TYPE_SCHOOL_DAY;
        }

        // Default: if not in database and not weekend, assume it's a school day
        return true;
    }

    /**
     * Get school days count between two dates
     */
    public static function countSchoolDays($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $count = 0;

        while ($start <= $end) {
            if (static::isSchoolDay($start->toDateString())) {
                $count++;
            }
            $start->addDay();
        }

        return $count;
    }

    /**
     * Generate Kenyan national holidays for a year
     */
    public static function generateKenyanHolidays($year): void
    {
        $holidays = [
            ['date' => "$year-01-01", 'name' => 'New Year\'s Day'],
            ['date' => "$year-05-01", 'name' => 'Labour Day'],
            ['date' => "$year-06-01", 'name' => 'Madaraka Day'],
            ['date' => "$year-10-10", 'name' => 'Moi Day'],
            ['date' => "$year-10-20", 'name' => 'Mashujaa Day'],
            ['date' => "$year-12-12", 'name' => 'Jamhuri Day'],
            ['date' => "$year-12-25", 'name' => 'Christmas Day'],
            ['date' => "$year-12-26", 'name' => 'Boxing Day'],
        ];

        // Calculate Good Friday and Easter Monday (variable dates)
        $easter = static::calculateEaster($year);
        $goodFriday = $easter->copy()->subDays(2);
        $easterMonday = $easter->copy()->addDay();

        $holidays[] = ['date' => $goodFriday->toDateString(), 'name' => 'Good Friday'];
        $holidays[] = ['date' => $easterMonday->toDateString(), 'name' => 'Easter Monday'];

        foreach ($holidays as $holiday) {
            static::updateOrCreate(
                ['date' => $holiday['date']],
                [
                    'type' => self::TYPE_HOLIDAY,
                    'name' => $holiday['name'],
                    'is_kenyan_holiday' => true,
                    'is_custom' => false,
                ]
            );
        }
    }

    /**
     * Calculate Easter date for a given year
     */
    private static function calculateEaster($year): Carbon
    {
        // Algorithm to calculate Easter (simplified)
        $a = $year % 19;
        $b = floor($year / 100);
        $c = $year % 100;
        $d = floor($b / 4);
        $e = $b % 4;
        $f = floor(($b + 8) / 25);
        $g = floor(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = floor($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = floor(($a + 11 * $h + 22 * $l) / 451);
        $month = floor(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }
}
