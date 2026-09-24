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
            'whatsapp' => 'nullable|string|min:11|max:20',
            'nid' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:100',
            'sales_source' => 'nullable|string|max:255',
            'ship_id' => 'required|exists:ships,id',
            'address' => 'nullable|string',
            'journey_date' => 'nullable|date|after_or_equal:today',
            'date_of_birth' => 'nullable|date',
            'return_date' => 'nullable|date|after_or_equal:today',
            'ticket_fee' => 'required|numeric',
            'received_amount' => 'required|numeric',
            'number_of_ticket' => 'required|numeric',
            'ticket_category' => 'nullable|string|max:255',
            'due_amount' => 'nullable|numeric',
            'bftn_status' => 'nullable',
            'company_id' => 'nullable',
            'issued_date' => 'required|date',
            'sold_by' => 'required|string|max:100',
            'remark1' => 'nullable|string',
            'remark2' => 'nullable|string',
            'other_fee' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_payable' => 'nullable|numeric|min:0',
            'payment_methods' => 'nullable|array',
            'payment_methods.*.transaction_id' => 'nullable|string|max:255',
            'payment_methods.*.proof_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'co_passengers' => 'nullable|array',
            'co_passengers.*.name' => 'required|string|max:255',
            'co_passengers.*.nid' => 'nullable|string',
            'co_passengers.*.co_passernger_number' => 'nullable|string',
            'co_passengers.*.date_of_birth' => 'nullable|date',
        ];
    }
}
