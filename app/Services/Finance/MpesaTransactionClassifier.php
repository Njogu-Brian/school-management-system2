<?php

namespace App\Services\Finance;

use App\Models\ExpenseStatementLine;

class MpesaTransactionClassifier
{
    /**
     * @return array{
     *   transaction_type: string,
     *   is_transaction_fee: bool,
     *   recipient_name: ?string,
     *   recipient_phone: ?string,
     *   paybill_number: ?string,
     *   account_reference: ?string,
     *   merchant_reference: ?string,
     *   group_key: string,
     *   display_name: string
     * }
     */
    public function classify(string $narration, float $withdrawn, float $paidIn): array
    {
        $normalized = $this->normalizeNarration($narration);
        $upper = strtoupper($normalized);
        $party = MpesaStatementIdentity::parseParty($normalized);

        if ($this->isFeeNarration($upper)) {
            return $this->buildResult(
                ExpenseStatementLine::TYPE_FEE,
                true,
                null,
                null,
                null,
                null,
                null,
                'fee:general',
                'M-Pesa Transaction Fees'
            );
        }

        if (str_contains($upper, 'CUSTOMER TRANSFER TO') || str_contains($upper, 'CUSTOMER TRANSFER FULIZA')) {
            $phone = $party['phone'];
            $name = $party['name'];

            return $this->buildResult(
                ExpenseStatementLine::TYPE_SEND_MONEY,
                false,
                $name,
                $phone,
                null,
                null,
                null,
                $this->groupKey('send_money', $phone, $name),
                $name ?: ($phone ?: 'Send Money')
            );
        }

        if (str_contains($upper, 'CUSTOMER PAYMENT TO SMALL BUSINESS') || str_contains($upper, 'CUSTOMER SEND MONEY TO MICRO SME')) {
            $phone = $party['phone'];
            $name = $party['name'];

            return $this->buildResult(
                ExpenseStatementLine::TYPE_POCHI,
                false,
                $name,
                $phone,
                null,
                null,
                null,
                $this->groupKey('pochi', $phone, $name),
                $name ?: ($phone ?: 'Pochi Payment')
            );
        }

        if (str_contains($upper, 'MERCHANT PAYMENT') && ! str_contains($upper, 'CHARGE')) {
            $merchant = $this->extractMerchantPayment($normalized);
            $name = $merchant['name'] ?: $party['name'];
            $till = $merchant['till'] ?: $party['till'];

            return $this->buildResult(
                ExpenseStatementLine::TYPE_BUY_GOODS,
                false,
                $name,
                null,
                null,
                null,
                $till,
                $this->groupKey('buy_goods', $till, $name),
                $name ?: ($till ?: 'Buy Goods')
            );
        }

        if (str_contains($upper, 'PAY BILL') && ! str_contains($upper, 'CHARGE')) {
            $paybill = $this->extractPaybill($normalized);

            return $this->buildResult(
                ExpenseStatementLine::TYPE_PAYBILL,
                false,
                $paybill['recipient'],
                null,
                $paybill['number'],
                $paybill['account'],
                null,
                $this->groupKey('paybill', $paybill['number'], $paybill['account'] ?: $paybill['recipient']),
                $paybill['recipient'] ?: ('Paybill ' . ($paybill['number'] ?: ''))
            );
        }

        if (str_contains($upper, 'BUNDLE PURCHASE')) {
            $bundle = $this->extractBundlePurchase($normalized);

            return $this->buildResult(
                ExpenseStatementLine::TYPE_PAYBILL,
                false,
                $bundle['recipient'],
                $bundle['phone'],
                $bundle['number'],
                $bundle['account'],
                null,
                $this->groupKey('paybill', $bundle['number'], $bundle['recipient']),
                $bundle['recipient'] ?: 'Bundle Purchase'
            );
        }

        if (str_contains($upper, 'AIRTIME PURCHASE')) {
            return $this->buildResult(
                ExpenseStatementLine::TYPE_OTHER,
                false,
                'Airtime Purchase',
                $party['phone'],
                null,
                null,
                null,
                'other:airtime',
                'Airtime Purchase'
            );
        }

        if ($paidIn > 0 && $withdrawn <= 0) {
            if (str_contains($upper, 'TRANSFER FROM BANK') || str_contains($upper, 'RECEIVED')) {
                return $this->buildResult(
                    ExpenseStatementLine::TYPE_TRANSFER_IN,
                    false,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'transfer_in:general',
                    'Incoming Transfers'
                );
            }
        }

        return $this->buildResult(
            ExpenseStatementLine::TYPE_OTHER,
            false,
            $this->otherRecipientName($normalized, $party['name']),
            $party['phone'],
            $party['paybill'],
            $party['account'],
            $party['till'],
            'other:' . substr(sha1($normalized), 0, 16),
            $this->truncate($normalized, 60) ?: 'Other'
        );
    }

    protected function isFeeNarration(string $upper): bool
    {
        return str_contains($upper, 'CUSTOMER TRANSFER OF FUNDS CHARGE')
            || str_contains($upper, 'PAY MERCHANT CHARGE')
            || str_contains($upper, 'PAY BILL CHARGE')
            || str_contains($upper, 'WITHDRAWAL CHARGE')
            || str_contains($upper, 'AGENT WITHDRAWAL CHARGE');
    }

