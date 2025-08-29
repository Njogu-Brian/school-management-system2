<?php

namespace App\Imports;

use App\Models\Route;
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
     * name | route_id? | route_name? | vehicle_ids? | vehicle_regs?
     * - vehicle_ids: comma-separated ids   (e.g., "3,8,12")
     * - vehicle_regs: comma-separated regs (e.g., "KDC123A,KDG456B")
     * Provide either route_id or route_name.
     */
    public function rules(): array
    {
        return [
            '*.name'        => ['required', 'string', 'max:255'],
            '*.route_id'    => ['nullable', 'integer'],
            '*.route_name'  => ['nullable', 'string', 'max:255'],
            '*.vehicle_ids' => ['nullable', 'string'],
            '*.vehicle_regs'=> ['nullable', 'string'],
        ];
    }

    public function model(array $row)
    {
        $name       = trim((string)($row['name'] ?? ''));
        $routeId    = $row['route_id'] ?? null;
        $routeName  = isset($row['route_name']) ? trim((string)$row['route_name']) : null;
        $vehIdsCsv  = $row['vehicle_ids']  ?? null;
        $vehRegsCsv = $row['vehicle_regs'] ?? null;

        // Resolve route
        $route = null;
        if ($routeId) {
            $route = Route::find($routeId);
        } elseif ($routeName) {
            $route = Route::whereRaw('LOWER(name) = ?', [Str::lower($routeName)])->first();
        }
        if (!$route) {
            $this->skippedMissingRoute++;
            return null;
        }

        // Upsert by unique (route_id, lower(name))
        $existing = DropOffPoint::withTrashed()
            ->where('route_id', $route->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($existing) {
            // If it was soft-deleted, restore; otherwise just treat as existing.
            if ($existing->trashed()) {
                $existing->restore();
            }
            $this->updated++;
            $point = $existing;
        } else {
            $point = DropOffPoint::create([
                'name'     => $name,
                'route_id' => $route->id,
            ]);
            $this->created++;
        }

        // Attach vehicles (optional)
        $vehicleIds = [];
        if ($vehIdsCsv) {
            $ids = collect(explode(',', $vehIdsCsv))
                ->map(fn($v) => (int)trim($v))
                ->filter();
            if ($ids->isNotEmpty()) {
                $vehicleIds = array_merge($vehicleIds, Vehicle::whereIn('id', $ids)->pluck('id')->all());
            }
        }
        if ($vehRegsCsv) {
            $regs = collect(explode(',', $vehRegsCsv))
                ->map(fn($v) => trim($v))
                ->filter();
            if ($regs->isNotEmpty()) {
                $vehicleIds = array_merge($vehicleIds, Vehicle::whereIn('registration_number', $regs)->pluck('id')->all());
            }
        }

        if (!empty($vehicleIds)) {
            // Avoid duplicates; keep existing links
            $point->vehicles()->syncWithoutDetaching(array_unique($vehicleIds));
            $this->vehicleLinks += count(array_unique($vehicleIds));
        }

        return $point;
    }
}
