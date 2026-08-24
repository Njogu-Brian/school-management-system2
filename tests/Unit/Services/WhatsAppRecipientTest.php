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
        $this->assertTrue($whatsapp->isValidRecipient('+4917697784839'));
        $this->assertSame('4917697784839', $whatsapp->normalizeRecipient('+4917697784839'));
    }

    public function test_whatsapp_recipients_include_phone_when_it_differs_from_whatsapp_field(): void
    {
        $parent = new \App\Models\ParentInfo([
            'mother_name' => 'Jane',
            'mother_whatsapp' => '+4917697784839',
            'mother_phone' => '+254724852028',
        ]);

        $phones = array_column($parent->schoolNotificationWhatsAppRecipients(), 'phone');

        $this->assertEqualsCanonicalizing(['+4917697784839', '+254724852028'], $phones);
    }

    public function test_flattens_newlines_in_template_body_parameter(): void
    {
        $whatsapp = app(WhatsAppService::class);
        $method = new \ReflectionMethod(WhatsAppService::class, 'truncateForTemplate');
        $method->setAccessible(true);

        $this->assertSame(
            'Hello Parent. Payment of Ksh 1,000 received.',
            $method->invoke($whatsapp, "Hello Parent.\n\nPayment of Ksh 1,000 received.")
        );
    }
}
