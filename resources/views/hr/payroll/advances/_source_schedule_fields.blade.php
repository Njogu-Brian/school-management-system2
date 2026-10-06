@php
    $advanceModel = $advance ?? null;
    $sourceType = old('source_type', $advanceModel?->source_type ?? 'company');
    $startYear = old('repayment_start_year', $advanceModel?->repayment_start_year ?? now()->year);
    $startMonth = old('repayment_start_month', $advanceModel?->repayment_start_month ?? now()->month);
    $excludeStaffId = $advanceModel?->staff_id;
    $selectedSourceStaff = old('source_staff_id', $advanceModel?->source_staff_id ?? '');
@endphp

<div class="col-md-6">
    <label class="form-label">Source <span class="text-danger">*</span></label>
    <select name="source_type" id="source_type" class="form-select @error('source_type') is-invalid @enderror" required>
        <option value="company" @selected($sourceType === 'company')>Company (Royal Kings)</option>
        <option value="staff" @selected($sourceType === 'staff')>Staff member (personal funds)</option>
    </select>
    <div class="form-text">If funded by a staff member, repayments are added back to their net pay (not taxed).</div>
    @error('source_type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 {{ $sourceType === 'staff' ? '' : 'd-none' }}" id="source_staff_field">
    <label class="form-label">Funded by (staff) <span class="text-danger">*</span></label>
    <select name="source_staff_id" id="source_staff_id" class="form-select @error('source_staff_id') is-invalid @enderror">
        <option value="">-- Select staff --</option>
        @foreach($staff as $s)
            @if($excludeStaffId && (int) $s->id === (int) $excludeStaffId)
                @continue
            @endif
            <option value="{{ $s->id }}" @selected((string) $selectedSourceStaff === (string) $s->id)>
                {{ $s->name }} ({{ $s->staff_id }})
            </option>
        @endforeach
    </select>
    @error('source_staff_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-3">
    <label class="form-label">Repayment start year <span class="text-danger">*</span></label>
    <input type="number" name="repayment_start_year" min="2000" max="2100"
           class="form-control @error('repayment_start_year') is-invalid @enderror"
           value="{{ $startYear }}" required>
    @error('repayment_start_year')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-3">
    <label class="form-label">Repayment start month <span class="text-danger">*</span></label>
    <select name="repayment_start_month" class="form-select @error('repayment_start_month') is-invalid @enderror" required>
        @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" @selected((int) $startMonth === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
        @endfor
    </select>
    @error('repayment_start_month')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
