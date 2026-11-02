<?php

namespace App\Http\Controllers;

use App\Models\PdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExportDownloadController extends Controller
{
    public function __invoke(Request $request, string $token): StreamedResponse
    {
        $export = PdfExport::query()
            ->where('token', $token)
            ->where('status', 'ready')
            ->firstOrFail();

        $user = $request->user();
        abort_unless($user && (int) $user->id === (int) $export->user_id, 403);

        $disk = Storage::disk($export->storage_disk);
        abort_unless($disk->exists($export->storage_path), 404);

        return $disk->download(
            $export->storage_path,
            $export->original_filename,
            ['Content-Type' => $export->mime_type ?: 'application/pdf']
        );
    }
}
