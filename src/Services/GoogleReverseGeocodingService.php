<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use DomainException;
use Dominasys\FilamentLocation\Contracts\ReverseGeocodingServiceContract;
use Dominasys\FilamentLocation\Data\GooglePlaceData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class GoogleReverseGeocodingService implements ReverseGeocodingServiceContract
{
    public function __construct(
        private GooglePlaceNormalizer $normalizer,
        private IbgeMunicipalityResolver $ibgeMunicipalityResolver,
    ) {}

    public function reverse(float $latitude, float $longitude): GooglePlaceData
    {
        $key = config('location.google.server_key');

        if (! config('location.google.enabled', false) || blank($key)) {
            throw new DomainException('Google reverse geocoding is not configured.');
        }

        $cacheKey = sprintf(
            'filament-location.google.reverse.%s',
            hash('xxh128', sprintf(
                '%.8F,%.8F|%s|%s',
                $latitude,
                $longitude,
                (string) config('location.google.language', 'pt-BR'),
                (string) config('location.google.region', 'BR'),
            )),
        );

        $cached = Cache::remember(
            $cacheKey,
            max(1, (int) config('location.google.reverse_geocoding_cache_ttl', 86400)),
            fn (): array => $this->reverseWithoutCache($latitude, $longitude, (string) $key)->toArray(),
        );

        return GooglePlaceData::fromArray($cached);
    }

    private function reverseWithoutCache(float $latitude, float $longitude, string $key): GooglePlaceData
    {
        $response = Http::connectTimeout((int) config('location.google.connect_timeout', 5))
            ->timeout((int) config('location.google.timeout', 10))
            ->retry([150, 350], when: function (Throwable $exception, PendingRequest $request): bool {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                return $exception instanceof RequestException
                    && ($exception->response->status() === 429 || $exception->response->serverError());
            })
            ->get((string) config('location.google.geocoding_url'), [
                'key' => $key,
                'latlng' => sprintf('%.8F,%.8F', $latitude, $longitude),
                'language' => config('location.google.language', 'pt-BR'),
                'region' => config('location.google.region', 'BR'),
            ])
            ->throw();

        if ($response->json('status') !== 'OK' || ! is_array($result = $response->json('results.0'))) {
            throw new DomainException('Google could not resolve an address for the selected point.');
        }

        $place = $this->normalizer->normalize($result);
        $cityCode = $this->ibgeMunicipalityResolver->resolve($place->city, $place->stateCode, $place->countryCode);

        return new GooglePlaceData(
            placeId: $place->placeId,
            name: $place->name,
            formattedAddress: $place->formattedAddress,
            latitude: $place->latitude,
            longitude: $place->longitude,
            postalCode: $place->postalCode,
            country: $place->country,
            countryCode: $place->countryCode,
            state: $place->state,
            stateCode: $place->stateCode,
            city: $place->city,
            cityCode: $cityCode,
            neighborhood: $place->neighborhood,
            street: $place->street,
            number: $place->number,
            source: $place->source,
            precision: $place->precision,
        );
    }
}
