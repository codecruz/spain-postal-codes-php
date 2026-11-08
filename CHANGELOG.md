# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/): data updates are minor releases.

## [1.0.1] - 2026-11-08

### Changed

- No functional changes. Release made to verify that Packagist picks up new
  tags automatically.

## [1.0.0] - 2026-11-08

### Added

- `PostalCodes` with `lookup()`, `isValidPostalCode()`, `normalizePostalCode()`,
  `getMunicipio()`, `getPostalCodes()`, `getProvincia()`, `getProvincias()`,
  `searchMunicipios()` and `metadata()`.
- Immutable, JSON-serializable `Municipio` and `Provincia` value objects.
- Data from the INE Callejero del Censo Electoral, period 2026-07
  (reference date 2026-06-30): 10,791 postal codes and 8,132 municipalities.

[1.0.1]: https://github.com/codecruz/spain-postal-codes-php/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/codecruz/spain-postal-codes-php/releases/tag/v1.0.0
