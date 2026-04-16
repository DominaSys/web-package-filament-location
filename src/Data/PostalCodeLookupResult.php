<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Data;

use Dominasys\FilamentLocation\Enums\PostalCodeLookupStatusEnum;

final readonly class PostalCodeLookupResult
{
    public function __construct(
        public PostalCodeLookupStatusEnum $status,
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?string $countryCode = null,
        public ?string $state = null,
        public ?string $stateCode = null,
        public ?string $city = null,
        public ?string $cityCode = null,
        public ?string $neighborhood = null,
        public ?string $street = null,
        public ?string $ibgeCode = null,
        public ?string $source = null,
        public ?string $message = null,
        public array $meta = [],
    ) {}

    public static function found(
        ?string $postalCode = null,
        ?string $country = null,
        ?string $countryCode = null,
        ?string $state = null,
        ?string $stateCode = null,
        ?string $city = null,
        ?string $cityCode = null,
        ?string $neighborhood = null,
        ?string $street = null,
        ?string $ibgeCode = null,
        ?string $source = null,
        array $meta = [],
    ): self {
        return new self(
            status: PostalCodeLookupStatusEnum::FOUND,
            postalCode: $postalCode,
            country: $country,
            countryCode: $countryCode,
            state: $state,
            stateCode: $stateCode,
            city: $city,
            cityCode: $cityCode,
            neighborhood: $neighborhood,
            street: $street,
            ibgeCode: $ibgeCode,
            source: $source,
            meta: $meta,
        );
    }

    public static function invalid(
        ?string $postalCode = null,
        ?string $country = null,
        ?string $countryCode = null,
        ?string $message = null,
        array $meta = [],
    ): self {
        return new self(
            status: PostalCodeLookupStatusEnum::INVALID,
            postalCode: $postalCode,
            country: $country,
            countryCode: $countryCode,
            message: $message ?? 'O CEP informado e invalido.',
            meta: $meta,
        );
    }

    public static function notFound(
        ?string $postalCode = null,
        ?string $country = null,
        ?string $countryCode = null,
        ?string $message = null,
        array $meta = [],
    ): self {
        return new self(
            status: PostalCodeLookupStatusEnum::NOT_FOUND,
            postalCode: $postalCode,
            country: $country,
            countryCode: $countryCode,
            message: $message ?? 'Nao foi possivel localizar o CEP informado.',
            meta: $meta,
        );
    }

    public static function unsupportedCountry(
        ?string $postalCode = null,
        ?string $country = null,
        ?string $countryCode = null,
        ?string $message = null,
        array $meta = [],
    ): self {
        return new self(
            status: PostalCodeLookupStatusEnum::UNSUPPORTED_COUNTRY,
            postalCode: $postalCode,
            country: $country,
            countryCode: $countryCode,
            message: $message ?? 'O pais selecionado ainda nao possui servico de CEP suportado.',
            meta: $meta,
        );
    }

    public function isFound(): bool
    {
        return $this->status === PostalCodeLookupStatusEnum::FOUND;
    }

    public function isInvalid(): bool
    {
        return $this->status === PostalCodeLookupStatusEnum::INVALID;
    }

    public function isNotFound(): bool
    {
        return $this->status === PostalCodeLookupStatusEnum::NOT_FOUND;
    }

    public function isUnsupportedCountry(): bool
    {
        return $this->status === PostalCodeLookupStatusEnum::UNSUPPORTED_COUNTRY;
    }

    public function address(): array
    {
        return [
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'state' => $this->state,
            'state_code' => $this->stateCode,
            'city' => $this->city,
            'city_code' => $this->cityCode,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'ibge_code' => $this->ibgeCode,
        ];
    }

    public function toArray(): array
    {
        return array_merge(
            $this->address(),
            [
                'status' => $this->status->value,
                'source' => $this->source,
                'message' => $this->message,
                'meta' => $this->meta,
            ]
        );
    }

    public function notificationTitle(): string
    {
        return match ($this->status) {
            PostalCodeLookupStatusEnum::INVALID => 'CEP invalido',
            PostalCodeLookupStatusEnum::NOT_FOUND => 'CEP nao encontrado',
            PostalCodeLookupStatusEnum::UNSUPPORTED_COUNTRY => 'Pais nao suportado',
            PostalCodeLookupStatusEnum::FOUND => 'CEP encontrado',
        };
    }

    public function notificationBody(): string
    {
        return $this->message ?? match ($this->status) {
            PostalCodeLookupStatusEnum::INVALID => 'O CEP informado e invalido.',
            PostalCodeLookupStatusEnum::NOT_FOUND => 'Nao foi possivel localizar o CEP informado.',
            PostalCodeLookupStatusEnum::UNSUPPORTED_COUNTRY => 'O pais selecionado ainda nao possui servico de CEP suportado.',
            PostalCodeLookupStatusEnum::FOUND => '',
        };
    }
}
