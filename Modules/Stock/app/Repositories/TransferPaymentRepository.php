<?php

namespace Modules\Stock\Repositories;

use Modules\Stock\Models\TransferPayment;
use Illuminate\Database\Eloquent\Collection;

class TransferPaymentRepository
{
    protected $model;

    public function __construct(TransferPayment $model)
    {
        $this->model = $model;
    }

    /**
     * Get all transfer payments for a company
     */
    public function all(int $companyId): Collection
    {
        return $this->model
            ->where('del_status', 'Live')
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get transfer payments with pagination and search for DataTable
     */
    public function getDataTableData(int $companyId, int $start = 0, int $length = 10, string $search = ''): array
    {
        $baseQuery = $this->model
            ->where('del_status', 'Live')
            ->where('company_id', $companyId)
            ->with('fromOutlet:id,outlet_name', 'toOutlet:id,outlet_name', 'account:id,name');

        $query = clone $baseQuery;

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('date', 'like', "%{$search}%")
                    ->orWhereHas('fromOutlet', function ($query) use ($search) {
                        $query->where('outlet_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('toOutlet', function ($query) use ($search) {
                        $query->where('outlet_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('account', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $recordsTotal = $baseQuery->count();
        $filteredCount = $query->count();

        $transferPayments = $query
            ->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        return [
            'recordsTotal' => $recordsTotal,
            'filteredCount' => $filteredCount,
            'data' => $transferPayments,
        ];
    }

    /**
     * Find transfer payment by ID
     */
    public function find(int $id): ?TransferPayment
    {
        return $this->model
            ->where('del_status', 'Live')
            ->find($id);
    }

    /**
     * Find transfer payment by encrypted ID
     */
    public function findByEncryptedId(string $encryptedId): ?TransferPayment
    {
        $decryptedId = decrypt($encryptedId);
        return $this->find($decryptedId);
    }

    /**
     * Create a new transfer payment
     */
    public function create(array $data): TransferPayment
    {
        return $this->model->create($data);
    }

    /**
     * Update a transfer payment
     */
    public function update(TransferPayment $transferPayment, array $data): bool
    {
        return $transferPayment->update($data);
    }

    /**
     * Soft delete a transfer payment (mark as deleted)
     */
    public function delete(TransferPayment $transferPayment): bool
    {
        return $transferPayment->update(['del_status' => 'Deleted']);
    }

    /**
     * Generate next reference number
     */
    public function generateReferenceNumber(): string
    {
        $companyId = session('company.company_id');
        $lastRecord = $this->model
            ->where('del_status', 'Live')
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
        $nextId = $lastRecord ? $lastRecord->id + 1 : 1;
        return str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get total payments made BY a given outlet (as the payer / to_outlet) for a company
     */
    public function getTotalPaymentsByOutlet(int $toOutletId, int $companyId, ?int $fromOutletId = null): float
    {
        $query = $this->model
            ->where('del_status', 'Live')
            ->where('company_id', $companyId)
            ->where('to_outlet_id', $toOutletId);

        if ($fromOutletId) {
            $query->where('from_outlet_id', $fromOutletId);
        }

        return (float) $query->sum('amount');
    }
}
