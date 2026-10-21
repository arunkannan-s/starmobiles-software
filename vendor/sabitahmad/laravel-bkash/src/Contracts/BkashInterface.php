<?php

namespace SabitAhmad\Bkash\Contracts;

use SabitAhmad\Bkash\Responses\AgreementResponse;
use SabitAhmad\Bkash\Responses\PaymentResponse;
use SabitAhmad\Bkash\Responses\QueryResponse;
use SabitAhmad\Bkash\Responses\RefundResponse;
use SabitAhmad\Bkash\Responses\SearchResponse;

interface BkashInterface
{
    public function createAgreement(string $payerReference, ?string $callbackURL = null): AgreementResponse;
    public function executeAgreement(string $paymentId): AgreementResponse;
    public function queryAgreement(string $agreementId): AgreementResponse;
    public function cancelAgreement(string $agreementId): AgreementResponse;
    
    public function createPayment(string $payerReference, float $amount, string $invoiceNumber, ?string $callbackURL = null, ?string $agreementId = null, ?string $merchantAssociationInfo = null): PaymentResponse;
    public function executePayment(string $paymentId): PaymentResponse;
    public function queryPayment(string $paymentId): QueryResponse;
    public function refundPayment(string $paymentId, float $amount, string $reason): RefundResponse;
    public function searchTransaction(string $trxId): SearchResponse;
    public function refundStatus(string $paymentId, string $refundId): RefundResponse;
    
    public function getConfig(): array;
    public function isSandbox(): bool;
    public function isLoggingEnabled(): bool;
}
