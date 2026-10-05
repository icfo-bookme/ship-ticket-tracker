<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'departure_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'ship_id' => ['nullable', 'integer', 'exists:ships,id'],
            'ticket_categories' => ['nullable', 'array:departure,return'],
            'ticket_categories.departure' => ['sometimes', 'array'],
            'ticket_categories.return' => ['sometimes', 'array'],
            'ticket_categories.*.*.package_id' => [
                'required',
                'integer',
                Rule::exists('ship_packages', 'id')->where('ship_id', $this->input('ship_id')),
            ],
            'ticket_categories.*.*.name' => ['required', 'string', 'max:250'],
            'ticket_categories.*.*.quantity' => ['required', 'integer', 'min:0'],
            'details' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_date.required' => 'Please enter the departure date.',
            'departure_date.date' => 'Please enter a valid departure date.',
            'return_date.date' => 'Please enter a valid return date.',
            'return_date.after_or_equal' => 'The return date cannot be earlier than the departure date.',
            'ship_id.exists' => 'Please select a valid ship.',
            'ticket_categories.*.*.package_id.exists' => 'Each selected category must belong to the selected ship.',
            'ticket_categories.*.*.quantity.min' => 'Category quantities cannot be negative.',
            'details.required' => 'Please enter the customer or ticket details.',
            'details.string' => 'The details must be valid text.',
            'note.string' => 'The note must be valid text.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('return_date')) {
                return;
            }

            $returnCategories = $this->input('ticket_categories.return', []);
            if (! is_array($returnCategories)) {
                return;
            }

            foreach ($returnCategories as $category) {
                if ((int) ($category['quantity'] ?? 0) > 0) {
                    $validator->errors()->add('ticket_categories.return', 'A return date is required for return ticket categories.');

                    return;
                }
            }
        });
    }
}
