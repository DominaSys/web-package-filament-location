<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Filament\Forms\Components\TextInput;

class Street extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.street.label'));
        $this->autocomplete('address-line1');
        $this->columnSpanFull();
        $this->maxLength(255);
    }
}
