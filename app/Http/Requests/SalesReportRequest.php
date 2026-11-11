<?php

namespace App\Http\Requests;

use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesReportRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(SaleStatus::class)],
            'payment_date_from' => ['nullable', 'date_format:Y-m-d'],
            'payment_date_to' => [
                'nullable',
                'date_format:Y-m-d',
                ...($this->filled('payment_date_from') ? ['after_or_equal:payment_date_from'] : []),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Select a valid sale status.',
            'payment_date_from.date_format' => 'Enter a valid Payment From date.',
            'payment_date_to.date_format' => 'Enter a valid Payment To date.',
            'payment_date_to.after_or_equal' => 'Payment To must be on or after Payment From.',
        ];
    }
}
