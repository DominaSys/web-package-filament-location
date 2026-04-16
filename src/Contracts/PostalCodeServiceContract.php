<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Contracts;

use Dominasys\FilamentLocation\Data\PostalCodeLookupResult;

interface PostalCodeServiceContract
{
    public function lookup(string $postalCode): PostalCodeLookupResult;
}
