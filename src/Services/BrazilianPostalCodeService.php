<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Filament\Notifications\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class BrazilianPostalCodeService
{
    const AWESOMEAPI = 'awesomeapi';
    const OPENCEP = 'opencep';
    const VIACEP = 'viacep';
    const BRASILAPI = 'brasilapi';

    public static function get(string $postalCode): array
    {
        $responseData = [];

        $postalCode = self::sanitizeCep($postalCode);

        try {
            $response = Http::get("https://cep.awesomeapi.com.br/json/{$postalCode}");

            if ($response->status() === 400) {
                Notification::make()
                    ->warning()
                    ->title('CEP inválido')
                    ->body('O CEP informado é inválido.')
                    ->send();
                return [];
            }

            $responseData = $response->json();

            return self::formatResponseData($responseData, self::AWESOMEAPI);
        } catch (ConnectionException) {}

        try {
            $responseData = Http::get('https://opencep.com/v1/' . $postalCode)->json();

            return self::formatResponseData($responseData, self::OPENCEP);
        } catch (ConnectionException) {
        }

        try {
            $responseData = Http::get('https://viacep.com.br/ws/' . $postalCode . '/json/')->json();

            if (Arr::has($responseData, 'erro')) {
                Notification::make()
                    ->warning()
                    ->title('CEP inválido')
                    ->body('O CEP informado é inválido.')
                    ->send();

                return [];
            }

            return self::formatResponseData($responseData, self::VIACEP);
        } catch (ConnectionException) {
        }

        try {
            $responseData = Http::get("https://brasilapi.com.br/api/cep/v2/$postalCode")->json();

            if (Arr::has($responseData, 'errors')) {
                Notification::make()
                    ->warning()
                    ->title('CEP inválido')
                    ->body('O CEP informado é inválido.')
                    ->send();
                return [];
            }

            return self::formatResponseData($responseData, self::BRASILAPI);
        } catch (ConnectionException) {
        }

        if (empty($responseData)) {
            Notification::make()
                ->warning()
                ->title('API\'s para Busca de CEP indisponíveis')
                ->body('Tente novamente mais tarde.')
                ->send();
        }

        return $responseData;
    }

    private static function formatResponseData(array $responseData, string $serviceName): array
    {
        if ($serviceName === self::AWESOMEAPI) {
            return [
                'street' => Arr::get($responseData, 'address'),
                'neighborhood' => Arr::get($responseData, 'district'),
                'city' => Arr::get($responseData, 'city'),
                'state' => null,
                'state_code' => Arr::get($responseData, 'state'),
                'ibge_code' => Arr::get($responseData, 'city_ibge'),
            ];
        }

        if ($serviceName === self::OPENCEP) {
            return [
            ];
        }

        if ($serviceName === self::VIACEP) {
            return [
                'street' => Arr::get($responseData, 'logradouro'),
                'neighborhood' => Arr::get($responseData, 'bairro'),
                'city' => Arr::get($responseData, 'localidade'),
                'state' => Arr::get($responseData, 'estado'),
                'state_code' => Arr::get($responseData, 'uf'),
                'ibge_code' => Arr::get($responseData, 'ibge'),
            ];
        }

        if ($serviceName === self::BRASILAPI) {
            return [
                'street' => Arr::get($responseData, 'street'),
                'neighborhood' => Arr::get($responseData, 'neighborhood'),
                'city' => Arr::get($responseData, 'city'),
                'state' => Arr::get($responseData, 'state') | null,
                'state_code' => Arr::get($responseData, 'uf'),
                'ibge_code' => Arr::get($responseData, 'ibge') | null,
            ];
        }

        return [];
    }

    private static function sanitizeCep(string $postalCode): string
    {
        return mb_substr(mb_str_pad(str_replace(['.', '-', '/', '(', ')', ' '], '', $postalCode), 8, '0', STR_PAD_LEFT), 0, 8);
    }
}
