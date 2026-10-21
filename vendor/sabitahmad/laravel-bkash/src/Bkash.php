<?php

namespace SabitAhmad\Bkash;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SabitAhmad\Bkash\Contracts\BkashInterface;
use SabitAhmad\Bkash\Events\AgreementCreated;
use SabitAhmad\Bkash\Events\PaymentCompleted;
use SabitAhmad\Bkash\Events\PaymentFailed;
use SabitAhmad\Bkash\Exceptions\BkashException;
use SabitAhmad\Bkash\Models\BkashPayment;
use SabitAhmad\Bkash\Responses\AgreementResponse;
use SabitAhmad\Bkash\Responses\PaymentResponse;
use SabitAhmad\Bkash\Responses\QueryResponse;
use SabitAhmad\Bkash\Responses\RefundResponse;
use SabitAhmad\Bkash\Responses\SearchResponse;

class Bkash implements BkashInterface
{
    protected array $config;

    protected bool $shouldLogTransactions;

    protected string $tokenCacheKey = 'bkash_token';

    public function __construct()
    {
        $this->config = config('bkash', []);
        $this->shouldLogTransactions = $this->config['log_transactions'] ?? true;
    }

    /**
     * Create Agreement for tokenized checkout
     *
     * @throws BkashException
     */
    public function createAgreement(string $payerReference, ?string $callbackURL = null): AgreementResponse
    {
        $callbackURL = $callbackURL ?? $this->config['callback_url'] ?? '';

        $this->validatePayerReference($payerReference);

        $response = $this->makeRequest('POST', $this->getUrl('agreement/create'), [
            'mode' => '0000',
            'callbackURL' => $callbackURL,
            'payerReference' => $payerReference,
            'currency' => 'BDT',
            'intent' => 'sale',
        ]);

        $this->logTransaction('create_agreement', $response);

        return new AgreementResponse($response);
    }

