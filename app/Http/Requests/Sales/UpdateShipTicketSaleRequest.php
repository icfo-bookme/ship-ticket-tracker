<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipTicketSaleRequest extends FormRequest
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
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|min:11|max:20',
            'whatsapp' => 'required_without:whatsapp_username|nullable|string|min:11|max:20',
            'whatsapp_username' => 'required_without:whatsapp|nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'nid' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:'.now()->subYears(18)->toDateString(),
            'collect_from_office' => 'required|boolean',
            'address' => 'required_unless:collect_from_office,1|nullable|string',
            'ship_id' => 'required|exists:ships,id',
            'company_id' => 'required|exists:company,id',
            'journey_date' => 'nullable|date',
            'return_date' => 'nullable|date|after_or_equal:journey_date',
            'number_of_ticket' => 'required|integer|min:1',
            'ticket_fee' => 'required|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0|lte:ticket_fee',
            'total_payable' => 'nullable|numeric|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'nullable|numeric',
            'bftn_status' => 'nullable',
            'sales_source' => 'nullable|string|max:255',
            'sold_by' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'status' => 'required',
            'shipment_id' => 'nullable|string|max:255',
            'remark1' => 'nullable|string',
            'remark2' => 'nullable|string',
            'departure_quantity' => 'nullable|array',
            'return_quantity' => 'nullable|array',
            'departure_quantity.*' => 'nullable|integer|min:0',
            'return_quantity.*' => 'nullable|integer|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|string',
            'payments.*.received_amount' => 'required|numeric|min:0',
            'payments.*.paid_date' => 'nullable|date',
            'payments.*.transaction_id' => 'nullable|string|max:255',
            'payments.*.payment_datetime' => 'nullable',
            'payments.*.payment_proof' => 'nullable|string',
            'payments.*.proof_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'payments.*.remark' => 'nullable|string',
            'co_passengers' => 'nullable|array',
            'co_passengers.*.name' => 'nullable|string|max:255',
            'co_passengers.*.nid' => 'nullable|string|max:255',
            'co_passengers.*.co_passernger_number' => 'nullable|string|max:20',
            'co_passengers.*.date_of_birth' => 'nullable|date|before_or_equal:'.now()->subYears(18)->toDateString(),
            'additional_pdf' => 'nullable|array',
            'additional_pdf.*' => 'nullable|string|max:255',
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
