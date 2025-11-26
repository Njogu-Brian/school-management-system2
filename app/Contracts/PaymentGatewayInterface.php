<?php

namespace App\Contracts;

use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment
     *
     * @param PaymentTransaction $transaction
     * @param array $options Additional options (phone number for M-Pesa, etc.)
     * @return array Response from gateway
     */
    public function initiatePayment(PaymentTransaction $transaction, array $options = []): array;

    /**
     * Verify a payment transaction
     *
     * @param string $transactionId Gateway transaction ID
     * @return array Transaction status
     */
    public function verifyPayment(string $transactionId): array;

    /**
     * Process webhook payload
     *
     * @param array $payload Webhook payload
     * @param string $signature Webhook signature
     * @return array Processed data
     */
    public function processWebhook(array $payload, string $signature): array;

    /**
     * Verify webhook signature
     *
     * @param array $payload Webhook payload
     * @param string $signature Webhook signature
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Refund a payment
     *
     * @param PaymentTransaction $transaction
     * @param float|null $amount Partial refund amount (null for full refund)
     * @return array Refund response
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null): array;
}

