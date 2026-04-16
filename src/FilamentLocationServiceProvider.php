<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation;

use Dominasys\FilamentLocation\Commands\SyncAddressDataCommand;
use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Dominasys\FilamentLocation\Contracts\AddressDataSourceFactoryContract;
use Dominasys\FilamentLocation\Services\AddressDataSourceFactory;
use Dominasys\FilamentLocation\Services\JsonAddressDataRepository;
use Dominasys\FilamentLocation\Testing\TestsFilamentLocation;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLocationServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-location';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('dominasys/filament-location');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath(sprintf('/../config/%s.php', $configFileName)))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AddressDataRepositoryContract::class, JsonAddressDataRepository::class);
        $this->app->singleton(AddressDataSourceFactoryContract::class, AddressDataSourceFactory::class);
    }

    public function packageBooted(): void
    {
        // Testing
        Testable::mixin(new TestsFilamentLocation);
    }

    /**
     * @return array<string>
     */
    protected function getCommands(): array
    {
        return [
            SyncAddressDataCommand::class,
        ];
    }
}
