<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Stock\Http\Requests\TransferPaymentRequest;
use Modules\Stock\Services\TransferPaymentService;

class TransferPaymentController extends Controller
{
    protected $transferPaymentService;

    public function __construct(TransferPaymentService $transferPaymentService)
    {
        $this->transferPaymentService = $transferPaymentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $length = request()->length ?? 10;
            $start = request()->start ?? 0;
            $search = request()->search['value'] ?? '';
            $draw = request()->draw;

            return response()->json(
                $this->transferPaymentService->getDataTableData($start, $length, $search, $draw)
            );
        }

        return view('stock::transfer-payment.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = $this->transferPaymentService->getFormData();
        return view('stock::transfer-payment.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransferPaymentRequest $request)
    {
        try {
            $this->transferPaymentService->createTransferPayment($request->validated());

            return redirect()->route('transfer-payment.index')
                ->with('success', __('Transfer Payment Created Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('transfer-payment.create')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $transferPayment = $this->transferPaymentService->getTransferPaymentByEncryptedId($id);

            if (!$transferPayment) {
                return redirect()->route('transfer-payment.index')
                    ->with('error', __('Transfer Payment not found'));
            }

            $data = $this->transferPaymentService->getFormData($transferPayment);
            return view('stock::transfer-payment.create', $data);
        } catch (\Exception $e) {
            return redirect()->route('transfer-payment.index')
                ->with('error', __('Transfer Payment not found'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransferPaymentRequest $request, string $id)
    {
        try {
            $transferPayment = $this->transferPaymentService->getTransferPaymentByEncryptedId($id);

            if (!$transferPayment) {
                return redirect()->route('transfer-payment.index')
                    ->with('error', __('Transfer Payment not found'));
            }

            $this->transferPaymentService->updateTransferPayment($transferPayment, $request->validated());

            return redirect()->route('transfer-payment.index')
                ->with('success', __('Transfer Payment Updated Successfully'));
        } catch (\Exception $e) {
            $decryptedId = decrypt($id);
            return redirect()->route('transfer-payment.edit', encrypt($decryptedId))
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $transferPayment = $this->transferPaymentService->getTransferPaymentByEncryptedId($id);

            if (!$transferPayment) {
                return redirect()->route('transfer-payment.index')
                    ->with('error', __('Transfer Payment not found'));
            }

            $this->transferPaymentService->deleteTransferPayment($transferPayment);

            return redirect()->route('transfer-payment.index')
                ->with('success', __('Transfer Payment Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('transfer-payment.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get the outstanding balance a branch outlet owes to the sending outlet (AJAX).
     */
    public function getBalanceByOutletId(Request $request, int $id)
    {
        try {
            $fromOutletId = $request->get('from_outlet_id');
            $balance = $this->transferPaymentService->getOutletDue($id, $fromOutletId ? (int) $fromOutletId : null);
            $balanceType = $balance >= 0 ? 'Debit' : 'Credit';
            $balanceAmount = abs($balance);

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $balance,
                    'balance_amount' => $balanceAmount,
                    'balance_type' => $balanceType,
                    'formatted_balance' => formatAmount($balanceAmount) . ' (' . $balanceType . ')'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
