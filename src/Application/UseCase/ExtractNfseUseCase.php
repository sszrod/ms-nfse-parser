<?php

declare(strict_types=1);

namespace MsNfseParser\Application\UseCase;

use MsNfseParser\Application\Dto\NfseExtractionOutput;
use MsNfseParser\Application\Port\NfseExtractorPort;
use MsNfseParser\Domain\Service\NfseDataNormalizerService;

final class ExtractNfseUseCase
{
    public function __construct(
        private readonly NfseExtractorPort $extractor,
        private readonly NfseDataNormalizerService $normalizer,
    ) {
    }

    public function execute(string $xml): NfseExtractionOutput
    {
        $rawData = $this->extractor->extractFromXml($xml);

        return new NfseExtractionOutput($this->normalizer->normalize($rawData));
    }
}
