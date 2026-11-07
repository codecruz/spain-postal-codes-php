<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes\Internal;

/**
 * @internal
 */
final class Text
{
    /** Letters used in Spanish, Catalan/Valencian, Galician and Basque place names. */
    private const ACCENTS = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ñ' => 'n', 'ç' => 'c', '·' => '',
        'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ä' => 'a', 'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i', 'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Ö' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ñ' => 'n', 'Ç' => 'c',
    ];

    private function __construct()
    {
    }

    /** Lowercase, accent-free form used for case- and accent-insensitive matching. */
    public static function fold(string $value): string
    {
        return strtolower(trim(strtr($value, self::ACCENTS)));
    }
}
