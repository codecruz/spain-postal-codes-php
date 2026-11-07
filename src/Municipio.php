<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes;

/**
 * A Spanish municipality and the postal codes that serve it.
 */
final class Municipio implements \JsonSerializable
{
    /**
     * @param string       $id              5-digit INE municipality code, e.g. "28079"
     * @param string       $nombre          official name, e.g. "Alcalá de Henares" or "Alicante/Alacant"
     * @param string       $provinciaId     2-digit province code, e.g. "28"
     * @param string       $provincia       province name
     * @param list<string> $codigosPostales postal codes of the municipality, sorted
     */
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
        public readonly string $provinciaId,
        public readonly string $provincia,
        public readonly array $codigosPostales,
    ) {
    }

    /**
     * @return array{id: string, nombre: string, provinciaId: string, provincia: string, codigosPostales: list<string>}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'provinciaId' => $this->provinciaId,
            'provincia' => $this->provincia,
            'codigosPostales' => $this->codigosPostales,
        ];
    }
}
