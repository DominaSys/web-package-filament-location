<?php

use Dominasys\FilamentLocation\Forms\Components\GooglePlacePicker;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component;

it('stays disabled by default and never exposes the server key', function () {
    config([
        'location.google.enabled' => false,
        'location.google.browser_key' => 'browser-key',
        'location.google.server_key' => 'server-secret',
    ]);

    $picker = GooglePlacePicker::make('location_picker');

    expect($picker->isGoogleEnabled())->toBeFalse()
        ->and(file_get_contents(__DIR__ . '/../../resources/views/forms/components/google-place-picker.blade.php'))
        ->not->toContain('server_key');
});

it('enables google only with the feature flag and browser key', function () {
    config([
        'location.google.enabled' => true,
        'location.google.browser_key' => 'browser-key',
    ]);

    expect(GooglePlacePicker::make('location_picker')->isGoogleEnabled())->toBeTrue()
        ->and(GooglePlacePicker::make('location_picker')->googleEnabled(false)->isGoogleEnabled())->toBeFalse();
});

it('keeps bindings opt in and configurable without changing postal code', function () {
    $picker = GooglePlacePicker::make('location_picker')
        ->bindNameField('venue_name')
        ->bindCityCodeField('ibge_code')
        ->bindLatitudeField('lat')
        ->bindLongitudeField('lng')
        ->placeSource('geocodificado')
        ->pinSource('manual')
        ->placePrecision('aproximada')
        ->pinPrecision('exata');

    $property = new ReflectionProperty($picker, 'bindings');
    $property->setAccessible(true);

    expect($property->getValue($picker))
        ->toMatchArray([
            'name' => 'venue_name',
            'city_code' => 'ibge_code',
            'latitude' => 'lat',
            'longitude' => 'lng',
        ])
        ->and($picker->getLocationMetadata())->toBe([
            'place_source' => 'geocodificado',
            'pin_source' => 'manual',
            'place_precision' => 'aproximada',
            'pin_precision' => 'exata',
        ]);
});

it('resolves bound paths in the current form scope and renders the lazy asset', function () {
    config([
        'location.google.enabled' => true,
        'location.google.browser_key' => 'browser-key',
        'location.google.server_key' => 'server-secret',
        'location.google.map_id' => 'map-id',
    ]);

    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];

        public function render(): string
        {
            return '';
        }
    };

    $picker = GooglePlacePicker::make('google_place')
        ->bindLatitudeField('lat')
        ->bindLongitudeField('lng');

    $schema = Schema::make($livewire)
        ->statePath('data')
        ->components([$picker]);

    $schema->fill(['lat' => -23.5, 'lng' => -46.6]);
    View::share('errors', new ViewErrorBag);

    expect($picker->getBoundStatePaths())
        ->toMatchArray([
            'latitude' => 'data.lat',
            'longitude' => 'data.lng',
        ]);

    expect($picker->toHtml())
        ->toContain('x-load-src=')
        ->toContain('x-load-css=')
        ->toContain('fi-input-wrp')
        ->toContain('browser-key')
        ->toContain('map-id')
        ->not->toContain('server-secret');
});

it('renders an explicit draggable pin and supports positioning it from the map', function () {
    $component = file_get_contents(__DIR__ . '/../../resources/js/dist/components/google-place-picker.js');

    expect($component)
        ->not->toContain('PinElement')
        ->not->toContain('content:')
        ->toContain('window.Alpine.raw(value)')
        ->toContain('const map = new Map')
        ->toContain("googleMaps.importLibrary('geocoding')")
        ->toContain('this.geocoder = new Geocoder()')
        ->toContain('const marker = new AdvancedMarkerElement')
        ->toContain('if (hasCoordinates)')
        ->toContain('marker.map = map')
        ->toContain('collisionBehavior: CollisionBehavior.REQUIRED')
        ->toContain('clickableIcons: true')
        ->toContain('event.stop?.()')
        ->toContain('if (event.placeId)')
        ->toContain('new Place({ id: event.placeId })')
        ->toContain('await this.reverseGeocodeCoordinates(event.latLng.lat(), event.latLng.lng())')
        ->toContain('normalizeGeocodedLocation(result, latitude, longitude, metadata)')
        ->toContain('name: null')
        ->toContain('this.clearAddressAtCoordinates(latitude, longitude)')
        ->toContain("fields: ['id', 'displayName', 'formattedAddress', 'location', 'addressComponents']")
        ->toContain("marker.addListener('dragend', async ()")
        ->toContain('await this.reverseGeocodeCoordinates(Number(latitude), Number(longitude))')
        ->not->toContain('console.info')
        ->not->toContain('console.warn')
        ->toContain('gmpDraggable: true')
        ->toContain("map.addListener('click'");
});
