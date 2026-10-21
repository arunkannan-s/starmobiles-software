<?php

namespace SabitAhmad\Bkash\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SabitAhmad\Bkash\Responses\AgreementResponse;

class AgreementCreated
{
    use Dispatchable, SerializesModels;

    public AgreementResponse $agreementResponse;

    public array $additionalData;

    public function __construct(AgreementResponse $agreementResponse, array $additionalData = [])
    {
        $this->agreementResponse = $agreementResponse;
        $this->additionalData = $additionalData;
    }

    public function getAgreementId(): ?string
    {
        return $this->agreementResponse->getAgreementId();
    }

    public function getPayerReference(): ?string
    {
        return $this->agreementResponse->getPayerReference();
    }

    public function getCustomerMsisdn(): ?string
    {
        return $this->agreementResponse->getCustomerMsisdn();
    }
}
