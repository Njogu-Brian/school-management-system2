<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmsLog;

class SmsLogController extends Controller
{
    public function index()
    {
        $smsLogs = SmsLog::all();
        return view('sms_logs.index', compact('smsLogs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string',
        ]);

        SmsLog::create([
            'phone_number' => $request->phone_number,
            'message' => $request->message,
        ]);

        return redirect()->back()->with('success', 'SMS log created successfully.');
    }
}