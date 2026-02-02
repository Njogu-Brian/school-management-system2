<?php

namespace App\Console\Commands;

use App\Models\OnlineAdmission;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Services\PhoneNumberService;
use Illuminate\Console\Command;

class NormalizePhoneNumbers extends Command
{
    protected $signature = 'phones:normalize {--apply : Apply updates (default: dry-run)} {--limit= : Limit rows per model}';
    protected $description = 'Normalize phone numbers and country codes across core records.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $service = app(PhoneNumberService::class);
        $logger = app(\App\Services\PhoneNumberNormalizationLogger::class);

        $this->info($apply ? 'Applying updates...' : 'Dry-run (no changes will be saved).');

        $summary = [
            'parents' => ['scanned' => 0, 'updated' => 0, 'invalid' => 0],
            'students' => ['scanned' => 0, 'updated' => 0, 'invalid' => 0],
            'staff' => ['scanned' => 0, 'updated' => 0, 'invalid' => 0],
            'online_admissions' => ['scanned' => 0, 'updated' => 0, 'invalid' => 0],
        ];

        $this->normalizeParents($service, $logger, $apply, $limit, $summary);
        $this->normalizeStudents($service, $logger, $apply, $limit, $summary);
        $this->normalizeStaff($service, $logger, $apply, $limit, $summary);
        $this->normalizeOnlineAdmissions($service, $logger, $apply, $limit, $summary);

        $this->table(['Model', 'Scanned', 'Updated', 'Invalid'], [
            ['ParentInfo', $summary['parents']['scanned'], $summary['parents']['updated'], $summary['parents']['invalid']],
            ['Student', $summary['students']['scanned'], $summary['students']['updated'], $summary['students']['invalid']],
            ['Staff', $summary['staff']['scanned'], $summary['staff']['updated'], $summary['staff']['invalid']],
            ['OnlineAdmission', $summary['online_admissions']['scanned'], $summary['online_admissions']['updated'], $summary['online_admissions']['invalid']],
        ]);

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function normalizeParents(PhoneNumberService $service, \App\Services\PhoneNumberNormalizationLogger $logger, bool $apply, ?int $limit, array &$summary): void
    {
        ParentInfo::query()
            ->when($limit, fn ($q) => $q->limit($limit))
            ->chunkById(200, function ($rows) use ($service, $apply, &$summary) {
                foreach ($rows as $row) {
                    $summary['parents']['scanned']++;
                    $updates = [];

                    $fatherCode = $service->normalizeCountryCode($row->father_phone_country_code ?: '+254');
                    $motherCode = $service->normalizeCountryCode($row->mother_phone_country_code ?: '+254');
                    $guardianCode = $service->normalizeCountryCode($row->guardian_phone_country_code ?: '+254');
                    $fatherWaCode = $service->normalizeCountryCode($row->father_whatsapp_country_code ?: $fatherCode);
                    $motherWaCode = $service->normalizeCountryCode($row->mother_whatsapp_country_code ?: $motherCode);

                    if ($row->father_phone_country_code !== $fatherCode) $updates['father_phone_country_code'] = $fatherCode;
                    if ($row->mother_phone_country_code !== $motherCode) $updates['mother_phone_country_code'] = $motherCode;
                    if ($row->guardian_phone_country_code !== $guardianCode) $updates['guardian_phone_country_code'] = $guardianCode;
                    if ($row->father_whatsapp_country_code !== $fatherWaCode) $updates['father_whatsapp_country_code'] = $fatherWaCode;
                    if ($row->mother_whatsapp_country_code !== $motherWaCode) $updates['mother_whatsapp_country_code'] = $motherWaCode;

                    $updates = $this->normalizePhones($service, $logger, $row, $updates, [
                        ['field' => 'father_phone', 'code' => $fatherCode],
                        ['field' => 'father_whatsapp', 'code' => $fatherWaCode],
                        ['field' => 'mother_phone', 'code' => $motherCode],
                        ['field' => 'mother_whatsapp', 'code' => $motherWaCode],
                        ['field' => 'guardian_phone', 'code' => $guardianCode],
                        ['field' => 'guardian_whatsapp', 'code' => $guardianCode],
                    ], $summary['parents'], ParentInfo::class, 'command', $apply);

                    if (!empty($updates)) {
                        $summary['parents']['updated']++;
                        if ($apply) {
                            $row->update($updates);
                        }
                    }
                }
            });
    }

