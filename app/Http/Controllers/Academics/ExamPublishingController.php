<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use Illuminate\Http\Request;

class ExamPublishingController extends Controller
{
    public function publish(Exam $exam, Request $r)
    {
        // Guard
        if (!$exam->publish_result) {
            return back()->with('error','This exam is not marked as publishable.');
        }
        if (!in_array($exam->status, ['approved','published','locked'])) {
            return back()->with('error','Exam must be approved/locked before publishing to report cards.');
        }

        // TODO: Implement your ReportCard push here (create/update items)
        // e.g. app(ReportCardPublisher::class)->pushExam($exam);

        $exam->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success','Results published to report cards.');
    }
}
