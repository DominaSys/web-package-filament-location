<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;
use Dominasys\FilamentLocation\Support\AccentInsensitiveSelectSearch;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class State extends Select
{
    private string $countryCodeField = 'country_code';

    private string $cityField = 'city';

    public function bindCountryCodeField(string $countryCodeField): self
    {
        $this->countryCodeField = $countryCodeField;

        return $this;
    }

    public function bindCityField(string $cityField): self
    {
        $this->cityField = $cityField;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.state.label'));
        $this->placeholder(__('filament-location::location.fields.state.placeholder'));
        $this->searchable();
        $this->preload();
        $this->native(false);
        $this->live();
        $this->options(fn (Get $get): array => AddressFieldOptionsFactory::states($this->resolveCountryCode($get)));
        $this->afterStateUpdated(function (Set $set, mixed $state, mixed $old): void {
            if ($state === $old) {
                return;
            }

            $set($this->cityField, null);
        });
        $this->extraAlpineAttributes([
            'x-init' => AccentInsensitiveSelectSearch::xInit(),
        ]);
    }

    private function resolveCountryCode(Get $get): string
    {
        return strtoupper(trim((string) ($get($this->countryCodeField) ?: 'BR')));
    }
}
