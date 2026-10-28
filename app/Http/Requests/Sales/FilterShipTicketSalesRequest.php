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
            'journey_date' => ['nullable', 'date_format:Y-m-d'],
            'journey_date_from' => ['nullable', 'date_format:Y-m-d'],
            'journey_date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('journey_date_from'), ['after_or_equal:journey_date_from'])],
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
            'journey_date_from.date_format' => 'Departure From date must use YYYY-MM-DD format.',
            'journey_date_to.date_format' => 'Departure To date must use YYYY-MM-DD format.',
            'journey_date_to.after_or_equal' => 'Departure To date cannot be before the From date.',
            'ship_id.required_with' => 'Select a ship before selecting a package.',
            'ship_id.exists' => 'The selected ship is unavailable.',
            'package_id.exists' => 'The selected package does not belong to the selected ship.',
            'package_id.integer' => 'Select a valid package.',
        ];
    }
}
