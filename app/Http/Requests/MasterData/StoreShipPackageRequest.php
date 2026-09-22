<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipPackageRequest extends FormRequest
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
            'name' => 'required|string|max:250',
            'ship_id' => 'required|integer|exists:ships,id',
            'price' => 'required|numeric|min:0',
            'round_trip_price' => 'required|numeric|min:0',
        ];
    }
}
