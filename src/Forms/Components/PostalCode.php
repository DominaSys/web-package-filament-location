<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components;

use BackedEnum;
use Dominasys\FilamentLocation\Data\PostalCodeFormat;
use Dominasys\FilamentLocation\Enums\ActionPositionEnum;
use Dominasys\FilamentLocation\Services\PostalCodeFormatFactory;
use Dominasys\FilamentLocation\Services\PostalCodeServiceFactory;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Livewire\Component as LivewireComponent;

class PostalCode extends TextInput
{
    private string | BackedEnum $actionIcon = Heroicon::OutlinedMagnifyingGlass;

    private ActionPositionEnum $actionPosition = ActionPositionEnum::SUFFIX;

    protected bool $dehydrateMask = false;

    private ?string $postalCodeMask = null;

    private ?int $postalCodeMinLength = null;

    private ?int $postalCodeMaxLength = null;

    private string $countryCodeField = 'country_code';

    private string $countryField = 'country';

    private string $stateCodeField = 'state_code';

    private string $stateField = 'state';

    private string $cityField = 'city';

    private string $cityCodeField = 'city_code';

    private string $neighborhoodField = 'neighborhood';

    private string $streetField = 'street';

    private string $nextFocusField = 'number';

    public function getPostalCode(LivewireComponent $livewire, Component $component, Get $get, Set $set): void
    {
        $countryCode = $get($this->countryCodeField) ?: 'BR';
        $postalCodeResponse = PostalCodeServiceFactory::make($countryCode)->lookup($this->getState());

        if (! $postalCodeResponse->isFound()) {
            Notification::make()
                ->warning()
                ->title($postalCodeResponse->notificationTitle())
                ->body($postalCodeResponse->notificationBody())
                ->send();

            $this->focusElement($livewire, $component->getKey());

            return;
        }

        if (! blank($postalCodeResponse->street)) {
            $set($this->streetField, $postalCodeResponse->street);
        }

        if (! blank($postalCodeResponse->neighborhood)) {
            $set($this->neighborhoodField, $postalCodeResponse->neighborhood);
        }

        if (! blank($postalCodeResponse->state)) {
            $set($this->stateField, $postalCodeResponse->state);
        }

        if (! blank($postalCodeResponse->stateCode)) {
            $set($this->stateCodeField, $postalCodeResponse->stateCode);
        }

        if (! blank($postalCodeResponse->city)) {
            $set($this->cityField, $postalCodeResponse->city, shouldCallUpdatedHooks: true);
        }

        if (! blank($postalCodeResponse->cityCode)) {
            $set($this->cityCodeField, $postalCodeResponse->cityCode, shouldCallUpdatedHooks: true);
        }

        if (! blank($postalCodeResponse->country)) {
            $set($this->countryField, $postalCodeResponse->country);
        }

        if (! blank($postalCodeResponse->countryCode)) {
            $set($this->countryCodeField, $postalCodeResponse->countryCode);
        }

        if ($component->statePath && $component->getKey()) {
            $nextFocusTargetField = str_replace($component->statePath, $this->nextFocusField, $component->getKey());
            $this->focusElement($livewire, $nextFocusTargetField);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.postal_code.label'));
        $this->placeholder(__('filament-location::location.fields.postal_code.placeholder'));
        $this->autocomplete('postal-code');
        $this->mask(fn (Get $get): ?string => $this->resolvePostalCodeFormat($get)->mask);
        $this->minLength(fn (Get $get): ?int => $this->resolvePostalCodeFormat($get)->minLength);
        $this->maxLength(fn (Get $get): ?int => $this->resolvePostalCodeFormat($get)->maxLength);
        $this->dehydrateStateUsing(function (?string $state) {
            if (! $this->dehydrateMask || $state === null) {
                return $state;
            }

            return preg_replace('/\D/', '', $state);
        });
        $this->required();
        $this->rules(fn (Get $get): array => $this->resolvePostalCodeFormat($get)->validationRules());

        $this->prefixAction(fn (): ?Action => ($this->actionPosition === ActionPositionEnum::PREFIX)
            ? Action::make('prefixFindPostalCode')
                ->icon(fn (): string | \BackedEnum => $this->actionIcon)
                ->action(function (LivewireComponent $livewire, Component $component, Get $get, Set $set): void {
                    $livewire->validateOnly($component->getStatePath());
                    $this->getPostalCode($livewire, $component, $get, $set);
                })
            : null);

        $this->suffixAction(fn (): ?Action => ($this->actionPosition === ActionPositionEnum::SUFFIX)
            ? Action::make('prefixFindPostalCode')
                ->icon(fn (): string | \BackedEnum => $this->actionIcon)
                ->action(function (LivewireComponent $livewire, Component $component, Get $get, Set $set): void {
                    $livewire->validateOnly($component->getStatePath());
                    $this->getPostalCode($livewire, $component, $get, $set);
                })
            : null);
    }

    public function actionIcon(string | BackedEnum $icon): self
    {
        $this->actionIcon = $icon;

        return $this;
    }

    public function postalCodeMask(string $mask): self
    {
        $this->postalCodeMask = $mask;

        return $this;
    }

    public function postalCodeMinLength(int $minLength): self
    {
        $this->postalCodeMinLength = $minLength;

        return $this;
    }

    public function postalCodeMaxLength(int $maxLength): self
    {
        $this->postalCodeMaxLength = $maxLength;

        return $this;
    }

    public function actionPosition(ActionPositionEnum $position): self
    {
        $this->actionPosition = $position;

        return $this;
    }

    public function bindCityField(string $cityField): self
    {
        $this->cityField = $cityField;

        return $this;
    }

    public function bindCityCodeField(string $cityCodeField): self
    {
        $this->cityCodeField = $cityCodeField;

        return $this;
    }

    public function bindCountryField(string $countryField): self
    {
        $this->countryField = $countryField;

        return $this;
    }

    public function bindCountryCodeField(string $countryCodeField): self
    {
        $this->countryCodeField = $countryCodeField;

        return $this;
    }

    public function bindNeighborhoodField(string $neighborhoodField): self
    {
        $this->neighborhoodField = $neighborhoodField;

        return $this;
    }

    public function bindStateField(string $stateField): self
    {
        $this->stateField = $stateField;

        return $this;
    }

    public function bindStateCodeField(string $stateCodeField): self
    {
        $this->stateCodeField = $stateCodeField;

        return $this;
    }

    public function bindStreetField(string $streetField): self
    {
        $this->streetField = $streetField;

        return $this;
    }

    public function nextFocusField(string $targetField): self
    {
        $this->nextFocusField = $targetField;

        return $this;
    }

    public function dehydrateMask(bool $condition = true): self
    {
        $this->dehydrateMask = $condition;

        return $this;
    }

    private function focusElement(LivewireComponent $livewire, string $elementId): void
    {
        $livewire->js(sprintf(<<<'JS'
const element = document.getElementById(%s);

if (element) {
    element.focus();
}
JS, json_encode($elementId, JSON_THROW_ON_ERROR)));
    }

    private function resolvePostalCodeFormat(Get $get): PostalCodeFormat
    {
        $countryCode = $get($this->countryCodeField) ?: 'BR';
        $defaultFormat = PostalCodeFormatFactory::make($countryCode);

        return new PostalCodeFormat(
            mask: $this->postalCodeMask ?? $defaultFormat->mask,
            minLength: $this->postalCodeMinLength ?? $defaultFormat->minLength,
            maxLength: $this->postalCodeMaxLength ?? $defaultFormat->maxLength,
        );
    }
}
