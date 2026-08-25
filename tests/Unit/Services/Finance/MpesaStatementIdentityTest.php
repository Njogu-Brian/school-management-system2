<?php

namespace Tests\Unit\Services\Finance;

use App\Services\Finance\MpesaStatementIdentity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MpesaStatementIdentityTest extends TestCase
{
    #[Test]
    public function it_masks_full_numbers_as_local_0700_star_form(): void
    {
        $this->assertSame('0708***397', MpesaStatementIdentity::toLocalMaskedPhone('254708225397'));
        $this->assertSame('0708***397', MpesaStatementIdentity::toLocalMaskedPhone('0708225397'));
        $this->assertSame('0721***848', MpesaStatementIdentity::toLocalMaskedPhone('254721404848'));
    }

    #[Test]
    public function it_converts_statement_masks_from_254_to_local(): void
    {
        $this->assertSame('070****430', MpesaStatementIdentity::toLocalMaskedPhone('25470****430'));
        $this->assertSame('071****366', MpesaStatementIdentity::toLocalMaskedPhone('25471****366'));
        $this->assertSame('07******768', MpesaStatementIdentity::toLocalMaskedPhone('2547******768'));
        $this->assertSame('07******914', MpesaStatementIdentity::toLocalMaskedPhone('07******914'));
        $this->assertSame('079****503', MpesaStatementIdentity::toLocalMaskedPhone('079****503'));
        $this->assertSame('011****816', MpesaStatementIdentity::toLocalMaskedPhone('25411****816'));
    }

    #[Test]
    public function it_ignores_daraja_hashed_msisdns_and_bank_account_tokens(): void
    {
        $this->assertNull(MpesaStatementIdentity::toLocalMaskedPhone(str_repeat('a', 64)));
        $this->assertNull(MpesaStatementIdentity::toLocalMaskedPhone('0120585497419'));
        $this->assertNull(MpesaStatementIdentity::toLocalMaskedPhone('254120585497'));
    }

    #[Test]
    public function it_parses_incoming_paybill_party_from_statement_narration(): void
    {
        $party = MpesaStatementIdentity::parseParty(
            "Pay Bill from 25470****430 -\nBELINDAH **** RATALA Acc.\nliamani pp1"
        );

        $this->assertSame('070****430', $party['phone']);
        $this->assertSame('Belindah **** Ratala', $party['name']);
        $this->assertSame('liamani pp1', $party['account']);
    }

    #[Test]
    public function it_parses_pay_bill_online_from_and_rejects_generic_pay_bill_label(): void
    {
        $party = MpesaStatementIdentity::parseParty(
            'Pay Bill Online from 25474****920 - diana **** omboga Acc. Phoebe njeri kamau'
        );

        $this->assertSame('074****920', $party['phone']);
        $this->assertSame('Diana **** Omboga', $party['name']);
        $this->assertNotSame('Pay Bill', $party['name']);
        $this->assertNotSame('Pay Bill Online', $party['name']);
    }

    #[Test]
    public function it_parses_merchant_payment_online_payee(): void
    {
        $party = MpesaStatementIdentity::parseParty(
            "Merchant Payment Online to\n7678156 - MEVED DAIRY\nFARM LIMITED"
        );

        $this->assertSame('7678156', $party['till']);
        $this->assertSame('Meved Dairy Farm Limited', $party['name']);
    }

    #[Test]
    public function it_prefers_statement_payee_over_daraja_first_name(): void
    {
        $this->assertSame(
            'Brian **** Njogu',
            MpesaStatementIdentity::preferStatementOrDaraja('BRIAN **** NJOGU', 'BRIAN')
        );
        $this->assertSame(
            'Abraham',
            MpesaStatementIdentity::preferStatementOrDaraja(null, 'ABRAHAM')
        );
        $this->assertNull(MpesaStatementIdentity::preferStatementOrDaraja('Pay Bill', '  '));
        $this->assertSame('Abraham', MpesaStatementIdentity::preferStatementOrDaraja('Pay Bill', 'ABRAHAM'));
    }

    #[Test]
    public function it_collapses_wrapped_pdf_newlines_without_joining_rows(): void
    {
        $this->assertSame(
            'Pay Bill from 25470****430 - BELINDAH **** RATALA Acc. liamani pp1',
            MpesaStatementIdentity::normalizeWhitespace("Pay Bill from 25470****430 -\nBELINDAH **** RATALA Acc.\nliamani pp1")
        );
    }

    #[Test]
    public function it_parses_equity_pos_deposit_payee_from_by_narrative(): void
    {
        $party = MpesaStatementIdentity::parseParty(
            'CHICKEN PARK-WAT BY:/623614658382/24-08-2026 17:28'
        );

        $this->assertSame('Chicken Park-Wat', $party['name']);
        $this->assertNull($party['phone']);
    }

    #[Test]
    public function it_does_not_display_hashed_msisdn_as_a_phone(): void
    {
        $hash = str_repeat('ab', 32);
        $row = (object) [
            'transaction_type' => 'c2b',
            'phone_number' => $hash,
            'msisdn' => $hash,
            'trans_code' => 'UHCN52HKGG',
        ];

        $this->assertNull(MpesaStatementIdentity::phoneForTransaction($row));
        $this->assertSame(
            '070****430',
            MpesaStatementIdentity::phoneForTransaction($row, ['UHCN52HKGG' => '070****430'])
        );
    }
}
