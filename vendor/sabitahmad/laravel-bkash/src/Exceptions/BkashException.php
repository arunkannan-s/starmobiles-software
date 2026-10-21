<?php

namespace SabitAhmad\Bkash\Exceptions;

use Exception;

class BkashException extends Exception
{
    /**
     * The error code returned by the bKash API.
     */
    protected ?string $errorCode;

    /**
     * The error message returned by the bKash API.
     */
    protected ?string $errorMessage;

    /**
     * Create a new BkashException instance.
     */
    public function __construct(
        string $message = 'An error occurred while processing the bKash request.',
        ?string $errorCode = null,
        ?string $errorMessage = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the error code returned by the bKash API.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the error message returned by the bKash API.
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Create a BkashException from an API response.
     *
     * @return static
     */
    public static function fromApiResponse(array $response): self
    {
        $errorCode = $response['errorCode'] ?? null;
        $errorMessage = $response['errorMessage'] ?? null;

        return new static(
            "bKash API Error: {$errorMessage} (Code: {$errorCode})",
            $errorCode,
            $errorMessage
        );
    }

    /**
     * Create a BkashException for token generation failures.
     *
     * @return static
     */
    public static function tokenGenerationFailed(): self
    {
        return new static('Failed to generate bKash token.');
    }

    /**
     * Create a BkashException for token refresh failures.
     *
     * @return static
     */
    public static function tokenRefreshFailed(): self
    {
        return new static('Failed to refresh bKash token.');
    }

    /**
     * Create a BkashException for invalid callback data.
     *
     * @return static
     */
    public static function invalidCallbackData(): self
    {
        return new static('Invalid callback data received from bKash.');
    }

    /**
     * Create a BkashException for network errors.
     *
     * @return static
     */
    public static function networkError(string $message = ''): self
    {
        return new static('Network error occurred while connecting to bKash: '.$message);
    }

    /**
     * Create a BkashException for configuration errors.
     *
     * @return static
     */
    public static function configurationError(string $message = ''): self
    {
        return new static('bKash configuration error: '.$message);
    }

    /**
     * Create a BkashException for validation errors.
     *
     * @return static
     */
    public static function validationError(string $field, string $message = ''): self
    {
        return new static("Validation error for field '{$field}': ".$message);
    }

    /**
     * Create a BkashException for insufficient balance.
     *
     * @return static
     */
    public static function insufficientBalance(): self
    {
        return new static('Insufficient balance in bKash account.', '2001', 'Insufficient Balance');
    }

    /**
     * Create a BkashException for expired transactions.
     *
     * @return static
     */
    public static function transactionExpired(): self
    {
        return new static('Transaction has expired.', '2054', 'Transaction Expired');
    }

    /**
     * Create a BkashException for duplicate transactions.
     *
     * @return static
     */
    public static function duplicateTransaction(): self
    {
        return new static('Duplicate transaction detected.', '2058', 'Duplicate Transaction');
    }

    /**
     * Create a BkashException for cancelled transactions.
     *
     * @return static
     */
    public static function transactionCancelled(): self
    {
        return new static('Transaction was cancelled by user.', '2061', 'Transaction Cancelled');
    }

    /**
     * Check if this is a network-related error
     */
    public function isNetworkError(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'network') ||
               str_contains(strtolower($this->getMessage()), 'connection') ||
               str_contains(strtolower($this->getMessage()), 'timeout');
    }

    /**
     * Check if this is a validation error
     */
    public function isValidationError(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'validation') ||
               str_contains(strtolower($this->getMessage()), 'invalid');
    }

    /**
     * Check if this is a token-related error
     */
    public function isTokenError(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'token') ||
               $this->getCode() === 401;
    }
}
