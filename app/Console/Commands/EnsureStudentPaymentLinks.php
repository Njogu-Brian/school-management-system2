<?php

namespace App\Console\Commands;

use App\Models\PaymentLink;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnsureStudentPaymentLinks extends Command
{
    protected $signature = 'students:ensure-payment-links
                            {--dry-run : List what would be done without creating links}
                            {--test-links : After ensuring links, HTTP GET each to verify it works}
                            {--skip-test : Do not test links (default if --test-links not set)}';

    protected $description = 'Ensure every active student has a working payment link; siblings share one family link. Optionally test links via HTTP.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $testLinks = (bool) $this->option('test-links');

        if ($dryRun) {
            $this->info('Dry run – no links will be created.');
        }

        $students = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('family_id')
            ->orderBy('id')
            ->get();

        if ($students->isEmpty()) {
            $this->warn('No active students found.');
            return self::SUCCESS;
        }

        $this->info('Ensuring payment links for ' . $students->count() . ' active student(s).');
        $this->newLine();

        $created = 0;
        $seenLinkIds = [];
        $linksByStudent = [];

        foreach ($students as $student) {
            if ($dryRun) {
                $link = $this->getExistingLinkForStudent($student);
                if (!$link) {
                    $created++;
                    $this->line('Would create ' . ($student->family_id ? 'family' : 'student') . ' link for: ' . $this->studentLabel($student));
                }
                continue;
            }

            $link = get_or_create_payment_link_for_student($student);
            if (!$link) {
                $this->warn('Could not get/create link for student ID ' . $student->id);
                continue;
            }

            $linksByStudent[$student->id] = $link;
            if (!isset($seenLinkIds[$link->id])) {
                $seenLinkIds[$link->id] = true;
                if ($link->wasRecentlyCreated) {
                    $created++;
                    $this->line('Created ' . ($link->family_id ? 'family' : 'student') . ' link: ' . $this->linkLabel($link));
                }
            }
        }

        if (!$dryRun) {
            $this->newLine();
            $this->info('Summary: ' . $created . ' new link(s) created.');
        }

        if ($testLinks && !$dryRun) {
            $this->newLine();
            $this->testLinks($linksByStudent);
        } elseif ($testLinks && $dryRun) {
            $this->line('Skipping link tests in dry run.');
        }

        return self::SUCCESS;
    }

    /**
     * Get existing active link for student (for dry run only).
     */
    protected function getExistingLinkForStudent(Student $student): ?PaymentLink
    {
        if ($student->family_id) {
            return PaymentLink::active()
                ->where('family_id', $student->family_id)
                ->whereNull('student_id')
                ->first();
        }
        return PaymentLink::active()
            ->where('student_id', $student->id)
            ->first();
    }

    protected function studentLabel(Student $student): string
    {
        $name = trim($student->first_name . ' ' . $student->last_name);
        $adm = $student->admission_number ?? 'ID:' . $student->id;
        return $name . ' (' . $adm . ')' . ($student->family_id ? ' [family ' . $student->family_id . ']' : '');
    }

    protected function linkLabel(PaymentLink $link): string
    {
        if ($link->family_id) {
            return 'Family #' . $link->family_id . ' – ' . $link->getPaymentUrl();
        }
        return 'Student #' . $link->student_id . ' – ' . $link->getPaymentUrl();
    }

    /**
     * Test unique payment links by HTTP GET; report OK or fail.
     */
    protected function testLinks(array $linksByStudent): void
    {
        $uniqueLinks = collect($linksByStudent)->unique('id')->values();
        $baseUrl = rtrim(config('app.url'), '/');
        $ok = 0;
        $failed = [];

        $this->info('Testing ' . $uniqueLinks->count() . ' unique payment link(s) at ' . $baseUrl . ' ...');
        $bar = $this->output->createProgressBar($uniqueLinks->count());
        $bar->start();

        foreach ($uniqueLinks as $link) {
            $url = $baseUrl . '/pay/' . $link->hashed_id;
            try {
                $response = Http::timeout(15)->get($url);
                if ($response->successful()) {
                    $ok++;
                } else {
                    $failed[] = ['url' => $url, 'status' => $response->status(), 'label' => $this->linkLabel($link)];
                }
            } catch (\Throwable $e) {
                $failed[] = ['url' => $url, 'status' => 'error', 'label' => $this->linkLabel($link), 'message' => $e->getMessage()];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Link test results: ' . $ok . ' OK, ' . count($failed) . ' failed.');

        if (!empty($failed)) {
            $this->table(
                ['Link', 'URL / Status', 'Detail'],
                collect($failed)->map(function ($f) {
                    return [
                        $f['label'] ?? '-',
                        $f['url'] ?? '-',
                        (isset($f['status']) && $f['status'] !== 'error' ? 'HTTP ' . $f['status'] : ($f['message'] ?? 'Request failed')),
                    ];
                })->toArray()
            );
        }
    }
}
