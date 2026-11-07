# Spain Postal Codes for PHP

[![CI](https://github.com/codecruz/spain-postal-codes-php/actions/workflows/ci.yml/badge.svg)](https://github.com/codecruz/spain-postal-codes-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/codecruz/spain-postal-codes.svg)](https://packagist.org/packages/codecruz/spain-postal-codes)
[![PHP](https://img.shields.io/packagist/dependency-v/codecruz/spain-postal-codes/php.svg)](https://packagist.org/packages/codecruz/spain-postal-codes)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](phpstan.neon.dist)

This library looks up and searches **Spanish postal codes, municipalities and provinces** without network access. It is built from official **INE** data.

- **Coverage:** 10,791 postal codes and 8,132 municipalities, with their INE codes.
- **Light:** no dependencies, PHP 8.1+, and it works with any framework (Laravel, Symfony, WordPress…) or none.
- **Forgiving input:** restores lost leading zeros (`1450` → `01450`). Name searches ignore accents and case.
- **Typed results:** immutable value objects that can be serialized to JSON.

This is the PHP version of [spain-postal-codes](https://github.com/codecruz/spain-postal-codes), which also offers the [CSV dataset](https://github.com/codecruz/spain-postal-codes/tree/main/data), a [static JSON API](https://codecruz.github.io/spain-postal-codes/) and an [npm package](https://www.npmjs.com/package/spain-postal-codes) with the same API.

## Install

```bash
composer require codecruz/spain-postal-codes
```

## Usage

```php
use Codecruz\SpainPostalCodes\PostalCodes;

$municipios = PostalCodes::lookup('41700');
$municipios[0]->nombre;          // "Dos Hermanas"
$municipios[0]->provincia;       // "Sevilla"
$municipios[0]->id;              // "41038" (INE code)

// One postal code can serve several municipalities, even in different provinces.
array_map(fn ($m) => $m->nombre, PostalCodes::lookup('01427'));
// ["Valdegovía/Gaubea", "Berberana", "Jurisdicción de San Zadornil"]

PostalCodes::isValidPostalCode(8001);          // true ("08001")
PostalCodes::getProvincia('28001')?->nombre;   // "Madrid"
PostalCodes::getProvincia('vizcaya')?->nombre; // "Bizkaia"
PostalCodes::getPostalCodes('28079');          // ["28001", "28002", ...]

PostalCodes::searchMunicipios('alcala', provinciaId: '28', limit: 5);
// [Municipio("Alcalá de Henares"), Municipio("Valverde de Alcalá")]
```

### Validating a form

```php
$code = PostalCodes::normalizePostalCode($request->get('postal_code'));
$municipios = $code === null ? [] : PostalCodes::lookup($code);

if ($municipios === []) {
    // unknown postal code
}
```

### Returning JSON

`Municipio` and `Provincia` implement `JsonSerializable`:

```php
echo json_encode(PostalCodes::lookup('28001'), JSON_UNESCAPED_UNICODE);
// [{"id":"28079","nombre":"Madrid","provinciaId":"28","provincia":"Madrid","codigosPostales":["28001",...]}]
```

## API

All methods are static on `Codecruz\SpainPostalCodes\PostalCodes`.

| Method | Returns |
|--------|---------|
| `lookup(string\|int $postalCode)` | `list<Municipio>`: municipalities served by the code (empty if unknown) |
| `isValidPostalCode(string\|int $postalCode)` | `bool`: whether the code exists |
| `normalizePostalCode(string\|int $postalCode)` | `?string`: 5-digit form, or `null` if malformed |
| `getMunicipio(string $id)` | `?Municipio`: by 5-digit INE code |
| `getPostalCodes(string $municipioId)` | `list<string>`: postal codes of a municipality |
| `getProvincia(string\|int $value)` | `?Provincia`: by code (`'28'`), postal code (`'28001'`) or name (`'Vizcaya'`, `'alacant'`) |
| `getProvincias()` | `list<Provincia>`: the 52 provinces, sorted by code |
| `searchMunicipios(string $query, ?string $provinciaId = null, int $limit = 10)` | `list<Municipio>`: name search; prefix matches first |
| `metadata()` | `array{period, referenceDate, source, license, attribution}` |

```php
final class Municipio {
    public readonly string $id;              // "28079"
    public readonly string $nombre;          // "Madrid"
    public readonly string $provinciaId;     // "28"
    public readonly string $provincia;       // "Madrid"
    public readonly array $codigosPostales;  // list<string>, sorted
}

final class Provincia {
    public readonly string $id;      // "28"
    public readonly string $nombre;  // "Madrid"
}
```

**Performance:** the data (~390 KB of PHP arrays) is loaded on the first call and kept for the rest of the request. Without opcache, loading takes about 15 ms and uses about 11 MB of memory. Once loaded, a lookup takes well under a microsecond.

## Data

The postal codes come from the INE *Callejero del Censo Electoral* and the names from the INE municipality list. See [how the dataset is built](https://github.com/codecruz/spain-postal-codes#where-the-data-comes-from). Postal codes that don't belong to a street address, such as PO boxes, are not included.

New data is released as a **minor version** after each INE publication (January and July). Check `PostalCodes::metadata()['period']` to see which period you have installed.

## Development

```bash
composer install
composer test        # PHPUnit
composer analyse     # PHPStan, level max
composer build-data  # regenerate resources/data.php from the published dataset
```

`resources/data.php` is generated: don't edit it by hand. Data errors should be reported in the [dataset repository](https://github.com/codecruz/spain-postal-codes/issues).

## License

- **Code:** [MIT](LICENSE).
- **Data:** derived from INE data, under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/). Credit the source: *"Fuente: Instituto Nacional de Estadística (INE)"*.
