<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Data\GooglePlaceData;
use Illuminate\Support\Arr;

final class GooglePlaceNormalizer
{
    /**
     * @param  array<string, mixed>  $place
     */
    public function normalize(array $place): GooglePlaceData
    {
        $components = $this->components($place);

        return new GooglePlaceData(
            placeId: $this->string($place, ['place_id', 'id']),
            name: $this->string($place, ['name']),
            formattedAddress: $this->string($place, ['formatted_address', 'formattedAddress']),
            latitude: $this->coordinate($place, ['geometry.location.lat', 'location.lat', 'latitude']),
            longitude: $this->coordinate($place, ['geometry.location.lng', 'location.lng', 'longitude']),
            postalCode: $this->component($components, 'postal_code') ?? $this->string($place, ['postal_code']),
            country: $this->component($components, 'country') ?? $this->string($place, ['country']),
            countryCode: $this->component($components, 'country', short: true) ?? $this->string($place, ['country_code']),
            state: $this->component($components, 'administrative_area_level_1') ?? $this->string($place, ['state']),
            stateCode: $this->component($components, 'administrative_area_level_1', short: true) ?? $this->string($place, ['state_code']),
            city: $this->component($components, 'administrative_area_level_2')
                ?? $this->component($components, 'locality')
                ?? $this->component($components, 'postal_town')
                ?? $this->string($place, ['city']),
            cityCode: $this->string($place, ['city_code']),
            neighborhood: $this->component($components, 'sublocality_level_1')
                ?? $this->component($components, 'neighborhood')
                ?? $this->string($place, ['neighborhood']),
            street: $this->component($components, 'route') ?? $this->string($place, ['street']),
            number: $this->component($components, 'street_number') ?? $this->string($place, ['number']),
            source: $this->string($place, ['source']) ?? 'google',
            precision: strtolower($this->string($place, ['geometry.location_type', 'precision']) ?? 'rooftop'),
        );
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<int, array<string, mixed>>
     */
    private function components(array $place): array
    {
        $components = Arr::get($place, 'address_components', Arr::get($place, 'addressComponents', []));

        return is_array($components) ? array_values(array_filter($components, 'is_array')) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    private function component(array $components, string $type, bool $short = false): ?string
    {
        foreach ($components as $component) {
            $types = Arr::get($component, 'types', []);

            if (! is_array($types) || ! in_array($type, $types, true)) {
                continue;
            }

            return $this->string($component, $short
                ? ['short_name', 'shortText', 'long_name', 'longText']
                : ['long_name', 'longText', 'short_name', 'shortText']);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function string(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function coordinate(array $data, array $keys): float
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }
}
