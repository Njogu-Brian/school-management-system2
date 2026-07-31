@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $termLabel = trim(($link->term?->name ?? '').' / '.($link->academicYear?->year ?? ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $brandPrimary }}">
    <title>Report Forms - {{ $schoolName }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --page-bg: #eef2f7;
            --text: #0f172a;
            --muted: #475569;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            color: var(--text);
        }
        .portal-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: color-mix(in srgb, var(--brand-primary) 94%, #000);
            color: #fff;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .18);
        }
        .portal-header-inner {
            max-width: 1040px;
            min-height: 64px;
            margin: 0 auto;
            padding: .7rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .portal-title { font-size: 1rem; font-weight: 700; margin: 0; }
        .portal-subtitle { font-size: .8rem; color: rgba(255,255,255,.78); }
        .portal-count {
            flex: 0 0 auto;
            padding: .35rem .65rem;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 999px;
            font-size: .78rem;
        }
        .portal-main {
            max-width: 1040px;
            margin: 0 auto;
            padding: 1rem;
        }
        .intro {
            margin-bottom: 1rem;
            color: var(--muted);
            font-size: .9rem;
        }
        .report-document {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .report-document-head {
            padding: .75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .student-name { font-weight: 700; font-size: .98rem; }
        .student-meta { color: var(--muted); font-size: .8rem; margin-top: .12rem; }
        .report-body {
            padding: 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .download-link {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .45rem .7rem;
            border: 1px solid #cbd5e1;
            border-radius: .55rem;
            color: var(--brand-primary);
            background: #fff;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 600;
            transition: background-color .2s, border-color .2s;
        }
        .download-link:hover { background: #f1f5f9; border-color: #94a3b8; }
        .fees-panel {
            margin: 0 1rem 1rem;
            border: 1px solid #bae6fd;
            border-left: 4px solid #0284c7;
            border-radius: .75rem;
            padding: .9rem 1rem;
            background: #f0f9ff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .fees-label {
            color: #075985;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .fees-term { font-size: .83rem; color: var(--muted); margin-top: .1rem; }
        .fees-total { font-size: 1.08rem; font-weight: 800; margin-top: .25rem; }
        .fees-note {
            margin-top: .35rem;
            font-size: .72rem;
            color: #0369a1;
            line-height: 1.35;
        }
        .invoice-button {
            flex: 0 0 auto;
            min-height: 44px;
            padding: .65rem 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            border-radius: .6rem;
            background: #0369a1;
            color: #fff;
            font-weight: 700;
            font-size: .86rem;
            text-decoration: none;
            transition: background-color .2s;
        }
        .invoice-button:hover { background: #075985; color: #fff; }
        .locked-panel {
            margin: 1rem;
            padding: 2rem 1rem;
            border: 1px solid #fde68a;
            border-radius: .75rem;
            background: #fffbeb;
            text-align: center;
        }
        .locked-icon { font-size: 1.8rem; color: #b45309; }
        .locked-title { font-weight: 750; margin-top: .5rem; }
        .locked-copy { color: #78350f; max-width: 520px; margin: .4rem auto 1rem; font-size: .9rem; }
        .empty-state {
            padding: 3rem 1rem;
            background: #fff;
            border-radius: .8rem;
            text-align: center;
            color: var(--muted);
        }
        .portal-footer {
            padding: .5rem 1rem 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: .78rem;
        }
        @media (max-width: 640px) {
            .portal-header-inner {
                padding: .65rem .75rem;
                min-height: 56px;
            }
            .portal-title { font-size: .92rem; line-height: 1.25; }
            .portal-main { padding: .55rem; }
            .intro { font-size: .84rem; margin-bottom: .75rem; }
            .report-document { border-radius: .65rem; margin-bottom: .85rem; }
            .report-document-head {
                align-items: flex-start;
                padding: .65rem .75rem;
                gap: .5rem;
            }
            .student-name { font-size: .92rem; }
            .download-link {
                min-height: 40px;
                padding: .4rem .55rem;
                font-size: .78rem;
            }
            .report-body {
                padding: .45rem;
                font-size: .82rem;
            }
            .report-body table {
                font-size: .72rem !important;
            }
            .fees-panel {
                margin: 0 .45rem .55rem;
                align-items: stretch;
                flex-direction: column;
                padding: .8rem .85rem;
            }
            .fees-total { font-size: 1.15rem; }
            .invoice-button { width: 100%; min-height: 48px; }
            .locked-panel { margin: .65rem; padding: 1.35rem .85rem; }
            .portal-count { display: none; }
        }
        @media print {
            body { background: #fff; }
            .portal-header, .intro, .download-link, .invoice-button, .portal-footer { display: none !important; }
            .portal-main { max-width: none; margin: 0; padding: 0; }
            .report-document {
                border: 0;
                border-radius: 0;
                box-shadow: none;
                margin: 0;
                break-after: page;
                page-break-after: always;
            }
            .report-document:last-child { break-after: auto; page-break-after: auto; }
            .report-document-head { display: none; }
            .report-body { padding: 0; overflow: visible; }
            .fees-panel { break-inside: avoid; page-break-inside: avoid; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
<header class="portal-header">
    <div class="portal-header-inner">
        <div>
            <h1 class="portal-title">{{ $schoolName }} · Report Forms</h1>
            <div class="portal-subtitle">{{ $termLabel }}</div>
        </div>
        <div class="portal-count">
            {{ count($children) }} {{ \Illuminate\Support\Str::plural('report', count($children)) }}
        </div>
    </div>
</header>

<main class="portal-main">
    @if(count($children) > 1)
        <div class="intro">
            All family report forms are shown below. Scroll down to view each child.
        </div>
    @endif

    @forelse($children as $child)
        @php
            $student = $child['student'];
            $reportCard = $child['report_card'];
            $billing = $child['billing'];
            $dto = $child['dto'];
            $locked = !($billing['can_view_report'] ?? false);
            $isNextTerm = ($billing['invoice_scope'] ?? '') === 'next_term';
            $hasInvoice = !empty($billing['invoices']);
            $invoiceTitle = $isNextTerm ? 'Next Term Fees' : 'Outstanding Fees';
            $feesUrl = $child['fees_url'] ?? $payUrl;
            $isFamilyFees = $payUrl && $feesUrl === $payUrl && count($children) > 1;
            $feesButtonLabel = $isFamilyFees ? 'View Family Fees' : 'View Fees & Pay';
        @endphp

        <article class="report-document">
            <div class="report-document-head">
                <div>
                    <div class="student-name">{{ $student->full_name ?? trim($student->first_name.' '.$student->last_name) }}</div>
                    <div class="student-meta">
                        {{ $student->admission_number }}
                        @if($reportCard->classroom?->name) · {{ $reportCard->classroom->name }} @endif
                        @if($reportCard->stream?->name) {{ $reportCard->stream->name }} @endif
                    </div>
                </div>
                @unless($locked)
                    <a href="{{ $child['pdf_url'] }}" class="download-link" target="_blank" rel="noopener">
                        <i class="bi bi-download" aria-hidden="true"></i>
                        <span>Download PDF</span>
                    </a>
                @endunless
            </div>

            @if($locked)
                <div class="locked-panel">
                    <i class="bi bi-lock-fill locked-icon" aria-hidden="true"></i>
                    <div class="locked-title">Report form unavailable</div>
                    <div class="locked-copy">
                        Please clear the outstanding {{ $termLabel }} fee balance of
                        <strong>KES {{ number_format($billing['report_term_balance'] ?? 0, 2) }}</strong>
                        to view this report form.
                    </div>
                    @if($feesUrl)
                        <a href="{{ $feesUrl }}" class="invoice-button">
                            <i class="bi bi-wallet2" aria-hidden="true"></i>
                            {{ $feesButtonLabel }}
                        </a>
                    @endif
                </div>
            @elseif($dto)
                <div class="report-body">
                    @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => false])
                </div>
            @endif

            @if(!$locked && $isNextTerm && $hasInvoice)
                <section class="fees-panel" aria-label="Next term fees">
                    <div>
                        <div class="fees-label">{{ $invoiceTitle }}</div>
                        <div class="fees-term">{{ $billing['display_term_label'] }}</div>
                        <div class="fees-total">KES {{ number_format($billing['invoice_total_balance'] ?? 0, 2) }}</div>
                        @if($isFamilyFees)
                            <div class="fees-note">Opens shared family fees for all children</div>
                        @endif
                    </div>
                    @if($feesUrl)
                        <a href="{{ $feesUrl }}" class="invoice-button">
                            <i class="bi bi-wallet2" aria-hidden="true"></i>
                            {{ $feesButtonLabel }}
                        </a>
                    @endif
                </section>
            @endif
        </article>
    @empty
        <div class="empty-state">
            <i class="bi bi-file-earmark-text fs-2 d-block mb-2" aria-hidden="true"></i>
            No published report forms are available for this family.
        </div>
    @endforelse
</main>

<footer class="portal-footer">
    @if($schoolPhone) Tel: {{ $schoolPhone }} · @endif
    @if($schoolEmail) {{ $schoolEmail }} @endif
</footer>
</body>
</html>
