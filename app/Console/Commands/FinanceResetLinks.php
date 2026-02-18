<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyReceiptLink;
use App\Models\FamilyUpdateLink;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FinanceResetLinks extends Command
{
    protected $signature = 'finance:reset-links
                            {--dry-run : List what would be done without making changes}
                            {--regenerate-tokens : Also regenerate payment receipt tokens and invoice hashed_ids (old receipt/invoice URLs will break)}';
    protected $description = 'Delete all finance-related links (payment links, optionally receipt/invoice tokens), then create one payment link per family (families share). Students without family get one link each.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $regenerateTokens = (bool) $this->option('regenerate-tokens');

        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $paymentLinksDeleted = 0;
        $paymentsRegenerated = 0;
        $invoicesRegenerated = 0;
        $familiesCreated = 0;
        $paymentLinksCreated = 0;
        $receiptLinksCreated = 0;

        // Step 1: Delete all payment links (force delete including soft-deleted)
        $toDelete = PaymentLink::withTrashed()->count();
        if ($dryRun) {
            $this->line("Would delete {$toDelete} payment link(s).");
            $paymentLinksDeleted = $toDelete;
        } else {
            PaymentLink::withTrashed()->forceDelete();
            $paymentLinksDeleted = $toDelete;
            $this->line("Deleted {$paymentLinksDeleted} payment link(s).");
        }

        // Step 2 (optional): Regenerate payment public_token and invoice hashed_id so old receipt/invoice URLs break
        if ($regenerateTokens) {
            if ($dryRun) {
                $paymentsRegenerated = Payment::count();
                $invoicesRegenerated = Invoice::count();
                $this->line("Would regenerate tokens for {$paymentsRegenerated} payment(s) and {$invoicesRegenerated} invoice(s).");
            } else {
                foreach (Payment::cursor() as $payment) {
                    $payment->public_token = Payment::generatePublicToken();
                    $payment->saveQuietly();
                    $paymentsRegenerated++;
                }
                foreach (Invoice::cursor() as $invoice) {
                    $invoice->hashed_id = Invoice::generateHashedId();
                    $invoice->saveQuietly();
                    $invoicesRegenerated++;
                }
                $this->line("Regenerated tokens for {$paymentsRegenerated} payment(s) and {$invoicesRegenerated} invoice(s).");
            }
        }

        // Step 3: Ensure every active student has a family (so they can share payment link)
        $studentsWithoutFamilies = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->whereNull('family_id')
            ->with('parent')
            ->get();

        foreach ($studentsWithoutFamilies as $student) {
            if ($dryRun) {
                $this->line("Would create family for student #{$student->id} ({$student->admission_number}).");
                $familiesCreated++;
                continue;
            }
            $family = Family::create([
                'guardian_name' => $student->parent
                    ? ($student->parent->guardian_name ?? $student->parent->father_name ?? $student->parent->mother_name ?? 'Family ' . $student->admission_number)
                    : 'Family ' . $student->admission_number,
                'phone' => $student->parent
                    ? ($student->parent->guardian_phone ?? $student->parent->father_phone ?? $student->parent->mother_phone)
                    : null,
                'email' => $student->parent
                    ? ($student->parent->guardian_email ?? $student->parent->father_email ?? $student->parent->mother_email)
                    : null,
            ]);
            $student->update(['family_id' => $family->id]);
            if (! $family->updateLink) {
                \App\Models\FamilyUpdateLink::firstOrCreate(
                    ['family_id' => $family->id],
                    ['is_active' => true]
                );
            }
            $familiesCreated++;
        }

        // Step 4: Create one payment link per family (and one per student if no family - but we just gave everyone a family)
        $families = Family::has('students')->with('students')->get();
        $seenFamilyIds = [];

        foreach ($families as $family) {
            if (isset($seenFamilyIds[$family->id])) {
                continue;
            }
            $seenFamilyIds[$family->id] = true;
            $student = $family->students()->where('archive', 0)->first();
            if (! $student) {
                continue;
            }

            if ($dryRun) {
                $this->line("Would create payment link for family #{$family->id}.");
                $paymentLinksCreated++;
                continue;
            }

            $link = ensure_family_payment_link($family->id);
            if ($link && $link->wasRecentlyCreated) {
                $paymentLinksCreated++;
                $this->line("Created payment link for family #{$family->id}.");
            }
        }

        // Step 5: Ensure every family has a permanent receipt link (my-receipts)
        if (class_exists(FamilyReceiptLink::class) && \Illuminate\Support\Facades\Schema::hasTable('family_receipt_links')) {
            foreach ($families as $family) {
                $existing = FamilyReceiptLink::where('family_id', $family->id)->first();
                if ($existing) {
                    continue;
                }
                if ($dryRun) {
                    $this->line("Would create receipt link for family #{$family->id}.");
                    $receiptLinksCreated++;
                    continue;
                }
                FamilyReceiptLink::firstOrCreate(
                    ['family_id' => $family->id],
                    ['is_active' => true]
                );
                $receiptLinksCreated++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $rows = [
            ['Payment links deleted', $paymentLinksDeleted],
            ['Families created (students without family)', $familiesCreated],
            ['Payment links created (one per family)', $paymentLinksCreated],
        ];
        if (class_exists(FamilyReceiptLink::class) && \Illuminate\Support\Facades\Schema::hasTable('family_receipt_links')) {
            $rows[] = ['Receipt links created (one per family)', $receiptLinksCreated];
        }
        if ($regenerateTokens) {
            $rows[] = ['Payment receipt tokens regenerated', $paymentsRegenerated];
            $rows[] = ['Invoice hashed_ids regenerated', $invoicesRegenerated];
        }
        $this->table(
            ['Action', $dryRun ? 'Would do' : 'Done'],
            $rows
        );

        return self::SUCCESS;
    }
}
