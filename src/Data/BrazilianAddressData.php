<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Data;

final class BrazilianAddressData
{
    /**
     * @return array<string, string>
     */
    public static function states(): array
    {
        $states = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        asort($states);

        return $states;
    }

    /**
     * @return array<string, string>
     */
    public static function cities(?string $stateCode): array
    {
        $normalizedStateCode = strtoupper(trim((string) $stateCode));

        $cities = match ($normalizedStateCode) {
            'AC' => ['Rio Branco', 'Cruzeiro do Sul', 'Sena Madureira'],
            'AL' => ['Maceió', 'Arapiraca', 'Palmeira dos Índios'],
            'AP' => ['Macapá', 'Santana'],
            'AM' => ['Manaus', 'Parintins', 'Itacoatiara'],
            'BA' => ['Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Camaçari'],
            'CE' => ['Fortaleza', 'Juazeiro do Norte', 'Sobral', 'Crato'],
            'DF' => ['Brasília'],
            'ES' => ['Vitória', 'Vila Velha', 'Serra', 'Cariacica'],
            'GO' => ['Goiânia', 'Aparecida de Goiânia', 'Anápolis'],
            'MA' => ['São Luís', 'Imperatriz', 'Timon'],
            'MT' => ['Cuiabá', 'Várzea Grande', 'Rondonópolis'],
            'MS' => ['Campo Grande', 'Dourados', 'Três Lagoas'],
            'MG' => ['Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim'],
            'PA' => ['Belém', 'Ananindeua', 'Santarém', 'Marabá'],
            'PB' => ['João Pessoa', 'Campina Grande', 'Patos'],
            'PR' => ['Curitiba', 'Londrina', 'Maringá', 'Ponta Grossa', 'Cascavel'],
            'PE' => ['Recife', 'Jaboatão dos Guararapes', 'Olinda', 'Caruaru'],
            'PI' => ['Teresina', 'Parnaíba', 'Picos'],
            'RJ' => ['Rio de Janeiro', 'Niterói', 'Duque de Caxias', 'São Gonçalo', 'Nova Iguaçu'],
            'RN' => ['Natal', 'Mossoró', 'Parnamirim'],
            'RS' => ['Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Canoas', 'Santa Maria'],
            'RO' => ['Porto Velho', 'Ji-Paraná', 'Ariquemes'],
            'RR' => ['Boa Vista', 'Rorainópolis'],
            'SC' => ['Florianópolis', 'Joinville', 'Blumenau', 'São José', 'Criciúma'],
            'SE' => ['Aracaju', 'Nossa Senhora do Socorro'],
            'SP' => ['São Paulo', 'Guarulhos', 'Campinas', 'São Bernardo do Campo', 'Santo André', 'Osasco', 'Ribeirão Preto', 'Sorocaba'],
            'TO' => ['Palmas', 'Araguaína', 'Gurupi'],
            default => [],
        };

        $cities = array_values(array_unique($cities));
        sort($cities);

        return array_combine($cities, $cities) ?: [];
    }
}