    private function normalizeStudents(PhoneNumberService $service, \App\Services\PhoneNumberNormalizationLogger $logger, bool $apply, ?int $limit, array &$summary): void
    {
        Student::withArchived()
            ->when($limit, fn ($q) => $q->limit($limit))
            ->chunkById(200, function ($rows) use ($service, $apply, &$summary) {
                foreach ($rows as $row) {
                    $summary['students']['scanned']++;
                    $updates = [];
                    $updates = $this->normalizePhones($service, $logger, $row, $updates, [
                        ['field' => 'emergency_contact_phone', 'code' => '+254'],
                    ], $summary['students'], Student::class, 'command', $apply);

                    if (!empty($updates)) {
                        $summary['students']['updated']++;
                        if ($apply) {
                            $row->update($updates);
                        }
                    }
                }
            });
    }

    private function normalizeStaff(PhoneNumberService $service, \App\Services\PhoneNumberNormalizationLogger $logger, bool $apply, ?int $limit, array &$summary): void
    {
        Staff::query()
            ->when($limit, fn ($q) => $q->limit($limit))
            ->chunkById(200, function ($rows) use ($service, $apply, &$summary) {
                foreach ($rows as $row) {
                    $summary['staff']['scanned']++;
                    $updates = [];
                    $updates = $this->normalizePhones($service, $logger, $row, $updates, [
                        ['field' => 'phone_number', 'code' => '+254'],
                        ['field' => 'emergency_contact_phone', 'code' => '+254'],
                    ], $summary['staff'], Staff::class, 'command', $apply);

                    if (!empty($updates)) {
                        $summary['staff']['updated']++;
                        if ($apply) {
                            $row->update($updates);
                        }
                    }
                }
            });
    }

    private function normalizeOnlineAdmissions(PhoneNumberService $service, \App\Services\PhoneNumberNormalizationLogger $logger, bool $apply, ?int $limit, array &$summary): void
    {
        OnlineAdmission::query()
            ->when($limit, fn ($q) => $q->limit($limit))
            ->chunkById(200, function ($rows) use ($service, $apply, &$summary) {
                foreach ($rows as $row) {
                    $summary['online_admissions']['scanned']++;
                    $updates = [];

                    $fatherCode = $service->normalizeCountryCode($row->father_phone_country_code ?: '+254');
                    $motherCode = $service->normalizeCountryCode($row->mother_phone_country_code ?: '+254');
                    $guardianCode = $service->normalizeCountryCode($row->guardian_phone_country_code ?: '+254');

                    if ($row->father_phone_country_code !== $fatherCode) $updates['father_phone_country_code'] = $fatherCode;
                    if ($row->mother_phone_country_code !== $motherCode) $updates['mother_phone_country_code'] = $motherCode;
                    if ($row->guardian_phone_country_code !== $guardianCode) $updates['guardian_phone_country_code'] = $guardianCode;

                    $updates = $this->normalizePhones($service, $logger, $row, $updates, [
                        ['field' => 'father_phone', 'code' => $fatherCode],
                        ['field' => 'father_whatsapp', 'code' => $fatherCode],
                        ['field' => 'mother_phone', 'code' => $motherCode],
                        ['field' => 'mother_whatsapp', 'code' => $motherCode],
                        ['field' => 'guardian_phone', 'code' => $guardianCode],
                        ['field' => 'guardian_whatsapp', 'code' => $guardianCode],
                        ['field' => 'emergency_contact_phone', 'code' => '+254'],
                    ], $summary['online_admissions'], OnlineAdmission::class, 'command', $apply);

                    if (!empty($updates)) {
                        $summary['online_admissions']['updated']++;
                        if ($apply) {
                            $row->update($updates);
                        }
                    }
                }
            });
    }

    private function normalizePhones(
        PhoneNumberService $service,
        \App\Services\PhoneNumberNormalizationLogger $logger,
        $row,
        array $updates,
        array $fields,
        array &$counters,
        string $modelType,
        string $source,
        bool $apply
    ): array {
        foreach ($fields as $item) {
            $field = $item['field'];
            $code = $item['code'] ?? '+254';
            $current = $row->{$field} ?? null;
            if (!$current) {
                continue;
            }
            $normalized = $service->formatWithCountryCode($current, $code);
            if (!$normalized) {
                $counters['invalid']++;
                continue;
            }
            if ($normalized !== $current) {
                $updates[$field] = $normalized;
                if ($apply) {
                    $logger->logIfChanged($modelType, $row->id ?? null, $field, $current, $normalized, $code, $source, null);
                }
            }
        }

        return $updates;
    }
}
