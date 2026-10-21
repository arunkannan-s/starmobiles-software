<?php

namespace SabitAhmad\Bkash\Responses;

use Illuminate\Support\Carbon;

class RefundResponse extends BaseResponse
{
    public function getRefundId(): ?string
    {
        return $this->data['refundTrxID'] ?? null;
    }

    public function getOriginalPaymentId(): ?string
    {
        return $this->data['originalTrxID'] ?? $this->data['originalPaymentID'] ?? $this->data['paymentID'] ?? null;
    }

    public function getRefundAmount(): ?float
    {
        return isset($this->data['amount']) ? (float) $this->data['amount'] : null;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->data['transactionStatus'] ?? null;
    }

    public function getRefundTime(): ?Carbon
    {
        return isset($this->data['completedTime'])
            ? Carbon::parse($this->data['completedTime'])
            : null;
    }

    public function getCharge(): ?float
    {
        return isset($this->data['charge']) ? (float) $this->data['charge'] : null;
    }

    public function getTrxId(): ?string
    {
        return $this->data['trxID'] ?? null;
    }

    public function getPaymentId(): ?string
    {
        return $this->data['paymentID'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? 'BDT';
    }

    public function getReason(): ?string
    {
        return $this->data['reason'] ?? null;
    }

    public function isCompleted(): bool
    {
        return $this->getTransactionStatus() === 'Completed';
    }
}
