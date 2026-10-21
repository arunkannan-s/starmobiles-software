<?php

namespace Modules\Stock\Services;

use Modules\Stock\Repositories\TransferPaymentRepository;
use Modules\Stock\Models\TransferPayment;
use Modules\Stock\Models\Transfer;
use Modules\Configuration\Models\Outlet;
use Modules\Accounting\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class TransferPaymentService
{
    protected $transferPaymentRepository;

    public function __construct(TransferPaymentRepository $transferPaymentRepository)
    {
        $this->transferPaymentRepository = $transferPaymentRepository;
    }

    /**
     * Get all transfer payments for the current company
     */
    public function getAllTransferPayments(): Collection
    {
        return $this->transferPaymentRepository->all($this->getCompanyId());
    }

    /**
     * Get DataTable data for transfer payments listing
     */
    public function getDataTableData(int $start, int $length, string $search, int $draw): array
    {
        $result = $this->transferPaymentRepository->getDataTableData(
            $this->getCompanyId(),
            $start,
            $length,
            $search
        );

        $startingNumber = $result['filteredCount'] - $start;

        $transformedData = $result['data']->map(function ($transferPayment, $index) use ($startingNumber) {
            return [
                'id' => $startingNumber - $index,
                'actual_id' => $transferPayment->id,
                'reference_no' => $transferPayment->reference_no,
                'date' => formatDate($transferPayment->date),
                'from_outlet_id' => $transferPayment->fromOutlet->outlet_name ?? '-',
                'to_outlet_id' => $transferPayment->toOutlet->outlet_name ?? '-',
                'amount' => formatAmount($transferPayment->amount),
                'payment_method_id' => $transferPayment->account->name ?? '-',
                'note' => truncateText($transferPayment->note, 30, 10),
                'encrypted_id' => $transferPayment->encrypted_id,
            ];
        });

        return [
            'draw' => $draw,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['filteredCount'],
            'data' => $transformedData,
        ];
    }

    /**
     * Get transfer payment by encrypted ID
     */
    public function getTransferPaymentByEncryptedId(string $encryptedId): ?TransferPayment
    {
        return $this->transferPaymentRepository->findByEncryptedId($encryptedId);
    }

    /**
     * Create a new transfer payment
     */
    public function createTransferPayment(array $data): TransferPayment
    {
        $storeData = $this->prepareTransferPaymentData($data);
        return $this->transferPaymentRepository->create($storeData);
    }

    /**
     * Update an existing transfer payment
     */
    public function updateTransferPayment(TransferPayment $transferPayment, array $data): bool
    {
        $updateData = $this->prepareTransferPaymentData($data, true);
        return $this->transferPaymentRepository->update($transferPayment, $updateData);
    }

    /**
     * Delete a transfer payment
     */
    public function deleteTransferPayment(TransferPayment $transferPayment): bool
    {
        return $this->transferPaymentRepository->delete($transferPayment);
    }

    /**
     * Get data for create/edit form
     */
    public function getFormData(?TransferPayment $transferPayment = null): array
    {
        $companyId = $this->getCompanyId();

        $data = [
            'outlets' => Outlet::where('del_status', 'Live')
                ->where('company_id', $companyId)
                ->select('id', 'outlet_name as name')
                ->orderBy('outlet_name')
                ->get(),
            'payment_methods' => PaymentMethod::where('del_status', 'Live')
                ->where('company_id', $companyId)
                ->where('status', 'Enable')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ];

        if ($transferPayment) {
            $data['transfer_payment'] = $transferPayment;
            $data['reference_no'] = $transferPayment->reference_no;
        } else {
            $data['reference_no'] = $this->transferPaymentRepository->generateReferenceNumber();
        }

        return $data;
    }

    /**
     * Net amount the "To" outlet owes the "From" outlet (or, with no From outlet, its net
     * position with every other outlet). Positive = the To outlet owes; negative = it is owed.
     *
     * Everything one outlet hands to the other increases what the receiver owes:
     *   + stock transferred From -> To (Sent or Received; Draft is not counted)
     *   + money paid From -> To (e.g. Head Branch giving cash to a branch)
     *   - stock transferred To -> From
     *   - money paid To -> From (e.g. the branch repaying Head Branch)
     */
    public function getOutletDue(int $toOutletId, ?int $fromOutletId = null): float
    {
        $companyId = $this->getCompanyId();

        $transfers = Transfer::where('company_id', $companyId)
            ->where('del_status', 'Live')
            ->whereIn('status', [2, 3]); // 2 = Sent, 3 = Received
        $payments = \Modules\Stock\Models\TransferPayment::where('company_id', $companyId)
            ->where('del_status', 'Live');

        $stockIn = (float) (clone $transfers)->where('to_outlet_id', $toOutletId)
            ->when($fromOutletId, fn ($q) => $q->where('from_outlet_id', $fromOutletId))->sum('total_amount');
        $stockOut = (float) (clone $transfers)->where('from_outlet_id', $toOutletId)
            ->when($fromOutletId, fn ($q) => $q->where('to_outlet_id', $fromOutletId))->sum('total_amount');
        $cashIn = (float) (clone $payments)->where('to_outlet_id', $toOutletId)
            ->when($fromOutletId, fn ($q) => $q->where('from_outlet_id', $fromOutletId))->sum('amount');
        $cashOut = (float) (clone $payments)->where('from_outlet_id', $toOutletId)
            ->when($fromOutletId, fn ($q) => $q->where('to_outlet_id', $fromOutletId))->sum('amount');

        return $stockIn - $stockOut + $cashIn - $cashOut;
    }

    /**
     * Prepare transfer payment data for store/update
     */
    protected function prepareTransferPaymentData(array $data, bool $isUpdate = false): array
    {
        $preparedData = [
            'reference_no' => $data['reference_no'],
            'date' => $data['date'],
            'from_outlet_id' => $data['from_outlet_id'],
            'to_outlet_id' => $data['to_outlet_id'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'payment_method_id' => $data['payment_method_id'],
            'del_status' => 'Live',
        ];

        if (!$isUpdate) {
            $preparedData['user_id'] = Auth::id();
            $preparedData['outlet_id'] = session('outlet.outlet_id') ?? 1;
            $preparedData['company_id'] = $this->getCompanyId();
        }

        return $preparedData;
    }

    /**
     * Get current company ID from session
     */
    protected function getCompanyId(): int
    {
        return session('company.company_id');
    }
}
