<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\AddressDataSourceFactoryContract;
use Dominasys\FilamentLocation\Data\AddressDataSyncResult;
use RuntimeException;

final readonly class AddressDataSyncService
{
    public function __construct(
        private AddressDataSourceFactoryContract $sourceFactory,
        private AddressDataWriter $writer,
        private AddressDataCacheManager $cacheManager,
    ) {}

    public function sync(string $countryCode, ?string $sourceKey = null): AddressDataSyncResult
    {
        $normalizedCountryCode = $this->normalizeCountryCode($countryCode);
        $source = $this->sourceFactory->make($normalizedCountryCode, $sourceKey);
        $dataset = $source->fetch($normalizedCountryCode);

        $this->assertValidDataset($normalizedCountryCode, $dataset);

        $dataset['synced_at'] = now()->toIso8601String();
        $dataset['country_code'] = $normalizedCountryCode;
        $dataset['source'] = $source->sourceKey();

        $filePath = $this->writer->write($normalizedCountryCode, $dataset);
        $this->cacheManager->forgetCountry($normalizedCountryCode);

        return new AddressDataSyncResult(
            countryCode: $normalizedCountryCode,
            sourceKey: $source->sourceKey(),
            filePath: $filePath,
            statesCount: $this->countStates($dataset),
            citiesCount: $this->countCities($dataset),
            syncedAt: $dataset['synced_at'],
        );
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function assertValidDataset(string $countryCode, array $dataset): void
    {
        if ($dataset === []) {
            throw new RuntimeException(sprintf('Empty address dataset returned for country "%s".', $countryCode));
        }

        $datasetCountryCode = strtoupper(trim((string) ($dataset['country_code'] ?? '')));

        if ($datasetCountryCode !== '' && $datasetCountryCode !== $countryCode) {
            throw new RuntimeException(sprintf(
                'The dataset country code "%s" does not match the requested country "%s".',
                $datasetCountryCode,
                $countryCode
            ));
        }

        if (! isset($dataset['states']) || ! is_array($dataset['states']) || $dataset['states'] === []) {
            throw new RuntimeException(sprintf('The address dataset for country "%s" does not contain states.', $countryCode));
        }
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function countStates(array $dataset): int
    {
        return isset($dataset['states']) && is_array($dataset['states']) ? count($dataset['states']) : 0;
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function countCities(array $dataset): int
    {
        if (! isset($dataset['states']) || ! is_array($dataset['states'])) {
            return 0;
        }

        $total = 0;

        foreach ($dataset['states'] as $state) {
            if (is_array($state) && isset($state['cities']) && is_array($state['cities'])) {
                $total += count($state['cities']);
            }
        }

        return $total;
    }

    private function normalizeCountryCode(string $countryCode): string
    {
        return strtoupper(trim($countryCode));
    }
}
