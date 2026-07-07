<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Filament\Forms\Components\TextInput;

class Neighborhood extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.neighborhood.label'));
        $this->autocomplete('address-level3');
        $this->maxLength(120);
    }
}
