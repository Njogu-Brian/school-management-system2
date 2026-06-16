<?php

namespace Tests\Unit\Services\Finance;

use App\Models\ExpenseStatementLine;
use App\Services\Finance\MpesaTransactionClassifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MpesaTransactionClassifierTest extends TestCase
{
    private MpesaTransactionClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new MpesaTransactionClassifier();
    }

    #[Test]
    public function it_classifies_send_money_transactions(): void
    {
        $result = $this->classifier->classify(
            'Customer Transfer to - 07******489 ruth kinyanjui',
            300,
            0
        );

        $this->assertSame(ExpenseStatementLine::TYPE_SEND_MONEY, $result['transaction_type']);
        $this->assertSame('Ruth Kinyanjui', $result['recipient_name']);
        $this->assertSame('07******489', $result['recipient_phone']);
        $this->assertFalse($result['is_transaction_fee']);
    }

    #[Test]
    public function it_classifies_pochi_transactions(): void
    {
        $result = $this->classifier->classify(
            'Customer Payment to Small Business to - 2547******435 peter kinuthia',
            100,
            0
        );

        $this->assertSame(ExpenseStatementLine::TYPE_POCHI, $result['transaction_type']);
        $this->assertSame('Peter Kinuthia', $result['recipient_name']);
    }

    #[Test]
    public function it_classifies_buy_goods_transactions(): void
    {
        $result = $this->classifier->classify(
            'Merchant Payment to 6331188 - TIMOTHY MWENDA NYAGA',
            300,
            0
        );

        $this->assertSame(ExpenseStatementLine::TYPE_BUY_GOODS, $result['transaction_type']);
        $this->assertSame('Timothy Mwenda Nyaga', $result['recipient_name']);
        $this->assertSame('6331188', $result['merchant_reference']);
    }

    #[Test]
    public function it_classifies_paybill_transactions_with_account(): void
    {
        $result = $this->classifier->classify(
            'Pay Bill to 901501 - Shamas Motor Parts Ltd Acc. SMP',
            110000,
            0
        );

        $this->assertSame(ExpenseStatementLine::TYPE_PAYBILL, $result['transaction_type']);
        $this->assertSame('901501', $result['paybill_number']);
        $this->assertSame('SMP', $result['account_reference']);
        $this->assertSame('Shamas Motor Parts Ltd', $result['recipient_name']);
    }

    #[Test]
    public function it_classifies_transaction_fees(): void
    {
        $result = $this->classifier->classify('Customer Transfer of Funds Charge', 7, 0);

        $this->assertSame(ExpenseStatementLine::TYPE_FEE, $result['transaction_type']);
        $this->assertTrue($result['is_transaction_fee']);
    }

    #[Test]
    public function it_strips_conversation_id_from_paybill_online_account_reference(): void
    {
        $result = $this->classifier->classify(
            "Pay Bill Online to 859528 - MALI Acc. 0708225397_34165387_289283 #6b15b857-641e-43c3-b209- b46071ef7258",
            4500,
            0
        );

        $this->assertSame(ExpenseStatementLine::TYPE_PAYBILL, $result['transaction_type']);
        $this->assertSame('859528', $result['paybill_number']);
        $this->assertSame('0708225397_34165387_289283', $result['account_reference']);
        $this->assertSame('Mali', $result['recipient_name']);
    }
}
