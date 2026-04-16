#!/usr/bin/env php
<?php

declare(strict_types=1);

use Dominasys\FilamentLocation\FilamentLocationServiceProvider;
use Dominasys\FilamentLocation\Services\AddressDataSyncService;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\Foundation\Application;

require __DIR__ . '/../vendor/autoload.php';

$bootstrapCacheDirectory = dirname(__DIR__) . '/bootstrap/cache';

if (! is_dir($bootstrapCacheDirectory) && ! mkdir($bootstrapCacheDirectory, 0755, true) && ! is_dir($bootstrapCacheDirectory)) {
    fwrite(STDERR, sprintf("Unable to create bootstrap cache directory: %s\n", $bootstrapCacheDirectory));
    exit(1);
}

$arguments = array_values(array_slice($argv, 1));
$countryCode = 'BR';
$sourceKey = null;

for ($i = 0; $i < count($arguments); $i++) {
    $argument = trim((string) $arguments[$i]);

    if ($argument === '') {
        continue;
    }

    if ($argument === '--help' || $argument === '-h') {
        fwrite(STDOUT, "Uso: php scripts/sync-address-data.php [country] [--source=source]\n");
        exit(0);
    }

    if (str_starts_with($argument, '--source=')) {
        $sourceKey = trim(substr($argument, 9));
        $sourceKey = $sourceKey !== '' ? $sourceKey : null;

        continue;
    }

    if ($argument === '--source') {
        $nextArgument = trim((string) ($arguments[$i + 1] ?? ''));
        if ($nextArgument !== '' && ! str_starts_with($nextArgument, '--')) {
            $sourceKey = $nextArgument;
            $i++;
        }

        continue;
    }

    if (str_starts_with($argument, '--')) {
        continue;
    }

    $countryCode = strtoupper($argument);
}

$app = Application::create(
    basePath: dirname(__DIR__),
    options: [
        'extra' => [
            'providers' => [
                FilamentLocationServiceProvider::class,
            ],
            'dont-discover' => ['*'],
        ],
    ]
);

Facade::setFacadeApplication($app);
$app['config']->set('cache.default', 'array');

try {
    $result = $app->make(AddressDataSyncService::class)->sync($countryCode, $sourceKey);

    fwrite(STDOUT, sprintf(
        "Sincronização concluída para %s usando a fonte %s.\nArquivo: %s\nEstados: %d\nCidades: %d\nSincronizado em: %s\n",
        $result->countryCode,
        $result->sourceKey,
        $result->filePath,
        $result->statesCount,
        $result->citiesCount,
        $result->syncedAt,
    ));

    exit(0);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);

    exit(1);
}
