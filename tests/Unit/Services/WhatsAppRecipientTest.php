<?php

namespace Tests\Unit\Services;

use App\Services\WhatsAppService;
use Tests\TestCase;

class WhatsAppRecipientTest extends TestCase
{
    public function test_normalizes_kenyan_mobile_numbers(): void
    {
        $whatsapp = app(WhatsAppService::class);

        $this->assertSame('254712345678', $whatsapp->normalizeRecipient('0712345678'));
        $this->assertSame('254112345678', $whatsapp->normalizeRecipient('0112345678'));
        $this->assertSame('254712345678', $whatsapp->normalizeRecipient('+254712345678'));
        $this->assertSame('254712345678', $whatsapp->normalizeRecipient('712345678'));
    }

    public function test_rejects_invalid_recipients(): void
    {
        $whatsapp = app(WhatsAppService::class);

        $this->assertFalse($whatsapp->isValidRecipient('+25424491554'));
        $this->assertFalse($whatsapp->isValidRecipient('12345'));
        $this->assertTrue($whatsapp->isValidRecipient('0712345678'));
        $this->assertTrue($whatsapp->isValidRecipient('+254712345678'));
    }
}
