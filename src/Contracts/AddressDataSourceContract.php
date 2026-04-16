<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Contracts;

interface AddressDataSourceContract
{
    public function sourceKey(): string;

    public function supportsCountry(string $countryCode): bool;

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $countryCode): array;
}
