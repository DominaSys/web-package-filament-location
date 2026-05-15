<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;
use Dominasys\FilamentLocation\Support\AccentInsensitiveSelectSearch;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class City extends Select
{
    private string $countryCodeField = 'country_code';

    private string $stateCodeField = 'state_code';

    private ?string $cityCodeField = null;

    private bool $useLabelAsValue = false;

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

    public function bindCityCodeField(string $cityCodeField): self
    {
        $this->cityCodeField = $cityCodeField;

        return $this;
    }

    public function useLabelAsValue(bool $condition = true): self
    {
        $this->useLabelAsValue = $condition;

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
        $this->options(fn (Get $get): array => $this->resolveOptions($get));
        $this->afterStateHydrated(function (Set $set, Get $get, mixed $state): void {
            $this->syncCityCodeField($set, $get, $state);
        });
        $this->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
            $this->syncCityCodeField($set, $get, $state);
        });
        $this->extraAlpineAttributes([
            'x-init' => AccentInsensitiveSelectSearch::xInit(),
        ]);
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

    /**
     * @return array<string, string>
     */
    private function resolveOptions(Get $get): array
    {
        $cities = AddressFieldOptionsFactory::cities(
            $this->resolveCountryCode($get),
            $this->resolveStateCode($get),
        );

        if (! $this->useLabelAsValue) {
            return $cities;
        }

        $labelOptions = [];

        foreach ($cities as $code => $label) {
            $label = trim((string) $label);

            if ($label === '' || array_key_exists($label, $labelOptions)) {
                continue;
            }

            $labelOptions[$label] = $label;
        }

        return $labelOptions;
    }

    private function syncCityCodeField(Set $set, Get $get, mixed $state): void
    {
        if ($this->cityCodeField === null) {
            return;
        }

        $cityCode = $this->useLabelAsValue
            ? $this->resolveCityCodeByLabel($get, $state)
            : $this->normalizeCityCode($state);

        $set($this->cityCodeField, $cityCode);
    }

    private function resolveCityCodeByLabel(Get $get, mixed $state): ?string
    {
        $cityLabel = $this->normalizeCityLabel($state);

        if ($cityLabel === null) {
            return null;
        }

        $cityCode = array_search(
            $cityLabel,
            AddressFieldOptionsFactory::cities(
                $this->resolveCountryCode($get),
                $this->resolveStateCode($get),
            ),
            true,
        );

        if (is_int($cityCode)) {
            return (string) $cityCode;
        }

        return is_string($cityCode) ? $cityCode : null;
    }

    private function normalizeCityCode(mixed $state): ?string
    {
        $cityCode = $this->normalizeCityLabel($state);

        return $cityCode !== null ? $cityCode : null;
    }

    private function normalizeCityLabel(mixed $state): ?string
    {
        $value = trim((string) ($state ?: ''));

        return $value !== '' ? $value : null;
    }
}
