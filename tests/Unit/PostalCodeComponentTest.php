<?php

use Dominasys\FilamentLocation\Forms\Components\PostalCode;
use Livewire\Component as LivewireComponent;

it('generates safe focus javascript when the target field is missing', function () {
    $livewire = new class extends LivewireComponent
    {
        public ?string $expression = null;

        public function js($expression, ...$params)
        {
            $this->expression = $expression;
        }
    };

    $component = PostalCode::make('postal_code');
    $method = new ReflectionMethod($component, 'focusElement');
    $method->setAccessible(true);
    $method->invoke($component, $livewire, 'address_number');

    expect($livewire->expression)
        ->toContain('document.getElementById("address_number")')
        ->toContain('if (element) {')
        ->toContain('element.focus();');
});

it('dehydrates postal code values as digits only', function () {
    $component = PostalCode::make('postal_code');

    $maskProperty = new ReflectionProperty($component, 'dehydrateMask');
    $maskProperty->setAccessible(true);

    $callbackProperty = new ReflectionProperty($component, 'dehydrateStateUsing');
    $callbackProperty->setAccessible(true);

    $callback = $callbackProperty->getValue($component);

    expect($maskProperty->getValue($component))->toBeFalse()
        ->and($callback('88807-215'))->toBe('88807-215')
        ->and($callback(null))->toBeNull();

    $component->dehydrateMask();

    expect($maskProperty->getValue($component))->toBeTrue()
        ->and($callback('88807-215'))->toBe('88807215')
        ->and($callback(null))->toBeNull();
});
