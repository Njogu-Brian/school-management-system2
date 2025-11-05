<table class="header" style="margin-bottom:8px;">
    <tr>
        <td style="width:80px;">
            @if(!empty($dto['branding']['logo_url']))
                <img src="{{ $dto['branding']['logo_url'] }}" class="logo" alt="Logo">
            @endif
        </td>
        <td>
            <div class="title">{{ $dto['branding']['school_name'] ?? 'School Name' }}</div>
            <div class="small muted">
                {{ $dto['branding']['address'] ?? '' }}
                @if(!empty($dto['branding']['phone'])) • Tel: {{ $dto['branding']['phone'] }} @endif
                @if(!empty($dto['branding']['email'])) • {{ $dto['branding']['email'] }} @endif
                @if(!empty($dto['branding']['website'])) • {{ $dto['branding']['website'] }} @endif
            </div>
        </td>
        <td class="text-right small" style="vertical-align:top;">
            <div><strong>{{ $title ?? '' }}</strong></div>
            <div>Printed: {{ $dto['meta']['printed_at'] ?? now()->format('Y-m-d H:i') }}</div>
            <div>By: {{ $dto['meta']['generated_by'] ?? 'System' }}</div>
        </td>
    </tr>
</table>
<hr style="border:0;border-top:1px solid #999;margin:0 0 10px 0;">
