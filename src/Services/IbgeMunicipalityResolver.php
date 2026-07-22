<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Illuminate\Support\Str;

final readonly class IbgeMunicipalityResolver
{
    public function __construct(private AddressDataRepositoryContract $addresses) {}

    public function resolve(?string $city, ?string $stateCode, ?string $countryCode = 'BR'): ?string
    {
        if (strtoupper(trim((string) $countryCode)) !== 'BR' || blank($city) || blank($stateCode)) {
            return null;
        }

        $expectedCity = $this->normalize((string) $city);

        foreach ($this->addresses->cities('BR', $stateCode) as $code => $name) {
            if ($this->normalize($name) === $expectedCity) {
                return (string) $code;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->toString();
    }
}
