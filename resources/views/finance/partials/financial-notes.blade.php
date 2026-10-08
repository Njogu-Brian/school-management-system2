{{-- Fee notes open in a sheet. Required: $student OR ($studentId + $familyId). --}}
@php
    $fnStudent = $student ?? null;
    $fnStudentId = $fnStudent?->id ?? ($studentId ?? null);
    $fnFamilyId = $fnStudent?->family_id ?? ($familyId ?? null);
    $fnTitle = $title ?? ($fnStudent?->full_name ?? ($fnFamilyId ? 'Family fee notes' : 'Fee notes'));
    $fnCount = $noteCount ?? null;
    $fnScope = $scope ?? ($fnStudentId ? 'student' : 'family');
@endphp

@include('finance.partials.fee-notes-launcher', [
    'studentId' => $fnStudentId,
    'familyId' => $fnFamilyId,
    'title' => $fnTitle,
    'noteCount' => $fnCount,
    'scope' => $fnScope,
    'buttonClass' => $buttonClass ?? 'btn btn-finance btn-finance-outline',
])
