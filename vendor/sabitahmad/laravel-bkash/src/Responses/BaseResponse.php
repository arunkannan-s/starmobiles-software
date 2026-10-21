<?php

namespace SabitAhmad\Bkash\Responses;

class BaseResponse
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getRawData(): array
    {
        return $this->data;
    }

    public function isSuccess(): bool
    {
        return isset($this->data['statusCode']) && $this->data['statusCode'] === '0000';
    }

    public function getErrorMessage(): ?string
    {
        return $this->data['errorMessage'] ?? $this->data['statusMessage'] ?? null;
    }

    public function getStatusCode(): ?string
    {
        return $this->data['statusCode'] ?? null;
    }

    public function getStatusMessage(): ?string
    {
        return $this->data['statusMessage'] ?? null;
    }

    public function getErrorCode(): ?string
    {
        return $this->data['errorCode'] ?? null;
    }

    public function hasError(): bool
    {
        return ! $this->isSuccess();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->data, $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
