<?php

namespace App\Http\Controllers;

use App\Models\Academics\ReportCard;
use App\Models\FamilyReportPortalLink;
use App\Models\PaymentLink;
use App\Models\Student;
use App\Services\ReportCardAccessService;
use App\Services\ReportCardBatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FamilyReportPortalController extends Controller
{
    public function portal(string $token)
    {
        $link = FamilyReportPortalLink::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->with(['academicYear', 'term', 'family'])
            ->firstOrFail();

        $link->recordClick();

        $students = $this->resolveStudents($link);
        $yearId = $link->academic_year_id;
        $termId = $link->term_id;

        $children = [];
        foreach ($students as $student) {
            $reportCard = ReportCard::query()
                ->with(['term', 'academicYear', 'classroom', 'stream'])
                ->where('student_id', $student->id)
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->when($termId, fn ($q) => $q->where('term_id', $termId))
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->first();

            if (! $reportCard) {
                continue;
            }

            $billing = ReportCardAccessService::billingContextForReportCard($reportCard);
            $canViewReport = (bool) ($billing['can_view_report'] ?? false);
            $firstInvoiceUrl = collect($billing['invoices'] ?? [])
                ->pluck('public_url')
                ->filter()
                ->first();

            $children[] = [
                'student' => $student,
                'report_card' => $reportCard,
                'billing' => $billing,
                'dto' => $canViewReport
                    ? ReportCardBatchService::build($reportCard->id)
                    : null,
                'invoice_url' => $firstInvoiceUrl ?: get_public_student_statement_url($student),
                'view_url' => route('family.reports.show', [$link->token, $reportCard->public_token]),
                'pdf_url' => route('family.reports.pdf', [$link->token, $reportCard->public_token]),
            ];
        }

        $paymentLink = null;
        if ($link->family_id) {
            $paymentLink = ensure_family_payment_link($link->family_id);
        } elseif ($students->isNotEmpty()) {
            $paymentLink = PaymentLink::active()
                ->where('student_id', $students->first()->id)
                ->first();
        }

        $payUrl = $paymentLink
            ? route('payment.link.show', $paymentLink->hashed_id ?? $paymentLink->token)
            : null;

        return view('family.reports.portal', [
            'link' => $link,
            'children' => $children,
            'payUrl' => $payUrl,
            'schoolName' => setting('school_name', config('app.name', 'School')),
            'schoolPhone' => setting('school_phone', ''),
            'schoolEmail' => setting('school_email', ''),
        ]);
    }

    public function show(string $token, string $publicToken)
    {
        [$reportCard, $link] = $this->resolveReportCard($token, $publicToken);
        [$allowed, $balance] = ReportCardAccessService::canViewPublicReportCard($reportCard);

        if (! $allowed) {
            return view('academics.report_cards.public_locked', [
                'report_card' => $reportCard,
                'balance' => $balance,
                'portalUrl' => $link->getUrl(),
            ]);
        }

        $dto = ReportCardBatchService::build($reportCard->id);

        return view('family.reports.show', [
            'report_card' => $reportCard,
            'dto' => $dto,
            'portalUrl' => $link->getUrl(),
            'pdfUrl' => route('family.reports.pdf', [$link->token, $reportCard->public_token]),
            'isPdf' => false,
        ]);
    }

    public function pdf(string $token, string $publicToken)
    {
        [$reportCard, $link] = $this->resolveReportCard($token, $publicToken);
        [$allowed] = ReportCardAccessService::canViewPublicReportCard($reportCard);

        if (! $allowed) {
            abort(403, 'Report card is locked until fees are cleared.');
        }

        $dto = ReportCardBatchService::build($reportCard->id);
        $filename = ReportCardBatchService::pdfFilename($dto);

        $pdf = Pdf::loadView('academics.report_cards.pdf', [
            'dto' => $dto,
            'report_card' => $reportCard,
        ])->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Legacy single-token public route redirect.
     */
    public function legacyPublic(string $token)
    {
        $reportCard = ReportCard::where('public_token', $token)
            ->whereNotNull('published_at')
            ->with('student')
            ->firstOrFail();

        $student = $reportCard->student;
        $portalLink = family_report_portal_link_for_student(
            $student,
            (int) $reportCard->academic_year_id,
            (int) $reportCard->term_id
        );

        if ($portalLink) {
            return redirect()->route('family.reports.show', [
                $portalLink->token,
                $reportCard->public_token,
            ]);
        }

        abort(404, 'Report card portal link not available.');
    }

    /**
     * @return array{0: ReportCard, 1: FamilyReportPortalLink}
     */
    protected function resolveReportCard(string $token, string $publicToken): array
    {
        $link = FamilyReportPortalLink::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $reportCard = ReportCard::query()
            ->where('public_token', $publicToken)
            ->whereNotNull('published_at')
            ->with(['student', 'term', 'academicYear'])
            ->firstOrFail();

        $students = $this->resolveStudents($link);
        $studentIds = $students->pluck('id')->all();

        if (! in_array((int) $reportCard->student_id, $studentIds, true)) {
            abort(404);
        }

        return [$reportCard, $link];
    }

  protected function resolveStudents(FamilyReportPortalLink $link)
    {
        if ($link->family_id) {
            return Student::query()
                ->where('family_id', $link->family_id)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->with(['classroom', 'stream'])
                ->orderBy('first_name')
                ->get();
        }

        if ($link->student_id) {
            return Student::query()
                ->where('id', $link->student_id)
                ->with(['classroom', 'stream'])
                ->get();
        }

        return collect();
    }
}
