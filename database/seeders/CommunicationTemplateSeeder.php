<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunicationTemplate;
use Carbon\Carbon;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Production-ready catalogue for SMS / WhatsApp / Email
        // Placeholders use double-curly style to align with existing merge fields.
        $templates = [
            // Admissions
            [
                'code'    => 'admissions_welcome_sms',
                'title'   => 'Welcome Student (SMS/WA)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nWelcome to {{school_name}}! 🎉\nWe are delighted to inform you that {{student_name}} has been successfully admitted.\n\nAdmission Number: {{admission_number}}\nClass: {{class_name}} {{stream_name}}\n\nWe look forward to partnering with you in nurturing your child’s growth and success.\n\nWarm regards,\n{{school_name}}",
            ],
            [
                'code'    => 'admissions_welcome_whatsapp',
                'title'   => 'Welcome Student (WhatsApp)',
                'type'    => 'whatsapp',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nWelcome to {{school_name}}! 🎉\nWe are delighted to inform you that {{student_name}} has been successfully admitted.\n\nAdmission Number: {{admission_number}}\nClass: {{class_name}} {{stream_name}}\n\nWe look forward to partnering with you in nurturing your child’s growth and success.\n\nWarm regards,\n{{school_name}}",
            ],
            [
                'code'    => 'admissions_welcome_email',
                'title'   => 'Welcome Student (Email)',
                'type'    => 'email',
                'subject' => 'Welcome to {{school_name}} – Admission Confirmation',
                'content' => "Dear {{parent_name}},\n\nWe are pleased to welcome you and your child, {{student_name}}, to the {{school_name}} family.\n\nStudent Name: {{student_name}}\nAdmission Number: {{admission_number}}\nClass & Stream: {{class_name}} {{stream_name}}\n\nYou may update your profile or access student information using the link below:\n{{profile_update_link}}\n\nFor any assistance, contact us at {{school_phone}} or {{school_email}}.\n\nWarm regards,\n{{school_name}} Administration",
            ],

            // Staff onboarding
            [
                'code'    => 'staff_welcome_sms',
                'title'   => 'Welcome Staff (SMS/WA)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{staff_name}},\n\nWelcome to {{school_name}}!\nYour staff account has been created successfully.\n\nLogin URL: {{app_url}}\nEmail: {{login_email}}\n\nWe are excited to have you join our team.\n\nRegards,\n{{school_name}}",
            ],
            [
                'code'    => 'staff_welcome_whatsapp',
                'title'   => 'Welcome Staff (WhatsApp)',
                'type'    => 'whatsapp',
                'subject' => null,
                'content' => "Dear {{staff_name}},\n\nWelcome to {{school_name}}!\nYour staff account has been created successfully.\n\nLogin URL: {{app_url}}\nEmail: {{login_email}}\n\nWe are excited to have you join our team.\n\nRegards,\n{{school_name}}",
            ],
            [
                'code'    => 'staff_welcome_email',
                'title'   => 'Welcome Staff (Email)',
                'type'    => 'email',
                'subject' => 'Welcome to {{school_name}} – Staff Account Details',
                'content' => "Dear {{staff_name}},\n\nWelcome to the {{school_name}} team!\n\nYour staff account has been set up. Below are your login details:\nLogin URL: {{app_url}}\nEmail: {{login_email}}\nTemporary Password: {{temporary_password}}\n\nPlease log in and update your profile.\n\nWe wish you success as {{staff_role}}.\n\nWarm regards,\n{{school_name}} Management",
            ],

            // Finance: payment received
            [
                'code'    => 'finance_payment_received_sms',
                'title'   => 'Payment Received (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "{{greeting}},\n\nWe have received a payment of {{amount}} for {{student_name}} ({{admission_number}}) on {{payment_date}}.\n\nReceipt Number: {{receipt_number}}\n\nView or download your receipt here:\n{{finance_portal_link}}\n\nThank you for your continued support.\n{{school_name}}",
            ],
            [
                'code'    => 'finance_payment_received_whatsapp',
                'title'   => 'Payment Received (WhatsApp)',
                'type'    => 'whatsapp',
                'subject' => null,
                'content' => "{{greeting}},\n\nWe have received a payment of {{amount}} for {{student_name}} ({{admission_number}}) on {{payment_date}}.\n\nReceipt Number: {{receipt_number}}\n\nView or download your receipt here:\n{{receipt_link}}\n\nThank you for your continued support.\n{{school_name}}",
            ],
            [
                'code'    => 'finance_payment_received_email',
                'title'   => 'Payment Received (Email)',
                'type'    => 'email',
                'subject' => 'Payment Receipt – {{student_name}}',
                'content' => "{{greeting}},\n\nThank you for your payment of {{amount}} received on {{payment_date}} for {{student_name}}.\nPlease find the payment receipt attached.\n\nYou may also view invoices, receipts, and statements here:\n{{finance_portal_link}}\n\nWe appreciate your cooperation.\n\nKind regards,\n{{school_name}} Finance Office",
            ],

            // Finance: share invoice/receipt/statement
            [
                'code'    => 'finance_share_link_sms',
                'title'   => 'Share Finance Link (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nYou can view and download {{student_name}}’s invoices, receipts, and fee statement using the link below:\n\n{{finance_portal_link}}\n\nThank you,\n{{school_name}}",
            ],
            [
                'code'    => 'finance_share_link_email',
                'title'   => 'Share Finance Link (Email)',
                'type'    => 'email',
                'subject' => 'Financial Document – {{student_name}}',
                'content' => "Dear {{parent_name}},\n\nPlease find the requested financial document for {{student_name}} attached.\nYou can also access all financial records anytime via:\n{{finance_portal_link}}\n\nWe are always happy to assist.\n\nWarm regards,\n{{school_name}} Finance Team",
            ],

            // Finance: fee reminders
            [
                'code'    => 'finance_fee_reminder_sms',
                'title'   => 'Fee Reminder (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nFriendly reminder: there is an outstanding fee balance for {{student_name}} for {{term_name}}, {{academic_year}}.\nPlease review details here:\n{{finance_portal_link}}\n\nThank you for your cooperation.\n{{school_name}}",
            ],
            [
                'code'    => 'finance_fee_plan_email',
                'title'   => 'Fee Payment Plan (Email)',
                'type'    => 'email',
                'subject' => 'Fee Payment Update – {{student_name}}',
                'content' => "Dear {{parent_name}},\n\nSchool fees for {{student_name}} remain pending for {{term_name}}, {{academic_year}}.\nIf you are on a payment plan or need assistance, kindly reach out.\n\nView the full statement here:\n{{finance_portal_link}}\n\nWe appreciate your continued partnership.\n\nWarm regards,\n{{school_name}} Accounts Office",
            ],

            // Academics
            [
                'code'    => 'academics_report_sms',
                'title'   => 'Report Card Ready (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\n{{student_name}}’s report card is now available.\nView and download here:\n{{report_card_link}}\n\nThank you,\n{{school_name}}",
            ],
            [
                'code'    => 'academics_report_email',
                'title'   => 'Report Card Ready (Email)',
                'type'    => 'email',
                'subject' => 'Academic Report – {{student_name}}',
                'content' => "Dear {{parent_name}},\n\nWe are pleased to share {{student_name}}’s academic report for {{term_name}}, {{academic_year}}.\nPlease find the report card attached and available online here:\n{{report_card_link}}\n\nWe appreciate your support.\n\nWarm regards,\n{{school_name}} Academic Office",
            ],

            // Attendance
            [
                'code'    => 'attendance_absent_sms',
                'title'   => 'Attendance: Absent (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\n{{student_name}} was marked {{attendance_status}} on {{attendance_date}}.\nReason: {{attendance_reason}}\nIf clarification is needed, kindly contact the school.\n\nRegards,\n{{school_name}}",
            ],
            [
                'code'    => 'attendance_status_email',
                'title'   => 'Attendance Update (Email)',
                'type'    => 'email',
                'subject' => 'Attendance Update – {{student_name}}',
                'content' => "Dear {{parent_name}},\n\nWe wish to inform you that {{student_name}}’s attendance status for {{attendance_date}} has been updated to {{attendance_status}}.\nReason: {{attendance_reason}}\n\nThank you for your cooperation.\n\nWarm regards,\n{{school_name}}",
            ],

            // Transport
            [
                'code'    => 'transport_trip_sms',
                'title'   => 'Transport Trip (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nTransport update for {{student_name}}:\nRoute: {{route_name}}\nDrop-off Point: {{drop_off_point}}\nTime: {{pickup_time}}\n\nThank you,\n{{school_name}} Transport Office",
            ],
            [
                'code'    => 'transport_delay_sms',
                'title'   => 'Transport Delay (SMS/WA)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nWe wish to inform you of a delay on {{route_name}} due to {{delay_reason}}.\n\nWe apologize for the inconvenience and appreciate your patience.\n\n{{school_name}}",
            ],
            [
                'code'    => 'transport_delay_whatsapp',
                'title'   => 'Transport Delay (WhatsApp)',
                'type'    => 'whatsapp',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nWe wish to inform you of a delay on {{route_name}} due to {{delay_reason}}.\n\nWe apologize for the inconvenience and appreciate your patience.\n\n{{school_name}}",
            ],

            // Extra useful operational templates
            [
                'code'    => 'finance_low_balance_sms',
                'title'   => 'Outstanding Balance Alert (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nThere is an outstanding balance of {{outstanding_amount}} for {{student_name}}. Please clear by {{due_date}}. \n{{school_name}}",
            ],
            [
                'code'    => 'finance_refund_email',
                'title'   => 'Refund / Overpayment Notice (Email)',
                'type'    => 'email',
                'subject' => 'Refund Notice – {{student_name}}',
                'content' => "Dear {{parent_name}},\n\nWe identified an overpayment for {{student_name}}. Please contact the finance office to process a refund or allocate to upcoming fees.\n\nContact: {{school_phone}}\n\nThank you,\n{{school_name}} Finance",
            ],
            [
                'code'    => 'academics_exam_timetable_sms',
                'title'   => 'Exam Timetable (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nThe exam timetable for {{class_name}} is now available. View here: {{exam_results_link}}\n\n{{school_name}}",
            ],
            [
                'code'    => 'attendance_consecutive_absence_sms',
                'title'   => 'Consecutive Absence Alert (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\n{{student_name}} has missed multiple days. Please contact the school to provide a reason or update attendance.\n\n{{school_name}}",
            ],
            [
                'code'    => 'transport_route_change_sms',
                'title'   => 'Transport Route Change (SMS)',
                'type'    => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nTransport update: {{student_name}} has been moved to route {{route_name}} with drop-off at {{drop_off_point}}. Pickup time: {{pickup_time}}.\n\n{{school_name}}",
            ],
        ];

        foreach ($templates as $tpl) {
            CommunicationTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                array_merge(
                    $tpl,
                    [
                        'created_at' => CommunicationTemplate::where('code', $tpl['code'])->value('created_at') ?? $now,
                        'updated_at' => $now,
                    ]
                )
            );
        }
    }
}
