<?php

namespace App\Services\Admissions;

use App\Models\Admissions\AdmissionApplication;
use App\Models\Admissions\AdmissionDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class AdmissionApplicationService
{
    public function directory(): string
    {
        $path = public_path('admissions');

        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    public function createDraft(array $data): AdmissionApplication
    {
        return AdmissionApplication::create([
            ...$data,
            'status' => AdmissionApplication::STATUS_PENDING,
            'current_step' => 1,
        ]);
    }

    public function saveProgress(AdmissionApplication $application, int $step, array $data): AdmissionApplication
    {
        $progress = $application->form_progress ?? [];
        $progress["step_{$step}"] = $data;

        $application->update([
            'current_step' => max($application->current_step, $step),
            'form_progress' => $progress,
            ...$this->mapStepData($step, $data),
        ]);

        return $application->fresh();
    }

    public function submit(AdmissionApplication $application, array $data): AdmissionApplication
    {
        $application->update([
            ...$this->mapStepData(4, $data),
            'status' => AdmissionApplication::STATUS_PENDING,
            'current_step' => 4,
        ]);

        return $application->fresh();
    }

    public function storeDocument(AdmissionApplication $application, UploadedFile $file, string $type): AdmissionDocument
    {
        if (! in_array($type, AdmissionDocument::types(), true)) {
            throw new \InvalidArgumentException('Invalid document type.');
        }

        $subdir = $application->application_no;
        $targetDir = $this->directory().DIRECTORY_SEPARATOR.$subdir;
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $filename = $type.'_'.time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($targetDir, $filename);
        $relative = $subdir.'/'.$filename;

        return AdmissionDocument::updateOrCreate(
            ['application_id' => $application->id, 'document_type' => $type],
            ['file_path' => $relative, 'verified' => false]
        );
    }

    public function updateStatus(AdmissionApplication $application, string $status, ?int $staffId = null, ?string $notes = null): AdmissionApplication
    {
        $application->update([
            'status' => $status,
            'assigned_staff' => $staffId ?? $application->assigned_staff,
            'admission_notes' => $notes ?? $application->admission_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return $application->fresh();
    }

    protected function mapStepData(int $step, array $data): array
    {
        return match ($step) {
            1 => [
                'parent_name' => $data['parent_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
            ],
            2 => [
                'child_name' => $data['child_name'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'age' => $data['age'] ?? null,
                'desired_class' => $data['desired_class'] ?? null,
                'previous_school' => $data['previous_school'] ?? null,
            ],
            3 => [
                'medical_notes' => $data['medical_notes'] ?? null,
                'special_needs' => $data['special_needs'] ?? null,
            ],
            default => $data,
        };
    }
}
