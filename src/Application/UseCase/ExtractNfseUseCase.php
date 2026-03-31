<?php

declare(strict_types=1);

namespace MsNfseParser\Application\UseCase;

use MsNfseParser\Application\Dto\NfseExtractionOutput;
use MsNfseParser\Application\Port\NfseExtractorPort;
use MsNfseParser\Application\Port\NfseTemplateRepositoryPort;
use MsNfseParser\Domain\Service\NfseDataNormalizerService;
use MsNfseParser\Domain\Service\NfseXmlTemplateEngineService;

final class ExtractNfseUseCase
{
    public function __construct(
        private readonly NfseExtractorPort $extractor,
        private readonly NfseTemplateRepositoryPort $templateRepository,
        private readonly NfseXmlTemplateEngineService $templateEngine,
        private readonly NfseDataNormalizerService $normalizer,
    ) {
    }

    public function execute(string $xml, string $municipioCodigo): NfseExtractionOutput
    {
        $templates = $this->templateRepository->findByMunicipioCodigo($municipioCodigo);
        $templateExtraction = $this->templateEngine->extractUsingTemplates($xml, $templates);

        if (is_array($templateExtraction)) {
            return new NfseExtractionOutput($this->normalizer->normalize($templateExtraction));
        }

        $rawData = $this->extractor->extractFromXml($xml);
        $normalized = $this->normalizer->normalize($rawData);

        $rawXpaths = $rawData['xpaths'] ?? null;
        $xpaths = is_array($rawXpaths) ? $rawXpaths : null;

        $templateDraft = $this->templateEngine->buildTemplateDraft($municipioCodigo, $xml, $normalized, $xpaths);
        if (is_array($templateDraft)) {
            $this->templateRepository->save($templateDraft);
        }

        return new NfseExtractionOutput($normalized);
    }
}
