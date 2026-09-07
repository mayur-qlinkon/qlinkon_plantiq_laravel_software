<?php

namespace App\Services\Formatting\AmountInWords;

use App\Services\Formatting\AmountInWords\Converters\EnglishConverter;
use App\Services\Formatting\AmountInWords\Converters\HindiConverter;
use App\Services\Formatting\AmountInWords\Converters\GujaratiConverter;

// class AmountInWordsService
// {
//     /**
//      * Convert a numeric amount into words based on the specified language.
//      *
//      * @param float $amount
//      * @param string $locale
//      * @return string
//      */
//     public static function convert(float $amount, string $locale = 'en'): string
//     {
//         $converter = match (strtolower($locale)) {
//             'hi' => new HindiConverter(),
//             'gu' => new GujaratiConverter(),
//             default => new EnglishConverter(),
//         };

//         return $converter->convert($amount);
//     }
// }