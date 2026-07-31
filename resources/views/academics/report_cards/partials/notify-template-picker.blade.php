@php
    /**
     * Per-channel template pickers for report form notifications.
     *
     * @var string $idPrefix  unique prefix so several forms can coexist on one page
     */
    $selectable = \App\Services\ReportCardPublishService::selectableTemplates();
    $defaults = [
        'sms' => \App\Services\ReportCardPublishService::resolveTemplateForChannel('sms'),
        'whatsapp' => \App\Services\ReportCardPublishService::resolveTemplateForChannel('whatsapp'),
        'email' => \App\Services\ReportCardPublishService::resolveTemplateForChannel('email'),
    ];
    $labels = ['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'];
@endphp

<div class="row g-2">
    @foreach($labels as $channel => $label)
        <div class="col-12 col-md-4">
            <label class="form-label small text-muted mb-1" for="{{ $idPrefix }}Tpl{{ $channel }}">
                {{ $label }} template
            </label>
            <select name="template_ids[{{ $channel }}]" id="{{ $idPrefix }}Tpl{{ $channel }}" class="form-select form-select-sm">
                <option value="">
                    Default{{ $defaults[$channel] ? ' — '.$defaults[$channel]->title : '' }}
                </option>
                @foreach($selectable[$channel] as $tpl)
                    <option value="{{ $tpl->id }}">{{ $tpl->title }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>
<div class="form-text small">
    Leave on <em>Default</em> to use the report form templates. Edit the wording under
    <a href="{{ route('communication-templates.index') }}" target="_blank">Communication &rsaquo; Templates</a>.
</div>
