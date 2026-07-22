# Filament Location

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dominasys/filament-location.svg?style=flat-square)](https://packagist.org/packages/dominasys/filament-location)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dominasys/web-package-filament-location/run-tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/dominasys/web-package-filament-location/actions?query=workflow%3A%22Run+Tests%22+branch%3A5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/dominasys/filament-location.svg?style=flat-square)](https://packagist.org/packages/dominasys/filament-location)

`filament-location` provides reusable Filament components for address forms, postal code lookup, and country-aware location fields.

## Features

- `PostalCode` lookup with standardized results
- Reusable components for `state`, `city`, `neighborhood`, `street`, `house number`, and `complement`
- Frontend-only search for `state` and `city`
- Accent-insensitive and typo-tolerant search
- Canonical address dataset stored per country
- Localization for `en` and `pt_BR`
- Optional Google Places and Maps picker, disabled by default

## Installation

Install the package with Composer:

```bash
composer require dominasys/filament-location
```

## Usage

Use the provided components in your Filament form schemas:

```php
use Dominasys\FilamentLocation\Forms\Components\Address\City;
use Dominasys\FilamentLocation\Forms\Components\Address\Complement;
use Dominasys\FilamentLocation\Forms\Components\Address\HouseNumber;
use Dominasys\FilamentLocation\Forms\Components\Address\Neighborhood;
use Dominasys\FilamentLocation\Forms\Components\Address\State;
use Dominasys\FilamentLocation\Forms\Components\Address\Street;
use Dominasys\FilamentLocation\Forms\Components\PostalCode;

PostalCode::make('postal_code');
State::make('state');
City::make('city');
Neighborhood::make('neighborhood');
Street::make('street');
HouseNumber::make('house_number');
Complement::make('complement');
```

### Optional Google integration

Enable Google only in applications that need it. Use a browser key restricted by domain for Maps JavaScript and Places, and a separate server key restricted by IP for explicit reverse geocoding:

For the complete Google Cloud, credentials, security, validation, and troubleshooting setup, see the [Google Maps integration guide](GOOGLE_MAPS.md).

```dotenv
FILAMENT_LOCATION_GOOGLE_ENABLED=true
FILAMENT_LOCATION_GOOGLE_BROWSER_KEY=
FILAMENT_LOCATION_GOOGLE_SERVER_KEY=
FILAMENT_LOCATION_GOOGLE_EMBED_KEY=
FILAMENT_LOCATION_GOOGLE_MAP_ID=
FILAMENT_LOCATION_GOOGLE_REVERSE_GEOCODING_CACHE_TTL=86400
```

The picker loads its Alpine component on demand and only initializes Google Maps after the field is actually visible:

```php
use Dominasys\FilamentLocation\Forms\Components\GooglePlacePicker;

GooglePlacePicker::make('google_place')
    ->bindVenueNameField('venue_name')
    ->bindAddressField('formatted_address')
    ->bindPostalCodeField('postal_code')
    ->bindStateField('state')
    ->bindStateCodeField('state_code')
    ->bindCityField('city')
    ->bindCityCodeField('city_code')
    ->bindLatitudeField('latitude')
    ->bindLongitudeField('longitude')
    ->bindPlaceIdField('place_id')
    ->bindSourceField('location_source')
    ->bindPrecisionField('location_precision')
    ->placeSource('google')
    ->pinSource('google_pin')
    ->placePrecision('rooftop')
    ->pinPrecision('user_selected');
```

Dragging the pin only changes coordinates, source, and precision. Reverse geocoding is never triggered during hydration or automatically after dragging; applications must invoke `ReverseGeocodingServiceContract::reverse()` from an explicit user action.

If you want the city select to store the label while also syncing a hidden code field:

```php
City::make('city')
    ->bindCityCodeField('city_ibge')
    ->bindCityLabelField('city')
    ->useLabelAsValue();
```

## Supported Countries

Today the package ships with support for Brazil (`BR`).

The address dataset is country-based, so additional countries can be added without changing the public component API.

## Extending With New Countries

To add support for another country, follow the same pattern used for Brazil:

1. Add a new address data source implementation for the country.
2. Register the new source in the source factory.
3. Generate the country dataset in the canonical JSON format used by the package.
4. Add or update translations if the country needs localized messages.
5. Add tests for lookup, state/city options, and dataset sync.

The package is intentionally structured so the UI components keep the same API even when the underlying data source changes.

## Testing

Run the full local quality gate with:

```bash
composer check
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

If you want to add support for a new country, keep the implementation country-driven and avoid adding Brazil-specific assumptions to shared code.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Bruno Mangilli](https://github.com/nobrudevz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
