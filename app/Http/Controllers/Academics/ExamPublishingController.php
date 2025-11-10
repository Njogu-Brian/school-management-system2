<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Services\ReportCardBatchService;
use Illuminate\Http\Request;

/**
 * Controller for publishing exam results to report cards
 */
class ExamPublishingController extends Controller
{
    /**
     * Publish exam results to report cards
     *
     * Updates report cards for all students who took the exam by recalculating
     * their term averages and grades based on the published exam marks.
     *
     * @param Exam $exam
     * @param Request $r
     * @return \Illuminate\Http\RedirectResponse
     */
    public function publish(Exam $exam, Request $r)
    {
        // Guard: Check if exam is publishable
        if (!$exam->publish_result) {
            return back()->with('error','This exam is not marked as publishable.');
        }
        if (!in_array($exam->status, ['approved','published','locked'])) {
            return back()->with('error','Exam must be approved/locked before publishing to report cards.');
        }

        // Update report cards for all students who took this exam
        try {
            $service = new ReportCardBatchService();
            $service->generateForClass(
                $exam->academic_year_id,
                $exam->term_id,
                $exam->classroom_id,
                $exam->stream_id
            );
        } catch (\Exception $e) {
            \Log::error('Failed to update report cards when publishing exam', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error','Failed to update report cards: ' . $e->getMessage());
        }

        $exam->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success','Results published to report cards.');
    }
}
