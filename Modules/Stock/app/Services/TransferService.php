<?php

namespace Modules\Stock\Services;

use Modules\Stock\Repositories\TransferRepository;
use Modules\Stock\Repositories\StockRepository;
use Modules\Stock\Models\Transfer;
use Modules\Stock\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TransferService
{
    protected $transferRepository;
    protected $stockRepository;

    public function __construct(TransferRepository $transferRepository, StockRepository $stockRepository)
    {
        $this->transferRepository = $transferRepository;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Get all transfers for the current company
     */
    public function getAllTransfers(): Collection
    {
        return $this->transferRepository->all($this->getCompanyId());
    }

    /**
     * Get DataTable data for transfers listing
     */
    public function getDataTableData(int $start, int $length, string $search, int $draw): array
    {
        $result = $this->transferRepository->getDataTableData(
            $this->getCompanyId(),
            $start,
            $length,
            $search
        );

        // Calculate the starting number for the current page
        $startingNumber = $result['filteredCount'] - $start;

        // Transform data for DataTable
        $transformedData = $result['data']->map(function ($transfer, $index) use ($startingNumber) {
            // Convert status integer to string
            $statusMap = [1 => 'Draft', 2 => 'Sent', 3 => 'Received'];
            $statusString = $statusMap[$transfer->status] ?? 'Draft';
            
            return [
                'id' => $startingNumber - $index,
                'actual_id' => $transfer->id,
                'reference_no' => $transfer->reference_no,
                'date' => formatDate($transfer->date),
                'from_outlet_name' => $transfer->fromOutlet->outlet_name ?? '',
                'to_outlet_name' => $transfer->toOutlet->outlet_name ?? '',
                'status' => $statusString,
                'total_amount' => formatAmount($transfer->total_amount ?? 0),
                'payment_status' => $transfer->payment_status ?? 'Unpaid',
                'encrypted_id' => $transfer->encrypted_id,
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
     * Get transfer by encrypted ID
     */
    public function getTransferByEncryptedId(string $encryptedId): ?Transfer
    {
        return $this->transferRepository->findByEncryptedId($encryptedId);
    }

    /**
     * Create a new transfer
     */
    public function createTransfer(array $data): Transfer
    {
        $this->validateTransferStockAtFromOutlet($data);
        $this->validateTransferImeiSerialAtFromOutlet($data);
        $preparedData = $this->prepareTransferData($data);
        return $this->transferRepository->create($preparedData);
    }

    /**
     * Update an existing transfer
     */
    public function updateTransfer(Transfer $transfer, array $data): bool
    {
        // The transfer being edited already counts as stock leaving the From Outlet, so give
        // its own items back before checking availability (otherwise editing a saved transfer
        // always fails with "... does not exist in From Outlet's stock").
        $ownCredit = $this->getOwnStockCredit($transfer, (int) ($data['from_outlet_id'] ?? 0));
        $this->validateTransferStockAtFromOutlet($data, $ownCredit);
        $this->validateTransferImeiSerialAtFromOutlet($data, $ownCredit);
        $preparedData = $this->prepareTransferData($data);
        return $this->transferRepository->update($transfer, $preparedData);
    }

    /**
     * Delete a transfer
     */
    public function deleteTransfer(Transfer $transfer): bool
    {
        return $this->transferRepository->delete($transfer);
    }

    /**
     * Generate reference number
     */
    public function generateReferenceNumber(): string
    {
        return $this->transferRepository->generateReferenceNumber();
    }

    /**
     * Convert status string to integer
     */
    protected function convertStatusToInteger(string $status): int
    {
        $statusMap = [
            'Draft' => 1,
            'Sent' => 2,
            'Received' => 3,
        ];
        
        return $statusMap[$status] ?? 1; // Default to Draft (1)
    }

    /**
     * Prepare transfer data for storage
     */
    protected function prepareTransferData(array $data): array
    {
        $companyId = $this->getCompanyId();
        $userId = Auth::id();
        $outletId = session('outlet.outlet_id');

        // Convert status string to integer
        $statusInteger = $this->convertStatusToInteger($data['status'] ?? 'Draft');

        // Prepare main transfer data
        $transferData = [
            'date' => $data['date'] ?? now()->toDateString(),
            'reference_no' => $data['reference_no'] ?? $this->generateReferenceNumber(),
            'from_outlet_id' => $data['from_outlet_id'],
            'to_outlet_id' => $data['to_outlet_id'],
            'status' => $statusInteger,
            'note_for_sender' => $data['note_for_sender'] ?? null,
            'note_for_receiver' => $data['note_for_receiver'] ?? null,
            'user_id' => $userId,
            'outlet_id' => $outletId,
            'company_id' => $companyId,
            'del_status' => 'Live',
        ];

        // Prepare transfer details
        $details = [];
        $items = $data['items'] ?? [];
        $quantityAmounts = $data['quantity_amount'] ?? [];
        $purchasePrices = $data['purchase_price'] ?? [];
        $itemTypes = $data['item_types'] ?? [];
        $expiryImeiSerials = $data['expiry_imei_serial'] ?? [];
        $transferDate = $data['date'] ?? now()->toDateString();
        $totalAmount = 0;

        foreach ($items as $index => $itemId) {
            if (!empty($itemId)) {
                // Get item type from request or fetch from database
                $itemType = null;
                if (isset($itemTypes[$index]) && !empty($itemTypes[$index])) {
                    $itemType = $itemTypes[$index];
                } else {
                    // Fetch item type from database if not provided
                    $item = \Modules\Stock\Models\Item::find($itemId);
                    $itemType = $item ? $item->type : null;
                }
                
                $quantityAmount = (int) ($quantityAmounts[$index] ?? 1);
                $expiryImeiSerial = isset($expiryImeiSerials[$index]) && !empty($expiryImeiSerials[$index]) 
                    ? $expiryImeiSerials[$index] 
                    : null;

                // Purchase price: use the value submitted from the form (pre-filled from the
                // item's current price, but editable). Frozen at time of transfer - fall back
                // to the item's current purchase price only if none was submitted at all.
                $purchasePrice = isset($purchasePrices[$index]) && $purchasePrices[$index] !== ''
                    ? max(0, (float) $purchasePrices[$index])
                    : (float) (Item::find($itemId)->purchase_price ?? 0);

                $totalAmount += $purchasePrice * $quantityAmount;
                
                $detailData = [
                    'status' => $statusInteger,
                    'item_id' => $itemId,
                    'item_type' => $itemType,
                    'quantity_amount' => $quantityAmount,
                    'purchase_price' => $purchasePrice,
                    'expiry_imei_serial' => $expiryImeiSerial,
                    'from_outlet_id' => $data['from_outlet_id'],
                    'to_outlet_id' => $data['to_outlet_id'],
                    'user_id' => $userId,
                    // Always the SENDING outlet (not the outlet you happen to be logged into).
                    // The stock view counts "Transfer Out" at this outlet and "Transfer In" at the
                    // To Outlet, so recording the From Outlet here keeps both sides paired.
                    'outlet_id' => $data['from_outlet_id'],
                    'company_id' => $companyId,
                    'del_status' => 'Live',
                ];
                
                $details[] = $detailData;
            }
        }

        $transferData['total_amount'] = $totalAmount;
        $transferData['payment_status'] = $data['payment_status'] ?? 'Unpaid';

        return [
            'transfer' => $transferData,
            'details' => $details,
        ];
    }

    /**
     * Get current company ID from session
     */
    protected function getCompanyId(): int
    {
        return session('company.company_id');
    }

    /**
     * Validate that transfer quantity for each item does not exceed stock at the From Outlet.
     */
    protected function validateTransferStockAtFromOutlet(array $data, array $ownCredit = []): void
    {
        $items = $data['items'] ?? [];
        $quantityAmounts = $data['quantity_amount'] ?? [];
        $itemTypes = $data['item_types'] ?? [];
        $fromOutletId = (int) ($data['from_outlet_id'] ?? 0);

        if ($fromOutletId <= 0) {
            return;
        }

        $itemTotalQty = [];
        foreach ($items as $index => $itemId) {
            if (empty($itemId)) {
                continue;
            }
            $itemType = $itemTypes[$index] ?? null;
            if (!$itemType) {
                $item = Item::find($itemId);
                $itemType = $item ? $item->type : null;
            }
            $qty = (int) ($quantityAmounts[$index] ?? 0);
            $itemTotalQty[$itemId] = ($itemTotalQty[$itemId] ?? 0) + $qty;
        }

        if (empty($itemTotalQty)) {
            return;
        }

        $itemIds = array_keys($itemTotalQty);
        $batch = $this->stockRepository->getStockQuantitiesBatch($itemIds, $fromOutletId);

        foreach ($itemTotalQty as $itemId => $totalQty) {
            if ($totalQty <= 0) {
                continue;
            }
            $stockIn = (float) ($batch['in'][$itemId] ?? 0);
            $stockOut = (float) ($batch['out'][$itemId] ?? 0);
            $availableStock = max(0, $stockIn - $stockOut + (float) ($ownCredit['qty'][$itemId] ?? 0));

            if ($totalQty > $availableStock) {
                $itemName = Item::find($itemId)->name ?? $itemId;
                throw ValidationException::withMessages([
                    'quantity_amount' => [
                        __('Transfer quantity for :item cannot exceed available stock at From Outlet. Available: :stock, requested: :qty.', [
                            'item' => $itemName,
                            'stock' => (int) $availableStock,
                            'qty' => $totalQty,
                        ]),
                    ],
                ]);
            }
        }
    }

    /**
     * Validate that each IMEI/Serial/Medicine in transfer details exists in the From Outlet's stock.
     */
    protected function validateTransferImeiSerialAtFromOutlet(array $data, array $ownCredit = []): void
    {
        $items = $data['items'] ?? [];
        $itemTypes = $data['item_types'] ?? [];
        $expiryImeiSerials = $data['expiry_imei_serial'] ?? [];
        $fromOutletId = (int) ($data['from_outlet_id'] ?? 0);

        if ($fromOutletId <= 0) {
            return;
        }

        $imeiSerialTypes = ['IMEI_Product', 'Serial_Product', 'Medicine_Product'];

        foreach ($items as $index => $itemId) {
            if (empty($itemId)) {
                continue;
            }
            $itemType = $itemTypes[$index] ?? null;
            if (!$itemType) {
                $item = Item::find($itemId);
                $itemType = $item ? $item->type : null;
            }
            if (!in_array($itemType, $imeiSerialTypes, true)) {
                continue;
            }

            $imeiSerialValue = isset($expiryImeiSerials[$index]) ? trim((string) $expiryImeiSerials[$index]) : '';
            if ($imeiSerialValue === '') {
                continue;
            }

            $toCheck = $this->parseTrackedValues($itemType, $imeiSerialValue);

            foreach ($toCheck as $oneValue) {
                $inQty = (float) DB::table('view_stock_detail')
                    ->where('item_id', $itemId)
                    ->where('outlet_id', $fromOutletId)
                    ->where('type', 1)
                    ->where('expiry_imei_serial', $oneValue)
                    ->sum('stock_quantity');
                $outQty = (float) DB::table('view_stock_detail')
                    ->where('item_id', $itemId)
                    ->where('outlet_id', $fromOutletId)
                    ->where('type', 2)
                    ->where('expiry_imei_serial', $oneValue)
                    ->sum('stock_quantity');
                $ownBack = !empty($ownCredit['values'][$itemId][$oneValue]) ? 1 : 0;
                $inStock = ($inQty - $outQty + $ownBack) > 0;

                if (!$inStock) {
                    $itemName = Item::find($itemId)->name ?? $itemId;
                    throw ValidationException::withMessages([
                        'expiry_imei_serial' => [
                            __(':value does not exist in From Outlet\'s stock for item :item.', [
                                'value' => $oneValue,
                                'item' => $itemName,
                            ]),
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Split a stored IMEI/Serial/Medicine value into the individual values to check.
     */
    protected function parseTrackedValues(?string $itemType, string $value): array
    {
        $values = [];
        if ($itemType === 'Medicine_Product' && strpos($value, ' - ') !== false) {
            foreach (array_map('trim', explode(',', $value)) as $entry) {
                $parts = explode(' - ', $entry, 2);
                if (count($parts) === 2) {
                    $values[] = trim($parts[1]);
                }
            }
        } else {
            foreach (array_map('trim', explode(',', $value)) as $v) {
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        }
        return $values;
    }

    /**
     * Stock this transfer already holds out of the From Outlet (its own saved lines).
     * Only counts when the From Outlet is unchanged, since the stock view counts a
     * transfer line as stock out at the outlet it was created from.
     */
    protected function getOwnStockCredit(Transfer $transfer, int $newFromOutletId): array
    {
        $credit = ['qty' => [], 'values' => []];

        if ($newFromOutletId <= 0) {
            return $credit;
        }

        $lines = \Modules\Stock\Models\TransferDetail::where('transfer_id', $transfer->id)
            ->where('del_status', 'Live')
            ->get();

        foreach ($lines as $line) {
            $countedAtFromOutlet = (int) $line->from_outlet_id === (int) $line->outlet_id
                && (int) $line->from_outlet_id === $newFromOutletId;
            if (!$countedAtFromOutlet) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $credit['qty'][$itemId] = ($credit['qty'][$itemId] ?? 0) + (int) $line->quantity_amount;

            $stored = trim((string) ($line->expiry_imei_serial ?? ''));
            if ($stored !== '') {
                foreach ($this->parseTrackedValues($line->item_type, $stored) as $value) {
                    $credit['values'][$itemId][$value] = true;
                }
            }
        }

        return $credit;
    }
}
