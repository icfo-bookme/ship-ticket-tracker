<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class SaveDuePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Bkash,Nagad,Bank Transfer',
            'remark' => 'nullable|string|max:500',
        ];
    }
}
