<?php

namespace App\Http\Requests\Finance;

use App\Models\ShipTicketSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveDuePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paid_amount' => 'required|numeric|min:0.01',
            'other_fee' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => ! $this->hasFile('payment_proof')
                    && in_array($this->input('payment_method'), ['Bkash', 'Nagad', 'Bank Transfer'], true)),
            ],
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'payment_method' => 'required|string|in:Cash,Bkash,Nagad,Bank Transfer',
            'remark' => 'nullable|string|max:500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $discount = (float) $this->input('discount_amount', 0);

            if ($discount <= 0) {
                return;
            }

            $sale = ShipTicketSale::find($this->route('id'));
            $availableDue = (float) ($sale?->due_amount ?? 0) + (float) $this->input('other_fee', 0);

            if ($discount > $availableDue) {
                $validator->errors()->add('discount_amount', 'The discount amount cannot exceed the total due amount.');
            }
        });
    }
}
