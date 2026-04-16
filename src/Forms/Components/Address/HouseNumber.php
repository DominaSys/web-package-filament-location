<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Filament\Forms\Components\TextInput;

class HouseNumber extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.number.label'));
        $this->placeholder(__('filament-location::location.fields.number.placeholder'));
        $this->autocomplete('address-line2');
        $this->maxLength(20);
    }
}
