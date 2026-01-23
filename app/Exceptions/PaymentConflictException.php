<?php

namespace App\Exceptions;

use App\Models\Payment;
use Exception;

class PaymentConflictException extends Exception
{
    public $conflictingPayments;
    public $studentId;
    public $transactionCode;
    public $transactionId;

    public function __construct(
        array $conflictingPayments,
        int $studentId,
        string $transactionCode,
        int $transactionId,
        string $message = 'Payment conflict detected'
    ) {
        parent::__construct($message);
        $this->conflictingPayments = $conflictingPayments;
        $this->studentId = $studentId;
        $this->transactionCode = $transactionCode;
        $this->transactionId = $transactionId;
    }
}
