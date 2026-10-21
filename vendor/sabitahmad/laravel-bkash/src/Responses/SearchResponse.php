<?php

namespace SabitAhmad\Bkash\Responses;

class SearchResponse extends BaseResponse
{
    public function getTransactions(): array
    {
        return $this->data['transaction'] ?? [];
    }

    public function getTransactionCount(): int
    {
        return count($this->getTransactions());
    }

    public function getFirstTransaction(): ?array
    {
        $transactions = $this->getTransactions();

        return ! empty($transactions) ? $transactions[0] : null;
    }

    public function getTransactionByTrxId(string $trxId): ?array
    {
        foreach ($this->getTransactions() as $transaction) {
            if (($transaction['trxID'] ?? '') === $trxId) {
                return $transaction;
            }
        }

        return null;
    }

    public function getTransactionByPaymentId(string $paymentId): ?array
    {
        foreach ($this->getTransactions() as $transaction) {
            if (($transaction['paymentID'] ?? '') === $paymentId) {
                return $transaction;
            }
        }

        return null;
    }

    public function getStatusCode(): ?string
    {
        return $this->data['statusCode'] ?? null;
    }

    public function getStatusMessage(): ?string
    {
        return $this->data['statusMessage'] ?? null;
    }
}
