<?php

namespace App\Http\Requests\Refunds;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundRequest extends FormRequest
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
            'customer_charge_percent' => 'required|numeric|min:0|max:100',
            'partner_share_percent' => 'required|numeric|min:0|max:100|lte:customer_charge_percent',
            'ticket_selections' => 'required|array|min:1',
            'ticket_selections.*.category_id' => 'required|integer',
            'ticket_selections.*.refunded_quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string|max:255',
        ];
    }
}
