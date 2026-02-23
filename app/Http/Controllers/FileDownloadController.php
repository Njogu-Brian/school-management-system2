<?php

namespace App\Http\Controllers;

use App\Models\OnlineAdmission;
use App\Models\ParentInfo;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    /**
     * Serve private files for admins only.
     */
    public function show(string $model, int $id, string $field): StreamedResponse
    {
        $map = [
            'student' => [
                'model' => Student::withArchived(),
                'fields' => ['photo_path', 'birth_certificate_path'],
                'label' => 'student',
            ],
            'parent' => [
                'model' => ParentInfo::query(),
                'fields' => ['father_id_document', 'mother_id_document', 'guardian_id_document'],
                'label' => 'parent',
            ],
            'online-admission' => [
                'model' => OnlineAdmission::query(),
                'fields' => ['passport_photo', 'birth_certificate', 'father_id_document', 'mother_id_document'],
                'label' => 'admission',
            ],
        ];

        abort_unless(isset($map[$model]), 404);
        abort_unless(in_array($field, $map[$model]['fields'], true), 404);

        $record = $map[$model]['model']->findOrFail($id);
        $path = $record->{$field};
        abort_unless($path, 404);

        abort_unless(storage_private()->exists($path), 404);

        $filename = $map[$model]['label'] . '-' . $id . '-' . basename($path);
        return storage_private()->download($path, $filename);
    }
}

