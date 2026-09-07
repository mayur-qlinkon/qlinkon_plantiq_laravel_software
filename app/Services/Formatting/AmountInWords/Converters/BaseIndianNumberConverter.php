<?php

namespace App\Services\Formatting\AmountInWords\Converters;

use App\Services\Formatting\AmountInWords\Contracts\NumberToWordsConverterInterface;

abstract class BaseIndianNumberConverter implements NumberToWordsConverterInterface
{
    /**
     * Provide the dictionary mapping numbers 0 to 99 to their respective words.
     */
    abstract protected function getDictionary(): array;

    /**
     * Provide the language-specific magnitude labels (Crore, Lakh, Thousand, Hundred).
     */
    abstract protected function getMagnitudeLabels(): array;

    /**
     * Provide the currency labels (Rupees, Paise, And, Only, Zero).
     */
    abstract protected function getCurrencyLabels(): array;

    /**
     * Main conversion execution.
     */
    public function convert(float $amount): string
    {
        $currencyLabels = $this->getCurrencyLabels();

        if ($amount == 0) {
            return $currencyLabels['zero'] . ' ' . $currencyLabels['rupees'] . ' ' . $currencyLabels['only'];
        }

        $rupees = $this->splitNumber($amount);
        $paise = $this->splitPaise($amount);

        $rupeesWords = $this->convertIndianDigitGrouping($rupees);
        
        $result = $rupeesWords . ' ' . $currencyLabels['rupees'];

        if ($paise > 0) {
            $paiseWords = $this->convertToWords($paise);
            $result .= ' ' . $currencyLabels['and'] . ' ' . $paiseWords . ' ' . $currencyLabels['paise'];
        }

        $result .= ' ' . $currencyLabels['only'];

        return trim(preg_replace('/\s+/', ' ', $result));
    }

    /**
     * Extracts the integer portion of the amount.
     */
    protected function splitNumber(float $amount): int
    {
        return (int) floor($amount);
    }

    /**
     * Extracts the fractional portion of the amount as a two-digit integer.
     */
    protected function splitPaise(float $amount): int
    {
        $fraction = round($amount - $this->splitNumber($amount), 2);
        
        return (int) round($fraction * 100);
    }

    /**
     * Recursively chunks the number based on the Indian numbering system.
     */
    protected function convertIndianDigitGrouping(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $labels = $this->getMagnitudeLabels();
        $parts = [];

        // Handle Crores
        $crore = (int) floor($number / 10000000);
        $number %= 10000000;
        if ($crore > 0) {
            $parts[] = $this->convertIndianDigitGrouping($crore) . ' ' . $labels['crore'];
        }

        // Handle Lakhs
        $lakh = (int) floor($number / 100000);
        $number %= 100000;
        if ($lakh > 0) {
            $parts[] = $this->convertToWords($lakh) . ' ' . $labels['lakh'];
        }

        // Handle Thousands
        $thousand = (int) floor($number / 1000);
        $number %= 1000;
        if ($thousand > 0) {
            $parts[] = $this->convertToWords($thousand) . ' ' . $labels['thousand'];
        }

        // Handle Hundreds
        $hundred = (int) floor($number / 100);
        $number %= 100;
        if ($hundred > 0) {
            $parts[] = $this->convertToWords($hundred) . ' ' . $labels['hundred'];
        }

        // Handle Tens and Units (1 to 99)
        if ($number > 0) {
            $parts[] = $this->convertToWords($number);
        }

        return implode(' ', $parts);
    }

    /**
     * Retrieves the word for a specific block from the dictionary.
     */
    protected function convertToWords(int $number): string
    {
        $dictionary = $this->getDictionary();
        
        if (isset($dictionary[$number])) {
            return $dictionary[$number];
        }

        return ''; 
    }
}