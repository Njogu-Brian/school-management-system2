<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunicationTemplate;

class PaymentWhatsAppTemplateSeeder extends Seeder
{
    public function run()
    {
        // Create finance_payment_received_whatsapp template
        CommunicationTemplate::firstOrCreate(
            ['code' => 'finance_payment_received_whatsapp'],
            [
                'title' => 'Payment Received (WhatsApp)',
                'type' => 'whatsapp',
                'subject' => null,
                'content' => "{{greeting}},\n\nWe have received a payment of {{amount}} for {{student_name}} ({{admission_number}}) on {{payment_date}}.\n\nReceipt Number: {{receipt_number}}\n\nView or download your receipt here:\n{{receipt_link}}\n\nThank you for your continued support.\n{{school_name}}",
            ]
        );

        // Also create payment_receipt_whatsapp as an alias
        CommunicationTemplate::firstOrCreate(
            ['code' => 'payment_receipt_whatsapp'],
            [
                'title' => 'Payment Receipt (WhatsApp)',
                'type' => 'whatsapp',
                'subject' => null,
                'content' => "{{greeting}},\n\nWe have received a payment of {{amount}} for {{student_name}} ({{admission_number}}) on {{payment_date}}.\n\nReceipt Number: {{receipt_number}}\n\nView or download your receipt here:\n{{receipt_link}}\n\nThank you for your continued support.\n{{school_name}}",
            ]
        );

        $this->command->info('✓ Payment WhatsApp templates created successfully!');
    }
}

