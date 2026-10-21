<?php

namespace SabitAhmad\Bkash\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SabitAhmad\Bkash\Enums\TransactionStatus;

class BkashPayment extends Model
{
    protected $table = 'bkash_payments';

    protected $fillable = [
        'type',
        'payment_id',
        'trx_id',
        'payer_reference',
        'invoice_number',
        'amount',
        'status',
        'status_code',
        'status_message',
        'response'
    ];

    protected $casts = [
        'response' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Query scopes for common filtering
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentId(Builder $query, string $paymentId): Builder
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByTrxId(Builder $query, string $trxId): Builder
    {
        return $query->where('trx_id', $trxId);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', TransactionStatus::SUCCESS)
              ->orWhere('status', TransactionStatus::COMPLETED);
        });
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', TransactionStatus::FAILED)
              ->orWhere('status', 'Failed');
        });
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, [TransactionStatus::SUCCESS, TransactionStatus::COMPLETED]);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [TransactionStatus::FAILED, 'Failed']);
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }

    public function getResponseData(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->response;
        }

        return $this->response[$key] ?? $default;
    }
}
