<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\ProductSku;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class LabelService
{

    /**
     * Get dashboard required data (Categories & Store info)
     */
    public function getDashboardData($user, $currentStoreId): array
    {
        $categories = Category::where('company_id', $user->company_id)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $currentStore = null;

        if ($currentStoreId) {
            // Resolve directly against company-scoped stores (matches active_store() behaviour),
            // instead of relying solely on $user->stores pivot, which company admins may not have.
            $currentStore = \App\Models\Store::where('id', $currentStoreId)
                ->where('company_id', $user->company_id)
                ->first();
        }

        if (! $currentStore) {
            $stores = $user->stores ?? collect();
            $currentStore = $stores->first();
        }

        $storeName = $currentStore ? $currentStore->name : 'My Store';

        return [
            'categories' => $categories,
            'storeName' => $storeName
        ];
    }

    /**
     * Generate PDF for Labels
     */
    public function generatePdf(array $labelsData, string $type, string $storeName)
    {
        $processedLabels = [];

        foreach ($labelsData as $item) {
            $value = $item['label_value'];
            $base64Image = $this->generateBase64Image($type, $value);

            for ($i = 0; $i < (int) $item['copies']; $i++) {
                $processedLabels[] = [
                    'name' => $item['name'],
                    'attributes' => $item['attributes'] ?? '',
                    'label_value' => $value,                    
                    'price' => !empty($item['price']) ? '₹' . number_format((float)$item['price'], 0) : '',
                    'image' => $base64Image
                ];
            }
        }

        // 141.732 pt = 50mm, 70.866 pt = 25mm
        $customPaper = [0, 0, 141.732, 70.866];

        return Pdf::loadView('admin.products.labels.pdf', compact('processedLabels', 'type', 'storeName'))
            ->setPaper($customPaper);
    }

    /**
     * Generate raw image binary data for Barcode or QR
     */
    public function generateRawImage(string $type, string $value, int $size): string
    {
        if (empty($value) || !in_array($type, ['qr', 'barcode'], true)) {
            // Return transparent PNG if invalid
            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }

        if ($type === 'qr') {
            $options = new QROptions([
                'eccLevel' => QRCode::ECC_M,
                'scale' => max(2, (int) round($size / 40)),
                'quietzoneSize' => 1,
                'imageBase64' => false,
            ]);

            if (defined('chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG')) {
                $options->outputType = QRCode::OUTPUT_IMAGE_PNG;
            }

            return (new QRCode($options))->render($value);
        }

        // Barcode fallback
        $generator = new BarcodeGeneratorPNG();
        $widthFactor = max(1, (int) round($size / 80));
        $height = max(30, (int) round($size * 0.45));

        return $generator->getBarcode(
            $value,
            BarcodeGenerator::TYPE_CODE_128,
            $widthFactor,
            $height
        );
    }

    /**
     * Fetch Paginated SKUs with Unified Search & Filters
     */
    public function getPaginatedSkus(int $companyId, int $perPage, string $search, int $categoryId)
    {
        $query = ProductSku::with([
            'product.category',
            'skuValues.attributeValue.attribute',
            'product.media' => function ($q) {
                $q->where('is_primary', true)->where('media_type', 'image');
            },
        ])
        ->where('is_active', true)
        ->where('company_id', $companyId); // Added missing company filter for security

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('hsn_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId > 0) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $paginator = $query->latest('id')->paginate($perPage);

        $formattedData = $paginator->getCollection()->map(fn($sku) => $this->formatSkuData($sku));

        return [
            'data' => $formattedData,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Fetch specifically checked SKUs
     */
    public function getSelectedSkus(int $companyId, array $skuIds)
    {
        if (empty($skuIds)) {
            return [];
        }

        $idString = implode(',', $skuIds);

        $skus = ProductSku::with(['product.category', 'skuValues.attributeValue.attribute', 'product.media'])
            ->where('company_id', $companyId)
            ->whereIn('id', $skuIds)
            ->orderByRaw("FIELD(id, {$idString})")
            ->get();

        return $skus->map(fn($sku) => $this->formatSkuData($sku))->toArray();
    }

    /**
     * Helper: Generate Base64 Image specifically for PDF embedding
     */
    private function generateBase64Image(string $type, string $value): string
    {
        if ($type === 'qr') {
            $options = new QROptions([
                'eccLevel' => QRCode::ECC_M,
                'scale' => 3,
                'quietzoneSize' => 1,
                'imageBase64' => true,
            ]);
            
            if (defined('chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG')) {
                $options->outputType = QRCode::OUTPUT_IMAGE_PNG;
            }
            return (new QRCode($options))->render($value);
        }

        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($value, $generator::TYPE_CODE_128, 2, 40);
        return 'data:image/png;base64,' . base64_encode($barcode);
    }

    /**
     * Helper: DRY formatting logic for SKUs
     */
    private function formatSkuData(ProductSku $sku): array
    {
        $attributes = $sku->skuValues->map(function ($skuValue) {
            return [
                'name' => $skuValue->attributeValue->attribute->name ?? '',
                'value' => $skuValue->attributeValue->value ?? '',
            ];
        })->values()->toArray();

        $imagePath = $sku->product->media->where('is_primary', true)->where('media_type', 'image')->first()?->media_path;

        return [
            'unique_id' => $sku->id,
            'id' => $sku->product_id,
            'sku_id' => $sku->id,
            'name' => $sku->product->name ?? 'Unknown',
            'sku' => $sku->sku,
            'display_price' => $sku->price,
            'category_name' => $sku->product->category->name ?? 'N/A',
            'attributes' => $attributes,
            'label_value' => $sku->display_barcode,
            'actual_barcode' => $sku->barcode,
            'image_url' => $imagePath ? asset('storage/' . $imagePath) : '',
        ];
    }
}