<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes;

/**
 * One of the 52 Spanish provinces (including the autonomous cities of Ceuta and Melilla).
 */
final class Provincia implements \JsonSerializable
{
    /**
     * @param string $id     2-digit province code, also the first two digits of its postal codes
     * @param string $nombre official name, e.g. "Madrid" or "Valencia/València"
     */
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
    ) {
    }

    /**
     * @return array{id: string, nombre: string}
     */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'nombre' => $this->nombre];
    }
}
