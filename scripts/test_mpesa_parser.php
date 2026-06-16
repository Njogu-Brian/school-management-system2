<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function say(string $message): void
{
    echo $message . PHP_EOL;
    if (function_exists('ob_flush')) {
        @ob_flush();
    }
    flush();
}

$quick = in_array('--quick', $argv ?? [], true);

$python = App\Support\PythonCommand::resolve();
say('Resolved: ' . ($python ?? 'NULL'));

if ($python === null) {
    say('ERROR: No Python with pdfplumber found. Run: cd app/Services/python && python -m venv venv && venv\\Scripts\\python.exe -m pip install pdfplumber');
    exit(1);
}

if ($quick) {
    say('Quick check OK (pdfplumber importable). Run without --quick to test a full PDF parse (~1-3 min).');
    exit(0);
}

$path = $argv[1] ?? 'c:/Users/Admin/Downloads/MPESA_Statement_2025-12-31_to_2025-01-01_2547xxxxxx397.pdf';
if (! is_file($path)) {
    say("Sample PDF not found at $path");
    say('Usage: php scripts/test_mpesa_parser.php [path-to.pdf]');
    say('       php scripts/test_mpesa_parser.php --quick');
    exit(1);
}

say('PDF: ' . $path);
say('Parsing… full-year statements usually take 1–3 minutes. This is normal — not stuck.');
$started = microtime(true);

$parser = app(App\Services\Finance\MpesaExpenseStatementParser::class);
$r = $parser->parse($path, '821967');

$elapsed = round(microtime(true) - $started, 1);
say('Finished in ' . $elapsed . 's');
say(json_encode([
    'success' => $r['success'] ?? false,
    'count' => $r['transaction_count'] ?? 0,
    'message' => $r['message'] ?? null,
], JSON_PRETTY_PRINT));

exit(($r['success'] ?? false) ? 0 : 1);
