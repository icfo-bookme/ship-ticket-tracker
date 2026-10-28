<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterShipTicketSalesRequest extends FormRequest
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
            'ship_id' => ['nullable', 'required_with:package_id', 'integer', 'exists:ships,id'],
            'package_id' => [
                'nullable', 'integer',
                Rule::exists('ship_packages', 'id')->where('ship_id', $this->input('ship_id')),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ship_id.required_with' => 'Select a ship before selecting a package.',
            'ship_id.exists' => 'The selected ship is unavailable.',
            'package_id.exists' => 'The selected package does not belong to the selected ship.',
            'package_id.integer' => 'Select a valid package.',
        ];
    }
}
