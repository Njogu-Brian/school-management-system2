<?php

namespace App\Exceptions;

use Exception;

class InsufficientSmsCreditsException extends Exception
{
    protected $balance;

    public function __construct(float $balance = 0, string $message = null)
    {
        $this->balance = $balance;
        $message = $message ?? "Insufficient SMS credits. Current balance: {$balance}. Please top up your SMS balance to send messages.";
        parent::__construct($message);
    }

    /**
     * User-friendly message to show in the UI (e.g. flash message).
     */
    public function getPublicMessage(): string
    {
        return 'SMS could not be sent: insufficient SMS credits (balance: ' . ($this->balance ?? 0) . '). Please top up your SMS balance or check Communication → Logs for details.';
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }
}
