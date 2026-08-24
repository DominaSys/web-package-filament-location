<?php

use Dominasys\FilamentLocation\Forms\Components\GooglePlacePicker;
use Dominasys\FilamentLocation\Support\Assets\ContentVersionedAlpineComponent;
use Dominasys\FilamentLocation\Support\Assets\ContentVersionedCss;
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

it('versions lazy assets from their contents', function () {
    $javascriptPath = __DIR__ . '/../../resources/js/dist/components/google-place-picker.js';
    $cssPath = __DIR__ . '/../../resources/css/google-place-picker.css';

    expect(ContentVersionedAlpineComponent::make('google-place-picker', $javascriptPath)->getVersion())
        ->toBe(hash_file('sha256', $javascriptPath))
        ->and(ContentVersionedCss::make('google-place-picker', $cssPath)->getVersion())
        ->toBe(hash_file('sha256', $cssPath));
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
        ])
        ->and($picker->storeSelectionState(false)->shouldStoreSelectionState())->toBeFalse();
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
        ->toContain('Buscando locais…')
        ->toContain('Nenhum local encontrado. Tente informar mais detalhes.')
        ->toContain('aria-label="Sugestões de locais"')
        ->toContain('x-bind:disabled="isSelecting"')
        ->toContain('x-bind:aria-busy="isSelecting"')
        ->toContain('Carregando local no mapa…')
        ->toContain('fi-loading-indicator')
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
        ->toContain('this.autocompleteSuggestion.fetchAutocompleteSuggestions')
        ->toContain('const predictionsByPlaceId = new Map()')
        ->toContain('predictionsByPlaceId.set(prediction.placeId, prediction)')
        ->toContain('mainText: prediction.mainText?.text')
        ->toContain('predictionsByPlaceId.get(suggestion?.placeId)')
        ->toContain('await this.selectPlace(prediction.toPlace(), suggestion.mainText || null)')
        ->toContain('const displayName = place.displayName?.text ?? place.displayName ?? null')
        ->toContain('name: predictionText || displayName')
        ->toContain('window.Livewire.find(livewireId)')
        ->toContain('await wire.$set(statePath, data, false)')
        ->toContain('await wire.$set(path, data[key] ?? null, false)')
        ->toContain('this.syncBoundInputs(data)')
        ->toContain('input.value = data[key] ??')
        ->not->toContain('this.$wire.set(')
        ->toContain('this.isSelecting = true')
        ->toContain('this.isSelecting = false')
        ->toContain('const root = this.$root')
        ->toContain("this.dispatchLocationEvent(root, 'filament-location:place-selected', data)")
        ->toContain("this.dispatchLocationEvent(root, 'filament-location:pin-moved', data)")
        ->toContain('if (!root?.isConnected)')
        ->toContain('root.dispatchEvent(new CustomEvent(name')
        ->not->toContain('this.$root.dispatchEvent')
        ->toContain('new this.autocompleteSessionTokenClass()')
        ->toContain('input.length < 3')
        ->toContain('requestId !== this.latestAutocompleteRequest')
        ->toContain("this.error = 'Não foi possível buscar locais agora. Tente novamente.'")
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
        ->toContain('await this.syncLocationData(root, data)')
        ->toContain('if (statePath)')
        ->toContain('name: null')
        ->toContain('await this.clearAddressAtCoordinates(root, latitude, longitude)')
        ->toContain("fields: ['id', 'displayName', 'formattedAddress', 'location', 'addressComponents']")
        ->toContain("marker.addListener('dragend', async ()")
        ->toContain('await this.reverseGeocodeCoordinates(Number(latitude), Number(longitude))')
        ->not->toContain('console.info')
        ->not->toContain('console.warn')
        ->toContain('gmpDraggable: true')
        ->toContain("map.addListener('click'");
});
