<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->invoice->student;
        $name = $student
            ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
            : 'your child';
        $number = $this->invoice->invoice_number ?: ('#'.$this->invoice->id);

        return [
            'type' => 'invoice',
            'category' => 'finance',
            'title' => 'New fee invoice',
            'body' => "Invoice {$number} for {$name} is ready to view.",
            'invoice_id' => $this->invoice->id,
            'student_id' => $student?->id,
            'deep_link' => $student
                ? "royalkingsusers://children/{$student->id}/invoices/{$this->invoice->id}"
                : null,
        ];
    }
}
