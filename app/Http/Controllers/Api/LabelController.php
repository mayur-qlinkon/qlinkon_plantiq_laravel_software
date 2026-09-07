<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Admin\LabelService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Label printing endpoints for the Flutter app.
 *
 * The app renders and prints the label itself through the printer's vendor
 * SDK — the same arrangement the web POS already uses via the
 * AndroidLabelPrinter bridge. So this side ships data, not layout: nothing
 * here decides what a label looks like, which means a label printed from
 * the app cannot drift out of sync because of a change made on the server.
 *
 * label_value is the field to encode. It resolves to the SKU's barcode, or
 * to the SKU code when no barcode is set (ProductSku::display_barcode), and
 * is the same value the POS scanner matches on — so a label printed here
 * always scans back correctly at the counter.
 */
class LabelController extends Controller
{
    public function __construct(protected LabelService $labelService) {}

    /**
     * Category filter list plus the store name that goes on the label.
     */
    public function bootstrap()
    {
        $data = $this->labelService->getDashboardData(Auth::user(), active_store()?->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'store_name' => $data['storeName'],
                'categories' => $data['categories'],
                'permissions' => [
                    'labels.print' => has_permission('labels.print'),
                ],
            ],
        ]);
    }

    /**
     * Paginated, searchable SKU list for the selection screen.
     */
    public function products(Request $request)
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));
        $search = trim($request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $result = $this->labelService->getPaginatedSkus(
            Auth::user()->company_id,
            $perPage,
            $search,
            $categoryId
        );

        return response()->json([
            'status' => 'success',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Full data for a specific set of SKUs, in the order they were sent.
     *
     * The selection screen is paginated, so by the time the user hits print
     * their picks may span pages the app no longer holds in memory. This
     * refetches exactly those rows in one call.
     */
    public function selected(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:500'],
            'product_ids.*' => ['integer'],
        ]);

        $skuIds = array_values(array_filter(array_map('intval', $validated['product_ids'])));

        if (empty($skuIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid SKUs provided.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->labelService->getSelectedSkus(Auth::user()->company_id, $skuIds),
        ]);
    }

    /**
     * Raw PNG of a barcode or QR code.
     *
     * A fallback, not the main path — the app should generate these locally
     * so printing works with no connectivity at the counter. This exists for
     * on-screen previews and for the case where a device's local renderer
     * cannot produce a symbology the printer accepts.
     */
    public function render(Request $request)
    {
        $type = strtolower(trim($request->query('type', '')));
        $value = trim($request->query('value', ''));
        $size = max(48, min(600, (int) $request->query('size', 200)));

        try {
            if (ob_get_length()) {
                ob_clean();
            }

            $imageBinary = $this->labelService->generateRawImage($type, $value, $size);

            return response($imageBinary, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        } catch (Exception $e) {
            if (ob_get_length()) {
                ob_clean();
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Image Error: '.$e->getMessage(),
            ], 500);
        }
    }
}