<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Jobs\PublishInvoicesJob;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Student;
use App\Services\InvoicePublishService;
use App\Support\AcademicContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoicePublishController extends Controller
{
    public function index(Request $request, InvoicePublishService $service)
    {
        $filters = $this->filtersFromRequest($request);
        $preview = $service->queryUnpublished($filters)
            ->with(['student.classroom', 'student.stream'])
            ->limit(100)
            ->get();
        $pendingCount = $service->queryUnpublished($filters)->count();

        return view('finance.invoices.publish', [
            'filters' => $filters,
            'preview' => $preview,
            'pendingCount' => $pendingCount,
            'academicYears' => AcademicContext::years(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'streams' => Stream::orderBy('name')->get(),
            'students' => $request->filled('student_id')
                ? Student::where('id', (int) $request->student_id)->get()
                : collect(),
        ]);
    }

    public function publish(Request $request, InvoicePublishService $service)
    {
        $data = $request->validate([
            'year' => 'nullable|integer',
            'term' => 'nullable|in:1,2,3',
            'class_id' => 'nullable|integer|exists:classrooms,id',
            'stream_id' => 'nullable|integer|exists:streams,id',
            'student_id' => 'nullable|integer|exists:students,id',
            'channels' => 'nullable|array',
            'channels.*' => 'in:sms,whatsapp',
            'notify' => 'nullable|boolean',
        ]);

        $filters = array_filter([
            'year' => $data['year'] ?? null,
            'term' => isset($data['term']) ? (int) $data['term'] : null,
            'class_id' => $data['class_id'] ?? null,
            'stream_id' => $data['stream_id'] ?? null,
            'student_id' => $data['student_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $channels = $data['channels'] ?? ['sms', 'whatsapp'];
        $notify = $request->boolean('notify', true);

        $pending = $service->queryUnpublished($filters)->count();
        if ($pending === 0) {
            return redirect()
                ->route('finance.invoices.publish', $filters)
                ->with('info', 'No unpublished invoices matched the selected scope.');
        }

        // Always queue: bulk publish + SMS/WhatsApp easily exceeds Cloudflare's ~100s origin timeout (524).
        PublishInvoicesJob::dispatch(
            $filters,
            $channels,
            $notify,
            (int) (Auth::id() ?? 0),
            $pending
        );

        $channelsLabel = $notify
            ? ('via '.implode('/', $channels ?: ['in-app only']))
            : 'without parent SMS/WhatsApp';

        return redirect()
            ->route('finance.invoices.publish', $filters)
            ->with(
                'success',
                "Queued publishing for {$pending} invoice(s) {$channelsLabel}. "
                .'The queue worker will mark them published and notify parents — refresh this page in a few minutes to confirm drafts are cleared.'
            );
    }

    /**
     * @return array<string, int|null>
     */
    protected function filtersFromRequest(Request $request): array
    {
        return array_filter([
            'year' => $request->filled('year') ? (int) $request->year : null,
            'term' => $request->filled('term') ? (int) $request->term : null,
            'class_id' => $request->filled('class_id') ? (int) $request->class_id : null,
            'stream_id' => $request->filled('stream_id') ? (int) $request->stream_id : null,
            'student_id' => $request->filled('student_id') ? (int) $request->student_id : null,
        ], fn ($v) => $v !== null);
    }
}
