<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes\Internal;

use Codecruz\SpainPostalCodes\Municipio;
use Codecruz\SpainPostalCodes\Provincia;

/**
 * Lazily loaded, in-memory indexes over resources/data.php.
 *
 * @internal
 */
final class Dataset
{
    private static ?self $instance = null;

    /**
     * Keyed by INE code. Numeric string keys such as '28079' become integers in PHP
     * arrays, so look them up with a string and do not rely on the key type.
     *
     * @var array<array-key, Municipio>
     */
    public readonly array $municipiosById;

    /** @var array<array-key, list<Municipio>> keyed by postal code (same caveat) */
    public readonly array $municipiosByPostalCode;

    /** @var array<array-key, Provincia> keyed by 2-digit code (same caveat), sorted */
    public readonly array $provincias;

    /** @var array{period: string, referenceDate: string, source: string, license: string, attribution: string} */
    public readonly array $meta;

    /** @var list<array{string, Municipio}>|null folded name => municipality, built on first search */
    private ?array $searchIndex = null;

    private function __construct()
    {
        /** @var array{meta: array{period: string, referenceDate: string, source: string, license: string, attribution: string}, provincias: array<int|string, string>, municipios: list<array{string, string, string}>} $data */
        $data = require \dirname(__DIR__, 2).'/resources/data.php';

        $provincias = [];
        foreach ($data['provincias'] as $id => $nombre) {
            // PHP turns numeric string keys such as '28' into integers.
            $id = str_pad((string) $id, 2, '0', \STR_PAD_LEFT);
            $provincias[$id] = new Provincia($id, $nombre);
        }
        ksort($provincias, \SORT_STRING);

        $byId = [];
        $byPostalCode = [];
        foreach ($data['municipios'] as [$id, $nombre, $codes]) {
            $provinciaId = substr($id, 0, 2);
            $municipio = new Municipio($id, $nombre, $provinciaId, $provincias[$provinciaId]->nombre, explode(' ', $codes));
            $byId[$id] = $municipio;
            foreach ($municipio->codigosPostales as $code) {
                $byPostalCode[$code][] = $municipio;
            }
        }

        $this->municipiosById = $byId;
        $this->municipiosByPostalCode = $byPostalCode;
        $this->provincias = $provincias;
        $this->meta = $data['meta'];
    }

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @return list<array{string, Municipio}>
     */
    public function searchIndex(): array
    {
        if (null === $this->searchIndex) {
            $index = [];
            foreach ($this->municipiosById as $municipio) {
                $index[] = [Text::fold($municipio->nombre), $municipio];
            }
            $this->searchIndex = $index;
        }

        return $this->searchIndex;
    }
}
