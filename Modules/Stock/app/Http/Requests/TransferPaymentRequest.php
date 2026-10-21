<?php

namespace Modules\Stock\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class TransferPaymentRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference_no' => ['required', 'string', 'max:55'],
            'date' => ['required', 'string', 'max:25'],
            'from_outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'to_outlet_id' => ['required', 'integer', 'exists:outlets,id', 'different:from_outlet_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $refNum = __('Reference_Number') ?: 'Reference Number';
        $date = __('Date');
        $fromOutlet = __('From_Outlet') ?: 'From Outlet';
        $toOutlet = __('To_Outlet') ?: 'To Outlet';
        $amount = __('Amount');
        $note = __('Note');
        $paymentAccount = __('Payment_Account') ?: 'Payment Account';

        return [
            'reference_no.required' => __('The') . ' ' . $refNum . ' ' . __('is required.'),
            'reference_no.max' => __('The') . ' ' . $refNum . ' ' . __('may not be greater than') . ' 55 ' . __('characters.'),
            'date.required' => $date . ' ' . __('is required.'),
            'date.max' => __('The') . ' ' . $date . ' ' . __('may not be greater than') . ' 25 ' . __('characters.'),
            'from_outlet_id.required' => __('The') . ' ' . $fromOutlet . ' ' . __('is required.'),
            'from_outlet_id.exists' => __('The selected') . ' ' . $fromOutlet . ' ' . __('is invalid.'),
            'to_outlet_id.required' => __('The') . ' ' . $toOutlet . ' ' . __('is required.'),
            'to_outlet_id.exists' => __('The selected') . ' ' . $toOutlet . ' ' . __('is invalid.'),
            'to_outlet_id.different' => __('The') . ' ' . $toOutlet . ' ' . __('and') . ' ' . $fromOutlet . ' ' . __('must be different.'),
            'amount.required' => __('The') . ' ' . $amount . ' ' . __('is required.'),
            'amount.min' => __('The') . ' ' . $amount . ' ' . __('must be at least') . ' 0.01.',
            'note.max' => __('The') . ' ' . $note . ' ' . __('may not be greater than') . ' 255 ' . __('characters.'),
            'payment_method_id.required' => __('The') . ' ' . $paymentAccount . ' ' . __('is required.'),
            'payment_method_id.exists' => __('The selected') . ' ' . $paymentAccount . ' ' . __('is invalid.'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reference_no' => __('Reference_Number') ?: 'Reference Number',
            'date' => __('Date'),
            'from_outlet_id' => __('From_Outlet') ?: 'From Outlet',
            'to_outlet_id' => __('To_Outlet') ?: 'To Outlet',
            'amount' => __('Amount'),
            'note' => __('Note'),
            'payment_method_id' => __('Payment_Account') ?: 'Payment Account',
        ];
    }
}
