<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PdfExport extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'title',
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'byte_size',
        'item_count',
        'module',
        'status',
        'meta',
        'ready_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'ready_at' => 'datetime',
        'byte_size' => 'integer',
        'item_count' => 'integer',
    ];

    public static function mintToken(): string
    {
        return Str::lower(Str::replace('-', '', (string) Str::uuid()).Str::random(16));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && filled($this->storage_path);
    }

    public function permanentDownloadUrl(): string
    {
        return route('pdf-exports.download', ['token' => $this->token]);
    }
}
