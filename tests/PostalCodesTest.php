<?php

declare(strict_types=1);

namespace Codecruz\SpainPostalCodes\Tests;

use Codecruz\SpainPostalCodes\Municipio;
use Codecruz\SpainPostalCodes\PostalCodes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PostalCodesTest extends TestCase
{
    /**
     * @return iterable<string, array{string|int, string|null}>
     */
    public static function normalizeProvider(): iterable
    {
        yield 'five digits' => ['28001', '28001'];
        yield 'lost leading zero' => [' 1450 ', '01450'];
        yield 'integer' => [1450, '01450'];
        yield 'letters' => ['abc', null];
        yield 'too long' => ['280011', null];
        yield 'too short integer' => [999, null];
        yield 'empty' => ['', null];
    }

    #[DataProvider('normalizeProvider')]
    public function testNormalizePostalCode(string|int $input, ?string $expected): void
    {
        self::assertSame($expected, PostalCodes::normalizePostalCode($input));
    }

    public function testLookup(): void
    {
        $municipios = PostalCodes::lookup('41700');

        self::assertCount(1, $municipios);
        self::assertSame('41038', $municipios[0]->id);
        self::assertSame('Dos Hermanas', $municipios[0]->nombre);
        self::assertSame('41', $municipios[0]->provinciaId);
        self::assertSame('Sevilla', $municipios[0]->provincia);
        self::assertContains('41700', $municipios[0]->codigosPostales);
    }

    public function testLookupAcceptsCodesWithoutLeadingZero(): void
    {
        self::assertSame(PostalCodes::lookup('01001'), PostalCodes::lookup(1001));
        self::assertSame('Vitoria-Gasteiz', PostalCodes::lookup('1001')[0]->nombre);
    }

    public function testPostalCodeSharedByMunicipalitiesOfDifferentProvinces(): void
    {
        $ids = array_map(static fn (Municipio $m): string => $m->id, PostalCodes::lookup('01427'));

        self::assertContains('01055', $ids);
        self::assertContains('09050', $ids, 'Berberana (Burgos) uses an Álava postal code');
    }

    public function testLookupOfUnknownOrInvalidCodes(): void
    {
        self::assertSame([], PostalCodes::lookup('99999'));
        self::assertSame([], PostalCodes::lookup('hello'));
    }

    public function testIsValidPostalCode(): void
    {
        self::assertTrue(PostalCodes::isValidPostalCode('28001'));
        self::assertTrue(PostalCodes::isValidPostalCode(8001));
        self::assertFalse(PostalCodes::isValidPostalCode('99999'));
        self::assertFalse(PostalCodes::isValidPostalCode('28000'), 'xx000 placeholders are not real postal codes');
    }

    public function testGetMunicipioAndPostalCodes(): void
    {
        self::assertSame('Madrid', PostalCodes::getMunicipio('28079')?->nombre);
        self::assertSame('Alegría-Dulantzi', PostalCodes::getMunicipio('01001')?->nombre, 'ids with a leading zero');
        self::assertNull(PostalCodes::getMunicipio('00000'));

        $codes = PostalCodes::getPostalCodes('28079');
        self::assertGreaterThan(50, \count($codes));
        $sorted = $codes;
        sort($sorted);
        self::assertSame($sorted, $codes);
        self::assertSame([], PostalCodes::getPostalCodes('00000'));
    }

    public function testGetProvincias(): void
    {
        $provincias = PostalCodes::getProvincias();

        self::assertCount(52, $provincias);
        self::assertSame('01', $provincias[0]->id);
        self::assertSame('52', $provincias[51]->id);
        foreach ($provincias as $provincia) {
            self::assertMatchesRegularExpression('/^\d{2}$/', $provincia->id);
        }
    }

    /**
     * @return iterable<string, array{string|int, string|null}>
     */
    public static function provinciaProvider(): iterable
    {
        yield 'code' => ['28', 'Madrid'];
        yield 'integer code' => [8, 'Barcelona'];
        yield 'one-digit code' => ['5', 'Ávila'];
        yield 'postal code' => ['41700', 'Sevilla'];
        yield 'name' => ['Madrid', 'Madrid'];
        yield 'name without accents' => ['avila', 'Ávila'];
        yield 'uppercase name' => ['CÁDIZ', 'Cádiz'];
        yield 'part of bilingual name' => ['Alacant', 'Alicante/Alacant'];
        yield 'spanish alias' => ['Vizcaya', 'Bizkaia'];
        yield 'unknown code' => ['99', null];
        yield 'unknown name' => ['Atlantis', null];
        yield 'empty' => ['', null];
    }

    #[DataProvider('provinciaProvider')]
    public function testGetProvincia(string|int $input, ?string $expected): void
    {
        self::assertSame($expected, PostalCodes::getProvincia($input)?->nombre);
    }

    public function testSearchIgnoresCaseAndAccentsAndRanksPrefixMatchesFirst(): void
    {
        $names = array_map(static fn (Municipio $m): string => $m->nombre, PostalCodes::searchMunicipios('alcala', limit: 50));

        self::assertContains('Alcalá de Henares', $names);
        self::assertStringStartsWith('Alcalá', $names[0]);
        self::assertSame('Dos Hermanas', PostalCodes::searchMunicipios('HERMANAS')[0]->nombre);
        self::assertSame('03014', PostalCodes::searchMunicipios('alacant')[0]->id, 'matches either part of bilingual names');
    }

    public function testSearchOptions(): void
    {
        self::assertCount(3, PostalCodes::searchMunicipios('a', limit: 3));
        self::assertSame([], PostalCodes::searchMunicipios('   '));
        self::assertSame([], PostalCodes::searchMunicipios('madrid', limit: 0));

        foreach (PostalCodes::searchMunicipios('villa', provinciaId: '28', limit: 100) as $municipio) {
            self::assertSame('28', $municipio->provinciaId);
        }
    }

    public function testJsonSerialization(): void
    {
        $json = json_encode(PostalCodes::lookup('28001'), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);

        self::assertStringStartsWith('[{"id":"28079","nombre":"Madrid","provinciaId":"28","provincia":"Madrid","codigosPostales":["', $json);
        self::assertSame('{"id":"28","nombre":"Madrid"}', json_encode(PostalCodes::getProvincia('28'), \JSON_THROW_ON_ERROR));
    }

    public function testMetadata(): void
    {
        $metadata = PostalCodes::metadata();

        self::assertMatchesRegularExpression('/^\d{4}-(01|07)$/', $metadata['period']);
        self::assertSame('CC BY 4.0', $metadata['license']);
        self::assertStringContainsString('INE', $metadata['attribution']);
    }
}
