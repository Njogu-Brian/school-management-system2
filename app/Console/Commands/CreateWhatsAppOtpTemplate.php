<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class CreateWhatsAppOtpTemplate extends Command
{
    protected $signature = 'whatsapp:create-otp-template';

    protected $description = 'Create the Meta WhatsApp AUTHENTICATION OTP template (edulynk_otp)';

    public function handle(WhatsAppService $whatsApp): int
    {
        $this->info('Submitting Meta WhatsApp OTP template…');

        $result = $whatsApp->createOtpMessageTemplate();

        if (($result['status'] ?? '') === 'success') {
            $this->info($result['message'] ?? 'Template submitted.');
            $this->line(json_encode($result['body'], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->error($result['message'] ?? 'Failed to create template.');
        $this->line(json_encode($result['body'], JSON_PRETTY_PRINT));

        return self::FAILURE;
    }
}
