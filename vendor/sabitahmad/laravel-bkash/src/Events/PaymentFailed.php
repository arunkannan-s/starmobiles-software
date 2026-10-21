<?php

namespace SabitAhmad\Bkash\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SabitAhmad\Bkash\Responses\PaymentResponse;

class PaymentFailed
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

    public function getErrorMessage(): ?string
    {
        return $this->paymentResponse->getErrorMessage();
    }

    public function getErrorCode(): ?string
    {
        return $this->paymentResponse->getErrorCode();
    }
}
