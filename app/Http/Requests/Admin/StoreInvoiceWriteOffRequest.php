<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreInvoiceWriteOffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'write_off_date' => ['nullable', 'date'],
            'reason' => ['required', 'in:customer_goodwill,rounding,bad_debt,dispute_settlement,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the Kasar amount.',
            'amount.min' => 'Kasar amount must be greater than zero.',
            'reason.required' => 'Please select a reason for this Kasar.',
        ];
    }
}