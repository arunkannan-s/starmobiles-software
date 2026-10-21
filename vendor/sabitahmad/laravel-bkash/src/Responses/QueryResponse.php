<?php

namespace SabitAhmad\Bkash\Responses;

use Illuminate\Support\Carbon;

class QueryResponse extends BaseResponse
{
    public function getPaymentId(): ?string
    {
        return $this->data['paymentID'] ?? null;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->data['transactionStatus'] ?? null;
    }

    public function getAmount(): ?float
    {
        return isset($this->data['amount']) ? (float) $this->data['amount'] : null;
    }

    public function getTrxId(): ?string
    {
        return $this->data['trxID'] ?? null;
    }

    public function getInitiationTime(): ?Carbon
    {
        return isset($this->data['initiationTime'])
            ? Carbon::parse($this->data['initiationTime'])
            : null;
    }

    public function getCompletedTime(): ?Carbon
    {
        return isset($this->data['completedTime'])
            ? Carbon::parse($this->data['completedTime'])
            : null;
    }

    public function getCustomerMsisdn(): ?string
    {
        return $this->data['customerMsisdn'] ?? null;
    }

    public function getMerchantInvoiceNumber(): ?string
    {
        return $this->data['merchantInvoiceNumber'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? 'BDT';
    }

    public function getIntent(): ?string
    {
        return $this->data['intent'] ?? null;
    }

    public function isCompleted(): bool
    {
        return $this->getTransactionStatus() === 'Completed';
    }

    public function isCancelled(): bool
    {
        return $this->getTransactionStatus() === 'Cancelled';
    }

    public function isFailed(): bool
    {
        return $this->getTransactionStatus() === 'Failed';
    }
}
