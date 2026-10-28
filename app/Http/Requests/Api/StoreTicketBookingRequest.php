<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $token = config('services.ticket_booking.api_token');

        return is_string($token) && $token !== ''
            && hash_equals($token, $this->bearerToken() ?? '');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{7,15}$/'],
            'ticket_quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'package_name' => ['required', 'string', 'max:150'],
            'trip_type' => ['required', 'in:single,round_trip'],
            'journey_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'return_date' => ['exclude_if:trip_type,single', 'required_if:trip_type,round_trip', 'nullable', 'date_format:Y-m-d', 'after_or_equal:journey_date'],
            'status' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide the customer name.',
            'mobile.required' => 'Please provide a mobile number.',
            'mobile.regex' => 'Provide 7 to 15 digits with an optional leading +.',
            'ticket_quantity.required' => 'Please provide the ticket quantity.',
            'ticket_quantity.integer' => 'Ticket quantity must be a whole number.',
            'ticket_quantity.min' => 'Ticket quantity must be at least one.',
            'package_name.required' => 'Please select a ticket package.',
            'trip_type.in' => 'Trip type must be single or round_trip.',
            'journey_date.date_format' => 'Journey date must use YYYY-MM-DD format.',
            'journey_date.after_or_equal' => 'Journey date cannot be in the past.',
            'return_date.required_if' => 'A return date is required for round trips.',
            'return_date.date_format' => 'Return date must use YYYY-MM-DD format.',
            'return_date.after_or_equal' => 'Return date cannot be before the journey date.',
            'status.prohibited' => 'Booking status is assigned automatically.',
        ];
    }
}
