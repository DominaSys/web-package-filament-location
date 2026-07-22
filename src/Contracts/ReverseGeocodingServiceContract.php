<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Contracts;

use Dominasys\FilamentLocation\Data\GooglePlaceData;

interface ReverseGeocodingServiceContract
{
    public function reverse(float $latitude, float $longitude): GooglePlaceData;
}
