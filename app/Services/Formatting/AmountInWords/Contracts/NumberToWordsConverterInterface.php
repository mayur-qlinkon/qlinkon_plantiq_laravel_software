<?php

namespace App\Services\Formatting\AmountInWords\Contracts;

interface NumberToWordsConverterInterface
{
    /**
     * Convert a numeric amount into its word representation.
     *
     * @param float $amount
     * @return string
     */
    public function convert(float $amount): string;
}