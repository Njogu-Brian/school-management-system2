<?php

namespace App\Services\BioTime;

use App\Models\BioTimePunch;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BioTimeSyncService
{
    public function __construct(private BioTimeEmployeeMapper $mapper) {}

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return array{imported: int, unmatched: int, days: int}
     */
    public function ingest(array $transactions): array
    {
        $this->mapper->refresh();
        $imported = 0;
        $unmatched = 0;
        $affected = [];

        foreach ($transactions as $row) {
            if (! is_array($row)) {
                continue;
            }
            $punch = $this->storePunch($row);
            if (! $punch) {
                continue;
            }
            $imported++;
            if (! $punch->staff_id) {
                $unmatched++;
                continue;
            }
            $date = Carbon::parse($punch->punch_time)->toDateString();
            $affected[$punch->staff_id.'|'.$date] = [(int) $punch->staff_id, $date];
        }

        foreach ($affected as [$staffId, $date]) {
            $this->rebuildDay($staffId, $date);
        }

        return [
            'imported' => $imported,
            'unmatched' => $unmatched,
            'days' => count($affected),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function storePunch(array $row): ?BioTimePunch
    {
        $empCode = BioTimeEmployeeMapper::normalize(
            isset($row['emp_code']) ? (string) $row['emp_code'] : null
        );
        $punchTime = $row['punch_time'] ?? null;
        if ($empCode === '' || ! $punchTime) {
            return null;
        }

        try {
            $when = Carbon::parse($punchTime);
        } catch (\Throwable) {
            return null;
        }

        $txId = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null;
        $staffId = $this->mapper->staffIdForEmpCode($empCode);

        $attributes = [
            'emp_code' => $empCode,
            'staff_id' => $staffId,
            'punch_time' => $when,
            'punch_state' => isset($row['punch_state']) ? (string) $row['punch_state'] : null,
            'terminal_sn' => $row['terminal_sn'] ?? null,
            'terminal_alias' => $row['terminal_alias'] ?? null,
            'payload' => $row,
            'processed_at' => now(),
        ];

        if ($txId) {
            return BioTimePunch::updateOrCreate(
                ['biotime_transaction_id' => $txId],
                $attributes
            );
        }

        return BioTimePunch::updateOrCreate(
            [
                'emp_code' => $empCode,
                'punch_time' => $when->format('Y-m-d H:i:s'),
                'terminal_sn' => $row['terminal_sn'] ?? null,
            ],
            $attributes
        );
    }

    public function rebuildDay(int $staffId, string $date): StaffAttendance
    {
        $punches = BioTimePunch::query()
            ->where('staff_id', $staffId)
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time')
            ->get();

        $first = $punches->first();
        $last = $punches->last();
        $checkIn = $first ? Carbon::parse($first->punch_time)->format('H:i:s') : null;
        $checkOut = null;
        if ($punches->count() >= 2 && $last) {
            $checkOut = Carbon::parse($last->punch_time)->format('H:i:s');
        }

        $onLeave = $this->isOnApprovedLeave($staffId, $date);
        $status = $onLeave ? 'present' : 'present';
        $notes = [];
        if ($onLeave) {
            $notes[] = 'On approved leave (gate punch recorded)';
        }
        if ($punches->count() === 1) {
            $notes[] = 'Incomplete: missing check-out (gate)';
        }

        $staff = Staff::find($staffId);
        $existing = StaffAttendance::query()
            ->where('staff_id', $staffId)
            ->whereDate('date', $date)
            ->first();

        $payload = [
            'status' => $status,
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'source' => 'biometric',
            'notes' => implode('. ', $notes) ?: ($existing && $existing->source !== 'biometric' ? $existing->notes : null),
        ];

        if ($staff?->biometric_exempt) {
            $payload['notes'] = trim(($payload['notes'] ? $payload['notes'].'. ' : '').'Biometric exempt');
        }

        return StaffAttendance::updateOrCreate(
            ['staff_id' => $staffId, 'date' => $date],
            $payload
        );
    }

    public function unmatchedEmpCodes(): Collection
    {
        return BioTimePunch::query()
            ->whereNull('staff_id')
            ->select('emp_code')
            ->selectRaw('COUNT(*) as punches')
            ->selectRaw('MAX(punch_time) as last_punch')
            ->groupBy('emp_code')
            ->orderByDesc('punches')
            ->get();
    }

    private function isOnApprovedLeave(int $staffId, string $date): bool
    {
        return LeaveRequest::query()
            ->where('staff_id', $staffId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }
}
