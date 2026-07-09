<?php

namespace App\Services\Hr\PayrollExports;

final class ExportResult
{
    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly string $filename,
        public readonly ?string $sha256,
        public readonly array $meta = [],
    ) {}
}

