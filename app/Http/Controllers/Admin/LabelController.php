<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\LabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class LabelController extends Controller
{
    protected LabelService $labelService;

    public function __construct(LabelService $labelService)
    {
        $this->labelService = $labelService;
    }

    /**
     * Display the Label Printing UI dashboard.
     */
    public function index()
    {
        $data = $this->labelService->getDashboardData(Auth::user(), active_store()?->id);        
        return view('admin.products.labels.index', [
            'categories' => $data['categories'],
            'storeName' => $data['storeName']
        ]);
    }

    /**
     * Download or stream the generated PDF labels.
     */
    public function downloadPdf(Request $request)
    {
        $labelsData = json_decode($request->input('labels_data', '[]'), true) ?? [];
        $type = $request->input('label_type', 'barcode');
        $storeName = $request->input('store_name', 'My Store');

        if (empty($labelsData)) {
            return back()->with('error', 'No label data provided.');
        }

        $pdf = $this->labelService->generatePdf($labelsData, $type, $storeName);

        return $pdf->stream('Product_Labels.pdf');
    }    

    /**
     * Streams a raw PNG image of a Barcode or QR code.
     * Accessible via <img src="..."> tags.
     */
    public function renderImage(Request $request)
    {
        $type = strtolower(trim($request->query('type', '')));
        $value = trim($request->query('value', ''));
        $size = max(48, min(600, (int) $request->query('size', 200)));

        try {
            // Clean any stray whitespace output to prevent binary corruption
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
            return response('Image Error: '.$e->getMessage(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * API Endpoint: Fetch Paginated SKUs with Unified Search & Filters
     */
    public function fetchProducts(Request $request)
    {        
        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));
        $search = trim($request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);        
        $companyId = Auth::user()->company_id;

        $result = $this->labelService->getPaginatedSkus($companyId, $perPage, $search, $categoryId);

        return response()->json([
            'status' => 'success',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * API Endpoint: Fetch fully populated data for specifically checked SKUs
     */
    public function fetchSelectedSkus(Request $request)
    {
        $rawIds = $request->input('product_ids', []);
        
        // Handle input (Legacy accepted comma separated strings or JSON arrays)
        if (is_string($rawIds)) {
            $rawIds = json_decode($rawIds, true) ?? explode(',', $rawIds);
        }

        $skuIds = array_filter(array_map('intval', $rawIds));

        if (empty($skuIds)) {
            return response()->json(['status' => 'error', 'message' => 'No valid SKUs provided.']);
        }

        $formattedData = $this->labelService->getSelectedSkus(Auth::user()->company_id, $skuIds);

        return response()->json([
            'status' => 'success',
            'data' => $formattedData,
        ]);
    }

}