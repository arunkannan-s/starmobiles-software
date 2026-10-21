<?php

namespace SabitAhmad\Bkash\Helpers;

use SabitAhmad\Bkash\Contracts\BkashInterface;
use SabitAhmad\Bkash\Exceptions\BkashException;
use SabitAhmad\Bkash\Models\BkashPayment;
use SabitAhmad\Bkash\Responses\PaymentResponse;
use Illuminate\Support\Str;

class BkashHelper
{
    protected BkashInterface $bkash;

    public function __construct(BkashInterface $bkash)
    {
        $this->bkash = $bkash;
    }

    /**
     * Create a complete payment flow (create + execute)
     *
     * @throws BkashException
     */
    public function processPayment(
        string $payerReference,
        float $amount,
        string $invoiceNumber,
        ?string $callbackURL = null,
        ?string $agreementId = null
    ): PaymentResponse {

        // Validate amount
        $this->validateAmount($amount);

        // Create payment
        $createResponse = $this->bkash->createPayment(
            $payerReference,
            $amount,
            $invoiceNumber,
            $callbackURL,
            $agreementId
        );

        if (! $createResponse->isSuccess()) {
            throw new BkashException(
                'Payment creation failed: '.$createResponse->getErrorMessage(),
                $createResponse->getErrorCode(),
                $createResponse->getErrorMessage()
            );
        }

        return $createResponse;
    }

    /**
     * Handle payment callback and execute payment
     *
     * @throws BkashException
     */
    public function handleCallback(array $callbackData): PaymentResponse
    {
        $paymentId = $callbackData['paymentID'] ?? null;
        $status = $callbackData['status'] ?? null;

        if (! $paymentId) {
            throw BkashException::invalidCallbackData();
        }

        if ($status !== 'success') {
            throw new BkashException('Payment was not successful: '.($status ?? 'unknown'));
        }

        return $this->bkash->executePayment($paymentId);
    }

    /**
     * Validate transaction amount
     *
     * @throws BkashException
     */
    protected function validateAmount(float $amount): void
    {
        $config = $this->bkash->getConfig();
        $validation = $config['validation'] ?? [];

        if (isset($validation['min_amount']) && $amount < $validation['min_amount']) {
            throw new BkashException("Amount must be at least {$validation['min_amount']}");
        }

        if (isset($validation['max_amount']) && $amount > $validation['max_amount']) {
            throw new BkashException("Amount cannot exceed {$validation['max_amount']}");
        }
    }

    /**
     * Get transaction history for a specific payer reference
     */
    public function getTransactionHistory(string $payerReference, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = BkashPayment::where('payer_reference', $payerReference)
            ->recent();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get successful transactions only
     */
    public function getSuccessfulTransactions(?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = BkashPayment::successful()->recent();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Format amount for bKash API (ensures 2 decimal places)
     */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Generate unique payer reference
     */
    public static function generatePayerReference(string $prefix = 'PAY'): string
    {
        return $prefix.'_'.time().'_'.Str::random(8);
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber(string $prefix = 'INV'): string
    {
        return $prefix.'_'.date('Ymd').'_'.Str::random(10);
    }

    /**
     * Check if transaction is refundable
     */
    public function isRefundable(string $paymentId): bool
    {
        try {
            $queryResponse = $this->bkash->queryPayment($paymentId);

            return $queryResponse->isCompleted();
        } catch (BkashException $e) {
            return false;
        }
    }

    /**
     * Get payment status with detailed information
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $queryResponse = $this->bkash->queryPayment($paymentId);

            return [
                'success' => true,
                'status' => $queryResponse->getTransactionStatus(),
                'payment_id' => $queryResponse->getPaymentId(),
                'trx_id' => $queryResponse->getTrxId(),
                'amount' => $queryResponse->getAmount(),
                'completed' => $queryResponse->isCompleted(),
                'cancelled' => $queryResponse->isCancelled(),
                'failed' => $queryResponse->isFailed(),
                'raw_data' => $queryResponse->getRawData(),
            ];
        } catch (BkashException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ];
        }
    }
}
