<?php

use Dominasys\FilamentLocation\Contracts\AddressDataSourceContract;
use Dominasys\FilamentLocation\Contracts\AddressDataSourceFactoryContract;
use Dominasys\FilamentLocation\Data\AddressDataSyncResult;
use Dominasys\FilamentLocation\Services\AddressDataCacheManager;
use Dominasys\FilamentLocation\Services\AddressDataPathResolver;
use Dominasys\FilamentLocation\Services\AddressDataSourceFactory;
use Dominasys\FilamentLocation\Services\AddressDataSyncService;
use Dominasys\FilamentLocation\Services\AddressDataWriter;
use Dominasys\FilamentLocation\Tests\Support\FakeAddressDataSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function syncDatasetTempDirectory(): string
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filament-location-sync-' . Str::uuid();
    File::ensureDirectoryExists($path);

    return $path;
}

it('syncs and writes a rich dataset file', function () {
    $directory = syncDatasetTempDirectory();

    config([
        'location.address_data_path' => $directory,
        'location.address_data_sync.default_source' => 'fake',
        'location.address_data_sync.sources.br.fake.class' => FakeAddressDataSource::class,
    ]);

    Cache::shouldReceive('forget')->once()->with('filament-location.address-data.br');

    $service = new AddressDataSyncService(
        new AddressDataSourceFactory,
        new AddressDataWriter(new AddressDataPathResolver),
        new AddressDataCacheManager,
    );

    $result = $service->sync('BR');

    expect($result)->toBeInstanceOf(AddressDataSyncResult::class)
        ->and($result->countryCode)->toBe('BR')
        ->and($result->sourceKey)->toBe('fake')
        ->and($result->statesCount)->toBe(1)
        ->and($result->citiesCount)->toBe(1);

    $data = json_decode(File::get($directory . DIRECTORY_SEPARATOR . 'br.json'), true);

    expect($data)
        ->toMatchArray([
            'country_code' => 'BR',
            'source' => 'fake',
        ])
        ->and($data['states'][0]['code'])->toBe('SP')
        ->and($data['states'][0]['identifiers']['source_id'])->toBe('35')
        ->and($data['states'][0]['cities'][0]['code'])->toBe('3509502')
        ->and(File::get($directory . DIRECTORY_SEPARATOR . 'br.json'))->not->toContain("\n");

    File::deleteDirectory($directory);
});

it('refuses to sync when the country is not configured', function () {
    $directory = syncDatasetTempDirectory();

    config([
        'location.address_data_path' => $directory,
        'location.address_data_sync.default_source' => 'fake',
    ]);

    $service = new AddressDataSyncService(
        new AddressDataSourceFactory,
        new AddressDataWriter(new AddressDataPathResolver),
        new AddressDataCacheManager,
    );

    expect(fn () => $service->sync('US'))
        ->toThrow(InvalidArgumentException::class);

    File::deleteDirectory($directory);
});

it('keeps the previous dataset when sync fails', function () {
    $directory = syncDatasetTempDirectory();
    $path = $directory . DIRECTORY_SEPARATOR . 'br.json';

    File::put($path, json_encode([
        'country_code' => 'BR',
        'source' => 'legacy',
        'states' => [],
    ]));

    config([
        'location.address_data_path' => $directory,
        'location.address_data_sync.default_source' => 'fake',
        'location.address_data_sync.sources.br.fake.class' => FakeAddressDataSource::class,
    ]);

    $failingFactory = new class implements AddressDataSourceFactoryContract
    {
        public function make(string $countryCode, ?string $sourceKey = null): AddressDataSourceContract
        {
            throw new RuntimeException('boom');
        }
    };

    $service = new AddressDataSyncService(
        $failingFactory,
        new AddressDataWriter(new AddressDataPathResolver),
        new AddressDataCacheManager,
    );

    expect(fn () => $service->sync('BR'))
        ->toThrow(RuntimeException::class, 'boom');

    expect(json_decode(File::get($path), true)['source'])->toBe('legacy');

    File::deleteDirectory($directory);
});
