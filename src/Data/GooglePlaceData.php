<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Data;

final readonly class GooglePlaceData
{
    public function __construct(
        public ?string $placeId,
        public ?string $name,
        public ?string $formattedAddress,
        public float $latitude,
        public float $longitude,
        public ?string $postalCode,
        public ?string $country,
        public ?string $countryCode,
        public ?string $state,
        public ?string $stateCode,
        public ?string $city,
        public ?string $cityCode,
        public ?string $neighborhood,
        public ?string $street,
        public ?string $number,
        public string $source = 'google',
        public string $precision = 'rooftop',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            placeId: self::nullableString($data['place_id'] ?? null),
            name: self::nullableString($data['name'] ?? null),
            formattedAddress: self::nullableString($data['formatted_address'] ?? null),
            latitude: (float) ($data['latitude'] ?? 0),
            longitude: (float) ($data['longitude'] ?? 0),
            postalCode: self::nullableString($data['postal_code'] ?? null),
            country: self::nullableString($data['country'] ?? null),
            countryCode: self::nullableString($data['country_code'] ?? null),
            state: self::nullableString($data['state'] ?? null),
            stateCode: self::nullableString($data['state_code'] ?? null),
            city: self::nullableString($data['city'] ?? null),
            cityCode: self::nullableString($data['city_code'] ?? null),
            neighborhood: self::nullableString($data['neighborhood'] ?? null),
            street: self::nullableString($data['street'] ?? null),
            number: self::nullableString($data['number'] ?? null),
            source: self::nullableString($data['source'] ?? null) ?? 'google',
            precision: self::nullableString($data['precision'] ?? null) ?? 'rooftop',
        );
    }

    /**
     * @return array<string, float|string|null>
     */
    public function toArray(): array
    {
        return [
            'place_id' => $this->placeId,
            'name' => $this->name,
            'formatted_address' => $this->formattedAddress,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'state' => $this->state,
            'state_code' => $this->stateCode,
            'city' => $this->city,
            'city_code' => $this->cityCode,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'number' => $this->number,
            'source' => $this->source,
            'precision' => $this->precision,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
