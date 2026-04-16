<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

final class BrazilianPostalCodeService
{
    const AWESOMEAPI = 'awesomeapi';
    const OPENCEP = 'opencep';
    const VIACEP = 'viacep';
    const BRASILAPI = 'brasilapi';

    public static function get(string $postalCode): array
    {
        $postalCode = self::sanitizeCep($postalCode);

        foreach ([
            self::AWESOMEAPI,
            self::OPENCEP,
            self::VIACEP,
            self::BRASILAPI,
        ] as $serviceName) {
            $responseData = self::fetchPostalCodeData($postalCode, $serviceName);

            if ($responseData !== null) {
                return $responseData;
            }
        }

        Notification::make()
            ->warning()
            ->title('CEP nao encontrado')
            ->body('Nao foi possivel localizar o CEP informado.')
            ->send();

        return [];
    }

    private static function fetchPostalCodeData(string $postalCode, string $serviceName): ?array
    {
        try {
            $response = match ($serviceName) {
                self::AWESOMEAPI => Http::get("https://cep.awesomeapi.com.br/json/{$postalCode}"),
                self::OPENCEP => Http::get("https://opencep.com/v1/{$postalCode}"),
                self::VIACEP => Http::get("https://viacep.com.br/ws/{$postalCode}/json/"),
                self::BRASILAPI => Http::get("https://brasilapi.com.br/api/cep/v2/{$postalCode}"),
            };

            if (!$response->successful()) {
                return null;
            }

            $responseData = $response->json();

            if (!is_array($responseData)) {
                return null;
            }

            return self::formatResponseData($responseData, $serviceName);
        } catch (Throwable) {
            return null;
        }
    }

    private static function formatResponseData(array $responseData, string $serviceName): ?array
    {
        if ($serviceName === self::AWESOMEAPI) {
            $formattedData = [
                'street' => Arr::get($responseData, 'address'),
                'neighborhood' => Arr::get($responseData, 'district'),
                'city' => Arr::get($responseData, 'city'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'state'),
                'ibge_code' => Arr::get($responseData, 'city_ibge'),
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }

        if ($serviceName === self::OPENCEP) {
            $formattedData = [
                'street' => Arr::get($responseData, 'logradouro'),
                'neighborhood' => Arr::get($responseData, 'bairro'),
                'city' => Arr::get($responseData, 'localidade'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'uf'),
                'ibge_code' => Arr::get($responseData, 'ibge'),
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }

        if ($serviceName === self::VIACEP) {
            if (Arr::get($responseData, 'erro') === 'true' || Arr::get($responseData, 'erro') === true) {
                return null;
            }

            $formattedData = [
                'street' => Arr::get($responseData, 'logradouro'),
                'neighborhood' => Arr::get($responseData, 'bairro'),
                'city' => Arr::get($responseData, 'localidade'),
                'state' => Arr::get($responseData, 'estado'),
                'state_code' => Arr::get($responseData, 'uf'),
                'ibge_code' => Arr::get($responseData, 'ibge'),
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }

        if ($serviceName === self::BRASILAPI) {
            if (Arr::has($responseData, 'errors')) {
                return null;
            }

            $formattedData = [
                'street' => Arr::get($responseData, 'street'),
                'neighborhood' => Arr::get($responseData, 'neighborhood'),
                'city' => Arr::get($responseData, 'city'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'state'),
                'ibge_code' => Arr::get($responseData, 'ibge'),
            ];

            return self::hasUsefulData($formattedData) ? $formattedData : null;
        }

        return null;
    }

    private static function hasUsefulData(array $responseData): bool
    {
        return count(array_filter($responseData, static fn ($value): bool => filled($value))) > 0;
    }

    private static function sanitizeCep(string $postalCode): string
    {
        return substr(str_pad(preg_replace('/\D+/', '', $postalCode) ?? '', 8, '0', STR_PAD_LEFT), 0, 8);
    }
}
