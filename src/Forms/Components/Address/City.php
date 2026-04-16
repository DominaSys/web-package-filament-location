<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

class City extends Select
{
    private string $countryCodeField = 'country_code';

    private string $stateCodeField = 'state_code';

    public function bindCountryCodeField(string $countryCodeField): self
    {
        $this->countryCodeField = $countryCodeField;

        return $this;
    }

    public function bindStateCodeField(string $stateCodeField): self
    {
        $this->stateCodeField = $stateCodeField;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.city.label'));
        $this->placeholder(__('filament-location::location.fields.city.placeholder'));
        $this->searchable();
        $this->preload();
        $this->native(false);
        $this->live();
        $this->disabled(fn (Get $get): bool => blank($this->resolveStateCode($get)));
        $this->options(fn (Get $get): array => AddressFieldOptionsFactory::cities(
            $this->resolveCountryCode($get),
            $this->resolveStateCode($get),
        ));
    }

    private function resolveCountryCode(Get $get): string
    {
        return strtoupper(trim((string) ($get($this->countryCodeField) ?: 'BR')));
    }

    private function resolveStateCode(Get $get): ?string
    {
        $stateCode = trim((string) ($get($this->stateCodeField) ?: ''));

        return $stateCode !== '' ? strtoupper($stateCode) : null;
    }
}
