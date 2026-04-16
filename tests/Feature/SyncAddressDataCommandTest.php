<?php

use Dominasys\FilamentLocation\Tests\Support\FakeAddressDataSource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('runs the sync command successfully', function () {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filament-location-command-' . Str::uuid();
    File::ensureDirectoryExists($directory);

    config([
        'location.address_data_path' => $directory,
        'location.address_data_sync.default_source' => 'fake',
        'location.address_data_sync.sources.br.fake.class' => FakeAddressDataSource::class,
    ]);

    Cache::shouldReceive('forget')->once()->with('filament-location.address-data.br');

    $exitCode = Artisan::call('filament-location:sync-address-data', [
        'country' => 'BR',
        '--source' => 'fake',
    ]);

    expect($exitCode)->toBe(0)
        ->and(File::exists($directory . DIRECTORY_SEPARATOR . 'br.json'))->toBeTrue();

    File::deleteDirectory($directory);
});
