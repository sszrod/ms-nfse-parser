<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Unit\Application\UseCase;

use MsNfseParser\Application\Port\NfseExtractorPort;
use MsNfseParser\Application\Port\NfseTemplateRepositoryPort;
use MsNfseParser\Application\UseCase\ExtractNfseUseCase;
use MsNfseParser\Domain\Service\NfseDataNormalizerService;
use MsNfseParser\Domain\Service\NfseXmlTemplateEngineService;
use PHPUnit\Framework\TestCase;

final class ExtractNfseUseCaseTest extends TestCase
{
    public function testExecuteFallsBackToExtractorAndSavesTemplateWhenNoTemplateExists(): void
    {
        $extractor = new class() implements NfseExtractorPort {
            public int $calls = 0;

            /**
             * @return array<string, mixed>
             */
            public function extractFromXml(string $xml): array
            {
                $this->calls++;

                return [
                    'numero' => '2002',
                    'codigo_verificacao' => 'ABC123',
                    'xpaths' => [
                        'nfse' => [
                            'numero' => "/*[local-name()='CompNfse']/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='Numero']",
                            'codigo_verificacao' => "/*[local-name()='CompNfse']/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='CodigoVerificacao']",
                        ],
                    ],
                ];
            }
        };

        $templateRepository = new class() implements NfseTemplateRepositoryPort {
            /** @var list<array<string, mixed>> */
            public array $saved = [];

            /**
             * @return list<array<string, mixed>>
             */
            public function findByMunicipioCodigo(string $municipioCodigo): array
            {
                return [];
            }

            /**
             * @param array<string, mixed> $template
             */
            public function save(array $template): void
            {
                $this->saved[] = $template;
            }
        };

        $useCase = new ExtractNfseUseCase(
            $extractor,
            $templateRepository,
            new NfseXmlTemplateEngineService(),
            new NfseDataNormalizerService(),
        );

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<CompNfse>
  <Nfse>
    <InfNfse>
      <Numero>2002</Numero>
      <CodigoVerificacao>ABC123</CodigoVerificacao>
    </InfNfse>
  </Nfse>
</CompNfse>
XML;

        $output = $useCase->execute($xml, '3550308');

        self::assertSame(1, $extractor->calls);
        self::assertSame('2002', $output->payload['nfse']['numero']);
        self::assertSame('gemini', $output->payload['metadados']['fonte_extracao']);
        self::assertCount(1, $templateRepository->saved);
        self::assertSame('3550308', $templateRepository->saved[0]['municipio_codigo']);
        self::assertSame('gemini_fallback', $templateRepository->saved[0]['origin']);
        self::assertArrayHasKey('field_xpaths', $templateRepository->saved[0]);
        self::assertNotEmpty($templateRepository->saved[0]['field_xpaths']);
        self::assertSame(
            "/*[local-name()='CompNfse']/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='Numero']",
            $templateRepository->saved[0]['field_xpaths']['nfse.numero'] ?? null,
        );
    }

    public function testExecuteReturnsNormalizedPayloadFromExtractorFallback(): void
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

        $templateRepository = new class() implements NfseTemplateRepositoryPort {
            /**
             * @return list<array<string, mixed>>
             */
            public function findByMunicipioCodigo(string $municipioCodigo): array
            {
                return [];
            }

            /**
             * @param array<string, mixed> $template
             */
            public function save(array $template): void
            {
            }
        };

        $useCase = new ExtractNfseUseCase(
            $extractor,
            $templateRepository,
            new NfseXmlTemplateEngineService(),
            new NfseDataNormalizerService(),
        );

        $output = $useCase->execute('<nfse />', '3550308');

        self::assertSame('NF-2026-001', $output->payload['nfse']['numero']);
        self::assertSame(1234.56, $output->payload['nfse']['servico']['valor_servicos']);
        self::assertSame(61.73, $output->payload['nfse']['servico']['valor_iss']);
        self::assertSame(5.0, $output->payload['nfse']['servico']['aliquota_iss']);
        self::assertSame('gemini', $output->payload['metadados']['fonte_extracao']);
        self::assertSame(0.91, $output->payload['metadados']['confianca']);
    }

    public function testExecuteUsesTemplateWhenItMatchesXml(): void
    {
        $extractor = new class() implements NfseExtractorPort {
            /**
             * @return array<string, mixed>
             */
            public function extractFromXml(string $xml): array
            {
                return [
                    'numero' => 'nao-deve-usar-ia',
                ];
            }
        };

        $templateRepository = new class() implements NfseTemplateRepositoryPort {
            /**
             * @return list<array<string, mixed>>
             */
            public function findByMunicipioCodigo(string $municipioCodigo): array
            {
                return [
                    [
                        '_id' => 'template-1',
                        'municipio_codigo' => $municipioCodigo,
                        'field_xpaths' => [
                            'nfse.numero' => "/*[local-name()='CompNfse']/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='Numero']",
                        ],
                        'validator_xpaths' => [
                            "/*[local-name()='CompNfse']/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='Numero']",
                        ],
                    ],
                ];
            }

            /**
             * @param array<string, mixed> $template
             */
            public function save(array $template): void
            {
                throw new \RuntimeException('Nao deveria salvar template quando ja existe um template valido.');
            }
        };

        $useCase = new ExtractNfseUseCase(
            $extractor,
            $templateRepository,
            new NfseXmlTemplateEngineService(),
            new NfseDataNormalizerService(),
        );

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<CompNfse>
  <Nfse>
    <InfNfse>
      <Numero>1001</Numero>
    </InfNfse>
  </Nfse>
</CompNfse>
XML;

        $output = $useCase->execute($xml, '3550308');

        self::assertSame('1001', $output->payload['nfse']['numero']);
        self::assertSame('template', $output->payload['metadados']['fonte_extracao']);
    }
}
