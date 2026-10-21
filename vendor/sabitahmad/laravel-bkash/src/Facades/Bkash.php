<?php

namespace SabitAhmad\Bkash\Facades;

use Illuminate\Support\Facades\Facade;
use SabitAhmad\Bkash\Responses\AgreementResponse;
use SabitAhmad\Bkash\Responses\PaymentResponse;
use SabitAhmad\Bkash\Responses\QueryResponse;
use SabitAhmad\Bkash\Responses\RefundResponse;
use SabitAhmad\Bkash\Responses\SearchResponse;

/**
 * @method static AgreementResponse createAgreement(string $payerReference, ?string $callbackURL = null)
 * @method static AgreementResponse executeAgreement(string $paymentId)
 * @method static AgreementResponse queryAgreement(string $agreementId)
 * @method static AgreementResponse cancelAgreement(string $agreementId)
 * @method static PaymentResponse createPayment(string $payerReference, float $amount, string $invoiceNumber, ?string $callbackURL = null, ?string $agreementId = null, ?string $merchantAssociationInfo = null)
 * @method static PaymentResponse executePayment(string $paymentId)
 * @method static QueryResponse queryPayment(string $paymentId)
 * @method static RefundResponse refundPayment(string $paymentId, float $amount, string $reason)
 * @method static RefundResponse refundStatus(string $paymentId, string $refundId)
 * @method static SearchResponse searchTransaction(string $trxId)
 * @method static array getConfig()
 * @method static bool isSandbox()
 * @method static bool isLoggingEnabled()
 *
 * @see \SabitAhmad\Bkash\Bkash
 */
class Bkash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SabitAhmad\Bkash\Bkash::class;
    }
}
