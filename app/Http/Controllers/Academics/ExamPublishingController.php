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
        if ($exam->status !== 'locked') {
            return back()->with('error', 'Lock the exam before it can be included on report cards.');
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

        if (! $exam->published_at) {
            $exam->update([
                'published_at' => now(),
                'publish_result' => true,
            ]);
        }

        return back()->with('success', 'Locked results are now on the report cards.');
    }
}
