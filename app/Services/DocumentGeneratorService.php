<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentGeneratorService
{
    /**
     * Generate a document from a template
     *
     * @param DocumentTemplate $template
     * @param array $data Data to replace placeholders
     * @param Student|null $student
     * @param Staff|null $staff
     * @return GeneratedDocument
     */
    public function generate(
        DocumentTemplate $template,
        array $data = [],
        ?Student $student = null,
        ?Staff $staff = null
    ): GeneratedDocument {
        // Merge default data with provided data
        $mergedData = $this->prepareData($data, $student, $staff);

        // Replace placeholders in template
        $html = $this->replacePlaceholders($template->template_html, $mergedData);

        // Generate PDF
        $pdf = Pdf::loadHTML($html);
        
        // Apply settings if available
        $settings = $template->settings ?? [];
        $paperSize = $settings['paper_size'] ?? 'A4';
        $orientation = $settings['orientation'] ?? 'portrait';
        $pdf->setPaper($paperSize, $orientation);

        // Generate filename
        $filename = $this->generateFilename($template, $student, $staff);

        // Save PDF
        $pdfPath = $this->savePdf($pdf, $filename);

        // Create generated document record
        $generatedDocument = GeneratedDocument::create([
            'template_id' => $template->id,
            'student_id' => $student?->id,
            'staff_id' => $staff?->id,
            'document_type' => $template->type,
            'pdf_path' => $pdfPath,
            'data' => $mergedData,
            'filename' => $filename,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
        ]);

        return $generatedDocument;
    }

    /**
     * Prepare data for placeholder replacement
     */
    protected function prepareData(array $data, ?Student $student, ?Staff $staff): array
    {
        $prepared = array_merge($data, $this->getSystemData());

        if ($student) {
            $prepared = array_merge($prepared, $this->getStudentData($student));
        }

        if ($staff) {
            $prepared = array_merge($prepared, $this->getStaffData($staff));
        }

        return $prepared;
    }

    /**
     * Get system-wide data (school info, dates, etc.)
     */
    protected function getSystemData(): array
    {
        $settings = Setting::whereIn('key', [
            'school_name',
            'school_address',
            'school_phone',
            'school_email',
        ])->pluck('value', 'key')->toArray();

        return [
            'school_name' => $settings['school_name'] ?? config('app.name'),
            'school_address' => $settings['school_address'] ?? '',
            'school_phone' => $settings['school_phone'] ?? '',
            'school_email' => $settings['school_email'] ?? '',
            'school_logo' => $this->getSchoolLogoPath(),
            'current_date' => now()->format('d F Y'),
            'current_year' => now()->year,
            'signature_headteacher' => $this->getSignaturePath('headteacher'),
            'signature_registrar' => $this->getSignaturePath('registrar'),
        ];
    }

    /**
     * Get student-specific data
     */
    protected function getStudentData(Student $student): array
    {
        return [
            'student_name' => $student->full_name,
            'student_admission_number' => $student->admission_number,
            'student_class' => $student->classroom?->name ?? '',
            'student_stream' => $student->stream?->name ?? '',
            'student_dob' => $student->dob?->format('d F Y') ?? '',
            'student_gender' => $student->gender ?? '',
            'student_parent_name' => $student->parent?->full_name ?? '',
            'student_parent_phone' => $student->parent?->phone ?? '',
            'student_photo' => $this->getStudentPhotoPath($student),
        ];
    }

    /**
     * Get staff-specific data
     */
    protected function getStaffData(Staff $staff): array
    {
        return [
            'staff_name' => $staff->full_name,
            'staff_id' => $staff->staff_id,
            'staff_department' => $staff->department?->name ?? '',
            'staff_position' => $staff->jobTitle?->name ?? '',
            'staff_photo' => $this->getStaffPhotoPath($staff),
        ];
    }

    /**
     * Replace placeholders in template HTML
     */
    protected function replacePlaceholders(string $html, array $data): string
    {
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $html = str_replace($placeholder, $value ?? '', $html);
        }

        // Remove any remaining placeholders
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);

        return $html;
    }

    /**
     * Generate filename for document
     */
    protected function generateFilename(
        DocumentTemplate $template,
        ?Student $student,
        ?Staff $staff
    ): string {
        $prefix = Str::slug($template->name);
        $identifier = $student?->admission_number ?? $staff?->staff_id ?? 'unknown';
        $timestamp = now()->format('Ymd_His');

        return "{$prefix}_{$identifier}_{$timestamp}.pdf";
    }

    /**
     * Save PDF to storage
     */
    protected function savePdf($pdf, string $filename): string
    {
        $directory = 'documents/' . date('Y/m');
        $path = $directory . '/' . $filename;
        storage_public()->put($path, $pdf->output());
        return $path;
    }

    /**
     * Get school logo path (if exists)
     */
    protected function getSchoolLogoPath(): string
    {
        $logoPath = Setting::where('key', 'school_logo')->value('value');
        
        if ($logoPath && storage_public()->exists($logoPath)) {
            return storage_local_path(config('filesystems.public_disk', 'public'), $logoPath);
        }

        return '';
    }

    /**
     * Get signature path
     */
    protected function getSignaturePath(string $type): string
    {
        $signaturePath = Setting::where('key', "signature_{$type}")->value('value');
        
        if ($signaturePath && storage_public()->exists($signaturePath)) {
            return storage_local_path(config('filesystems.public_disk', 'public'), $signaturePath);
        }

        return '';
    }

    /**
     * Get student photo path
     */
    protected function getStudentPhotoPath(Student $student): string
    {
        if ($student->photo_path && storage_public()->exists($student->photo_path)) {
            return storage_local_path(config('filesystems.public_disk', 'public'), $student->photo_path);
        }

        return '';
    }

    /**
     * Get staff photo path
     */
    protected function getStaffPhotoPath(Staff $staff): string
    {
        if ($staff->photo_path && storage_public()->exists($staff->photo_path)) {
            return storage_local_path(config('filesystems.public_disk', 'public'), $staff->photo_path);
        }

        return '';
    }
}

