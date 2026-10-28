<?php

namespace App\Http\Requests\Sales;

use App\Models\Company;
use Illuminate\Validation\Rule;

class SelectShippedSalesRequest extends FilterShipTicketSalesRequest
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
            ...parent::rules(),
            'company_id' => ['nullable', 'integer', Rule::exists((new Company)->getTable(), 'id')],
            'journey_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
