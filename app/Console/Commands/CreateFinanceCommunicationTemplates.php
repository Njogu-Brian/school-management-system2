<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CommunicationTemplate;

class CreateFinanceCommunicationTemplates extends Command
{
    protected $signature = 'finance:create-templates';
    protected $description = 'Create communication templates for finance (receipts, invoices, reminders, payment plans)';

    public function handle()
    {
        $this->info('Creating finance communication templates...');

        $templates = [
            // Receipt Templates
            [
                'code' => 'payment_receipt_sms',
                'title' => 'Payment Receipt SMS',
                'type' => 'sms',
                'subject' => 'Payment Receipt',
                'content' => 'Dear {{parent_name}}, Payment of Ksh {{amount}} received for {{student_name}} ({{admission_number}}). Receipt #{{receipt_number}}. View: {{receipt_link}}',
            ],
            [
                'code' => 'payment_receipt_email',
                'title' => 'Payment Receipt Email',
                'type' => 'email',
                'subject' => 'Payment Receipt - {{receipt_number}}',
                'content' => '<p>Dear {{parent_name}},</p><p>Payment of <strong>Ksh {{amount}}</strong> has been received for <strong>{{student_name}}</strong> (Admission: {{admission_number}}).</p><p><strong>Receipt Number:</strong> {{receipt_number}}<br><strong>Transaction Code:</strong> {{transaction_code}}<br><strong>Payment Date:</strong> {{payment_date}}</p><p>Please find the receipt attached.</p><p><a href="{{receipt_link}}">View Receipt Online</a></p>',
            ],

            // Invoice Templates
            [
                'code' => 'invoice_issued_sms',
                'title' => 'Invoice Issued SMS',
                'type' => 'sms',
                'subject' => 'New Invoice',
                'content' => 'Dear {{parent_name}}, Invoice #{{invoice_number}} of Ksh {{total_amount}} issued for {{student_name}} ({{admission_number}}). Due: {{due_date}}. View: {{invoice_link}}',
            ],
            [
                'code' => 'invoice_issued_email',
                'title' => 'Invoice Issued Email',
                'type' => 'email',
                'subject' => 'Invoice #{{invoice_number}} - {{student_name}}',
                'content' => '<p>Dear {{parent_name}},</p><p>A new invoice has been issued for <strong>{{student_name}}</strong> (Admission: {{admission_number}}).</p><p><strong>Invoice Number:</strong> {{invoice_number}}<br><strong>Total Amount:</strong> Ksh {{total_amount}}<br><strong>Due Date:</strong> {{due_date}}<br><strong>Status:</strong> {{status}}</p><p>Please find the invoice attached.</p><p><a href="{{invoice_link}}">View Invoice Online</a></p>',
            ],

            // Fee Reminder Templates
            [
                'code' => 'fee_reminder_sms',
                'title' => 'Fee Reminder SMS',
                'type' => 'sms',
                'subject' => 'Fee Reminder',
                'content' => 'Dear {{parent_name}}, Reminder: Invoice #{{invoice_number}} for {{student_name}} ({{admission_number}}) has outstanding balance of Ksh {{outstanding_amount}}. Due: {{due_date}}. View: {{invoice_link}}',
            ],
            [
                'code' => 'fee_reminder_email',
                'title' => 'Fee Reminder Email',
                'type' => 'email',
                'subject' => 'Fee Reminder - Invoice #{{invoice_number}}',
                'content' => '<p>Dear {{parent_name}},</p><p>This is a reminder that Invoice #{{invoice_number}} for <strong>{{student_name}}</strong> (Admission: {{admission_number}}) has an outstanding balance.</p><p><strong>Outstanding Amount:</strong> Ksh {{outstanding_amount}}<br><strong>Due Date:</strong> {{due_date}}<br><strong>Days Overdue:</strong> {{days_overdue}}</p><p>Please make payment at your earliest convenience.</p><p><a href="{{invoice_link}}">View Invoice Online</a></p>',
            ],

            // Payment Plan Templates
            [
                'code' => 'payment_plan_created_sms',
                'title' => 'Payment Plan Created SMS',
                'type' => 'sms',
                'subject' => 'Payment Plan Created',
                'content' => 'Dear {{parent_name}}, Payment plan created for {{student_name}} ({{admission_number}}). Total: Ksh {{total_amount}}, {{installment_count}} installments of Ksh {{installment_amount}}. View: {{payment_plan_link}}',
            ],
            [
                'code' => 'payment_plan_created_email',
                'title' => 'Payment Plan Created Email',
                'type' => 'email',
                'subject' => 'Payment Plan Created - {{student_name}}',
                'content' => '<p>Dear {{parent_name}},</p><p>A payment plan has been created for <strong>{{student_name}}</strong> (Admission: {{admission_number}}).</p><p><strong>Total Amount:</strong> Ksh {{total_amount}}<br><strong>Installments:</strong> {{installment_count}}<br><strong>Installment Amount:</strong> Ksh {{installment_amount}}<br><strong>Start Date:</strong> {{start_date}}<br><strong>End Date:</strong> {{end_date}}</p><p><a href="{{payment_plan_link}}">View Payment Plan Online</a></p>',
            ],
            [
                'code' => 'payment_plan_installment_due_sms',
                'title' => 'Payment Plan Installment Due SMS',
                'type' => 'sms',
                'subject' => 'Installment Due',
                'content' => 'Dear {{parent_name}}, Installment #{{installment_number}} of Ksh {{installment_amount}} for {{student_name}} ({{admission_number}}) is due on {{due_date}}. View: {{payment_plan_link}}',
            ],
            [
                'code' => 'payment_plan_installment_due_email',
                'title' => 'Payment Plan Installment Due Email',
                'type' => 'email',
                'subject' => 'Installment Due - {{student_name}}',
                'content' => '<p>Dear {{parent_name}},</p><p>This is a reminder that Installment #{{installment_number}} for <strong>{{student_name}}</strong> (Admission: {{admission_number}}) is due.</p><p><strong>Installment Amount:</strong> Ksh {{installment_amount}}<br><strong>Due Date:</strong> {{due_date}}<br><strong>Remaining Installments:</strong> {{remaining_installments}}</p><p><a href="{{payment_plan_link}}">View Payment Plan Online</a></p>',
            ],

            // Custom Finance Communication
            [
                'code' => 'custom_finance_sms',
                'title' => 'Custom Finance SMS',
                'type' => 'sms',
                'subject' => 'Finance Notice',
                'content' => '{{custom_message}}',
            ],
            [
                'code' => 'custom_finance_email',
                'title' => 'Custom Finance Email',
                'type' => 'email',
                'subject' => '{{custom_subject}}',
                'content' => '<p>Dear {{parent_name}},</p><p>{{custom_message}}</p>',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($templates as $template) {
            $existing = CommunicationTemplate::where('code', $template['code'])->first();
            
            if ($existing) {
                $existing->update($template);
                $updated++;
                $this->line("Updated: {$template['code']}");
            } else {
                CommunicationTemplate::create($template);
                $created++;
                $this->line("Created: {$template['code']}");
            }
        }

        $this->info("\nDone! Created: {$created}, Updated: {$updated}");
        return 0;
    }
}
