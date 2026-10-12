<?php

namespace App\Http\Requests\MasterData;

use App\Models\SalesSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $salesSource = $this->route('sales_source');
        $salesSourceId = $salesSource instanceof SalesSource ? $salesSource->getKey() : $salesSource;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sales_sources', 'name')->ignore($salesSourceId),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A sales source name is required.',
            'name.unique' => 'This sales source already exists.',
            'sort_order.integer' => 'Sort order must be a whole number.',
            'sort_order.min' => 'Sort order cannot be negative.',
        ];
    }
}
