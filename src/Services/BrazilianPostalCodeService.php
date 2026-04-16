<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\PostalCodeServiceContract;
use Dominasys\FilamentLocation\Data\PostalCodeLookupResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

final class BrazilianPostalCodeService implements PostalCodeServiceContract
{
    private const COUNTRY_CODE = 'BR';

    private const COUNTRY_NAME = 'Brasil';

    public function lookup(string $postalCode): PostalCodeLookupResult
    {
        $normalizedPostalCode = self::sanitizePostalCode($postalCode);

        if (strlen($normalizedPostalCode) !== 8) {
            return PostalCodeLookupResult::invalid(
                postalCode: $normalizedPostalCode,
                country: self::COUNTRY_NAME,
                countryCode: self::COUNTRY_CODE,
                message: 'O CEP informado e invalido.',
            );
        }

        foreach ([
            self::awesomeApiPayload($normalizedPostalCode),
            self::openCepPayload($normalizedPostalCode),
            self::viaCepPayload($normalizedPostalCode),
            self::brasilApiPayload($normalizedPostalCode),
        ] as $responseData) {
            if ($responseData !== null) {
                return PostalCodeLookupResult::found(
                    postalCode: $normalizedPostalCode,
                    country: self::COUNTRY_NAME,
                    countryCode: self::COUNTRY_CODE,
                    state: $responseData['state'],
                    stateCode: $responseData['state_code'],
                    city: $responseData['city'],
                    cityCode: $responseData['city_code'],
                    neighborhood: $responseData['neighborhood'],
                    street: $responseData['street'],
                    ibgeCode: $responseData['ibge_code'],
                    source: $responseData['source'],
                );
            }
        }

        return PostalCodeLookupResult::notFound(
            postalCode: $normalizedPostalCode,
            country: self::COUNTRY_NAME,
            countryCode: self::COUNTRY_CODE,
        );
    }

    private static function awesomeApiPayload(string $postalCode): ?array
    {
        return self::requestAndFormat(function (array $responseData): ?array {
            if (Arr::get($responseData, 'code') === 'not_found') {
                return null;
            }

            if (Arr::get($responseData, 'code') === 'invalid') {
                return null;
            }

            $formattedData = [
                'street' => Arr::get($responseData, 'address'),
                'neighborhood' => Arr::get($responseData, 'district'),
                'city' => Arr::get($responseData, 'city'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'state'),
                'city_code' => null,
                'ibge_code' => Arr::get($responseData, 'city_ibge'),
                'source' => 'awesomeapi',
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }, "https://cep.awesomeapi.com.br/json/{$postalCode}");
    }

    private static function openCepPayload(string $postalCode): ?array
    {
        return self::requestAndFormat(function (array $responseData): ?array {
            if (Arr::get($responseData, 'error') === true) {
                return null;
            }

            $formattedData = [
                'street' => Arr::get($responseData, 'logradouro'),
                'neighborhood' => Arr::get($responseData, 'bairro'),
                'city' => Arr::get($responseData, 'localidade'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'uf'),
                'city_code' => null,
                'ibge_code' => Arr::get($responseData, 'ibge'),
                'source' => 'opencep',
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }, "https://opencep.com/v1/{$postalCode}");
    }

    private static function viaCepPayload(string $postalCode): ?array
    {
        return self::requestAndFormat(function (array $responseData): ?array {
            if (Arr::get($responseData, 'erro') === 'true' || Arr::get($responseData, 'erro') === true) {
                return null;
            }

            $formattedData = [
                'street' => Arr::get($responseData, 'logradouro'),
                'neighborhood' => Arr::get($responseData, 'bairro'),
                'city' => Arr::get($responseData, 'localidade'),
                'state' => Arr::get($responseData, 'estado'),
                'state_code' => Arr::get($responseData, 'uf'),
                'city_code' => null,
                'ibge_code' => Arr::get($responseData, 'ibge'),
                'source' => 'viacep',
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }, "https://viacep.com.br/ws/{$postalCode}/json/");
    }

    private static function brasilApiPayload(string $postalCode): ?array
    {
        return self::requestAndFormat(function (array $responseData): ?array {
            if (Arr::has($responseData, 'errors')) {
                return null;
            }

            $formattedData = [
                'street' => Arr::get($responseData, 'street'),
                'neighborhood' => Arr::get($responseData, 'neighborhood'),
                'city' => Arr::get($responseData, 'city'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'state'),
                'city_code' => null,
                'ibge_code' => Arr::get($responseData, 'ibge'),
                'source' => 'brasilapi',
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }, "https://brasilapi.com.br/api/cep/v2/{$postalCode}");
    }

    private static function requestAndFormat(callable $formatter, string $url): ?array
    {
        try {
            $response = Http::get($url);

            if (! $response->successful()) {
                return null;
            }

            $responseData = $response->json();

            if (! is_array($responseData)) {
                return null;
            }

            $formattedData = $formatter($responseData);

            return is_array($formattedData) ? $formattedData : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function hasUsefulData(array $responseData): bool
    {
        unset($responseData['source']);

        return count(array_filter($responseData, static fn ($value): bool => filled($value))) > 0;
    }

    private static function sanitizePostalCode(string $postalCode): string
    {
        return preg_replace('/\D+/', '', $postalCode) ?? '';
    }
}
