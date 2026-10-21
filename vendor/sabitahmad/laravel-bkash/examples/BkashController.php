<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SabitAhmad\Bkash\Exceptions\BkashException;
use SabitAhmad\Bkash\Facades\Bkash;
use SabitAhmad\Bkash\Helpers\BkashHelper;

class BkashController extends Controller
{
    protected BkashHelper $bkashHelper;

    public function __construct()
    {
        $this->bkashHelper = new BkashHelper(app(\SabitAhmad\Bkash\Bkash::class));
    }

    /**
     * Create Agreement (for tokenized checkout)
     */
    public function createAgreement(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $payerReference = BkashHelper::generatePayerReference('USER_'.auth()->id());

            $agreement = Bkash::createAgreement($payerReference);

            if ($agreement->isSuccess()) {
                // Store agreement details in session for later use
                session([
                    'bkash_agreement_id' => $agreement->getAgreementId(),
                    'bkash_payer_reference' => $payerReference,
                ]);

                return redirect()->away($agreement->getAgreementUrl());
            }

            return back()->withErrors(['error' => 'Agreement creation failed: '.$agreement->getErrorMessage()]);

        } catch (BkashException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Agreement Callback
     */
    public function agreementCallback(Request $request): RedirectResponse
    {
        try {
            $paymentId = $request->input('paymentID');
            $status = $request->input('status');

            if ($status !== 'success' || ! $paymentId) {
                return redirect()->route('payment.failed')
                    ->withErrors(['error' => 'Agreement creation was not successful']);
            }

            $agreement = Bkash::executeAgreement($paymentId);

            if ($agreement->isSuccess()) {
                // Store agreement ID for future use
                // You might want to store this in database associated with user
                session(['bkash_stored_agreement_id' => $agreement->getAgreementId()]);

                return redirect()->route('agreement.success')
                    ->with('success', 'bKash account added successfully!');
            }

            return redirect()->route('agreement.failed')
                ->withErrors(['error' => 'Agreement execution failed']);

        } catch (BkashException $e) {
            return redirect()->route('agreement.failed')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create Payment (Regular Checkout)
     */
    public function createPayment(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:999999.99',
            'invoice_number' => 'required|string|max:255',
        ]);

        try {
            $payerReference = BkashHelper::generatePayerReference('PAYMENT_'.auth()->id());
            $amount = (float) $request->input('amount');
            $invoiceNumber = $request->input('invoice_number');

            // Use tokenized payment if user has stored agreement
            $agreementId = session('bkash_stored_agreement_id');

            $payment = Bkash::createPayment(
                $payerReference,
                $amount,
                $invoiceNumber,
                route('bkash.callback'), // callback URL
                $agreementId // Use agreement for tokenized payment
            );

            if ($payment->isSuccess()) {
                // Store payment details for callback verification
                session([
                    'bkash_payment_id' => $payment->getPaymentId(),
                    'bkash_amount' => $amount,
                    'bkash_invoice' => $invoiceNumber,
                ]);

                return redirect()->away($payment->getPaymentUrl());
            }

            return back()->withErrors(['error' => 'Payment creation failed: '.$payment->getErrorMessage()]);

        } catch (BkashException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Payment Callback
     */
    public function paymentCallback(Request $request): RedirectResponse
    {
        try {
            $result = $this->bkashHelper->handleCallback($request->all());

            if ($result->isCompleted()) {
                // Clear session data
                session()->forget(['bkash_payment_id', 'bkash_amount', 'bkash_invoice']);

                return redirect()->route('payment.success')
                    ->with('success', 'Payment completed successfully!')
                    ->with('transaction_id', $result->getTrxId());
            }

            return redirect()->route('payment.failed')
                ->withErrors(['error' => 'Payment execution failed']);

        } catch (BkashException $e) {
            return redirect()->route('payment.failed')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Query Payment Status
     */
    public function queryPayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|string',
        ]);

        try {
            $status = $this->bkashHelper->getPaymentStatus($request->input('payment_id'));

            return response()->json($status);

        } catch (BkashException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Process Refund
     */
    public function refundPayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $refund = Bkash::refundPayment(
                $request->input('payment_id'),
                (float) $request->input('amount'),
                $request->input('reason')
            );

            if ($refund->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'refund_id' => $refund->getRefundId(),
                    'status' => $refund->getTransactionStatus(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $refund->getErrorMessage(),
            ], 400);

        } catch (BkashException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Search Transaction
     */
    public function searchTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'trx_id' => 'required|string',
        ]);

        try {
            $search = Bkash::searchTransaction($request->input('trx_id'));

            if ($search->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'transactions' => $search->getTransactions(),
                    'count' => $search->getTransactionCount(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $search->getErrorMessage(),
            ], 400);

        } catch (BkashException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get Transaction History
     */
    public function transactionHistory(): JsonResponse
    {
        try {
            $payerReference = 'USER_'.auth()->id();
            $transactions = $this->bkashHelper->getTransactionHistory($payerReference, 20);

            return response()->json([
                'success' => true,
                'transactions' => $transactions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve transaction history',
            ], 500);
        }
    }
}
