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
            'customer_mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'nid' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:100',
            'sales_source' => 'nullable|string|max:255',
            'ship_id' => 'required|string|max:100',
            'address' => 'nullable|string',
            'journey_date' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'return_date' => 'nullable|date',
            'ticket_fee' => 'required|numeric',
            'received_amount' => 'required|numeric',
            'number_of_ticket' => 'required|numeric',
            'ticket_category' => 'nullable|string|max:255',
            'due_amount' => 'nullable|numeric',
            'bftn_status' => 'nullable',
            'company_id' => 'nullable|string|max:100',
            'issued_date' => 'required|date',
            'sold_by' => 'nullable|string|max:100',
            'remark1' => 'nullable|string|max:255',
            'remark2' => 'nullable|string|max:255',
        ];
    }
}
