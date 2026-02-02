<?php

namespace App\Services;

use App\Models\PhoneNumberNormalizationLog;

class PhoneNumberNormalizationLogger
{
    public function logChange(
        string $modelType,
        ?int $modelId,
        string $field,
        ?string $oldValue,
        ?string $newValue,
        ?string $countryCode,
        string $source,
        ?int $userId = null
    ): void {
        PhoneNumberNormalizationLog::create([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'country_code' => $countryCode,
            'source' => $source,
            'user_id' => $userId,
        ]);
    }

    public function logIfChanged(
        string $modelType,
        ?int $modelId,
        string $field,
        ?string $oldValue,
        ?string $newValue,
        ?string $countryCode,
        string $source,
        ?int $userId = null
    ): void {
        if ($newValue === null || $newValue === '') {
            return;
        }
        if ((string) $oldValue === (string) $newValue) {
            return;
        }

        $this->logChange($modelType, $modelId, $field, $oldValue, $newValue, $countryCode, $source, $userId);
    }
}
