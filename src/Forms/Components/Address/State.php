<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

class State extends Select
{
    private string $countryCodeField = 'country_code';

    public function bindCountryCodeField(string $countryCodeField): self
    {
        $this->countryCodeField = $countryCodeField;

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
    }

    private function resolveCountryCode(Get $get): string
    {
        return strtoupper(trim((string) ($get($this->countryCodeField) ?: 'BR')));
    }
}