    protected function extractPhoneAndName(string $narration, string $prefix): array
    {
        $pattern = '/'.preg_quote($prefix, '/').'\s*(?:to\s*)?[-–]?\s*(.+)$/i';
        if (! preg_match($pattern, $narration, $matches)) {
            return [null, null];
        }

        $tail = trim($matches[1]);
        $phone = null;
        if (preg_match('/(\d+\*+\d+|\d{10,12})/', $tail, $phoneMatch)) {
            $phone = MpesaStatementIdentity::toLocalMaskedPhone($phoneMatch[1]);
        }

        $name = trim(preg_replace('/\d+\*+\d+|\d{10,12}/', '', $tail));
        $name = $this->titleCase($name);

        return [$phone, $name ?: null];
    }

    protected function extractMerchantPayment(string $narration): array
    {
        $till = null;
        $name = null;

        if (preg_match('/Merchant Payment(?:\s+Online)?\s+to\s+(\d+)\s*[-–]?\s*(.+)$/i', $narration, $matches)) {
            $till = $matches[1];
            $name = $this->titleCase(trim($matches[2]));
        } elseif (preg_match('/Merchant Payment(?:\s+Online)?\s+to\s+(.+)$/i', $narration, $matches)) {
            $name = $this->titleCase(trim($matches[1]));
        }

        return ['till' => $till, 'name' => $name];
    }

    protected function extractPaybill(string $narration): array
    {
        $number = null;
        $recipient = null;
        $account = null;

        if (preg_match('/Pay Bill(?:\s+Online)?\s+to\s+(\d+)\s*[-–]\s*(.+)$/i', $narration, $matches)) {
            $number = $matches[1];
            $tail = trim($matches[2]);

            if (preg_match('/^(.+?)\s+Acc\.?\s+(.+)$/i', $tail, $accountMatch)) {
                $recipient = $this->titleCase(trim($accountMatch[1]));
                $account = $this->sanitizeAccountReference($accountMatch[2]);
            } else {
                $recipient = $this->titleCase($tail);
            }
        }

        return [
            'number' => $number,
            'recipient' => $recipient,
            'account' => $account,
        ];
    }

    /**
     * @return array{number: ?string, recipient: ?string, account: ?string, phone: ?string}
     */
    protected function extractBundlePurchase(string $narration): array
    {
        $number = null;
        $recipient = null;
        $phone = null;

        if (preg_match('/Bundle Purchase to\s+(\d+)\s*(.+?)(?:\s+by\s*[-–]?\s*(.+))?$/i', $narration, $matches)) {
            $number = $matches[1];
            $recipient = $this->titleCase(trim(preg_replace('/\d+\*+\d+|\d{10,12}/', '', $matches[2])));
            if (isset($matches[3])) {
                [$phone] = $this->extractPhoneAndName('Customer Transfer to ' . $matches[3], 'Customer Transfer to');
            }
        }

        return [
            'number' => $number,
            'recipient' => $recipient ?: 'Safaricom Bundles',
            'account' => null,
            'phone' => $phone,
        ];
    }

    protected function otherRecipientName(string $normalized, ?string $parsedName): ?string
    {
        if ($parsedName && mb_strlen($parsedName) <= 80 && strtoupper($parsedName) !== strtoupper($normalized)) {
            return $parsedName;
        }

        return null;
    }

    protected function normalizeNarration(string $narration): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', str_replace(["\n", "\r"], ' ', $narration)));

        return $this->stripFundingSource($normalized);
    }

    /**
     * M-Pesa injects the Fuliza overdraft funding phrase between the action verb and
     * the recipient when an overdraft is used. It appears in several shapes:
     *   "Pay Bill Online Fuliza M-Pesa to 247247 ..."
     *   "Merchant Payment Fuliza M-Pesa Online to 794242 - ASTROL ..."
     *   "Customer Transfer Fuliza MPesa to - 2547****397 NAME"
     *   "Customer Send Money to Micro SME Business with Fuliza MPesa to - ..."
     * That phrase breaks recipient / paybill / merchant / account extraction, causing
     * Fuliza-funded payments to lose their payee. Strip it (with its optional
     * "with", "M-Pesa" and "Online" tokens) so "<verb> [...] to <recipient>" parses as
     * "<verb> to <recipient>".
     */
    protected function stripFundingSource(string $narration): string
    {
        return preg_replace(
            '/\s+(?:with\s+)?Fuliza(?:\s+M-?PESA)?(?:\s+Online)?\s+to\b/i',
            ' to',
            $narration
        );
    }

    protected function groupKey(string $type, ?string $primary, ?string $secondary): string
    {
        $parts = array_filter([
            $type,
            $primary ? preg_replace('/\s+/', '', strtolower($primary)) : null,
            $secondary ? preg_replace('/\s+/', '', strtolower($secondary)) : null,
        ]);

        return substr(sha1(implode('|', $parts)), 0, 32);
    }

    protected function buildResult(
        string $type,
        bool $isFee,
        ?string $recipientName,
        ?string $recipientPhone,
        ?string $paybillNumber,
        ?string $accountReference,
        ?string $merchantReference,
        string $groupKey,
        string $displayName
    ): array {
        return [
            'transaction_type' => $type,
            'is_transaction_fee' => $isFee,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'paybill_number' => $paybillNumber,
            'account_reference' => $accountReference,
            'merchant_reference' => $merchantReference,
            'group_key' => $groupKey,
            'display_name' => trim($displayName),
        ];
    }

    protected function titleCase(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    protected function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) . '…' : $value;
    }

    protected function sanitizeAccountReference(?string $account): ?string
    {
        if ($account === null) {
            return null;
        }

        $account = trim(preg_replace('/\s+/', ' ', $account));
        if ($account === '') {
            return null;
        }

        if (($hashPos = strpos($account, '#')) !== false) {
            $account = trim(substr($account, 0, $hashPos));
        }

        $account = strtoupper($account);

        return $account === '' ? null : $this->truncate($account, 255);
    }
}
