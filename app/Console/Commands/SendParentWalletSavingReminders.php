<?php

namespace App\Console\Commands;

use App\Models\ParentWalletSavingPlan;
use App\Models\User;
use App\Services\ExpoPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendParentWalletSavingReminders extends Command
{
    protected $signature = 'parent-wallet:send-saving-reminders';

    protected $description = 'Push/in-app reminders for parent wallet weekly saving plans';

    public function handle(ExpoPushService $push): int
    {
        $due = ParentWalletSavingPlan::query()
            ->where('active', true)
            ->whereNotNull('next_remind_at')
            ->where('next_remind_at', '<=', now())
            ->limit(200)
            ->get();

        $sent = 0;
        foreach ($due as $plan) {
            try {
                $user = User::find($plan->user_id);
                if (! $user) {
                    $this->advancePlan($plan);
                    continue;
                }

                $title = 'Saving reminder';
                $body = sprintf(
                    'Time to save KES %s to your family wallet.',
                    number_format((float) $plan->amount, 0)
                );
                $data = [
                    'type' => 'wallet_saving',
                    'saving_plan_id' => $plan->id,
                    'deep_link' => 'royalkingsusers://wallet/save/'.$plan->id,
                ];

                try {
                    Notification::send($user, new \App\Notifications\ParentWalletSavingReminderNotification($plan));
                } catch (\Throwable $e) {
                    Log::warning('Wallet saving in-app notify failed: '.$e->getMessage());
                }

                $tokens = DB::table('user_device_tokens')
                    ->where('user_id', $user->id)
                    ->pluck('token')
                    ->filter(fn ($t) => is_string($t) && $t !== '')
                    ->values()
                    ->all();
                if ($tokens !== []) {
                    $push->sendToTokens($tokens, $title, $body, $data);
                }

                $this->advancePlan($plan);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Wallet saving reminder failed', [
                    'plan_id' => $plan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} saving reminders.");

        return self::SUCCESS;
    }

    protected function advancePlan(ParentWalletSavingPlan $plan): void
    {
        $tz = $plan->timezone ?: 'Africa/Nairobi';
        $time = substr((string) $plan->remind_at_time, 0, 5);
        $now = Carbon::now($tz);
        $next = $now->copy()->addDay()->startOfDay();
        $next->setTimeFromTimeString(strlen($time) === 5 ? $time.':00' : $time);
        while ((int) $next->dayOfWeek !== (int) $plan->day_of_week || $next->lessThanOrEqualTo($now)) {
            $next->addDay();
            $next->setTimeFromTimeString(strlen($time) === 5 ? $time.':00' : $time);
        }
        $plan->next_remind_at = $next->utc();
        $plan->save();
    }
}
