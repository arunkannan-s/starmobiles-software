<?php

namespace SabitAhmad\Bkash\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SabitAhmad\Bkash\Responses\PaymentResponse;

class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public PaymentResponse $paymentResponse;

    public array $additionalData;

    public function __construct(PaymentResponse $paymentResponse, array $additionalData = [])
    {
        $this->paymentResponse = $paymentResponse;
        $this->additionalData = $additionalData;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentResponse->getPaymentId();
    }

    public function getTrxId(): ?string
    {
        return $this->paymentResponse->getTrxId();
    }

    public function getAmount(): ?float
    {
        return $this->paymentResponse->getAmount();
    }

    public function getCustomerMsisdn(): ?string
    {
        return $this->paymentResponse->getCustomerMsisdn();
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->paymentResponse->getInvoiceNumber();
    }
}
