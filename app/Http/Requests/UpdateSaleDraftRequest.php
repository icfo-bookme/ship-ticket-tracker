<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'details.required' => 'Please enter the customer or ticket details.',
            'details.string' => 'The details must be valid text.',
            'note.string' => 'The note must be valid text.',
        ];
    }
}
