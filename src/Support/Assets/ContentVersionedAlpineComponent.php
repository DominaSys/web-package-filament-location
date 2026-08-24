<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Support\Assets;

use Filament\Support\Assets\AlpineComponent;

class ContentVersionedAlpineComponent extends AlpineComponent
{
    public function getVersion(): string
    {
        $path = $this->getPath();

        if (! is_string($path) || ! is_file($path)) {
            return parent::getVersion();
        }

        return hash_file('sha256', $path) ?: parent::getVersion();
    }
}
