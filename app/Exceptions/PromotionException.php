<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a promotion cannot be redeemed (limit hit, already used, etc.).
 * Carries a user-safe message so callers can surface it directly.
 */
class PromotionException extends RuntimeException
{
}