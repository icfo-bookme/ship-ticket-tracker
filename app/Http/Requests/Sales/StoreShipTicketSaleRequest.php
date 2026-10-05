<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipTicketSaleRequest extends FormRequest
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
            'customer_name' => 'required|string|max:100',
            'customer_mobile' => 'required|string|min:11|max:20',
            'whatsapp' => 'required_without:whatsapp_username|nullable|string|min:11|max:20',
            'whatsapp_username' => 'required_without:whatsapp|nullable|string|max:100',
            'nid' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:100',
            'sales_source' => 'nullable|string|max:255',
            'ship_id' => 'required|exists:ships,id',
            'collect_from_office' => 'required|boolean',
            'address' => 'required_unless:collect_from_office,1|nullable|string',
            'journey_date' => 'nullable|date|after_or_equal:today',
            'date_of_birth' => 'nullable|date|before_or_equal:'.now()->subYears(18)->toDateString(),
            'return_date' => 'nullable|date|after_or_equal:journey_date',
            'ticket_fee' => 'required|numeric',
            'received_amount' => 'nullable|numeric|min:0',
            'number_of_ticket' => 'required|numeric',
            'due_amount' => 'nullable|numeric',
            'bftn_status' => 'nullable',
            'company_id' => 'nullable',
            'issued_date' => 'required|date',
            'sold_by' => 'required|string|max:100',
            'remark1' => 'nullable|string',
            'remark2' => 'nullable|string',
            'other_fee' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric|min:0|lte:ticket_fee',
            'total_payable' => 'nullable|numeric|min:0',
            'payment_methods' => 'nullable|array',
            'payment_methods.*.method' => 'required_with:payment_methods|string|max:100',
            'payment_methods.*.amount' => 'required_with:payment_methods|numeric|min:0',
            'payment_methods.*.transaction_id' => 'nullable|string|max:255',
            'payment_methods.*.proof_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'ticket_categories' => 'nullable|array',
            'ticket_categories.departure' => 'nullable|array',
            'ticket_categories.return' => 'nullable|array',
            'ticket_categories.*.*.package_id' => 'required|integer|exists:ship_packages,id',
            'ticket_categories.*.*.quantity' => 'required|integer|min:0',
            'co_passengers' => 'nullable|array',
            'co_passengers.*.name' => 'required|string|max:255',
            'co_passengers.*.nid' => 'nullable|string',
            'co_passengers.*.co_passernger_number' => 'nullable|string',
            'co_passengers.*.date_of_birth' => 'nullable|date|before_or_equal:'.now()->subYears(18)->toDateString(),
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp.required_without' => 'Please provide either a WhatsApp number or a WhatsApp username.',
            'whatsapp_username.required_without' => 'Please provide either a WhatsApp number or a WhatsApp username.',
        ];
    }
}
