<?php

namespace App\Http\Requests;

use App\Enums\SaleStatus;
use App\Models\ShipTicketSale;
use App\Services\Sales\SaleGroupingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTicketIssueRequest extends FormRequest
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
            'pdf' => 'nullable|array',
            'pdf.*' => 'nullable|string|max:255',
            'additional_pdf' => 'nullable|array',
            'additional_pdf.*' => 'nullable|string|max:255',
            'existing_pdf_action' => 'nullable|in:yes,no',
            'group_tickets' => 'nullable|in:yes,no',
            'group_by_id' => [
                'required_if:group_tickets,yes',
                'nullable',
                'integer',
                Rule::exists('ship_ticket_sales', 'id')->whereNotIn('status', [
                    SaleStatus::Shipped->value,
                    SaleStatus::CollectFromOffice->value,
                ]),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('group_tickets') !== 'yes') {
                return;
            }

            $sale = ShipTicketSale::find($this->route('ship_ticket_sale'));
            $referenceSale = ShipTicketSale::find($this->input('group_by_id'));

            if (! $sale || ! $referenceSale) {
                return;
            }

            $saleGrouping = app(SaleGroupingService::class);

            if (! $saleGrouping->canJoinGroup($sale, (int) $referenceSale->id)) {
                $validator->errors()->add(
                    'group_by_id',
                    $saleGrouping->groupFailureMessage($sale, (int) $referenceSale->id)
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'group_by_id.exists' => 'This sale cannot be grouped because it is for office collection or has already shipped.',
        ];
    }
}
