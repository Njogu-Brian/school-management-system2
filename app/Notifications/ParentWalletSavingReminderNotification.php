<?php

namespace App\Notifications;

use App\Models\ParentWalletSavingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ParentWalletSavingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ParentWalletSavingPlan $plan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $amount = number_format((float) $this->plan->amount, 0);

        return [
            'type' => 'wallet_saving',
            'category' => 'finance',
            'title' => 'Saving reminder',
            'body' => "Time to save KES {$amount} to your family wallet. Tap to pay.",
            'saving_plan_id' => $this->plan->id,
            'deep_link' => 'royalkingsusers://wallet/save/'.$this->plan->id,
        ];
    }
}
