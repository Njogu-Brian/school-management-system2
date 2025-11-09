<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Services\ReportCardPublisher;
use Illuminate\Http\Request;

class ExamPublishingController extends Controller
{
    public function __construct(
        protected ReportCardPublisher $publisher
    ) {
    }

    public function publish(Exam $exam, Request $r)
    {
        // Guard
        if (!$exam->publish_result) {
            return back()->with('error','This exam is not marked as publishable.');
        }
        if (!in_array($exam->status, ['approved','published','locked'])) {
            return back()->with('error','Exam must be approved/locked before publishing to report cards.');
        }

        $result = $this->publisher->pushExam($exam);

        $exam->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $message = $result['updated'] > 0
            ? "Results published to report cards ({$result['updated']} cards refreshed across {$result['groups']} class groups)."
            : 'Exam marked as published, no matching students needed updates.';

        return back()->with('success', $message);
    }
}
