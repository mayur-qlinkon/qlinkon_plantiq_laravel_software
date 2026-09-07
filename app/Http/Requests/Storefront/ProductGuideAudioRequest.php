<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request for product guide audio.
 *
 * Deliberately accepts only identifiers — never raw text. The text to be
 * synthesised is resolved server-side from the database, which is what stops
 * the public endpoint from being driven as a free speech-synthesis service
 * against the account's Google billing.
 */
class ProductGuideAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public storefront endpoint. Tenant scoping is enforced in the
        // controller by looking the product up within the resolved company.
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],

            // Index into the product_guide JSON array. The upper bound matches
            // the 'max:50' cap already enforced when saving a product.
            'index' => ['required', 'integer', 'min:0', 'max:49'],

            // Free-form because the Google Translate widget can emit codes the
            // app has never seen. Unknown codes are normalised to the default
            // language rather than rejected, so a hard whitelist here would
            // only produce needless failures.
            'lang' => ['nullable', 'string', 'max:15'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product reference is missing.',
            'index.required'      => 'Guide section reference is missing.',
        ];
    }
}