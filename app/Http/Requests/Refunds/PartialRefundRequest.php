<?php

namespace App\Http\Requests\Refunds;

use Illuminate\Foundation\Http\FormRequest;

class PartialRefundRequest extends FormRequest
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
            'refunded_number_of_tickets' => 'required|integer|min:1',
            'refunded_amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string|max:255',
        ];
    }
}
