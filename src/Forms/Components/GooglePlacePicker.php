<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class GooglePlacePicker extends Field
{
    protected string $view = 'filament-location::forms.components.google-place-picker';

    protected bool | Closure $googleEnabled = true;

    protected int | Closure $mapHeight = 360;

    protected int | Closure $zoom = 16;

    protected string | Closure | null $searchPlaceholder = null;

    protected string | Closure $placeSource = 'google';

    protected string | Closure $pinSource = 'google_pin';

    protected string | Closure $placePrecision = 'rooftop';

    protected string | Closure $pinPrecision = 'user_selected';

    /**
     * @var array<string, string>
     */
    protected array $bindings = [
        'name' => 'name',
        'formatted_address' => 'formatted_address',
        'postal_code' => 'postal_code',
        'country' => 'country',
        'country_code' => 'country_code',
        'state' => 'state',
        'state_code' => 'state_code',
        'city' => 'city',
        'city_code' => 'city_code',
        'neighborhood' => 'neighborhood',
        'street' => 'street',
        'number' => 'number',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'place_id' => 'place_id',
        'source' => 'location_source',
        'precision' => 'location_precision',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.google.label'));
        $this->helperText(__('filament-location::location.google.helper'));
        $this->dehydrated(false);
        $this->visible(fn (): bool => $this->isGoogleEnabled());
    }

    public function googleEnabled(bool | Closure $condition = true): static
    {
        $this->googleEnabled = $condition;

        return $this;
    }

    public function mapHeight(int | Closure $height): static
    {
        $this->mapHeight = $height;

        return $this;
    }

    public function zoom(int | Closure $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function searchPlaceholder(string | Closure | null $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    public function placeSource(string | Closure $source): static
    {
        $this->placeSource = $source;

        return $this;
    }

    public function pinSource(string | Closure $source): static
    {
        $this->pinSource = $source;

        return $this;
    }

    public function placePrecision(string | Closure $precision): static
    {
        $this->placePrecision = $precision;

        return $this;
    }

    public function pinPrecision(string | Closure $precision): static
    {
        $this->pinPrecision = $precision;

        return $this;
    }

    public function bindNameField(string $field): static
    {
        return $this->bind('name', $field);
    }

    public function bindVenueNameField(string $field): static
    {
        return $this->bindNameField($field);
    }

    public function bindFormattedAddressField(string $field): static
    {
        return $this->bind('formatted_address', $field);
    }

    public function bindAddressField(string $field): static
    {
        return $this->bindFormattedAddressField($field);
    }

    public function bindPostalCodeField(string $field): static
    {
        return $this->bind('postal_code', $field);
    }

    public function bindCountryField(string $field): static
    {
        return $this->bind('country', $field);
    }

    public function bindCountryCodeField(string $field): static
    {
        return $this->bind('country_code', $field);
    }

    public function bindStateField(string $field): static
    {
        return $this->bind('state', $field);
    }

    public function bindStateCodeField(string $field): static
    {
        return $this->bind('state_code', $field);
    }

    public function bindCityField(string $field): static
    {
        return $this->bind('city', $field);
    }

    public function bindCityCodeField(string $field): static
    {
        return $this->bind('city_code', $field);
    }

    public function bindNeighborhoodField(string $field): static
    {
        return $this->bind('neighborhood', $field);
    }

    public function bindStreetField(string $field): static
    {
        return $this->bind('street', $field);
    }

    public function bindNumberField(string $field): static
    {
        return $this->bind('number', $field);
    }

    public function bindLatitudeField(string $field): static
    {
        return $this->bind('latitude', $field);
    }

    public function bindLongitudeField(string $field): static
    {
        return $this->bind('longitude', $field);
    }

    public function bindPlaceIdField(string $field): static
    {
        return $this->bind('place_id', $field);
    }

    public function bindSourceField(string $field): static
    {
        return $this->bind('source', $field);
    }

    public function bindPrecisionField(string $field): static
    {
        return $this->bind('precision', $field);
    }

    public function isGoogleEnabled(): bool
    {
        return (bool) config('location.google.enabled', false)
            && filled(config('location.google.browser_key'))
            && (bool) $this->evaluate($this->googleEnabled);
    }

    public function getMapHeight(): int
    {
        return max(200, (int) $this->evaluate($this->mapHeight));
    }

    public function getZoom(): int
    {
        return min(22, max(1, (int) $this->evaluate($this->zoom)));
    }

    public function getSearchPlaceholder(): string
    {
        return (string) ($this->evaluate($this->searchPlaceholder)
            ?? __('filament-location::location.google.search_placeholder'));
    }

    /** @return array{place_source: string, pin_source: string, place_precision: string, pin_precision: string} */
    public function getLocationMetadata(): array
    {
        return [
            'place_source' => (string) $this->evaluate($this->placeSource),
            'pin_source' => (string) $this->evaluate($this->pinSource),
            'place_precision' => (string) $this->evaluate($this->placePrecision),
            'pin_precision' => (string) $this->evaluate($this->pinPrecision),
        ];
    }

    public function getBinding(string $key): string
    {
        return $this->bindings[$key];
    }

    /**
     * @return array<string, string>
     */
    public function getBoundStatePaths(): array
    {
        $containerStatePath = $this->getContainer()->getStatePath();

        return collect($this->bindings)
            ->mapWithKeys(fn (string $field, string $key): array => [
                $key => filled($containerStatePath) ? "{$containerStatePath}.{$field}" : $field,
            ])
            ->all();
    }

    private function bind(string $key, string $field): static
    {
        $this->bindings[$key] = $field;

        return $this;
    }
}
