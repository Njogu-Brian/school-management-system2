<?php

namespace App\Imports;

use App\Models\Vehicle;
use App\Models\DropOffPoint;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class DropOffPointsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public int $created = 0;
    public int $updated = 0;
    public int $skippedDuplicate = 0;
    public int $skippedMissingRoute = 0;
    public int $vehicleLinks = 0;

    /**
     * Expected headings:
     * name | two_way_amount? | one_way_amount? | vehicle_ids? | vehicle_regs?
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.two_way_amount' => ['nullable', 'numeric', 'min:0'],
            '*.one_way_amount' => ['nullable', 'numeric', 'min:0'],
            '*.vehicle_ids' => ['nullable', 'string'],
            '*.vehicle_regs' => ['nullable', 'string'],
        ];
    }

    public function model(array $row)
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $twoWay = $this->parseAmount($row['two_way_amount'] ?? null);
        $oneWay = $this->parseAmount($row['one_way_amount'] ?? null);
        $vehIdsCsv = $row['vehicle_ids'] ?? null;
        $vehRegsCsv = $row['vehicle_regs'] ?? null;

        $existing = DropOffPoint::withTrashed()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill(array_filter([
                'two_way_amount' => $twoWay,
                'one_way_amount' => $oneWay,
            ], fn ($v) => $v !== null));
            $existing->save();
            $this->updated++;
            $point = $existing;
        } else {
            $point = DropOffPoint::create([
                'name' => $name,
                'two_way_amount' => $twoWay,
                'one_way_amount' => $oneWay,
            ]);
            $this->created++;
        }

        $vehicleIds = $this->parseIds($vehIdsCsv);
        $vehicleIdsFromRegs = $this->resolveVehicleIdsByRegs($vehRegsCsv);
        $allVehicleIds = collect($vehicleIds)->merge($vehicleIdsFromRegs)->unique()->filter()->values();

        if ($allVehicleIds->isNotEmpty()) {
            $point->vehicles()->syncWithoutDetaching($allVehicleIds->all());
            $this->vehicleLinks += $allVehicleIds->count();
        }

        return null;
    }

    private function parseAmount($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^\d.-]/', '', (string) $value);
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }
        $amount = (float) $cleaned;

        return $amount < 0 ? null : $amount;
    }

    private function parseIds($csv): array
    {
        if (!$csv) {
            return [];
        }

        return collect(explode(',', (string) $csv))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveVehicleIdsByRegs($csv): array
    {
        if (!$csv) {
            return [];
        }

        $regs = collect(explode(',', (string) $csv))
            ->map(fn ($v) => Str::upper(trim($v)))
            ->filter()
            ->values();

        if ($regs->isEmpty()) {
            return [];
        }

        return Vehicle::query()
            ->where(function ($q) use ($regs) {
                foreach ($regs as $reg) {
                    $q->orWhereRaw('UPPER(COALESCE(registration_number, vehicle_number, "")) = ?', [$reg]);
                }
            })
            ->pluck('id')
            ->all();
    }
}
