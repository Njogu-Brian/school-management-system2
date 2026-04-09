{{-- Expects $invoices: list of invoice summaries with balance + lines (votehead breakdown). --}}
@foreach($invoices ?? [] as $inv)
    @if(($inv['balance'] ?? 0) > 0)
        <div class="invoice-block">
            <div class="inv-line inv-line-header">
                <span class="inv-meta">{{ $inv['invoice_number'] }} · {{ $inv['year'] ?? '–' }} Term {{ $inv['term'] ?? '–' }}@if(!empty($inv['due_date_label'])) · due {{ $inv['due_date_label'] }}@endif</span>
                <span class="inv-bal">KES {{ number_format($inv['balance'], 2) }}</span>
            </div>
            @if(!empty($inv['lines']))
                @foreach($inv['lines'] as $line)
                    <div class="inv-line inv-line-item">
                        <span class="inv-meta">{{ $line['label'] }}</span>
                        <span class="inv-item-amt">KES {{ number_format($line['balance'], 2) }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
@endforeach
