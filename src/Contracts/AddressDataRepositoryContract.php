<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Contracts;

interface AddressDataRepositoryContract
{
    /**
     * @return array<string, string>
     */
    public function states(?string $countryCode = null): array;

    /**
     * @return array<string, string>
     */
    public function cities(?string $countryCode = null, ?string $stateCode = null): array;
}
