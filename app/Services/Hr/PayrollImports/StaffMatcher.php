<?php

namespace App\Services\Hr\PayrollImports;

use App\Models\Staff;

final class StaffMatcher
{
    public function matchByBestKey(array $row): array
    {
        // This wizard intentionally errs on the side of NOT matching.
        // If multiple matches are found, it returns ambiguous so UI can resolve.

        $candidates = collect();

        $bank = trim((string) ($row['bank_account'] ?? ''));
        if ($bank !== '') {
            $candidates = Staff::query()->where('bank_account', $bank)->get();
            return $this->result('bank_account', $bank, $candidates);
        }

        $id = trim((string) ($row['id_number'] ?? ''));
        if ($id !== '') {
            $candidates = Staff::query()->where('id_number', $id)->get();
            return $this->result('id_number', $id, $candidates);
        }

        $kra = strtoupper(trim((string) ($row['kra_pin'] ?? '')));
        if ($kra !== '') {
            $candidates = Staff::query()->whereRaw('UPPER(kra_pin) = ?', [$kra])->get();
            return $this->result('kra_pin', $kra, $candidates);
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name !== '') {
            // Name match is inherently risky; only auto-match if exactly 1 candidate.
            $q = Staff::query()->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $name . '%']);
            $candidates = $q->limit(5)->get();
            return $this->result('name', $name, $candidates, allowSingleOnly: true);
        }

        return [
            'status' => 'unmatched',
            'match_key' => null,
            'match_value' => null,
            'staff_id' => null,
            'candidates' => [],
        ];
    }

    private function result(string $key, string $value, $candidates, bool $allowSingleOnly = false): array
    {
        $count = $candidates->count();
        if ($count === 1) {
            return [
                'status' => 'matched',
                'match_key' => $key,
                'match_value' => $value,
                'staff_id' => $candidates->first()->id,
                'candidates' => $candidates->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name, 'staff_id' => $s->staff_id])->all(),
            ];
        }
        if ($count > 1) {
            return [
                'status' => $allowSingleOnly ? 'ambiguous' : 'ambiguous',
                'match_key' => $key,
                'match_value' => $value,
                'staff_id' => null,
                'candidates' => $candidates->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name, 'staff_id' => $s->staff_id])->all(),
            ];
        }
        return [
            'status' => 'unmatched',
            'match_key' => $key,
            'match_value' => $value,
            'staff_id' => null,
            'candidates' => [],
        ];
    }
}

