<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Forms\Components\Address;

use Filament\Forms\Components\TextInput;

class Complement extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-location::location.fields.complement.label'));
        $this->placeholder(__('filament-location::location.fields.complement.placeholder'));
        $this->autocomplete('address-line2');
        $this->columnSpanFull();
        $this->maxLength(255);
    }
}
