<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\ResolvesStorefrontCompany;
use App\Http\Requests\Storefront\ProductGuideAudioRequest;
use App\Models\Product;
use App\Services\Platform\Tts\ProductGuideAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Serves cached or freshly generated audio for public storefront content.
 */
class TtsController extends Controller
{
    use ResolvesStorefrontCompany;

    public function __construct(
        protected ProductGuideAudioService $guideAudio,
    ) {
    }

    /**
     * Return a playable URL for one product guide section.
     */
    public function productGuide(ProductGuideAudioRequest $request): JsonResponse
    {
        if (! config('tts.enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Audio playback is temporarily unavailable.',
            ], 503);
        }

        $company = $this->resolveStorefrontCompany();

        /*
        | Tenant scoping and visibility are enforced here, not in the request
        | class. A product id from another company, or one hidden from the
        | storefront, must not be readable through this endpoint even though
        | the id itself is a valid integer.
        */
        $product = Product::where('id', $request->integer('product_id'))
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('show_in_storefront', true)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        try {
            $audio = $this->guideAudio->forGuideEntry(
                product:  $product,
                index:    $request->integer('index'),
                language: $request->input('lang'),
            );
        } catch (Exception $e) {
            Log::error('Product guide audio generation failed', [
                'company_id' => $company->id,
                'product_id' => $product->id,
                'index'      => $request->integer('index'),
                'lang'       => $request->input('lang'),
                'message'    => $e->getMessage(),
            ]);

            /*
            | The underlying exception may carry Google API details or config
            | hints, so it is logged but never returned to a public visitor.
            */
            return response()->json([
                'success' => false,
                'message' => 'Could not generate audio for this section.',
            ], 500);
        }

        return response()->json([
            'success'  => true,
            'url'      => $audio->url,
            'language' => $audio->language_code,
            'duration' => null, // Reserved: populate if duration is ever measured.
        ]);
    }
}