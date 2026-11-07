<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes\Tests;

use Codecruz\SpainPostalCodes\PostalCodes;
use PHPUnit\Framework\TestCase;

/**
 * Integrity checks on the bundled resources/data.php.
 */
final class DatasetTest extends TestCase
{
    public function testEveryMunicipalityIsConsistent(): void
    {
        $municipalities = 0;
        $postalCodes = [];

        /** @var array{municipios: list<array{string, string, string}>} $data */
        $data = require \dirname(__DIR__).'/resources/data.php';
        foreach ($data['municipios'] as [$id]) {
            $municipio = PostalCodes::getMunicipio($id);
            self::assertNotNull($municipio, $id);
            self::assertMatchesRegularExpression('/^\d{5}$/', $municipio->id);
            self::assertSame(substr($municipio->id, 0, 2), $municipio->provinciaId);
            self::assertNotSame('', $municipio->nombre);
            self::assertNotEmpty($municipio->codigosPostales);

            foreach ($municipio->codigosPostales as $code) {
                self::assertMatchesRegularExpression('/^\d{5}$/', $code);
                self::assertContains($municipio, PostalCodes::lookup($code));
                $postalCodes[$code] = true;
            }
            ++$municipalities;
        }

        self::assertGreaterThan(8000, $municipalities);
        self::assertGreaterThan(10000, \count($postalCodes));
    }
}
