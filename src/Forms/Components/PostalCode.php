<?php

use Dominasys\FilamentLocation\Enums\ActionPositionEnum;
use Dominasys\FilamentLocation\Services\BrazilianPostalCodeService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Component;
use Livewire\Component as LivewireComponent;

class PostalCode extends TextInput
{
    private string | BackedEnum $actionIcon = Heroicon::OutlinedMagnifyingGlass;

    private ActionPositionEnum $actionPosition = ActionPositionEnum::SUFFIX;

    private string $postalCodeMask = '99999-999';

    private int $postalCodeMinLength = 9;

    private int $postalCodeMaxLength = 9;

    private string $countryCodeField = 'country_code';

    private string $countryField = 'country';

    private string $stateCodeField = 'state_code';

    private string $stateField = 'state';

    private string $cityCodeField = 'city_code';

    private string $cityField = 'city';

    private string $neighborhoodField = 'neighborhood';

    private string $streetField = 'street';

    private string $nextFocusField = 'number';

    public function getPostalCode(LivewireComponent $livewire, Component $component, Set $set): void
    {
        $postalCodeResponse = BrazilianPostalCodeService::get($this->getState());

        if ($postalCodeResponse === []) {
            $livewire->js("document.getElementById('{$component->getKey()}').focus()");

            return;
        }

        if (! empty($postalCodeResponse['street'])) {
            $set($this->streetField, $postalCodeResponse['street']);
        }

        if (! empty($postalCodeResponse['neighborhood'])) {
            $set($this->neighborhoodField, $postalCodeResponse['neighborhood']);
        }

        if (! empty($postalCodeResponse['city'])) {
            $set($this->cityField, $postalCodeResponse['city']);
        }

        if (! empty($postalCodeResponse['state'])) {
            $set($this->stateField, $postalCodeResponse['state']);
        }

        if (! empty($postalCodeResponse['state_code'])) {
            $set($this->stateCodeField, $postalCodeResponse['state_code']);
        }

        if (! empty($postalCodeResponse['ibge_code'])) {
            $set($this->ibgeCodeField, $postalCodeResponse['ibge_code']);
        }

        $set($this->countryField, 'Brasil');

        $set($this->countryCodeField, 'BR');

        if ($component->statePath && $component->getKey()) {
            $nextFocusTargetField = str_replace($component->statePath, $this->nextFocusField, $component->getKey());
            $livewire->js("document.getElementById('{$nextFocusTargetField}').focus()");
        }

    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->mask($this->postalCodeMask);
        $this->minLength($this->postalCodeMinLength);
        $this->maxLength($this->postalCodeMaxLength);
        $this->required();
        $this->rules(['required', "min:{$this->postalCodeMinLength}", "max:{$this->postalCodeMaxLength}"]);

        $this->prefixAction(function (): ?Action {
            return ($this->actionPosition === ActionPositionEnum::PREFIX)
                ? Action::make('prefixFindPostalCode')
                    ->icon(fn () => $this->actionIcon)
                    ->action(function (LivewireComponent $livewire, Component $component, Set $set) {
                        $livewire->validateOnly($component->getStatePath());
                        $this->getPostalCode($livewire, $component, $set);
                    })
                : null;
        });

        $this->suffixAction(function (): ?Action {
            return ($this->actionPosition === ActionPositionEnum::SUFFIX)
                ? Action::make('prefixFindPostalCode')
                    ->icon(fn () => $this->actionIcon)
                    ->action(function (LivewireComponent $livewire, Component $component, Set $set) {
                        $livewire->validateOnly($component->getStatePath());
                        $this->getPostalCode($livewire, $component, $set);
                    })
                : null;
        });

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

    public function bindIbgeCodeField(string $ibgeCodeField): self
    {
        $this->ibgeCodeField = $ibgeCodeField;

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
}
