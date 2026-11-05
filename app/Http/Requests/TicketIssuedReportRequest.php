<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketIssuedReportRequest extends FormRequest
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
            'ticket_issued_from' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'ticket_issued_to' => [
                'bail',
                'nullable',
                'date_format:Y-m-d',
                ...($this->filled('ticket_issued_from') ? ['after_or_equal:ticket_issued_from'] : []),
            ],
            'ship_ids' => ['nullable', 'array', 'list'],
            'issued_date_order' => ['sometimes', 'string', 'in:asc,desc'],
            'ship_ids.*' => ['bail', 'required', 'integer', 'distinct', 'exists:ships,id'],
            'search' => ['sometimes', 'array'],
            'search.value' => ['nullable', 'string', 'max:255'],
            'draw' => ['sometimes', 'integer', 'min:0'],
            'start' => ['sometimes', 'integer', 'min:0'],
            'length' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'issued_date_order.in' => 'Select Oldest First or Newest First for issued date order.',
            'issued_date_order.string' => 'Select a valid issued date order.',
            'ticket_issued_from.date_format' => 'Enter a valid Issued From date in YYYY-MM-DD format.',
            'ticket_issued_to.date_format' => 'Enter a valid Issued To date in YYYY-MM-DD format.',
            'ticket_issued_to.after_or_equal' => 'Issued To must be on or after Issued From.',
            'ship_ids.array' => 'Select ships as a list.',
            'ship_ids.list' => 'Select ships as a sequential list.',
            'ship_ids.*.required' => 'Each selected ship must have an ID.',
            'ship_ids.*.integer' => 'Select a valid ship.',
            'ship_ids.*.distinct' => 'Select each ship only once.',
            'ship_ids.*.exists' => 'One or more selected ships are unavailable.',
            'search.array' => 'Enter search text using the report search field.',
            'search.value.string' => 'Search text must be a string.',
            'search.value.max' => 'Search text must not exceed 255 characters.',
            'draw.integer' => 'The report draw must be an integer.',
            'draw.min' => 'The report draw must be zero or greater.',
            'start.integer' => 'The pagination offset must be an integer.',
            'start.min' => 'The pagination offset must be zero or greater.',
            'length.integer' => 'The page size must be an integer.',
            'length.min' => 'The page size must be at least 1.',
            'length.max' => 'The page size must not exceed 100.',
        ];
    }
}
