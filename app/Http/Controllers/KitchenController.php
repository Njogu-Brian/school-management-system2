<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SMSService;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function showForm()
    {
        $classCounts = \App\Models\Attendance::whereDate('date', today())
            ->where('is_present', true)
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->select('classrooms.name as class', DB::raw('COUNT(*) as count'))
            ->groupBy('classrooms.name')
            ->get();

        return view('notify-kitchen', compact('classCounts'));
    }

    public function notifyKitchen(Request $request)
    {
        $classCounts = \App\Models\Attendance::whereDate('date', today())
            ->where('is_present', true)
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->select('classrooms.name as class', DB::raw('COUNT(*) as count'))
            ->groupBy('classrooms.name')
            ->get();

        $message = "Today's Attendance Summary:\n";
        foreach ($classCounts as $c) {
            $message .= "{$c->class}: {$c->count}\n";
        }

        $template = SmsTemplate::where('code', 'kitchen_summary')->first();
        $text = $template ? str_replace('{summary}', $message, $template->message) : $message;

        $kitchenPhone = '254708225397';
        $this->smsService->sendSMS($kitchenPhone, $text);

        return back()->with('success', 'Kitchen notified successfully.');
    }
}
