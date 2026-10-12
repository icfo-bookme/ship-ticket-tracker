<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class BulkMarkShippedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'sale_ids' => ['required', 'array', 'min:1', 'max:500'],
            'sale_ids.*' => ['required', 'integer', 'distinct', 'exists:ship_ticket_sales,id'],
        ];
    }
}
