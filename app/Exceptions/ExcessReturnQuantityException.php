<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Thrown when a user attempts to return a quantity greater than what
 * remains returnable on the original invoice line.
 *
 * Carries the numbers needed to render a friendly, non-technical message
 * for the tenant; the raw exception message keeps engineering context for logs.
 */
class ExcessReturnQuantityException extends \RuntimeException
{
    public function __construct(
        public readonly string $productLabel,
        public readonly float $originalQty,
        public readonly float $alreadyReturnedQty,
        public readonly float $remainingQty,
        public readonly float $requestedQty,
    ) {
        parent::__construct(
            "Excess return for [{$productLabel}]. Original: {$originalQty}, Returned: {$alreadyReturnedQty}, Remaining: {$remainingQty}, Requested: {$requestedQty}"
        );
    }

    /**
     * Tenant-friendly message shown on UI flash / form errors.
     */
    public function friendlyMessage(): string
    {
        $requested = $this->format($this->requestedQty);
        $remaining = $this->format($this->remainingQty);
        $original = $this->format($this->originalQty);

        if ($this->remainingQty <= 0) {
            return "You cannot return {$requested} units for {$this->productLabel} because this item has already been fully returned against the original sale of {$original} units.";
        }

        return "You cannot return {$requested} units for {$this->productLabel} because only {$remaining} units are still returnable from the original sale of {$original} units.";
    }

    /**
     * Render as JSON for API responses. Laravel calls this automatically.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->friendlyMessage(),
            'errors' => [
                'return_quantity' => [
                    'product' => $this->productLabel,
                    'original' => $this->originalQty,
                    'already_returned' => $this->alreadyReturnedQty,
                    'remaining' => $this->remainingQty,
                    'requested' => $this->requestedQty,
                ],
            ],
        ], 422);
    }

    /**
     * Format a quantity number without trailing zeros (e.g. 5.5000 -> "5.5", 5.0000 -> "5").
     */
    private function format(float $qty): string
    {
        $formatted = number_format($qty, 4, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
