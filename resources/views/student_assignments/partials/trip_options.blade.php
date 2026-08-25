@php
    $writer = \App\Services\TransportAssignmentWriter::class;
    $grouped = $writer::groupedByVehicle($trips ?? collect());
    $selectedId = $selected ?? null;
@endphp
<option value="">{{ $placeholder ?? '—' }}</option>
@foreach($grouped as $vehicleTrips)
    <optgroup label="{{ $writer::vehicleLabel($vehicleTrips->first()) }}">
        @foreach($vehicleTrips as $trip)
            <option value="{{ $trip->id }}" @selected($selectedId !== null && $selectedId !== '' && (int) $selectedId === (int) $trip->id)>
                {{ $writer::tripLabel($trip) }}
            </option>
        @endforeach
    </optgroup>
@endforeach
