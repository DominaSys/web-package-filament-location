# Changelog

All notable changes to `filament-location` will be documented in this file.

## v5.5.0 - 2026-07-02

**Full Changelog**: https://github.com/DominaSys/web-package-filament-location/compare/v5.4.0...v5.5.0

## v5.4.0 - 2026-05-21

laravel 13

**Full Changelog**: https://github.com/DominaSys/web-package-filament-location/compare/v5.3.0...v5.4.0

## v5.3.0 - 2026-05-21

### What's Changed

* Bump dependabot/fetch-metadata from 2.5.0 to 3.1.0 by @dependabot[bot] in https://github.com/DominaSys/web-package-filament-location/pull/3

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/DominaSys/web-package-filament-location/pull/3

**Full Changelog**: https://github.com/DominaSys/web-package-filament-location/compare/v5.2.0...v5.3.0

## v5.2.0 - 2026-05-15

**Full Changelog**: https://github.com/DominaSys/web-package-filament-location/compare/v5.1.1...v5.2.0

## v5.1.1 - 2026-04-20

**Full Changelog**: https://github.com/DominaSys/web-package-filament-location/compare/v5.1.0...v5.1.1

## Unreleased

### Added

- Added an explicit `HouseNumber` component for address forms
- Kept `Number` as a backward-compatible alias for existing integrations
- Improved `state` and `city` search with accent-insensitive matching and typo tolerance in the frontend
- Updated GitHub workflows to work with the repository's `5.x` branch

### Changed

- Continued improvements to the canonical, country-aware address dataset and sync pipeline

## v5.0.0 - 2026-04-16

### Added

- Canonical, extensible address dataset structure by country
- `postal code` support with per-country factory resolution and standardized lookup results
- Reusable components for `postal code`, `state`, `city`, `neighborhood`, `street`, `number`, and `complement`
- Frontend-only search for `state` and `city`, with no request delay
- Search with accent-insensitive matching and light typo tolerance
- Minified Brazil dataset, ready for multi-country expansion
- Internal dataset synchronization from external sources, starting with IBGE
- Localization in `pt_BR` and `en`

### Changed

- This release marks the first major 5.x version, focused on scalability, standardization, and future multi-country extensibility.
