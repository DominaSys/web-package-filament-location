<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Commands;

use Dominasys\FilamentLocation\Services\AddressDataSyncService;
use Illuminate\Console\Command;
use Throwable;

final class SyncAddressDataCommand extends Command
{
    protected $signature = 'filament-location:sync-address-data
        {country=BR : Código do país a sincronizar}
        {--source= : Fonte de sincronização opcional}';

    protected $description = 'Sincroniza os dados de estados e cidades para um país';

    public function handle(AddressDataSyncService $syncService): int
    {
        $countryCode = strtoupper(trim((string) $this->argument('country')));
        $sourceKey = trim((string) $this->option('source'));
        $sourceKey = $sourceKey !== '' ? $sourceKey : null;

        try {
            $result = $syncService->sync($countryCode, $sourceKey);

            $this->info(sprintf(
                'Sincronização concluída para %s usando a fonte %s.',
                $result->countryCode,
                $result->sourceKey
            ));

            $this->table(
                ['País', 'Fonte', 'Estados', 'Cidades', 'Arquivo', 'Sincronizado em'],
                [[
                    'country' => $result->countryCode,
                    'source' => $result->sourceKey,
                    'states' => $result->statesCount,
                    'cities' => $result->citiesCount,
                    'file' => $result->filePath,
                    'synced_at' => $result->syncedAt,
                ]]
            );

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }
}
