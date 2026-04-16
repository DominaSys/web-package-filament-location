<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\PostalCodeServiceContract;
use Dominasys\FilamentLocation\Data\PostalCodeLookupResult;

final readonly class UnsupportedCountryPostalCodeService implements PostalCodeServiceContract
{
    public function __construct(
        private ?string $countryCode = null,
    ) {}

    public function lookup(string $postalCode): PostalCodeLookupResult
    {
        return PostalCodeLookupResult::unsupportedCountry(
            postalCode: $postalCode,
            countryCode: $this->countryCode,
        );
    }
}
