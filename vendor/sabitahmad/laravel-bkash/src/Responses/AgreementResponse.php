<?php

namespace SabitAhmad\Bkash\Responses;

use Illuminate\Support\Carbon;

class AgreementResponse extends BaseResponse
{
    public function getAgreementId(): ?string
    {
        return $this->data['agreementID'] ?? null;
    }

    public function getPaymentId(): ?string
    {
        return $this->data['paymentID'] ?? null;
    }

    public function getAgreementExecuteTime(): ?Carbon
    {
        return isset($this->data['agreementExecuteTime'])
            ? Carbon::parse($this->data['agreementExecuteTime'])
            : null;
    }

    public function getAgreementUrl(): ?string
    {
        return $this->data['bkashURL'] ?? null;
    }

    public function getPayerReference(): ?string
    {
        return $this->data['payerReference'] ?? null;
    }

    public function getAgreementStatus(): ?string
    {
        return $this->data['agreementStatus'] ?? null;
    }

    public function getCustomerMsisdn(): ?string
    {
        return $this->data['customerMsisdn'] ?? null;
    }

    public function getStatusCode(): ?string
    {
        return $this->data['statusCode'] ?? null;
    }

    public function getStatusMessage(): ?string
    {
        return $this->data['statusMessage'] ?? null;
    }

    public function isAuthorized(): bool
    {
        return $this->getAgreementStatus() === 'Authorized';
    }
}
