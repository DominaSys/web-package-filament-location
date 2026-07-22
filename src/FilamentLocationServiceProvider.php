<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation;

use Dominasys\FilamentLocation\Commands\SyncAddressDataCommand;
use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Dominasys\FilamentLocation\Contracts\AddressDataSourceFactoryContract;
use Dominasys\FilamentLocation\Contracts\ReverseGeocodingServiceContract;
use Dominasys\FilamentLocation\Services\AddressDataSourceFactory;
use Dominasys\FilamentLocation\Services\GoogleReverseGeocodingService;
use Dominasys\FilamentLocation\Services\JsonAddressDataRepository;
use Dominasys\FilamentLocation\Testing\TestsFilamentLocation;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
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

        if (file_exists($package->basePath('/../config/location.php'))) {
            $package->hasConfigFile('location');
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews();
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AddressDataRepositoryContract::class, JsonAddressDataRepository::class);
        $this->app->singleton(AddressDataSourceFactoryContract::class, AddressDataSourceFactory::class);
        $this->app->singleton(ReverseGeocodingServiceContract::class, GoogleReverseGeocodingService::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make(
                'google-place-picker',
                __DIR__ . '/../resources/js/dist/components/google-place-picker.js',
            ),
            Css::make(
                'google-place-picker',
                __DIR__ . '/../resources/css/google-place-picker.css',
            )->loadedOnRequest(),
        ], package: 'dominasys/filament-location');

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
