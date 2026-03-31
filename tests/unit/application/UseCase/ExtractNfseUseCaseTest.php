<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Unit\Application\UseCase;

use MsNfseParser\Application\Port\NfseExtractorPort;
use MsNfseParser\Application\UseCase\ExtractNfseUseCase;
use MsNfseParser\Domain\Service\NfseDataNormalizerService;
use PHPUnit\Framework\TestCase;

final class ExtractNfseUseCaseTest extends TestCase
{
    public function testExecuteReturnsNormalizedPayload(): void
    {
        $extractor = new class() implements NfseExtractorPort {
            /**
             * @return array<string, mixed>
             */
            public function extractFromXml(string $xml): array
            {
                return [
                    'numero' => 'NF-2026-001',
                    'servico' => [
                        'valor_servicos' => '1.234,56',
                        'valor_iss' => '61,73',
                        'aliquota_iss' => '5,00',
                    ],
                    'metadados' => [
                        'confianca' => '0.91',
                    ],
                ];
            }
        };

        $useCase = new ExtractNfseUseCase($extractor, new NfseDataNormalizerService());

        $output = $useCase->execute('<nfse />');

        self::assertSame('NF-2026-001', $output->payload['nfse']['numero']);
        self::assertSame(1234.56, $output->payload['nfse']['servico']['valor_servicos']);
        self::assertSame(61.73, $output->payload['nfse']['servico']['valor_iss']);
        self::assertSame(5.0, $output->payload['nfse']['servico']['aliquota_iss']);
        self::assertSame('gemini', $output->payload['metadados']['fonte_extracao']);
        self::assertSame(0.91, $output->payload['metadados']['confianca']);
    }
}
