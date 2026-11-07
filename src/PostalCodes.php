<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes;

use Codecruz\SpainPostalCodes\Internal\Dataset;
use Codecruz\SpainPostalCodes\Internal\Text;

/**
 * Offline lookup of Spanish postal codes, municipalities and provinces.
 *
 * The data is loaded on first use and kept in memory for the rest of the request.
 */
final class PostalCodes
{
    /** Alternative names (folded), mostly the Spanish form of co-official names. */
    private const PROVINCE_ALIASES = [
        'alava' => '01', 'araba' => '01', 'alicante' => '03', 'alacant' => '03',
        'baleares' => '07', 'islas baleares' => '07', 'castellon' => '12', 'castello' => '12',
        'la coruna' => '15', 'coruna' => '15', 'gerona' => '17', 'guipuzcoa' => '20',
        'lerida' => '25', 'orense' => '32', 'valencia' => '46', 'vizcaya' => '48',
    ];

    private function __construct()
    {
    }

    /**
     * Normalizes a postal code to its 5-digit string form, restoring a lost leading
     * zero ("1450" or 1450 -> "01450"). Returns null if it cannot be a postal code.
     */
    public static function normalizePostalCode(string|int $postalCode): ?string
    {
        if (\is_int($postalCode)) {
            if ($postalCode < 1000 || $postalCode > 99999) {
                return null;
            }
            $postalCode = (string) $postalCode;
        }

        $value = trim($postalCode);
        if (1 === preg_match('/^\d{4}$/', $value)) {
            return '0'.$value;
        }

        return 1 === preg_match('/^\d{5}$/', $value) ? $value : null;
    }

    /**
     * Municipalities served by a postal code; empty if the code is unknown or malformed.
     *
     * @return list<Municipio>
     */
    public static function lookup(string|int $postalCode): array
    {
        $code = self::normalizePostalCode($postalCode);

        return null === $code ? [] : (Dataset::get()->municipiosByPostalCode[$code] ?? []);
    }

    /** Whether the postal code exists in the dataset. */
    public static function isValidPostalCode(string|int $postalCode): bool
    {
        return [] !== self::lookup($postalCode);
    }

    /** A municipality by its 5-digit INE code, e.g. "28079" for Madrid. */
    public static function getMunicipio(string $id): ?Municipio
    {
        return Dataset::get()->municipiosById[str_pad(trim($id), 5, '0', \STR_PAD_LEFT)] ?? null;
    }

    /**
     * Postal codes of a municipality; empty if the municipality is unknown.
     *
     * @return list<string>
     */
    public static function getPostalCodes(string $municipioId): array
    {
        $municipio = self::getMunicipio($municipioId);

        return null === $municipio ? [] : $municipio->codigosPostales;
    }

    /**
     * The 52 provinces, sorted by code.
     *
     * @return list<Provincia>
     */
    public static function getProvincias(): array
    {
        return array_values(Dataset::get()->provincias);
    }

    /**
     * A province by its code ("28" or 28), by a postal code ("28001"), or by name.
     * Names are matched ignoring case and accents, in either language of
     * bilingual provinces and with common Spanish forms ("Vizcaya", "Gerona").
     */
    public static function getProvincia(string|int $value): ?Provincia
    {
        $provincias = Dataset::get()->provincias;
        $value = trim((string) $value);

        if (1 === preg_match('/^\d{1,2}$/', $value)) {
            return $provincias[str_pad($value, 2, '0', \STR_PAD_LEFT)] ?? null;
        }

        $postalCode = self::normalizePostalCode($value);
        if (null !== $postalCode) {
            return $provincias[substr($postalCode, 0, 2)] ?? null;
        }

        $needle = Text::fold($value);
        if ('' === $needle) {
            return null;
        }
        if (isset(self::PROVINCE_ALIASES[$needle])) {
            return $provincias[self::PROVINCE_ALIASES[$needle]];
        }
        foreach ($provincias as $provincia) {
            $name = Text::fold($provincia->nombre);
            if ($name === $needle || \in_array($needle, explode('/', $name), true)) {
                return $provincia;
            }
        }

        return null;
    }

    /**
     * Municipalities whose name contains the query, ignoring case and accents.
     * Names starting with the query (in either part of bilingual names) come first,
     * then the rest; each group is sorted by name.
     *
     * @param string|null $provinciaId only return municipalities of this province (2-digit code)
     *
     * @return list<Municipio>
     */
    public static function searchMunicipios(string $query, ?string $provinciaId = null, int $limit = 10): array
    {
        $needle = Text::fold($query);
        if ('' === $needle || $limit <= 0) {
            return [];
        }

        $starts = [];
        $contains = [];
        foreach (Dataset::get()->searchIndex() as [$name, $municipio]) {
            if (null !== $provinciaId && $municipio->provinciaId !== $provinciaId) {
                continue;
            }
            $prefix = str_starts_with($name, $needle);
            if (!$prefix) {
                foreach (explode('/', $name) as $part) {
                    if (str_starts_with($part, $needle)) {
                        $prefix = true;
                        break;
                    }
                }
            }
            if ($prefix) {
                $starts[] = $municipio;
            } elseif (str_contains($name, $needle)) {
                $contains[] = $municipio;
            }
        }

        $byName = static fn (Municipio $a, Municipio $b): int => strcmp(Text::fold($a->nombre), Text::fold($b->nombre));
        usort($starts, $byName);
        usort($contains, $byName);

        return \array_slice([...$starts, ...$contains], 0, $limit);
    }

    /**
     * Source and license of the bundled data.
     *
     * @return array{period: string, referenceDate: string, source: string, license: string, attribution: string}
     */
    public static function metadata(): array
    {
        return Dataset::get()->meta;
    }
}
