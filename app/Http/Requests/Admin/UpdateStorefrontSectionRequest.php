<?php

namespace App\Http\Requests\Admin;

use App\Models\StorefrontSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorefrontSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [

            // ── Identity ──
            'title' => ['nullable', 'string', 'max:150'],
            'admin_label' => ['nullable', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],

            // ── Type ──
            // On update, type can change — same rules apply
            'type' => ['required', Rule::in(StorefrontSection::TYPES)],

            // ── Type-specific reference ──
            'category_id' => [
                Rule::requiredIf(fn () => $this->input('type') === 'category'),
                'nullable',
                Rule::exists('categories', 'id')->where('company_id', $companyId),
            ],

            'banner_position' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('type') === 'banner'),
                'string',
                Rule::in(['home_top', 'home_middle', 'home_bottom', 'category_page', 'product_page']),
            ],

            // ── Display config ──
            'layout' => [
                'required',
                Rule::in(StorefrontSection::LAYOUTS),
            ],
            'products_limit' => ['required', 'integer', 'min:1', 'max:48'],
            'columns' => ['required', 'integer', 'min:1', 'max:6'],

            // ── Toggles ──
            'show_view_all' => ['nullable', 'boolean'],
            'show_section_title' => ['nullable', 'boolean'],
            'show_on_mobile' => ['nullable', 'boolean'],
            'show_on_desktop' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],

            // ── Optional fields ──
            'view_all_url' => ['nullable', 'url', 'max:500'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'heading_color' => ['nullable', 'string', 'max:20'],
            'custom_html' => ['nullable', 'string'],

            // ── Scheduling ──
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            // ── Sort ──
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Invalid section type selected.',
            'layout.in' => 'Invalid layout selected.',
            'category_id.required_if' => 'Please select a category for this section type.',
            'category_id.exists' => 'Selected category does not exist.',
            'ends_at.after_or_equal' => 'End date must be after or equal to the start date.',
            'products_limit.max' => 'Maximum 48 products per section.',
            'columns.max' => 'Maximum 6 columns allowed.',
        ];
    }

    /**
     * Prepare data before validation.
     * Identical to store — keeps boolean handling consistent.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_view_all' => $this->boolean('show_view_all'),
            'show_section_title' => $this->boolean('show_section_title'),
            'show_on_mobile' => $this->boolean('show_on_mobile'),
            'show_on_desktop' => $this->boolean('show_on_desktop'),
            'is_active' => $this->boolean('is_active'),

            // Clear category_id if type doesn't need it
            'category_id' => in_array($this->input('type'), ['category'])
                ? $this->input('category_id')
                : null,

            // Clear custom_html if type isn't custom_html
            'custom_html' => $this->input('type') === 'custom_html'
                ? $this->input('custom_html')
                : null,
        ]);
    }
}
