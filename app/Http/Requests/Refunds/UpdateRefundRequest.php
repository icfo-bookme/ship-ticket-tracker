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
            'partner_share_percent' => 'required|numeric|min:0|max:100'.($this->boolean('is_company_error') ? '' : '|lte:customer_charge_percent'),
            'ticket_selections' => 'required|array|min:1',
            'ticket_selections.*.category_id' => 'required|integer|distinct',
            'ticket_selections.*.refunded_quantity' => 'required|integer|min:1',
            'is_company_error' => 'sometimes|boolean',
            'company_loss_type' => 'exclude_unless:is_company_error,true|required|in:partner_share,custom',
            'company_loss_percent' => 'exclude_unless:is_company_error,true|exclude_unless:company_loss_type,custom|required|numeric|min:0|max:100',
            'company_error_reason' => 'exclude_unless:is_company_error,true|required|string|max:2000',
            'remark' => 'nullable|string|max:255',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'company_loss_type.required' => 'Select a company loss type.',
            'company_loss_type.in' => 'Select Partner Share Loss or Custom Loss.',
            'company_loss_percent.required' => 'Enter the company loss percentage.',
            'company_loss_percent.numeric' => 'Company loss percentage must be a number.',
            'company_loss_percent.min' => 'Company loss percentage must be zero or greater.',
            'company_loss_percent.max' => 'Company loss percentage cannot exceed 100%.',
            'company_error_reason.required' => 'Explain the company error.',
            'company_error_reason.max' => 'The company error reason must not exceed 2000 characters.',
            'partner_share_percent.lte' => 'Partner share cannot exceed customer charge unless company error is selected.',
        ];
    }
}
