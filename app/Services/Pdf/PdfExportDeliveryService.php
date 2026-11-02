<?php

namespace App\Services\Pdf;

use App\Mail\GenericMail;
use App\Models\PdfExport;
use App\Models\User;
use App\Services\AppChannelNotifyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Store finished PDF/ZIP exports on the public disk (S3 in production) and notify the user
 * via branded email + web/mobile in-app alerts with a permanent app download link.
 */
class PdfExportDeliveryService
{
    public function __construct(
        protected AppChannelNotifyService $appNotify,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function storeAndNotify(
        int $userId,
        string $localOrBinaryPath,
        string $filename,
        string $title,
        string $module = 'general',
        ?int $itemCount = null,
        array $meta = [],
        ?string $mimeType = null,
    ): PdfExport {
        $disk = (string) config('filesystems.public_disk', 'public');
        $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'export';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'pdf');
        $storagePath = 'pdf-exports/'.now()->format('Y/m').'/'.$safeName.'-'.Str::lower(Str::random(8)).'.'.$ext;

        $binary = is_file($localOrBinaryPath)
            ? (string) file_get_contents($localOrBinaryPath)
            : (string) $localOrBinaryPath;

        if ($binary === '') {
            throw new \RuntimeException('Refusing to store an empty PDF export.');
        }

        Storage::disk($disk)->put($storagePath, $binary);

        $mime = $mimeType
            ?? ($ext === 'zip' ? 'application/zip' : 'application/pdf');

        $export = PdfExport::create([
            'user_id' => $userId,
            'token' => PdfExport::mintToken(),
            'title' => $title,
            'original_filename' => $filename,
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
            'mime_type' => $mime,
            'byte_size' => strlen($binary),
            'item_count' => $itemCount,
            'module' => $module,
            'status' => 'ready',
            'meta' => $meta,
            'ready_at' => now(),
        ]);

        $this->notifyUser($export);

        return $export;
    }

    public function notifyUser(PdfExport $export): void
    {
        $user = User::find($export->user_id);
        if (! $user) {
            return;
        }

        $url = $export->permanentDownloadUrl();
        $isZip = str_ends_with(strtolower($export->original_filename), '.zip');
        $schoolName = (string) (setting('school_name') ?: config('app.name', 'School'));
        $title = $isZip ? 'Your PDF export ZIP is ready' : 'Your PDF export is ready';
        $body = trim(
            ($export->title ?: 'PDF export').' is ready to download'.
            ($export->item_count ? " ({$export->item_count} items)" : '').'.'
        );

        try {
            $this->appNotify->notifyUsers(collect([$user]), $title, $body, [
                'type' => 'pdf_export_ready',
                'category' => 'exports',
                'push_channel' => 'teacher-alerts',
                'url' => $url,
                'download_url' => $url,
                'filename' => $export->original_filename,
                'pdf_export_token' => $export->token,
                'module' => $export->module,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PDF export app notify failed', ['error' => $e->getMessage(), 'export_id' => $export->id]);
        }

        if (filled($user->email)) {
            try {
                Mail::send('emails.pdf-export-ready', [
                    'schoolName' => $schoolName,
                    'schoolLogo' => $this->schoolLogoUrl(),
                    'schoolMotto' => setting('school_motto') ?: '',
                    'userName' => $user->name ?? 'there',
                    'title' => $export->title,
                    'filename' => $export->original_filename,
                    'itemCount' => $export->item_count,
                    'downloadUrl' => $url,
                    'isZip' => $isZip,
                ], function ($message) use ($user, $title, $schoolName) {
                    $message->to($user->email)->subject("{$schoolName}: {$title}");
                });
            } catch (\Throwable $e) {
                Log::warning('PDF export email failed', ['error' => $e->getMessage(), 'export_id' => $export->id]);
                try {
                    Mail::to($user->email)->send(new GenericMail(
                        "{$schoolName}: {$title}",
                        "Hi {$user->name},\n\n{$body}\n\nDownload (permanent link; sign in if prompted):\n{$url}\n"
                    ));
                } catch (\Throwable $e2) {
                    Log::warning('PDF export fallback email failed', ['error' => $e2->getMessage()]);
                }
            }
        }
    }

    protected function schoolLogoUrl(): string
    {
        $logo = setting('school_logo');
        if ($logo && function_exists('public_image_url')) {
            try {
                return (string) public_image_url($logo);
            } catch (\Throwable) {
                // fall through
            }
        }

        return (string) (setting('school_website') ?: config('app.url'));
    }
}
