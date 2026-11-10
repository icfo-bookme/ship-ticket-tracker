<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyLossReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ship_id' => ['nullable', 'integer', 'exists:ships,id'],
            'company_id' => ['nullable', 'integer', 'exists:company,id'],
            'status' => ['nullable', 'in:requested,partner_approved,payment_details_added,completed'],
            'loss_type' => ['nullable', 'in:partner_share,custom'],
            'requested_from' => ['nullable', 'date_format:Y-m-d'],
            'requested_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('requested_from') ? ['after_or_equal:requested_from'] : [])],
            'search' => ['sometimes', 'array'],
            'search.value' => ['nullable', 'string', 'max:255'],
            'draw' => ['sometimes', 'integer', 'min:0'],
            'start' => ['sometimes', 'integer', 'min:0'],
            'length' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'requested_from.date_format' => 'Enter a valid Requested From date.',
            'requested_to.date_format' => 'Enter a valid Requested To date.',
            'requested_to.after_or_equal' => 'Requested To must be on or after Requested From.',
            'status.in' => 'Select a valid refund status.',
            'loss_type.in' => 'Select Partner Share Loss or Custom Loss.',
            'ship_id.exists' => 'Select an available ship.',
            'company_id.exists' => 'Select an available company.',
            'length.max' => 'Select no more than 100 records per page.',
        ];
    }
}
