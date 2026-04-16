<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Data;

final readonly class AddressDataSyncResult
{
    public function __construct(
        public string $countryCode,
        public string $sourceKey,
        public string $filePath,
        public int $statesCount,
        public int $citiesCount,
        public string $syncedAt,
    ) {}
}
