# Google Maps integration

Google Maps support is optional and disabled by default. Applications without Google credentials continue using the regular address fields.

## Google Cloud setup

Create or select a Google Cloud project, attach a billing account, and enable:

- Maps JavaScript API
- Places API (New)
- Geocoding API
- Maps Embed API, only when the consuming application renders public embedded maps

### Map ID

In Google Maps Platform, open **Map Management** and create a Map ID with:

- Map type: JavaScript
- Rendering: Vector
- Tilt and rotation: optional

Advanced markers require this Map ID.

### Browser key

Create an API key for browser requests:

- Application restriction: Websites
- API restrictions: Maps JavaScript API and Places API (New)
- Website restrictions: only the local, staging, and production origins that render the picker

Examples:

```text
https://app.example.test/*
https://example.com/*
https://*.example.com/*
```

The wildcard entry covers subdomains, not necessarily the root domain. Add both when both are used.

### Server key

Create a separate key for server-side reverse geocoding:

- Application restriction: IP addresses
- API restriction: Geocoding API
- IP restrictions: public outbound IP addresses of the application servers

The server key must never be exposed to browser code or committed to source control. An unrestricted key may be used temporarily in local development, but must not be used in production.

### Embed key

When the consuming application renders Google Maps Embed, create another browser key:

- Application restriction: Websites
- API restriction: Maps Embed API
- Website restrictions: only origins that render the embedded map

The package itself does not render the public embed. This key is available for consuming applications.

## Environment configuration

Add credentials to the consuming application's `.env`:

```dotenv
FILAMENT_LOCATION_GOOGLE_ENABLED=true
FILAMENT_LOCATION_GOOGLE_BROWSER_KEY=
FILAMENT_LOCATION_GOOGLE_SERVER_KEY=
FILAMENT_LOCATION_GOOGLE_EMBED_KEY=
FILAMENT_LOCATION_GOOGLE_MAP_ID=
FILAMENT_LOCATION_GOOGLE_LANGUAGE=pt-BR
FILAMENT_LOCATION_GOOGLE_REGION=BR
FILAMENT_LOCATION_GOOGLE_CONNECT_TIMEOUT=5
FILAMENT_LOCATION_GOOGLE_TIMEOUT=10
FILAMENT_LOCATION_GOOGLE_REVERSE_GEOCODING_CACHE_TTL=86400
```

Then clear cached configuration:

```bash
php artisan optimize:clear
```

Rebuild frontend assets when the consuming application includes package-related styling in its own asset bundle:

```bash
npm run build
```

## Filament field

```php
use Dominasys\FilamentLocation\Forms\Components\GooglePlacePicker;

GooglePlacePicker::make('google_place')
    ->bindVenueNameField('venue_name')
    ->bindAddressField('formatted_address')
    ->bindPostalCodeField('postal_code')
    ->bindCountryField('country')
    ->bindStateField('state')
    ->bindStateCodeField('state_code')
    ->bindCityField('city')
    ->bindCityCodeField('city_code')
    ->bindNeighborhoodField('neighborhood')
    ->bindStreetField('street')
    ->bindNumberField('number')
    ->bindLatitudeField('latitude')
    ->bindLongitudeField('longitude')
    ->bindPlaceIdField('place_id')
    ->bindSourceField('location_source')
    ->bindPrecisionField('location_precision')
    ->placeSource('google')
    ->pinSource('google_pin')
    ->placePrecision('approximate')
    ->pinPrecision('exact');
```

The Google script loads on demand. The map is initialized only when the field becomes visible, which avoids creating a billable map session while a containing tab is closed.

Selecting a Places result updates the configured address bindings and coordinates. Dragging the pin or clicking the map updates only latitude, longitude, source, and precision. Neither action executes reverse geocoding.

## Reverse geocoding

Resolve `ReverseGeocodingServiceContract` only from an explicit application action, such as a **Locate again** button. Do not call it during hydration or rendering.

Responses are cached using normalized coordinates. Timeout and cache duration use the environment values above.

## Validation checklist

1. Open the form section or tab containing the picker.
2. Confirm the map is not requested before the field becomes visible.
3. Search for a venue or address.
4. Select a Places suggestion.
5. Confirm bound address fields and coordinates are filled.
6. Drag the marker or click the map and confirm coordinates, source, and precision change.
7. Save and reload the record.
8. When applicable, validate the public embed separately.

## Troubleshooting

### `Places API (New) has not been used ... or it is disabled`

Enable **Places API (New)** in the same Google Cloud project as the browser key. Also add it to that key's API restrictions. Propagation may take several minutes.

### `RefererNotAllowedMapError`

Add the exact page origin to the browser key's website restrictions. Check scheme, domain, subdomain, and port.

### `ApiNotActivatedMapError`

Enable the API named by the browser error in the same project as the credential.

### The map loads, but search returns `403`

The Maps JavaScript API may be enabled while Places API (New) is disabled or excluded from the browser key.

### Server reverse geocoding returns `403`

Confirm Geocoding API is enabled, allowed on the server key, and the request originates from an allowed public outbound IP.

## Security and cost controls

- Never reuse the server key in browser requests.
- Restrict every production key by API and application type.
- Keep separate keys for browser maps, server geocoding, and embeds.
- Configure Google Cloud budgets, quota limits, and billing alerts.
- Register only required production, staging, and development origins.
- Remove temporary unrestricted keys after local validation.
