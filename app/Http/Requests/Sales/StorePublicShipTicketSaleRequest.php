<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicShipTicketSaleRequest extends FormRequest
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
            'ship_id' => 'required|string|max:100',
            'address' => 'nullable|string',
            'journey_date' => 'nullable|date|after_or_equal:today',
            'date_of_birth' => 'nullable|date|before_or_equal:'.now()->subYears(18)->toDateString(),
            'return_date' => 'nullable|date|after_or_equal:journey_date',
            'ticket_fee' => 'required|numeric',
            'received_amount' => 'required|numeric',
            'other_fee' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0|lte:ticket_fee',
            'ticket_categories' => 'nullable|array',
            'ticket_categories.departure' => 'nullable|array',
            'ticket_categories.return' => 'nullable|array',
            'ticket_categories.*.*.package_id' => 'required|integer|exists:ship_packages,id',
            'ticket_categories.*.*.quantity' => 'required|integer|min:0',
            'payment_methods' => 'nullable|array',
            'payment_methods.*.method' => 'required_with:payment_methods|string|max:100',
            'payment_methods.*.amount' => 'required_with:payment_methods|numeric|min:0',
            'number_of_ticket' => 'required|numeric',
            'due_amount' => 'nullable|numeric',
            'bftn_status' => 'nullable',
            'company_id' => 'nullable|string|max:100',
            'issued_date' => 'required|date',
            'sold_by' => 'nullable|string|max:100',
            'remark1' => 'nullable|string|max:255',
            'remark2' => 'nullable|string|max:255',
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
