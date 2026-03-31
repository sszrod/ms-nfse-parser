<?php

declare(strict_types=1);

namespace MsNfseParser\Application\Dto;

final class NfseExtractionOutput
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload,
    ) {
    }
}
