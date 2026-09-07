<?php

namespace App\Exceptions;

use App\Models\ProductSku;
use Illuminate\Http\JsonResponse;

class InsufficientStockException extends \RuntimeException
{
    public function __construct(
        public readonly ProductSku $sku,
        public readonly float $available,
        public readonly float $requested
    ) {
        parent::__construct(
            "Insufficient stock for SKU [{$sku->sku}]. Available: {$available}, Requested: {$requested}"
        );
    }

    /**
     * Render as JSON for API responses.
     * Laravel calls this automatically if it exists.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'stock' => [
                    'sku' => $this->sku->sku,
                    'available' => $this->available,
                    'requested' => $this->requested,
                ],
            ],
        ], 422);
    }
}
