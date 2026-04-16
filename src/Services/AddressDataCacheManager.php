<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class AddressDataCacheManager
{
    public function forgetCountry(string $countryCode): void
    {
        Cache::forget($this->keyForCountry($countryCode));
    }

    public function keyForCountry(string $countryCode): string
    {
        $prefix = trim((string) config('location.address_data_cache_key_prefix', 'filament-location.address-data'));

        return $prefix . '.' . Str::lower($countryCode);
    }
}
