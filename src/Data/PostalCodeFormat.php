<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Data;

final class PostalCodeFormat
{
    public function __construct(
        public readonly ?string $mask = null,
        public readonly ?int $minLength = null,
        public readonly ?int $maxLength = null,
    ) {}

    public static function brazilian(): self
    {
        return new self(
            mask: '99999-999',
            minLength: 9,
            maxLength: 9,
        );
    }

    public static function generic(): self
    {
        return new self;
    }

    public function validationRules(): array
    {
        $rules = [];

        if (filled($this->minLength)) {
            $rules[] = "min:{$this->minLength}";
        }

        if (filled($this->maxLength)) {
            $rules[] = "max:{$this->maxLength}";
        }

        return $rules;
    }
}
