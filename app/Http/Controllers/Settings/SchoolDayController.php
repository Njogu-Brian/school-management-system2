<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SchoolDay;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SchoolDayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $type = $request->get('type');
        
        $query = SchoolDay::whereYear('date', $year);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $schoolDays = $query->orderBy('date')->paginate(50);
        $types = [
            'school_day' => 'School Day',
            'holiday' => 'Holiday',
            'midterm_break' => 'Midterm Break',
            'weekend' => 'Weekend',
            'custom_off_day' => 'Custom Off Day',
        ];
        
        return view('settings.school_days.index', compact('schoolDays', 'year', 'type', 'types'));
    }

    public function generateHolidays(Request $request)
    {
        $year = $request->get('year', date('Y'));
        SchoolDay::generateKenyanHolidays($year);
        
        return back()->with('success', "Kenyan national holidays for {$year} have been generated.");
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:school_day,holiday,midterm_break,weekend,custom_off_day',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        SchoolDay::updateOrCreate(
            ['date' => $request->date],
            [
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'is_custom' => true,
            ]
        );

        return back()->with('success', 'School day record created successfully.');
    }

    public function destroy(SchoolDay $schoolDay)
    {
        if ($schoolDay->is_kenyan_holiday) {
            return back()->with('error', 'Cannot delete auto-generated Kenyan holidays. Deactivate them instead.');
        }
        
        $schoolDay->delete();
        return back()->with('success', 'School day record deleted successfully.');
    }
}
