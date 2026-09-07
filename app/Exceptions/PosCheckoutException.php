<?php

namespace App\Exceptions;

/**
 * A checkout rejected for a reason the cashier can act on.
 *
 * Distinct from a generic Throwable so PosController can return the message
 * verbatim with a 422. Every message here is written for the cashier — never
 * build one from an internal exception, or the 500-leak returns.
 */
class PosCheckoutException extends \RuntimeException
{
    /**
     * @param  array<int, string>  $products  Names shown to the cashier.
     */
    public function __construct(string $message, public readonly array $products = [])
    {
        parent::__construct($message);
    }

    /**
     * The cart is stale: what the browser quoted no longer matches the master.
     * Never silently re-price — the customer was shown a number.
     */
    public static function priceChanged(array $products): self
    {
        $list = implode(', ', $products);

        return new self(
            "Prices have changed for: {$list}. Please refresh the cart and try again.",
            $products
        );
    }

    public static function unavailableProduct(): self
    {
        return new self('One of the products in this cart is no longer available. Please refresh the cart and try again.');
    }
}