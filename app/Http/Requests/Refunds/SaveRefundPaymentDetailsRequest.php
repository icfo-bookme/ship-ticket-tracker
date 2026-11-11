<?php

namespace App\Http\Requests\Refunds;

use Illuminate\Foundation\Http\FormRequest;

class SaveRefundPaymentDetailsRequest extends FormRequest
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
            'refund_payment_details' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'refund_payment_details.required' => 'Payment details are required.',
            'refund_payment_details.string' => 'Payment details must be text.',
            'refund_payment_details.max' => 'Payment details may not exceed 5000 characters.',
        ];
    }
}
