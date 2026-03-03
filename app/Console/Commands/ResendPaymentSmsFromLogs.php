<?php

namespace App\Console\Commands;

use App\Models\CommunicationLog;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-time: Resend payment receipt SMS from communication logs for a period when
 * the SMS service was low on credits. Only payment/receipt SMS (payment_id set).
 * Uses RKS finance sender. Does not resend attendance, fee balance, or manual SMS.
 */
class ResendPaymentSmsFromLogs extends Command
{
    protected $signature = 'finance:resend-payment-sms
                            {--dry-run : List logs that would be resent without sending}
                            {--from= : Start of window (Y-m-d H:i), default 2026-02-27 12:06}
                            {--to=   : End of window (Y-m-d H:i), default 2026-03-02 13:36}';

    protected $description = 'Resend payment receipt SMS from logs (Feb 27 12:06 – Mar 2 13:36) using RKS finance sender';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::parse('2026-02-27 12:06:00');
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::parse('2026-03-02 13:36:00');

        $this->info('Payment SMS resend (payment_id logs only, RKS finance sender).');
        $this->line('Window: ' . $from->format('Y-m-d H:i') . ' → ' . $to->format('Y-m-d H:i'));
        if ($dryRun) {
            $this->warn('DRY RUN – no SMS will be sent.');
        }

        $logs = CommunicationLog::query()
            ->whereNotNull('payment_id')
            ->where('channel', 'sms')
            ->whereBetween('sent_at', [$from, $to])
            ->orderBy('sent_at')
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No payment SMS logs found in that window.');
            return Command::SUCCESS;
        }

        // One resend per (payment_id, contact) using the first log's message
        $grouped = $logs->groupBy(fn ($log) => $log->payment_id . '|' . $log->contact);
        $total = $grouped->count();
        $this->info('Found ' . $logs->count() . ' log(s), ' . $total . ' unique payment+contact to resend.');

        $smsService = app(SMSService::class);
        $senderId = $smsService->getFinanceSenderId();
        $sent = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($grouped as $key => $group) {
            $first = $group->first();
            $phone = $first->contact;
            $message = $first->message;
            $paymentId = $first->payment_id;
            $title = $first->title ?? 'Payment Receipt';

            if ($dryRun) {
                $this->newLine();
                $this->line("  [DRY RUN] Would resend to {$phone} (payment_id={$paymentId})");
                $sent++;
                $bar->advance();
                continue;
            }

            try {
                $smsService->sendSMS($phone, $message, $senderId);
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id'   => $first->recipient_id,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'title'          => $title . ' (resend)',
                    'message'        => $message,
                    'type'           => 'sms',
                    'status'         => 'sent',
                    'response'       => ['resend' => true],
                    'scope'          => 'sms',
                    'sent_at'        => now(),
                    'payment_id'     => $paymentId,
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("  Failed {$phone} (payment_id={$paymentId}): " . $e->getMessage());
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id'   => $first->recipient_id,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'title'          => $title . ' (resend)',
                    'message'        => $message,
                    'type'           => 'sms',
                    'status'         => 'failed',
                    'response'       => ['error' => $e->getMessage()],
                    'scope'          => 'sms',
                    'sent_at'        => now(),
                    'payment_id'     => $paymentId,
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done. Resent: ' . $sent . ', Failed: ' . $failed . '.');

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
