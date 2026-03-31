<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Double\Infrastructure\Provider;

use MsNfseParser\Application\Port\NfseExtractorPort;

final class FakeGeminiNfseExtractorProvider implements NfseExtractorPort
{
    /**
     * @return array<string, mixed>
     */
    public function extractFromXml(string $xml): array
    {
        return [
            'nfse' => [
                'numero' => '1001',
                'serie' => 'A1',
                'codigo_verificacao' => 'VER-2026',
                'data_emissao' => '2026-03-31T10:00:00-03:00',
                'municipio' => [
                    'codigo_ibge' => '3550308',
                    'nome' => 'Sao Paulo',
                    'uf' => 'SP',
                ],
                'prestador' => [
                    'razao_social' => 'Empresa Prestadora LTDA',
                    'cnpj' => '12345678000199',
                    'inscricao_municipal' => '123456',
                ],
                'tomador' => [
                    'razao_social' => 'Cliente SA',
                    'cnpj' => '99887766000155',
                    'email' => 'fiscal@cliente.com.br',
                ],
                'servico' => [
                    'descricao' => 'Servico de desenvolvimento de software',
                    'codigo_servico' => '0107',
                    'valor_servicos' => '1500.00',
                    'valor_iss' => '75.00',
                    'aliquota_iss' => '5.00',
                ],
                'totais' => [
                    'valor_bruto' => '1500.00',
                    'valor_deducoes' => '0.00',
                    'valor_liquido' => '1500.00',
                ],
            ],
            'metadados' => [
                'modelo_nfse' => 'abrasf-2.04',
                'confianca' => '0.98',
            ],
        ];
    }
}
