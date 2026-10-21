<?php

namespace SabitAhmad\Bkash\Responses;

use Illuminate\Support\Carbon;

class PaymentResponse extends BaseResponse
{
    public function getPaymentId(): ?string
    {
        return $this->data['paymentID'] ?? null;
    }

    public function getPaymentExecuteTime(): ?Carbon
    {
        return isset($this->data['paymentExecuteTime'])
            ? Carbon::parse($this->data['paymentExecuteTime'])
            : null;
    }

    // Common methods
    public function getAmount(): ?float
    {
        return isset($this->data['amount'])
            ? (float) $this->data['amount']
            : null;
    }

    public function getPaymentUrl(): ?string
    {
        return $this->data['bkashURL'] ?? null;
    }

    public function getTrxId(): ?string
    {
        return $this->data['trxID'] ?? null; // Only in execute response
    }

    public function getCustomerMsisdn(): ?string
    {
        return $this->data['customerMsisdn'] ?? null;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->data['transactionStatus'] ?? null;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->data['merchantInvoiceNumber'] ?? null;
    }

    public function getStatusCode(): ?string
    {
        return $this->data['statusCode'] ?? null;
    }

    public function getStatusMessage(): ?string
    {
        return $this->data['statusMessage'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? 'BDT';
    }

    public function getIntent(): ?string
    {
        return $this->data['intent'] ?? null;
    }

    public function getMode(): ?string
    {
        return $this->data['mode'] ?? null;
    }

    public function getPayerReference(): ?string
    {
        return $this->data['payerReference'] ?? null;
    }

    public function getCreateTime(): ?Carbon
    {
        return isset($this->data['createTime'])
            ? Carbon::parse($this->data['createTime'])
            : null;
    }

    public function getUpdateTime(): ?Carbon
    {
        return isset($this->data['updateTime'])
            ? Carbon::parse($this->data['updateTime'])
            : null;
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
