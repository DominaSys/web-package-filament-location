<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Contracts;

interface AddressDataSourceFactoryContract
{
    public function make(string $countryCode, ?string $sourceKey = null): AddressDataSourceContract;
}