    /**
     * Execute Agreement after user authorization
     *
     * @throws BkashException
     */
    public function executeAgreement(string $paymentId): AgreementResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('agreement/execute'), [
            'paymentID' => $paymentId,
        ]);

        $this->logTransaction('execute_agreement', $response);

        $agreementResponse = new AgreementResponse($response);

        // Dispatch event if agreement was successful
        if ($agreementResponse->isSuccess()) {
            event(new AgreementCreated($agreementResponse));
        }

        return $agreementResponse;
    }

    /**
     * Query Agreement status
     *
     * @throws BkashException
     */
    public function queryAgreement(string $agreementId): AgreementResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('agreement/query'), [
            'agreementID' => $agreementId,
        ]);

        $this->logTransaction('query_agreement', $response);

        return new AgreementResponse($response);
    }

    /**
     * Cancel Agreement
     *
     * @throws BkashException
     */
    public function cancelAgreement(string $agreementId): AgreementResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('agreement/cancel'), [
            'agreementID' => $agreementId,
        ]);

        $this->logTransaction('cancel_agreement', $response);

        return new AgreementResponse($response);
    }

    /**
     * Create Payment (both regular and tokenized)
     *
     * @throws BkashException
     */
    public function createPayment(string $payerReference, float $amount, string $invoiceNumber, ?string $callbackURL = null, ?string $agreementId = null, ?string $merchantAssociationInfo = null): PaymentResponse
    {
        $callbackURL = $callbackURL ?? $this->config['callback_url'] ?? '';

        $this->validateParameters($payerReference, $invoiceNumber, $merchantAssociationInfo);

        $payload = [
            'mode' => $agreementId ? '0001' : '0011', // 0001 for tokenized, 0011 for regular
            'amount' => (string) $amount,
            'callbackURL' => $callbackURL,
            'payerReference' => $payerReference,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => $invoiceNumber,
            'intent' => 'sale',
        ];

        // Add agreementID for tokenized payments
        if ($agreementId) {
            $payload['agreementID'] = $agreementId;
        }

        $response = $this->makeRequest('POST', $this->getUrl('create'), $payload);

        $this->logTransaction('create_payment', $response);

        return new PaymentResponse($response);
    }

    /**
     * @throws BkashException
     */
    public function executePayment(string $paymentId): PaymentResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('execute'), [
            'paymentID' => $paymentId,
        ]);

        $this->logTransaction('execute_payment', $response);

        $paymentResponse = new PaymentResponse($response);

        // Dispatch appropriate event based on payment status
        if ($paymentResponse->isCompleted()) {
            event(new PaymentCompleted($paymentResponse));
        } elseif ($paymentResponse->isFailed()) {
            event(new PaymentFailed($paymentResponse));
        }

        return $paymentResponse;
    }

    /**
     * @throws BkashException
     */
    public function refundPayment(string $paymentId, float $amount, string $reason): RefundResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('refund'), [
            'paymentID' => $paymentId,
            'amount' => (string) $amount,
            'reason' => $reason,
        ]);

        $this->logTransaction('refund_payment', $response);

        return new RefundResponse($response);
    }

    /**
     * Query Payment status
     *
     * @throws BkashException
     */
    public function queryPayment(string $paymentId): QueryResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('query'), [
            'paymentID' => $paymentId,
        ]);

        $this->logTransaction('query_payment', $response);

        return new QueryResponse($response);
    }

    /**
     * Search Transaction by trxID
     *
     * @throws BkashException
     */
    public function searchTransaction(string $trxId): SearchResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('search'), [
            'trxID' => $trxId,
        ]);

        $this->logTransaction('search_transaction', $response);

        return new SearchResponse($response);
    }

    /**
     * Get Refund Status
     *
     * @throws BkashException
     */
    public function refundStatus(string $paymentId, string $refundId): RefundResponse
    {
        $response = $this->makeRequest('POST', $this->getUrl('refund/status'), [
            'paymentID' => $paymentId,
            'trxID' => $refundId,
        ]);

        $this->logTransaction('refund_status', $response);

        return new RefundResponse($response);
    }

    /**
     * Log transaction to database if enabled
     */
    protected function logTransaction(string $type, array $response): void
    {
        if (! $this->shouldLogTransactions) {
            return;
        }

        try {
            BkashPayment::create([
                'type' => $type,
                'payment_id' => $response['paymentID'] ?? $response['agreementID'] ?? null,
                'trx_id' => $response['trxID'] ?? null,
                'payer_reference' => $response['payerReference'] ?? null,
                'invoice_number' => $response['merchantInvoiceNumber'] ?? null,
                'amount' => $response['amount'] ?? null,
                'status' => $response['transactionStatus'] ?? $response['agreementStatus'] ?? ($response['statusCode'] === '0000' ? 'success' : 'failed'),
                'status_code' => $response['statusCode'] ?? null,
                'status_message' => $response['statusMessage'] ?? null,
                'response' => json_encode($response),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log to Laravel log if database logging fails
            Log::warning('Failed to log bKash transaction to database: '.$e->getMessage(), [
                'type' => $type,
                'response' => $response,
            ]);
        }
    }

    /**
     * Make HTTP request to bKash API with proper error handling
     *
     * @throws BkashException
     */
    protected function makeRequest(string $method, string $url, array $data = []): array
    {
        $maxRetries = $this->config['retry_attempts'] ?? 3;
        $retryDelay = $this->config['retry_delay'] ?? 1000;
        $timeout = $this->config['timeout'] ?? 30;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $token = $this->getToken();

                $response = Http::withHeaders([
                    'Authorization' => $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                    ->timeout($timeout)
                    ->{$method}($url, $data);

                if ($response->successful()) {
                    return $response->json();
                }

                // Handle token expiration
                if ($response->status() === 401 && $attempt < $maxRetries - 1) {
                    $this->clearTokenCache();
                    $attempt++;

                    continue;
                }

                // Parse error response
                $responseData = $response->json();
                $errorMessage = $responseData['errorMessage'] ?? $responseData['statusMessage'] ?? 'API request failed';
                $errorCode = $responseData['errorCode'] ?? $responseData['statusCode'] ?? $response->status();

                throw BkashException::fromApiResponse([
                    'errorMessage' => $errorMessage,
                    'errorCode' => $errorCode,
                    'statusCode' => $response->status(),
                ]);

            } catch (ConnectionException $e) {
                if ($attempt < $maxRetries - 1) {
                    $attempt++;
                    usleep($retryDelay * 1000); // Delay in microseconds

                    continue;
                }
                throw new BkashException('Network connection failed: '.$e->getMessage());
            } catch (BkashException $e) {
                throw $e;
            } catch (\Exception $e) {
                if ($attempt < $maxRetries - 1) {
                    $attempt++;

                    continue;
                }
                throw new BkashException('API request failed: '.$e->getMessage());
            }
        }

        throw new BkashException('Maximum retry attempts exceeded');
    }

    /**
     * Get cached token or generate new one
     *
     * @throws BkashException
     */
    protected function getToken(): string
    {
        $ttl = $this->config['token_cache_ttl'] ?? 3300;
        
        return Cache::remember($this->tokenCacheKey, $ttl, function () {
            return $this->generateToken();
        });
    }

    /**
     * Clear token cache
     */
    protected function clearTokenCache(): void
    {
        Cache::forget($this->tokenCacheKey);
    }

    /**
     * Generate new access token
     *
     * @throws BkashException
     */
    protected function generateToken(): string
    {
        try {
            $credentials = $this->config['credentials'] ?? [];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
            ])
                ->timeout(30)
                ->post($this->getUrl('token'), [
                    'app_key' => $credentials['app_key'] ?? '',
                    'app_secret' => $credentials['app_secret'] ?? '',
                ]);

            if (! $response->successful()) {
                $error = $response->json();
                throw new BkashException(
                    'Token generation failed: '.($error['errorMessage'] ?? 'Unknown error'),
                    $error['errorCode'] ?? null,
                    $error['errorMessage'] ?? null
                );
            }

            $token = $response->json('id_token');
            if (! $token) {
                throw BkashException::tokenGenerationFailed();
            }

            return $token;
        } catch (ConnectionException $e) {
            throw new BkashException('Network error during token generation: '.$e->getMessage());
        } catch (BkashException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BkashException('Token generation failed: '.$e->getMessage());
        }
    }



    /**
     * Get API URL for specific endpoint
     *
     * @throws BkashException
     */
    protected function getUrl(string $type): string
    {
        $mode = ($this->config['sandbox'] ?? true) ? 'sandbox' : 'production';
        
        // Backward compatibility with old config structure
        if (isset($this->config['urls'][$mode][$type])) {
            return $this->config['urls'][$mode][$type];
        }

        $baseUrl = rtrim($this->config['base_url'][$mode] ?? '', '/');
        $endpoint = ltrim($this->config['endpoints'][$type] ?? '', '/');

        if (empty($baseUrl) || empty($endpoint)) {
            throw new BkashException("API endpoint '{$type}' not found for {$mode} mode");
        }

        return $baseUrl . '/' . $endpoint;
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if in sandbox mode
     */
    public function isSandbox(): bool
    {
        return $this->config['sandbox'] ?? true;
    }

    /**
     * Check if transaction logging is enabled
     */
    public function isLoggingEnabled(): bool
    {
        return $this->shouldLogTransactions;
    }

    /**
     * Validate request parameters
     *
     * @throws BkashException
     */
    /**
     * Validate payer reference
     *
     * @throws BkashException
     */
    protected function validatePayerReference(string $payerReference): void
    {
        $invalidChars = ['<', '>', '&', '"', "'"];

        if (empty($payerReference)) {
            throw new BkashException('Payer reference cannot be empty');
        }

        if (strlen($payerReference) > 255) {
            throw new BkashException('Payer reference cannot exceed 255 characters');
        }

        foreach ($invalidChars as $char) {
            if (str_contains($payerReference, $char)) {
                throw new BkashException('Payer reference contains invalid characters: '.implode(', ', $invalidChars));
            }
        }
    }

    /**
     * Validate request parameters
     *
     * @throws BkashException
     */
    protected function validateParameters(string $payerReference, string $invoiceNumber, ?string $merchantAssociationInfo = null): void
    {
        $this->validatePayerReference($payerReference);

        $invalidChars = ['<', '>', '&', '"', "'"];

        if (empty($invoiceNumber)) {
            throw new BkashException('Invoice number cannot be empty');
        }

        if (strlen($invoiceNumber) > 255) {
            throw new BkashException('Invoice number cannot exceed 255 characters');
        }

        foreach ($invalidChars as $char) {
            if (str_contains($invoiceNumber, $char)) {
                throw new BkashException('Invoice number contains invalid characters: '.implode(', ', $invalidChars));
            }
        }

        if ($merchantAssociationInfo !== null) {
            if (strlen($merchantAssociationInfo) > 255) {
                throw new BkashException('Merchant association info cannot exceed 255 characters');
            }

            foreach ($invalidChars as $char) {
                if (str_contains($merchantAssociationInfo, $char)) {
                    throw new BkashException('Merchant association info contains invalid characters: '.implode(', ', $invalidChars));
                }
            }
        }
    }
}
