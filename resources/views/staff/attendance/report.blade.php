@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        #attendanceMap {
            width: 100%;
            height: 460px;
            border-radius: 14px;
        }
    </style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Staff Attendance Report</h1>
                <p class="text-muted mb-0">View attendance history and statistics.</p>
            </div>
            <a href="{{ route('staff.attendance.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Marking
            </a>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Filter by staff and date range.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Total</div>
                        <h3 class="mb-0">{{ $summary['total'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-success h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Present</div>
                        <h3 class="mb-0">{{ $summary['present'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Late</div>
                        <h3 class="mb-0">{{ $summary['late'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Absent</div>
                        <h3 class="mb-0">{{ $summary['absent'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Attendance Location Map</h5>
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="small text-muted">
                        <span class="me-3"><i class="bi bi-circle-fill text-success"></i> Check-in</span>
                        <span class="me-3"><i class="bi bi-circle-fill text-danger"></i> Check-out</span>
                        <span><i class="bi bi-bullseye text-primary"></i> School geofence</span>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="outOfFenceOnlyToggle">
                        <label class="form-check-label small" for="outOfFenceOnlyToggle">
                            Show only out-of-geofence points
                        </label>
                    </div>
                    <span id="mapPointCount" class="pill-badge pill-secondary">0 / 0 visible</span>
                </div>
            </div>
            <div class="card-body">
                @if(empty($mapPoints))
                    <div class="alert alert-info mb-0">
                        No location points found for this filter range.
                    </div>
                @else
                    @if(($schoolGeofence['latitude'] ?? null) === null || ($schoolGeofence['longitude'] ?? null) === null)
                        <div class="alert alert-warning mb-3">
                            School geofence is not configured. Out-of-geofence filtering requires school coordinates.
                        </div>
                    @endif
                    <div id="attendanceMap"></div>
                @endif
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Attendance Records (with geofence tracking)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>In Distance</th>
                                <th>Check Out</th>
                                <th>Out Distance</th>
                                <th>Location Track</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendance as $record)
                                @php
                                    $recordOutOfFence = false;
                                    if (($schoolGeofence['latitude'] ?? null) !== null && ($schoolGeofence['longitude'] ?? null) !== null) {
                                        $radius = (float)($schoolGeofence['radius_meters'] ?? 100);
                                        $checkInOut = $record->check_in_distance_meters !== null && (float)$record->check_in_distance_meters > $radius;
                                        $checkOutOut = $record->check_out_distance_meters !== null && (float)$record->check_out_distance_meters > $radius;
                                        $recordOutOfFence = $checkInOut || $checkOutOut;
                                    }
                                @endphp
                                <tr data-attendance-row="1" data-out-of-fence="{{ $recordOutOfFence ? '1' : '0' }}">
                                    <td>{{ $record->date->format('d M Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $record->staff->full_name }}</div>
                                        <small class="text-muted">{{ $record->staff->staff_id }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'present' => 'pill-success',
                                                'absent' => 'pill-danger',
                                                'late' => 'pill-warning',
                                                'half_day' => 'pill-info'
                                            ];
                                        @endphp
                                        <span class="pill-badge {{ $statusColors[$record->status] ?? 'pill-secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $record->check_in_time ? $record->check_in_time->format('H:i') : '—' }}</td>
                                    <td>{{ $record->check_in_distance_meters !== null ? number_format($record->check_in_distance_meters, 1).' m' : '—' }}</td>
                                    <td>{{ $record->check_out_time ? $record->check_out_time->format('H:i') : '—' }}</td>
                                    <td>{{ $record->check_out_distance_meters !== null ? number_format($record->check_out_distance_meters, 1).' m' : '—' }}</td>
                                    <td class="small">
                                        @if($record->check_in_latitude !== null && $record->check_in_longitude !== null)
                                            In: {{ number_format((float)$record->check_in_latitude, 5) }}, {{ number_format((float)$record->check_in_longitude, 5) }}<br>
                                        @endif
                                        @if($record->check_out_latitude !== null && $record->check_out_longitude !== null)
                                            Out: {{ number_format((float)$record->check_out_latitude, 5) }}, {{ number_format((float)$record->check_out_longitude, 5) }}
                                        @endif
                                        @if($record->check_in_latitude === null && $record->check_out_latitude === null)
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $record->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr id="noAttendanceRowsFallback">
                                    <td colspan="9" class="text-center text-muted py-4">No attendance records found.</td>
                                </tr>
                            @endforelse
                            <tr id="noFilteredRows" style="display:none;">
                                <td colspan="9" class="text-center text-muted py-4">No out-of-geofence records on this page.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($attendance->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing {{ $attendance->firstItem() }}–{{ $attendance->lastItem() }} of {{ $attendance->total() }}
                    </div>
                    {{ $attendance->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        (function () {
            const points = @json($mapPoints ?? []);
            if (!Array.isArray(points) || points.length === 0) return;

            const geofence = @json($schoolGeofence ?? null);
            const mapEl = document.getElementById('attendanceMap');
            if (!mapEl) return;

            const first = points[0];
            const hasGeoCenter = geofence && geofence.latitude !== null && geofence.longitude !== null;

            const map = L.map('attendanceMap', {
                center: hasGeoCenter ? [geofence.latitude, geofence.longitude] : [first.lat, first.lng],
                zoom: 15,
                scrollWheelZoom: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const markerEntries = [];
            const toggleEl = document.getElementById('outOfFenceOnlyToggle');
            const countEl = document.getElementById('mapPointCount');
            const tableRows = Array.from(document.querySelectorAll('tr[data-attendance-row="1"]'));
            const noFilteredRowsEl = document.getElementById('noFilteredRows');

            points.forEach((p) => {
                if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;
                const isIn = p.type === 'check_in';
                const color = isIn ? '#198754' : '#dc3545';
                const marker = L.circleMarker([p.lat, p.lng], {
                    radius: 6,
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.9,
                    weight: 2
                }).addTo(map);

                const label = isIn ? 'Check-in' : 'Check-out';
                const distance = p.distance_meters !== null ? `${Number(p.distance_meters).toFixed(1)} m` : 'N/A';
                marker.bindPopup(
                    `<strong>${p.staff_name || 'Staff'}</strong><br>` +
                    `${label}: ${p.time || '--'}<br>` +
                    `Date: ${p.date || '--'}<br>` +
                    `Distance: ${distance}`
                );

                const allowedRadius = hasGeoCenter ? Number(geofence.radius_meters || 100) : null;
                const isOutOfFence = hasGeoCenter
                    && p.distance_meters !== null
                    && Number(p.distance_meters) > Number(allowedRadius);

                markerEntries.push({
                    marker,
                    latlng: [p.lat, p.lng],
                    isOutOfFence: Boolean(isOutOfFence)
                });
            });

            let geofenceCircle = null;
            if (hasGeoCenter) {
                const radius = Number(geofence.radius_meters || 100);
                geofenceCircle = L.circle([geofence.latitude, geofence.longitude], {
                    radius: radius,
                    color: '#0d6efd',
                    fillColor: '#0d6efd',
                    fillOpacity: 0.12,
                    weight: 2
                }).addTo(map).bindPopup(`School geofence (${radius}m)`);
            }

            if (toggleEl && !hasGeoCenter) {
                toggleEl.disabled = true;
            }

            function applyFilter() {
                const outOnly = Boolean(toggleEl && toggleEl.checked);
                const visible = [];

                markerEntries.forEach((entry) => {
                    const shouldShow = !outOnly || entry.isOutOfFence;
                    if (shouldShow) {
                        if (!map.hasLayer(entry.marker)) map.addLayer(entry.marker);
                        visible.push(entry.latlng);
                    } else if (map.hasLayer(entry.marker)) {
                        map.removeLayer(entry.marker);
                    }
                });

                if (geofenceCircle && !map.hasLayer(geofenceCircle)) {
                    map.addLayer(geofenceCircle);
                }

                if (countEl) {
                    countEl.textContent = `${visible.length} / ${markerEntries.length} visible`;
                }

                if (tableRows.length > 0) {
                    let visibleTableRows = 0;
                    tableRows.forEach((row) => {
                        const outOfFence = row.getAttribute('data-out-of-fence') === '1';
                        const shouldShow = !outOnly || outOfFence;
                        row.style.display = shouldShow ? '' : 'none';
                        if (shouldShow) visibleTableRows += 1;
                    });

                    if (noFilteredRowsEl) {
                        noFilteredRowsEl.style.display = visibleTableRows === 0 ? '' : 'none';
                    }
                }

                const fitBounds = [...visible];
                if (hasGeoCenter) {
                    fitBounds.push([geofence.latitude, geofence.longitude]);
                }

                if (fitBounds.length > 1) {
                    map.fitBounds(fitBounds, { padding: [30, 30] });
                } else if (fitBounds.length === 1) {
                    map.setView(fitBounds[0], 16);
                }
            }

            if (toggleEl) {
                toggleEl.addEventListener('change', applyFilter);
            }

            applyFilter();
        })();
    </script>
@endpush

