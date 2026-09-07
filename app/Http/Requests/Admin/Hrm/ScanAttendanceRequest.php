<?php

namespace App\Http\Requests\Admin\Hrm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ScanAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'force_checkout' => ['nullable', 'boolean'],
            'force_checkout_gps' => ['nullable', 'boolean'],
            'force_checkin_gps' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->latitude == 0 && $this->longitude == 0) {
                $validator->errors()->add('latitude', 'Invalid GPS coordinates. Please enable location services.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Store ID is required. Please scan the QR code again.',
            'store_id.exists' => 'Invalid store. Please scan a valid QR code.',
            'latitude.required' => 'Location access is required for attendance.',
            'longitude.required' => 'Location access is required for attendance.',
        ];
    }
}
