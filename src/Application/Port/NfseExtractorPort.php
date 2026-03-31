<?php

declare(strict_types=1);

namespace MsNfseParser\Application\Port;

interface NfseExtractorPort
{
    /**
     * @return array<string, mixed>
     */
    public function extractFromXml(string $xml): array;
}
